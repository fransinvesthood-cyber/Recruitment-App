<?php
include 'config.php';
header('Content-Type: application/json');

session_start();

$interviewer_names = $_GET['interviewer_names'] ?? '';
$date_time = $_GET['date_time'] ?? '';
$duration = (int)($_GET['duration'] ?? 30);
$exclude_interview_id = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

if (empty($interviewer_names) || empty($date_time)) {
    echo json_encode(['available' => true, 'conflicts' => [], 'message' => 'No data provided']);
    exit;
}

$names = array_map('trim', explode(',', $interviewer_names));
$names = array_filter($names);
$interviewer_names_lower = array_map('strtolower', $names);

if (empty($interviewer_names_lower)) {
    echo json_encode(['available' => true, 'conflicts' => []]);
    exit;
}

$requested_start = new DateTime($date_time);
$requested_end = clone $requested_start;
$requested_end->modify("+{$duration} minutes");

$conflicts = [];

// 1. Check existing interviews for each interviewer
$sql = "
    SELECT i.interview_id, i.interview_date, i.duration_minutes, i.interviewer, i.interview_status,
           u.fullname AS candidate_name, jp.position
    FROM interviews i
    JOIN users u ON i.user_id = u.user_id
    JOIN job_postings jp ON i.job_id = jp.job_id
    WHERE i.interview_status NOT IN ('Cancelled', 'Completed')
";

if ($exclude_interview_id) {
    $sql .= " AND i.interview_id != ?";
}

$stmt = $conn->prepare($sql);
if ($exclude_interview_id) {
    $stmt->bind_param("i", $exclude_interview_id);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $existing_start = new DateTime($row['interview_date']);
    $existing_end = clone $existing_start;
    $existing_dur = (int)($row['duration_minutes'] ?? 30);
    $existing_end->modify("+{$existing_dur} minutes");

    // Check overlap
    $overlap = ($requested_start < $existing_end && $requested_end > $existing_start);

    if ($overlap) {
        // Check if any selected interviewer is in this existing interview
        $existing_interviewer_str = strtolower($row['interviewer'] ?? '');
        $has_conflict = false;
        foreach ($interviewer_names_lower as $name) {
            if (stripos($existing_interviewer_str, $name) !== false) {
                $has_conflict = true;
                break;
            }
        }

        if ($has_conflict) {
            $conflicts[] = [
                'interview_id' => (int)$row['interview_id'],
                'candidate_name' => $row['candidate_name'],
                'position' => $row['position'],
                'existing_time' => $existing_start->format('M d, Y g:i A') . ' - ' . $existing_end->format('g:i A'),
                'interviewers' => $row['interviewer']
            ];
        }
    }
}

// 2. Check interviewer_availability table (vacation/unavailable dates)
$date_only = $requested_start->format('Y-m-d');
$placeholders = implode(',', array_fill(0, count($interviewer_names_lower), '?'));
$avail_sql = "SELECT i.name, ia.reason FROM interviewer_availability ia JOIN interviewers i ON ia.interviewer_id = i.interviewer_id WHERE LOWER(i.name) IN ($placeholders) AND ia.unavailable_date = ?";
$avail_stmt = $conn->prepare($avail_sql);
$avail_types = str_repeat('s', count($interviewer_names_lower)) . 's';
$avail_params = array_merge($interviewer_names_lower, [$date_only]);
$avail_stmt->bind_param($avail_types, ...$avail_params);
$avail_stmt->execute();
$avail_res = $avail_stmt->get_result();

while ($a = $avail_res->fetch_assoc()) {
    $conflicts[] = [
        'type' => 'unavailable',
        'interviewer_name' => $a['name'],
        'reason' => $a['reason'] ?? 'Not available on this date'
    ];
}

$available = empty($conflicts);

echo json_encode([
    'available' => $available,
    'conflicts' => $conflicts,
    'message' => $available ? 'All selected interviewers are available.' : 'Scheduling conflict detected.'
]);
?>

