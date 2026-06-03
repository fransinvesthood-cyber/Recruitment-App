<?php
include('config.php');

// Set headers to force download CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="leave_requests.csv"');

$output = fopen('php://output', 'w');

// CSV header row
fputcsv($output, ['Name', 'Email', 'Leave Type', 'Start Date', 'End Date', 'Status']);

// Fetch data
$sql = "SELECT cl.*, u.fullname, u.email 
        FROM consultant_leaves cl
        JOIN users u ON cl.user_id = u.user_id
        ORDER BY cl.start_date DESC";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['fullname'],
        $row['email'],
        $row['leave_type'],
        $row['start_date'],
        $row['end_date'],
        $row['status']
    ]);
}

fclose($output);
exit;
?>