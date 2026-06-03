<?php
session_start();
include('config.php');

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_id']) || !isset($input['new_rate'])) {
    echo json_encode(['success' => false, 'message' => 'User ID and new rate required']);
    exit();
}

$user_id = $input['user_id'];
$new_rate = $input['new_rate'];

// Validate the rate
if (!is_numeric($new_rate) || $new_rate <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid rate value']);
    exit();
}

try {
    // Check if user has a profile record
    $check_sql = "SELECT app_profile_id FROM applicant_profile WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE applicant_profile SET hourly_rate = ?, updated_at = NOW() WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("di", $new_rate, $user_id);
        $success = $update_stmt->execute();
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO applicant_profile (user_id, hourly_rate, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("id", $user_id, $new_rate);
        $success = $insert_stmt->execute();
    }
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Hourly rate updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update hourly rate']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>