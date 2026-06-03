<?php
session_start();
include('config.php');

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

// Check if the date is a public holiday
$stmt = $conn->prepare("SELECT holiday_name FROM public_holidays WHERE holiday_date = ?");
$stmt->bind_param("s", $date);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($holidayName);
    $stmt->fetch();
    echo json_encode([
        'is_holiday' => true,
        'holiday_name' => $holidayName
    ]);
} else {
    echo json_encode(['is_holiday' => false]);
}
?>