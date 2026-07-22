<?php
include 'config.php'; // Database connection

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $interview_id = $_POST['interview_id'];
    $availability_status = $_POST['availability_status']; // 'Available' or 'Not Available'
    $is_ajax = !empty($_POST['ajax']); // Check if this is an AJAX request

    // Update availability status in the database
    $sql = "UPDATE interviews 
            SET availability_status = ? 
            WHERE interview_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $availability_status, $interview_id);
        
        if ($stmt->execute()) {
            if ($is_ajax) {
                // Return JSON response for AJAX requests
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'status' => $availability_status,
                    'message' => ($availability_status === 'Available') ? 'Accepted' : 'Declined'
                ]);
                exit();
            } else {
                // Determine redirect status based on availability choice
                $redirect_status = ($availability_status === 'Available') ? 'accepted' : 'declined';
                // Redirect back to the interviews page with a success message
                header("Location: my_interviews.php?status=" . $redirect_status);
                exit();
            }
        } else {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $stmt->error]);
                exit();
            }
            echo "Error updating availability status: " . $stmt->error;
        }
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit();
        }
        echo "Error preparing the query: " . $conn->error;
    }
}
?>
