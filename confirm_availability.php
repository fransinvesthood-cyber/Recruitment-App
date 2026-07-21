<?php
include 'config.php'; // Database connection

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $interview_id = $_POST['interview_id'];
    $availability_status = $_POST['availability_status']; // 'Available' or 'Not Available'

    // Update availability status in the database
    $sql = "UPDATE interviews 
            SET availability_status = ? 
            WHERE interview_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $availability_status, $interview_id);
        
        if ($stmt->execute()) {
            // Redirect back to the interviews page with a success message
            header("Location: my_interviews.php?status=updated");
            exit();
        } else {
            echo "Error updating availability status: " . $stmt->error;
        }
    } else {
        echo "Error preparing the query: " . $conn->error;
    }
}
?>