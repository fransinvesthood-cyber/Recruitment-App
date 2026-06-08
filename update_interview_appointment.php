<?php
include('config.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$interview_id = isset($_POST['interview_id']) ? (int)$_POST['interview_id'] : 0;
$new_date = $_POST['new_date'] ?? '';

if ($interview_id <= 0 || empty($new_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing interview_id or new_date']);
    exit;
}

// Parse and normalize
$dt = DateTime::createFromFormat('Y-m-d\TH:i:s', $new_date);
if (!$dt) {
    // FullCalendar may send without seconds sometimes
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $new_date);
}
if (!$dt) {
    // Fallback: let DateTime try
    try {
        $dt = new DateTime($new_date);
    } catch (Exception $e) {
        $dt = false;
    }
}

if (!$dt) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

$normalized_new_date = $dt->format('Y-m-d H:i:s');

$admin_id = (int)$_SESSION['user_id'];

// 1) Update interview appointment date (and keep as Rescheduled)
$update_sql = "UPDATE interviews
               SET interview_date = ?,
                   interview_status = 'Rescheduled'
               WHERE interview_id = ?";

$update_stmt = $conn->prepare($update_sql);
if (!$update_stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare update']);
    exit;
}

$update_stmt->bind_param('si', $normalized_new_date, $interview_id);
$update_ok = $update_stmt->execute();
$update_stmt->close();

if (!$update_ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update interview']);
    exit;
}

// 2) Fetch candidate and position for notifications
$info_sql = "SELECT u.user_id AS candidate_user_id,
                     u.fullname AS candidate_name,
                     jp.position AS position
              FROM interviews i
              JOIN users u ON i.user_id = u.user_id
              JOIN job_postings jp ON i.job_id = jp.job_id
              WHERE i.interview_id = ?";

$info_stmt = $conn->prepare($info_sql);
$info_stmt->bind_param('i', $interview_id);
$info_stmt->execute();
$info_result = $info_stmt->get_result();
$info_stmt->close();

$info = $info_result ? $info_result->fetch_assoc() : null;
if (!$info) {
    // Appointment update succeeded, but we cannot notify details
    echo json_encode(['success' => true, 'message' => 'Interview updated, but notification info not found']);
    exit;
}

$candidate_user_id = (int)$info['candidate_user_id'];
$candidate_name = $info['candidate_name'];
$position = $info['position'];

// 3) Create reminder notification (24 hours before appointment)
// Your implementation request: notify BOTH admin and candidate.
// Also chosen behavior earlier: insert immediately.
$reminder_message = "Reminder: Interview for {$candidate_name} ({$position}) is scheduled for " . $dt->format('F j, Y, g:i a') . ".";

// Keep reference_id so client UIs can reliably link the notification to the interview
$insert_reference_id = $interview_id;

$insert_sql = "INSERT INTO notifications
    (user_id, message, is_read, type, reference_id, created_at)
    VALUES (?, ?, 0, 'interview_reminder', ?, NOW())";

$insert_stmt_admin = $conn->prepare($insert_sql);
if (!$insert_stmt_admin) {
    // Appointment update succeeded; still return success even if notification fails
    echo json_encode(['success' => true]);
    exit;
}
$insert_stmt_admin->bind_param('sis', $admin_id, $reminder_message, $insert_reference_id);
$insert_stmt_admin->execute();
$insert_stmt_admin->close();

$insert_stmt_candidate = $conn->prepare($insert_sql);
if ($insert_stmt_candidate) {
    $insert_stmt_candidate->bind_param('sis', $candidate_user_id, $reminder_message, $insert_reference_id);
    $insert_stmt_candidate->execute();
    $insert_stmt_candidate->close();
}

echo json_encode(['success' => true]);

