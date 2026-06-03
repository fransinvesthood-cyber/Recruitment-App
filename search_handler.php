<?php
// Standalone search handler - no includes to avoid any issues
error_reporting(0);
ini_set('display_errors', 0);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "recruitment_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    exit('{"error":"Database connection failed"}');
}

// Get search query
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($search)) {
    header('Content-Type: application/json');
    echo '{"jobs":[],"candidates":[],"applications":[],"users":[],"leave_requests":[]}';
    $conn->close();
    exit;
}

$search_escaped = $conn->real_escape_string($search);
$results = [
    'jobs' => [],
    'candidates' => [],
    'applications' => [],
    'users' => [],
    'leave_requests' => []
];

// Search Jobs
$job_sql = "SELECT job_id, position, location, 'internal' as job_type
            FROM job_postings 
            WHERE position LIKE '%$search_escaped%' OR location LIKE '%$search_escaped%'
            LIMIT 5";
$job_result = $conn->query($job_sql);
if ($job_result && $job_result->num_rows > 0) {
    while ($row = $job_result->fetch_assoc()) {
        $results['jobs'][] = $row;
    }
}

// Search External Jobs
$ext_job_sql = "SELECT job_id, position, company_name as location, 'external' as job_type
                FROM external_jobs 
                WHERE position LIKE '%$search_escaped%' OR company_name LIKE '%$search_escaped%'
                LIMIT 5";
$ext_job_result = $conn->query($ext_job_sql);
if ($ext_job_result && $ext_job_result->num_rows > 0) {
    while ($row = $ext_job_result->fetch_assoc()) {
        $results['jobs'][] = $row;
    }
}

// Search Candidates
$candidate_sql = "SELECT u.user_id, u.fullname, u.email
                  FROM users u
                  WHERE u.role = 'applicant' 
                  AND (u.fullname LIKE '%$search_escaped%' OR u.email LIKE '%$search_escaped%')
                  LIMIT 5";
$candidate_result = $conn->query($candidate_sql);
if ($candidate_result && $candidate_result->num_rows > 0) {
    while ($row = $candidate_result->fetch_assoc()) {
        $results['candidates'][] = $row;
    }
}

// Search Applications
$app_sql = "SELECT ja.application_id, ja.application_status, jp.position, u.fullname as candidate_name
            FROM job_applications ja
            JOIN job_postings jp ON ja.job_id = jp.job_id
            JOIN users u ON ja.user_id = u.user_id
            WHERE jp.position LIKE '%$search_escaped%' OR u.fullname LIKE '%$search_escaped%'
            LIMIT 5";
$app_result = $conn->query($app_sql);
if ($app_result && $app_result->num_rows > 0) {
    while ($row = $app_result->fetch_assoc()) {
        $results['applications'][] = $row;
    }
}

// Search Users
$user_sql = "SELECT user_id, fullname, email, role 
             FROM users 
             WHERE fullname LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%'
             LIMIT 5";
$user_result = $conn->query($user_sql);
if ($user_result && $user_result->num_rows > 0) {
    while ($row = $user_result->fetch_assoc()) {
        $results['users'][] = $row;
    }
}

// Search Leave Requests
$leave_sql = "SELECT cl.consult_leave_id, cl.leave_type, cl.status, u.fullname
              FROM consultant_leaves cl
              JOIN users u ON cl.user_id = u.user_id
              WHERE u.fullname LIKE '%$search_escaped%' OR cl.leave_type LIKE '%$search_escaped%'
              LIMIT 5";
$leave_result = $conn->query($leave_sql);
if ($leave_result && $leave_result->num_rows > 0) {
    while ($row = $leave_result->fetch_assoc()) {
        $results['leave_requests'][] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($results);
$conn->close();
