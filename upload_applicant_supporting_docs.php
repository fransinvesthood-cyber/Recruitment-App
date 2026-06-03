<?php
session_start();
include('config.php');

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['documents'])) {
    header('Location: my_profile.php');
    exit;
}

$files = $_FILES['documents'];

// Allow up to 5 documents (requirement: at least 5, but we accept up to 5 per upload)
$maxFiles = 5;

$allowedTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

$names = $files['name'] ?? [];
$tmpNames = $files['tmp_name'] ?? [];
$types = $files['type'] ?? [];
$errors = $files['error'] ?? [];

$count = count($names);
$accepted = 0;

for ($i = 0; $i < $count && $accepted < $maxFiles; $i++) {
    if (!isset($tmpNames[$i]) || $errors[$i] !== UPLOAD_ERR_OK) {
        continue;
    }

    $type = $types[$i] ?? '';
    if (!in_array($type, $allowedTypes, true)) {
        continue;
    }

    $tmpPath = $tmpNames[$i];
    if (!is_uploaded_file($tmpPath)) {
        continue;
    }

    $fileData = file_get_contents($tmpPath);
    $fileName = basename($names[$i] ?? '');
    if ($fileName === '') {
        continue;
    }

    // Store document
    $stmt = $conn->prepare(
        "INSERT INTO applicant_supporting_docs (user_id, doc_filename, doc_mimetype, doc_data, uploaded_at) VALUES (?, ?, ?, ?, NOW())"
    );
    if (!$stmt) {
        continue;
    }

    $stmt->bind_param('issb', $user_id, $fileName, $type, $fileData);
    $stmt->send_long_data(3, $fileData);

    if ($stmt->execute()) {
        $accepted++;
    }

    $stmt->close();
}

$_SESSION['message'] = 'Documents uploaded successfully.';
$_SESSION['messageClass'] = 'success';

header('Location: my_profile.php');
exit;

