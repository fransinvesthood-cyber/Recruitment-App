<?php
session_start();
include('config.php');

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT work_date, client_project, hours_worked, billable, description, status 
        FROM consultant_timesheets 
        WHERE user_id = ? 
        ORDER BY work_date DESC";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $entries = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $entries[] = $row;
    }

    echo json_encode(["entries" => $entries]);

    mysqli_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Query preparation failed: " . mysqli_error($conn)]);
}

mysqli_close($conn);
?>