<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$date = $_GET['date'] ?? null;

// Validate date
if (!$date || !strtotime($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date']);
    exit;
}

// Check if the date is a weekend
$dayOfWeek = date('N', strtotime($date)); // 6 = Saturday, 7 = Sunday
$isWeekend = ($dayOfWeek == 6 || $dayOfWeek == 7);

echo json_encode(['is_weekend' => $isWeekend]);
?>