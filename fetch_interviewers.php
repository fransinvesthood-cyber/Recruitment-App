<?php
include 'config.php';
header('Content-Type: application/json');

session_start();

// Fetch active interviewers (seeded from Admin/Consultant users + manual entries)
$sql = "
    SELECT 
        i.interviewer_id,
        i.name,
        i.email,
        i.department,
        i.is_active,
        u.user_id,
        u.fullname AS user_fullname
    FROM interviewers i
    LEFT JOIN users u ON i.user_id = u.user_id
    WHERE i.is_active = 1
    ORDER BY i.name ASC
";

$result = $conn->query($sql);
$interviewers = [];

while ($row = $result->fetch_assoc()) {
    $interviewers[] = [
        'interviewer_id' => (int)$row['interviewer_id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'department' => $row['department'] ?? 'General',
        'display_name' => $row['name'] . ($row['department'] ? ' (' . $row['department'] . ')' : '')
    ];
}

// Fallback: if no interviewers table data, fetch from users directly
if (empty($interviewers)) {
    $fallback = $conn->query("SELECT user_id, fullname AS name, email, role AS department FROM users WHERE role IN ('Admin', 'Consultant') ORDER BY fullname ASC");
    while ($row = $fallback->fetch_assoc()) {
        $interviewers[] = [
            'interviewer_id' => (int)$row['user_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'department' => $row['department'] ?? 'General',
            'display_name' => $row['name'] . ' (' . $row['department'] . ')'
        ];
    }
}

echo json_encode($interviewers);
?>

