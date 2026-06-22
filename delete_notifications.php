<?php
include('config.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    if (isset($_POST['notification_id'])) {
        $notification_id = (int)$_POST['notification_id'];

        $sql = "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $notification_id, $user_id);
    } elseif (isset($_POST['delete_all'])) {
        $sql = "DELETE FROM notifications WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete notification(s)']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();
?>

