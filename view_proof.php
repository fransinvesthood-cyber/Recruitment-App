<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login_signup.php');
    exit();
}

$consult_leave_id = isset($_GET['consult_leave_id']) ? intval($_GET['consult_leave_id']) : 0;

if ($consult_leave_id <= 0) {
    die('Invalid ID');
}

// Fetch file info and blob from DB
$stmt = $conn->prepare("SELECT proof, proof_filename, proof_mimetype FROM consultant_leaves WHERE consult_leave_id = ?");
$stmt->bind_param("i", $consult_leave_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($proof, $filename, $mimetype);
    $stmt->fetch();

    if ($proof !== null) {
        header('Content-Type: ' . $mimetype);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $proof;
        exit();
    } else {
        echo "No proof file found.";
    }
} else {
    echo "File not found.";
}
$stmt->close();
?>