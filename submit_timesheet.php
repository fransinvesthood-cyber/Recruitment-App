<?php
session_start();
include('config.php');

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

// Collect and sanitize input
$user_id = $_SESSION['user_id'];
$work_date = mysqli_real_escape_string($conn, $_POST['work_date'] ?? '');
$client_project = mysqli_real_escape_string($conn, $_POST['client_project'] ?? '');
$hours_worked = floatval($_POST['hours_worked'] ?? 0);
$billable = mysqli_real_escape_string($conn, $_POST['billable'] ?? '');
$description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');

// Server-side validation
if (empty($work_date) || empty($client_project) || $hours_worked <= 0 || empty($billable) || empty($description)) {
    echo json_encode(["error" => "All fields are required and must be valid."]);
    exit;
}

// Prepare the SQL query - Added status field with default value 'Pending'
$sql = "INSERT INTO consultant_timesheets 
        (user_id, work_date, client_project, hours_worked, billable, description, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'Pending')";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "issdss", $user_id, $work_date, $client_project, $hours_worked, $billable, $description);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . mysqli_stmt_error($stmt)]);
    }

    mysqli_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(["error" => "SQL preparation failed: " . mysqli_error($conn)]);
}

mysqli_close($conn);
?>