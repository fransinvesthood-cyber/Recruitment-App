<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $interview_id = $_POST['interview_id'];
    $new_status = $_POST['interview_status'];

    // Update the interview status
    $sql = "UPDATE interviews SET interview_status = ? WHERE interview_id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $interview_id);
        $stmt->execute();
        $stmt->close();

        // Redirect back with success message
        header("Location: scheduled_interviews.php?success=Interview status updated successfully");
        exit();
    } else {
        die("Error updating interview status: " . $conn->error);
    }
}
?>