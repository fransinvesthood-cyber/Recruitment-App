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
$summary = mysqli_real_escape_string($conn, $_POST['summary'] ?? '');
$challenges = mysqli_real_escape_string($conn, $_POST['challenges'] ?? '');
$support = mysqli_real_escape_string($conn, $_POST['support'] ?? '');
$notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

// Server-side validation
if (empty($work_date) || empty($client_project) || empty($summary)) {
    echo json_encode(["error" => "Date, Client/Project, and Description are required."]);
    exit;
}

// Prepare the SQL query
$sql = "INSERT INTO consultant_task_logs 
        (user_id, work_date, client_project, summary, challenges, support, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "issssss", $user_id, $work_date, $client_project, $summary, $challenges, $support, $notes);

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
