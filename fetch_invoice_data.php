<?php
session_start();
include('config.php');

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$user_id = $input['user_id'];
$start_date = $input['start_date'];
$end_date = $input['end_date'];
$client_filter = $input['client_filter'] ?? '';
$billable_filter = $input['billable_filter'] ?? '';

// Validate required fields
if (!$user_id || !$start_date || !$end_date) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Build the SQL query
$sql = "SELECT ct.consult_timesheet_id, ct.work_date, ct.client_project, ct.hours_worked, 
               ct.billable, ct.description, ct.status 
        FROM consultant_timesheets ct 
        WHERE ct.user_id = ? AND ct.work_date BETWEEN ? AND ? AND ct.status = 'Approved'";

$params = [$user_id, $start_date, $end_date];
$types = "iss";

// Add client filter if specified
if (!empty($client_filter)) {
    $sql .= " AND ct.client_project = ?";
    $params[] = $client_filter;
    $types .= "s";
}

// Add billable filter if specified
if (!empty($billable_filter)) {
    $sql .= " AND ct.billable = ?";
    $params[] = $billable_filter;
    $types .= "s";
}

$sql .= " ORDER BY ct.work_date ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>