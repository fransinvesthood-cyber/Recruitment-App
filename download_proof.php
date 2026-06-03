<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login_signup.php');
    exit();
}

$consult_leave_id = isset($_GET['consult_leave_id']) ? intval($_GET['consult_leave_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($consult_leave_id <= 0) {
    die('Invalid ID');
}

$stmt = $conn->prepare("SELECT proof, proof_filename, proof_mimetype FROM consultant_leaves WHERE consult_leave_id = ? AND user_id = ?");
$stmt->bind_param("ii", $consult_leave_id, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($proof, $filename, $mimetype);
    $stmt->fetch();

    if ($proof !== null) {
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimetype);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($proof));
        echo $proof;
        exit();
    } else {
        echo "No proof file found.";
    }
} else {
    echo "File not found or access denied.";
}
$stmt->close();
?>