<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

$user_id = (int)$_SESSION['user_id'];

$doc_id = 0;

if (isset($_GET['doc_id'])) {
    $doc_id = (int)$_GET['doc_id'];
}

if (!$doc_id) {
    http_response_code(404);
    die('Not found');
}

$stmt = $conn->prepare(
    "SELECT doc_data, doc_filename, doc_mimetype FROM applicant_supporting_docs WHERE doc_id = ? AND user_id = ? LIMIT 1"
);
$stmt->bind_param('ii', $doc_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    http_response_code(404);
    die('Not found');
}

$stmt->bind_result($doc_data, $doc_filename, $doc_mimetype);
$stmt->fetch();
$stmt->close();

if ($doc_data === null) {
    http_response_code(404);
    die('Not found');
}

header('Content-Type: ' . ($doc_mimetype ?: 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . ($doc_filename ?: 'document') . '"');
header('Content-Length: ' . strlen($doc_data));

echo $doc_data;
exit;
?>
