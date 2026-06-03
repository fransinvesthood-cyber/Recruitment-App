<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$date = $_GET['date'] ?? null;

if (!$date || !strtotime($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date']);
    exit;
}

// Check if date is within any approved leave
$stmt = $conn->prepare("
    SELECT leave_type 
    FROM consultant_leaves 
    WHERE user_id = ? 
      AND status = 'Approved' 
      AND ? BETWEEN start_date AND end_date
");
$stmt->bind_param("is", $user_id, $date);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($leaveType);
    $stmt->fetch();
    echo json_encode([
        'on_leave' => true,
        'leave_type' => $leaveType
    ]);
} else {
    echo json_encode(['on_leave' => false]);
}
?>