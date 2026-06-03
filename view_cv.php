<?php
include('config.php');

if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    die('Invalid request');
}

$user_id = intval($_GET['user_id']);

// Fetch CV from database
$query = "SELECT ja.resume AS cv, u.fullname FROM job_applications ja JOIN users u ON ja.user_id = u.user_id WHERE ja.user_id = ? AND ja.resume IS NOT NULL ORDER BY ja.application_id DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die('CV not found');
}

$row = $result->fetch_assoc();
$cv_data = $row['cv'];
$fullname = $row['fullname'];

if (empty($cv_data)) {
    die('No CV available');
}

// Display CV in browser
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $fullname . '_CV.pdf"');
echo $cv_data;
?>
