<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!isset($_GET['doc_id'])) {
    header('Location: my_profile.php');
    exit;
}

$doc_id = (int)$_GET['doc_id'];

$stmt = $conn->prepare("DELETE FROM applicant_supporting_docs WHERE doc_id = ? AND user_id = ?");
if ($stmt) {
    $stmt->bind_param('ii', $doc_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header('Location: my_profile.php');
exit;
?>
