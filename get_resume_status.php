<?php
include('config.php');
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'resumeExists' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT resume_filename FROM resume WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

$resumeExists = ($stmt->num_rows > 0);

$filename = null;
if ($resumeExists) {
    $stmt->bind_result($filename);
    $stmt->fetch();
}

$stmt->close();

echo json_encode(['ok' => true, 'resumeExists' => $resumeExists, 'filename' => $filename]);
?>

