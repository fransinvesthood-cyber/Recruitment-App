<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT work_date, client_project, hours_worked, description 
        FROM consultant_timesheets 
        WHERE user_id = ? AND billable = 'Yes' AND status = 'Approved' 
        ORDER BY work_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="invoice.csv"');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['Date', 'Client/Project', 'Hours Worked', 'Description']);

// Data rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, $row);
}

fclose($output);
mysqli_close($conn);
exit;