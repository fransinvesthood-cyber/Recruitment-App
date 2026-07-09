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
$title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
$description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
$eventDate = isset($_POST['event_date']) ? trim((string)$_POST['event_date']) : '';
$startTime = isset($_POST['start_time']) ? trim((string)$_POST['start_time']) : '';
$endTime = isset($_POST['end_time']) ? trim((string)$_POST['end_time']) : '';
$eventType = isset($_POST['event_type']) ? (string)$_POST['event_type'] : 'Other';

// Prevent any PHP notices/warnings from corrupting JSON response
ini_set('display_errors', '0');
error_reporting(E_ALL);


$allowedTypes = ['Interview', 'Training', 'Meeting', 'Reminder', 'Other'];
if (!in_array($eventType, $allowedTypes, true)) {
    $eventType = 'Other';
}

// Server-side validation (required)
if ($title === '' || $eventDate === '') {
    echo json_encode(['success' => false, 'error' => 'Title and Event Date are required']);
    exit();
}

// Basic format checks
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
    echo json_encode(['success' => false, 'error' => 'Invalid event_date']);
    exit();
}

// Optional time format checks
if ($startTime !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime)) {
    echo json_encode(['success' => false, 'error' => 'Invalid start_time']);
    exit();
}
if ($endTime !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $endTime)) {
    echo json_encode(['success' => false, 'error' => 'Invalid end_time']);
    exit();
}

// Normalize times to HH:MM if seconds are provided
if ($startTime !== '') {
    $startTime = substr($startTime, 0, 5);
}
if ($endTime !== '') {
    $endTime = substr($endTime, 0, 5);
}

$description = $description === '' ? null : $description;
$startTimeDb = $startTime === '' ? null : $startTime;
$endTimeDb = $endTime === '' ? null : $endTime;

if ($eventId > 0) {
    // Update (ensure ownership)
    $sql = "
        UPDATE calendar_events
        SET title = ?, description = ?, event_type = ?, event_date = ?, start_time = ?, end_time = ?
        WHERE event_id = ? AND created_by = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'sssssi i',
        $title,
        $description,
        $eventType,
        $eventDate,
        $startTimeDb,
        $endTimeDb,
        $eventId,
        $createdBy
    );

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
        exit();
    }

    if ($stmt->affected_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Event not found or not allowed']);
        exit();
    }

    echo json_encode(['success' => true, 'mode' => 'update', 'event_id' => $eventId]);
    exit();
}

// Insert
$sql = "
    INSERT INTO calendar_events (created_by, title, description, event_type, event_date, start_time, end_time)
    VALUES (?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('issssss', $createdBy, $title, $description, $eventType, $eventDate, $startTimeDb, $endTimeDb);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Insert failed']);
    exit();
}

$newId = (int)$stmt->insert_id;
echo json_encode(['success' => true, 'mode' => 'insert', 'event_id' => $newId]);
exit();


