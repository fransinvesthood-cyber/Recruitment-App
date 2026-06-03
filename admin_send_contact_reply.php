<?php
session_start();
include('config.php');

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['error'] = 'Method not allowed';
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
    echo json_encode($response);
    exit;
}

if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['error'] = 'Invalid recipient email';
    echo json_encode($response);
    exit;
}

if ($subject === '' || $body === '') {
    http_response_code(400);
    $response['error'] = 'Subject and message are required';
    echo json_encode($response);
    exit;
}

// Basic sanitization
$to_email = filter_var($to_email, FILTER_SANITIZE_EMAIL);
$subject = str_replace(["\r", "\n"], '', $subject);

// Convert body to plain text and preserve line breaks
$body = str_replace(["\r\n", "\r"], "\n", $body);

// Admin "from" address (keep it configurable if needed)
$from_email = 'admin@investhoodit.co.za';
$from_name = 'Admin';

$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/plain; charset=UTF-8';
$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';

try {
    // Attempt to send email using PHP mail().
    // If your server doesn't have mail configured, configure it in php.ini / SMTP layer.
    $ok = @mail($to_email, $subject, $body, implode("\r\n", $headers));

    if (!$ok) {
        http_response_code(500);
        $response['error'] = 'Failed to send email (mail() returned false)';
        echo json_encode($response);
        exit;
    }

    // Optionally mark as replied in DB if column exists
    // (Some older installs may not yet have the `replied` column.)
    try {
        $stmt = $conn->prepare(
            'UPDATE contact_messages SET replied = 1 WHERE contact_message_id = ? AND replied = 0'
        );
        if ($stmt) {
            $stmt->bind_param('i', $contact_message_id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $ignored) {
        // ignore if column doesn't exist
    }

    $response['success'] = true;

    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    $response['error'] = 'Server error while sending reply';
    echo json_encode($response);
}

