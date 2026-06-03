<?php
include('config.php');
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'documentsCount' => 0]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Expecting table: supporting_documents (user_id, doc_filename, doc_data, uploaded_at, etc)
// We only need count here.
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM supporting_documents WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$count = (int)($row['cnt'] ?? 0);
$stmt->close();

echo json_encode(['ok' => true, 'documentsCount' => $count]);
?>
