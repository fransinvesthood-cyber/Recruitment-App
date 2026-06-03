<?php
include('config.php');
session_start();

// Check if admin is logged in or if user_id is provided via GET for admin access
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin' && isset($_GET['user_id'])) {
    $user_id = (int) $_GET['user_id'];
} elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    die("Not authorized");
}

$sql = "SELECT resume_filename, resume FROM resume WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($filename, $data);
    $stmt->fetch();

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $contentType = ($ext === 'pdf') ? 'application/pdf' : 'application/octet-stream';

    header("Content-Type: $contentType");
    header("Content-Disposition: inline; filename=\"$filename\"");
    echo $data;
} else {
    echo "No resume found.";
}

$stmt->close();
$conn->close();
?>
