<?php
include('config.php');

if (isset($_GET['application_id'])) {
    $application_id = (int) $_GET['application_id'];

    $sql = "SELECT application_status FROM job_applications WHERE application_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    
    echo htmlspecialchars($status);

    $stmt->close();
    $conn->close();
}
?>