<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Expecting input name: documents (multiple)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['documents'])) {
    header('Location: my_profile.php');
    exit;
}

$files = $_FILES['documents'];

// Hard limit: allow up to 5 documents total after this upload.
$maxTotalDocs = 5;

// Allowed MIME types
$allowedTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

// How many docs already uploaded?
$currentCount = 0;
$stmtCount = $conn->prepare("SELECT COUNT(*) AS cnt FROM applicant_supporting_docs WHERE user_id = ?");
if ($stmtCount) {
    $stmtCount->bind_param('i', $user_id);
    $stmtCount->execute();
    $result = $stmtCount->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $currentCount = (int)($row['cnt'] ?? 0);
    $stmtCount->close();
}

$remaining = max(0, $maxTotalDocs - $currentCount);
if ($remaining <= 0) {
    $_SESSION['message'] = 'You already uploaded the maximum number of supporting documents (5).';
    $_SESSION['messageClass'] = 'success';
    header('Location: my_profile.php');
    exit;
}

$names = $files['name'] ?? [];
$tmpNames = $files['tmp_name'] ?? [];
$types = $files['type'] ?? [];
$errors = $files['error'] ?? [];

$count = count($names);
$accepted = 0;

for ($i = 0; $i < $count && $accepted < $remaining; $i++) {
    if (!isset($tmpNames[$i]) || ($errors[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
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
    if ($fileName === '' || $fileData === false) {
        continue;
    }

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

if ($accepted > 0) {
    $_SESSION['message'] = 'Supporting documents uploaded successfully.';
    $_SESSION['messageClass'] = 'success';
} else {
    $_SESSION['message'] = 'No valid documents were uploaded. Allowed: PDF, JPG, PNG, DOC, DOCX.';
    $_SESSION['messageClass'] = 'error';
}

header('Location: my_profile.php');
exit;

