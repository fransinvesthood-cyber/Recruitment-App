<?php
include('config.php');
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$createdBy = (int)$_SESSION['user_id'];
$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

if ($eventId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid event_id']);
    exit();
}

// Security: ensure the event belongs to the logged-in user
$sql = "DELETE FROM calendar_events WHERE event_id = ? AND created_by = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $eventId, $createdBy);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Delete failed']);
    exit();
}

if ($stmt->affected_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Event not found']);
    exit();
}

echo json_encode(['success' => true]);

