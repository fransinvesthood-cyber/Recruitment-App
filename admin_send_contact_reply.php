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
        $response['debug'] = [
            'to_email' => $to_email,
            'from_email' => $from_email,
            'subject' => $subject,
            'sendmail_path' => function_exists('ini_get') ? ini_get('sendmail_path') : null,
            'php_last_error' => function_exists('error_get_last') ? error_get_last() : null,
            'headers' => $headers,
        ];

        ob_clean();
        echo json_encode($response);
        exit;
    }

            // IMPORTANT: Do NOT mark as replied automatically.
            // Reply emails are sent by the admin explicitly; your dashboard badge logic
            // should reflect the actual reply/unread state.
            //
            // If you want 'replied' to change, it should be done only by an explicit
            // action after the user confirms the reply in UI.

    $response['success'] = true;
    ob_clean();
    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    $response['error'] = 'Server error while sending reply';
    $response['debug'] = [
        'exception_message' => $e->getMessage(),
    ];
    ob_clean();
    echo json_encode($response);
}

