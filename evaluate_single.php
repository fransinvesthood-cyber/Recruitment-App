<?php
// Single-application evaluation endpoint
// Expects POST: application_id
// Returns JSON with evaluation result

// Buffer output and return JSON on fatal errors
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ob_start();

include('config.php');
session_start();
header('Content-Type: application/json');

// Only admins allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Read input (support both form-data and raw JSON)
$application_id = null;
if (!empty($_POST['application_id'])) {
    $application_id = (int) $_POST['application_id'];
} else {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!empty($payload['application_id'])) $application_id = (int) $payload['application_id'];
}

if (!$application_id) {
    http_response_code(400);
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid application_id']);
    exit;
}

include('evaluate_application.php');
include('send_evaluation_email.php');

// Allow a debug flag (GET or POST) to return detailed matching info
$debugMode = false;
if ((isset($_REQUEST['debug']) && ($_REQUEST['debug'] == '1' || $_REQUEST['debug'] === 'true')) || (!empty($_POST['debug']) && $_POST['debug'] == 1)) {
    $debugMode = true;
}
try {
    $result = evaluateApplicant($conn, $application_id, $debugMode);

    // Send evaluation email
    $email_result = sendEvaluationEmail($conn, $application_id, $result);
    if (!$email_result['success']) {
        error_log("Email sending failed for application {$application_id}: " . $email_result['message']);
        // Add email error to result if needed
        if (!isset($result['errors'])) $result['errors'] = [];
        $result['errors'][] = 'Email notification failed: ' . $email_result['message'];
    }
} catch (Throwable $e) {
    http_response_code(500);
    if (ob_get_length()) ob_clean();
    error_log('[evaluate_single] admin:' . ($_SESSION['user_id'] ?? 'unknown') . ' app:' . $application_id . ' EXCEPTION: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Evaluation exception', 'details' => $e->getMessage()]);
    exit;
}

// Capture buffered output (warnings etc.)
$buf = '';
if (ob_get_length()) {
    $buf = ob_get_clean();
    $buf = trim(strip_tags($buf));
    if (!empty($buf)) {
        if (!is_array($result)) $result = [];
        if (!isset($result['errors'])) $result['errors'] = [];
        $result['errors'][] = 'Debug output: ' . $buf;
    }
}

if (!is_array($result) || !isset($result['status'])) {
    http_response_code(500);
    error_log('[evaluate_single] admin:' . ($_SESSION['user_id'] ?? 'unknown') . ' app:' . $application_id . ' FAILED: ' . var_export($result, true));
    echo json_encode(['status' => 'error', 'message' => 'Evaluation failed', 'details' => $result]);
    exit;
}
// Log a summary of the evaluation
error_log('[evaluate_single] admin:' . ($_SESSION['user_id'] ?? 'unknown') . ' app:' . $application_id . ' status:' . $result['status'] . ' reasons:' . json_encode($result['reasons'] ?? []));

// Return structured response
echo json_encode([
    'status' => 'ok',
    'application_id' => $application_id,
    'result' => $result
]);

$conn->close();
exit;
?>