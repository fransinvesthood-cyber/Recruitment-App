<?php
// get_total_unread_messages.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['total_unread' => 0]);
    exit();
}

include('config.php');

$user_id = $_SESSION['user_id'];

// Get total unread message count from all consultants
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_unread 
    FROM chat_messages cm 
    JOIN users u ON cm.sender_id = u.user_id 
    WHERE cm.receiver_id = ? AND u.role = 'Consultant' AND cm.is_read = FALSE
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

echo json_encode(['total_unread' => intval($data['total_unread'])]);

$conn->close();
?>