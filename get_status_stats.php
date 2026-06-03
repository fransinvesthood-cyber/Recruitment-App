<?php
include('config.php');

header('Content-Type: application/json');

// Get status statistics
$query = "SELECT application_status, COUNT(*) as count FROM job_applications GROUP BY application_status";
$result = $conn->query($query);

$stats = [];
while ($row = $result->fetch_assoc()) {
    $stats[$row['application_status']] = (int)$row['count'];
}

echo json_encode($stats);
$conn->close();
?>
