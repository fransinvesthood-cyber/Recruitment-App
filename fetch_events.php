<?php
include('config.php');
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Admin view: return all created events
$sql = "
    SELECT 
        event_id,
        title,
        description,
        event_type,
        event_date,
        start_time,
        end_time,
        created_at,
        updated_at
    FROM calendar_events
    ORDER BY event_date ASC, start_time ASC, event_id ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $start = $row['event_date'];
    $end = $row['event_date'];

    if (!empty($row['start_time'])) {
        $start = $row['event_date'] . 'T' . $row['start_time'];
    }
    if (!empty($row['end_time'])) {
        $end = $row['event_date'] . 'T' . $row['end_time'];
    }

    $events[] = [
        'id' => (int)$row['event_id'],
        'title' => $row['title'],
        'start' => $start,
        'end' => (!empty($row['end_time']) ? $end : null),
        'allDay' => empty($row['start_time']),
        'className' => 'event-' . strtolower($row['event_type']),
        'extendedProps' => [
            'description' => $row['description'],
            'event_type' => $row['event_type'],
            'event_date' => $row['event_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time']
        ]
    ];
}

echo json_encode(['success' => true, 'events' => $events]);

