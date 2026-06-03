<?php
include 'config.php';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    $stmt = $conn->prepare("
        SELECT jp.job_id, jp.position
        FROM job_applications ja
        JOIN job_postings jp ON ja.job_id = jp.job_id
        WHERE ja.user_id = ? AND ja.application_status = 'Shortlisted' AND jp.job_status = 'Active'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }

    echo json_encode($jobs);
} else {
    // Return positions that have shortlisted applicants if no user_id
    $stmt = $conn->prepare("
        SELECT DISTINCT jp.job_id, jp.position
        FROM job_postings jp
        JOIN job_applications ja ON jp.job_id = ja.job_id
        WHERE jp.job_status = 'Active' AND ja.application_status = 'Shortlisted'
        ORDER BY jp.position
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }

    echo json_encode($jobs);
}
?>
