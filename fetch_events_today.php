<?php
include('config.php');
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Today's date in DB format
$today = date('Y-m-d');

$sql = "
    SELECT 
        event_id,
        title,
        description,
        event_type,
        event_date,
        start_time,
        end_time
    FROM calendar_events
    WHERE event_date = ?
    ORDER BY start_time ASC, event_id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => (int)$row['event_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'event_type' => $row['event_type'],
        'event_date' => $row['event_date'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
    ];
}

echo json_encode(['success' => true, 'events' => $events]);

