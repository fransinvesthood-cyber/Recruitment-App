<?php
// get_unread_admin_messages.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread_count' => 0]);
    exit();
}

include('config.php');

$user_id = $_SESSION['user_id'];

// Get unread message count from admin
$stmt = $conn->prepare("
    SELECT COUNT(*) as unread_count 
    FROM chat_messages cm 
    JOIN users u ON cm.sender_id = u.user_id 
    WHERE cm.receiver_id = ? AND u.role = 'Admin' AND cm.is_read = FALSE
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

echo json_encode(['unread_count' => intval($data['unread_count'])]);

$conn->close();
?>