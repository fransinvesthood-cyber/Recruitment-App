<?php
// Direct test without session requirement
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "recruitment_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = isset($_GET['q']) ? trim($_GET['q']) : 'test';

$search_escaped = $conn->real_escape_string($search);
$results = [
    'jobs' => [],
    'candidates' => [],
    'applications' => [],
    'users' => [],
    'leave_requests' => []
];

// Search Jobs
$job_sql = "SELECT job_id, position, location FROM job_postings WHERE position LIKE '%$search_escaped%' LIMIT 5";
$job_result = $conn->query($job_sql);
if ($job_result && $job_result->num_rows > 0) {
    while ($row = $job_result->fetch_assoc()) {
        $results['jobs'][] = $row;
    }
}

// Search Users
$user_sql = "SELECT user_id, fullname, email, role FROM users WHERE fullname LIKE '%$search_escaped%' LIMIT 5";
$user_result = $conn->query($user_sql);
if ($user_result && $user_result->num_rows > 0) {
    while ($row = $user_result->fetch_assoc()) {
        $results['users'][] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($results);
$conn->close();
