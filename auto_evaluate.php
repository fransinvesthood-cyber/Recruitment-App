<?php
include('config.php');
include('evaluate_application.php');
include('send_evaluation_email.php');

header('Content-Type: application/json');

// Check for filtered app_ids
$app_ids_json = $_POST['app_ids'] ?? null;
$app_ids = $app_ids_json ? json_decode($app_ids_json, true) : null;

if ($app_ids && is_array($app_ids) && !empty($app_ids)) {
    $placeholders = str_repeat('?,', count($app_ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT application_id FROM job_applications WHERE application_id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($app_ids)), ...$app_ids);
} else {
    $stmt = $conn->prepare("SELECT application_id FROM job_applications WHERE application_status IN ('Submitted', 'Under Review')");
}
$stmt->execute();
$applications = $stmt->get_result();

$shortlisted = $rejected = $skipped = 0;
$kept_submitted = 0;
$errors = [];
$updates = [];

while ($row = $applications->fetch_assoc()) {
    $application_id = $row['application_id'];

    try {
        $result = evaluateApplicant($conn, $application_id);

        // Status already updated by evaluateApplicant()

        // Send evaluation email
        $email_result = sendEvaluationEmail($conn, $application_id, $result);
        if (!$email_result['success']) {
            $errors[] = "Application {$application_id}: Email failed - " . $email_result['message'];
            error_log("Email sending failed for application {$application_id}: " . $email_result['message']);
        }

        if ($result['status'] === 'Shortlisted') {
            $shortlisted++;
        } else if ($result['status'] === 'Rejected') {
            $rejected++;
        } else if ($result['status'] === 'Submitted') {
            $kept_submitted++;
        }

        $updates[] = ['application_id' => $application_id, 'status' => $result['status']];

    } catch (Exception $e) {
        $skipped++;
        $errors[] = "Application {$application_id}: Evaluation failed - " . $e->getMessage();
        error_log("Auto-evaluation failed for application {$application_id}: " . $e->getMessage());
    }
}

echo json_encode([
    'shortlisted' => $shortlisted,
    'rejected' => $rejected,
    'kept_submitted' => $kept_submitted,
    'skipped' => $skipped,
    'errors' => $errors,
    'updates' => $updates
]);
?>
