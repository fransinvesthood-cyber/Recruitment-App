<?php
include('config.php');

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate inputs
$application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
$new_status = isset($_POST['new_status']) ? $conn->real_escape_string(trim($_POST['new_status'])) : '';

if (!$application_id || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Validate status against allowed values
$allowed_statuses = ['Submitted', 'Under Review', 'Shortlisted', 'Rejected', 'Hired'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

// Get current status and user_id before updating
$current_query = "SELECT ja.user_id, ja.application_status, jp.position
                  FROM job_applications ja
                  JOIN job_postings jp ON ja.job_id = jp.job_id
                  WHERE ja.application_id = $application_id";
$current_result = $conn->query($current_query);

if ($current_result && $current_result->num_rows > 0) {
    $current_data = $current_result->fetch_assoc();
    $user_id = $current_data['user_id'];
    $old_status = $current_data['application_status'];
    $position = $current_data['position'];

    // Only proceed if status is actually changing
    if ($old_status !== $new_status) {
        // Update the application status
        $query = "UPDATE job_applications SET application_status = '$new_status' WHERE application_id = $application_id";

        if ($conn->query($query) === TRUE) {
            if ($conn->affected_rows > 0) {
                // Create notification for the applicant
                $notification_message = "Your application status for '{$position}' position has been updated from '{$old_status}' to '{$new_status}'.";
                $notification_query = "INSERT INTO notifications (user_id, message, is_read, created_at, type, reference_id)
                                       VALUES ($user_id, '$notification_message', 0, NOW(), 'application_status', $application_id)";

                if ($conn->query($notification_query) === TRUE) {
                    echo json_encode(['success' => true, 'message' => 'Status updated successfully and notification sent']);
                } else {
                    // Status updated but notification failed - still return success but log error
                    error_log("Failed to create notification: " . $conn->error);
                    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Application not found or no changes made']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Status is already set to ' . $new_status]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Application not found']);
}

$conn->close();
?>
