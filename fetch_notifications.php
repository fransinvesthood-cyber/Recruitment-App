<?php
session_start();
include('config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get recent notifications (latest 10)
$stmt = $conn->prepare("SELECT notification_id, message, is_read, created_at, type, reference_id
                        FROM notifications
                        WHERE user_id = ?
                        ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);
?>