<?php
include 'config.php'; // Adjust this according to your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $interview_id = $_POST['interview_id'];
    $availability_status = $_POST['availability_status'];

    $stmt = $conn->prepare("UPDATE interviews SET availability_status = ? WHERE interview_id = ?");
    $stmt->bind_param("si", $availability_status, $interview_id);

    if ($stmt->execute()) {
        echo "<script>alert('Availability status updated successfully!!'); window.location.href='my_interviews.php';</script>";
    } else {
        echo "Error updating availability status.";
    }

    $stmt->close();
    $conn->close();
}
?>