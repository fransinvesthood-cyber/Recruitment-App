<?php
session_start();
include ('config.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT work_date, client_project, summary, challenges, support, notes
        FROM consultant_task_logs 
        WHERE user_id = ? 
        ORDER BY work_date DESC 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode($logs);

$stmt->close();
$conn->close();
?>