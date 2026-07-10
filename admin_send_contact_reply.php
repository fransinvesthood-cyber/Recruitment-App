<?php
session_start();
include('config.php');

// Ensure we *only* output JSON (no stray whitespace/notices).
ob_start();
header('Content-Type: application/json; charset=UTF-8');

$response = ['success' => false];

// If PHP produces warnings/notices before our JSON, prevent it from breaking the client.
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['error'] = 'Method not allowed';
    ob_clean();
    echo json_encode($response);
    exit;
}

$contact_message_id = isset($_POST['contact_message_id']) ? (int)$_POST['contact_message_id'] : 0;
$to_email = trim((string)($_POST['to_email'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$body = (string)($_POST['body'] ?? '');

if ($contact_message_id <= 0) {
    http_response_code(400);
    $response['error'] = 'Invalid contact_message_id';
    ob_clean();
    echo json_encode($response);
    exit;
}

if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['error'] = 'Invalid recipient email';
    ob_clean();
    echo json_encode($response);
    exit;
}

if ($subject === '' || $body === '') {
    http_response_code(400);
    $response['error'] = 'Subject and message are required';
    ob_clean();
    echo json_encode($response);
    exit;
}

// Basic sanitization
$to_email = filter_var($to_email, FILTER_SANITIZE_EMAIL);
$subject = str_replace(["\r", "\n"], '', $subject);

// Convert body to plain text and preserve line breaks
$body = str_replace(["\r\n", "\r"], "\n", $body);

// Admin "from" address
$from_email = 'delanideco69@gmail.com';
$from_name = 'Admin';

try {
    // Send email using PHPMailer.
    // Requirement: Do NOT use PHP's mail() anywhere.
    require_once __DIR__ . '/email/EmailService.php';

    // Keep reply functionality unchanged:
    // - This endpoint sends the admin reply email.
    // - It intentionally does NOT update contact message rows.
    // - Frontend badges/state should continue to be driven by existing logic.

    $toName = '';
    \Email\EmailService::sendText($to_email, $toName, $subject, $body, $from_email, $from_name);

    $response['success'] = true;
    ob_clean();
    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    $response['error'] = 'Server error while sending reply';

    // Requirement: If email sending fails, return the PHPMailer exception message.
    $response['debug'] = [
        'exception_message' => $e->getMessage(),
    ];

    ob_clean();
    echo json_encode($response);
}

