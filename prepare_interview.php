<?php
// Robust error handling endpoint for interview tips generation.
// Never expose API keys, stack traces, quota details, or internal errors to the client.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Helpful: ensure PHP doesn't output warnings/notices into JSON.
ini_set('display_errors', '0');
error_reporting(E_ALL);

/**
 * Return a clean, user-safe JSON error.
 */
function send_json_error(string $message, int $httpStatus = 500, ?string $userTitle = null): void {
    http_response_code($httpStatus);
    $payload = [
        'ok' => false,
        'error' => $message,
    ];
    if ($userTitle) {
        $payload['title'] = $userTitle;
    }
    echo json_encode($payload);
    exit;
}

/**
 * Logs only technical details server-side.
 */
function log_technical_error(string $context, mixed $details = null): void {
    $msg = $context;
    if ($details !== null) {
        $msg .= ' | ' . (is_string($details) ? $details : json_encode($details));
    }
    error_log($msg);
}

class GeminiApiException extends Exception {}

try {
    // ---- API Key (hard-coded for testing) ----
    $apiKey = "AIzaSyDqXfsh-v1Vs3mRmzwNH1-xRg4dzz6Qt_Q";
    if (!$apiKey) {
        log_technical_error('prepare_interview.php: missing GEMINI_API_KEY');
        send_json_error('Interview Tips Temporarily Unavailable', 500, 'Interview Tips Temporarily Unavailable');
    }

    // ---- Input from frontend ----
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        send_json_error('Invalid request. Please try again.', 400);
    }

    $position     = trim($data['position'] ?? '');
    $department   = trim($data['department'] ?? '');
    $company_name = trim($data['company_name'] ?? '');
    $experience   = trim($data['experience'] ?? '');

    if (!$position || !$department) {
        send_json_error('Invalid request. Please fill required fields and try again.', 400);
    }

    // ---- Build prompt ----
    $companyText = $company_name ? " at $company_name" : '';
    $experienceText = $experience ? "$experience " : '';

    $prompt = <<<PROMPT
Generate interview preparation material for a {$experienceText}{$position}{$companyText} in the {$department} department.
Return JSON with keys:
- custom_tips (bullet list of 5-8 items)
- common_questions (10-15 questions)
- dos (6-10 items)
- donts (6-10 items)
Only output valid JSON.
PROMPT;

    // ---- Gemini request body ----
    $body = [
        'contents' => [[ 'parts' => [[ 'text' => $prompt ]] ]]
    ];

    $modelCandidates = [
        'gemini-2.0-flash',
        'gemini-2.0-flash-lite'
    ];

    $lastErrorTechnical = null;
    $geminiResponse = null;

    foreach ($modelCandidates as $model) {
        $endpoint = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key=" . $apiKey;

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $res = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $lastErrorTechnical = 'cURL error: ' . curl_error($ch);
            curl_close($ch);
            continue;
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            $decoded = json_decode($res, true);
            $lastErrorTechnical = 'Gemini HTTP ' . $httpCode;
            // Keep raw details off the wire; log technical info server-side.
            log_technical_error('Gemini non-200', [
                'httpCode' => $httpCode,
                'model' => $model,
                'providerError' => $decoded['error']['message'] ?? null,
            ]);
            continue;
        }

        $decoded = json_decode($res, true);
        if (!is_array($decoded) || isset($decoded['error'])) {
            $lastErrorTechnical = 'Gemini returned an error response';
            log_technical_error('Gemini API error', [
                'model' => $model,
                'providerError' => $decoded['error']['message'] ?? null,
            ]);
            continue;
        }

        $content = trim($decoded['candidates'][0]['content']['parts'][0]['text'] ?? '');
        if ($content === '') {
            $lastErrorTechnical = 'Gemini returned empty content';
            continue;
        }

        $geminiResponse = $decoded;
        break;
    }

    if ($geminiResponse === null) {
        // Map common provider issues to a friendly message.
        $friendlyTitle = 'Interview Tips Temporarily Unavailable';
        $friendlyMsg = "We're currently unable to generate interview tips because the AI service is temporarily unavailable or has reached its usage limit.\n\nPlease try again in a few minutes. If the problem persists, contact the system administrator.";

        log_technical_error('Gemini generation failed', $lastErrorTechnical);
        send_json_error($friendlyMsg, 502, $friendlyTitle);
    }

    // ---- Extract candidate content ----
    $content = trim($geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '');

    // ---- Clean up model fences if present ----
    $content = preg_replace('/^```[a-z]*\s*/mi', '', $content);
    $content = preg_replace('/```$/m', '', $content);
    $content = trim($content);

    $payload = json_decode($content, true);
    if (!is_array($payload)) {
        log_technical_error('Gemini invalid JSON payload', ['raw' => substr($content, 0, 1000)]);
        send_json_error('Interview Tips Temporarily Unavailable', 502, 'Interview Tips Temporarily Unavailable');
    }

    // ---- Validate JSON structure ----
    $required = ['custom_tips', 'common_questions', 'dos', 'donts'];
    foreach ($required as $key) {
        if (!isset($payload[$key]) || !is_array($payload[$key])) {
            log_technical_error('Gemini missing/invalid key', ['key' => $key]);
            send_json_error('Interview Tips Temporarily Unavailable', 502, 'Interview Tips Temporarily Unavailable');
        }
    }

    // ---- Success ----
    echo json_encode(["ok" => true, "data" => $payload]);
    exit;

} catch (GeminiApiException $e) {
    log_technical_error('GeminiApiException', $e->getMessage());
    send_json_error("We're currently unable to generate interview tips because the AI service is temporarily unavailable or has reached its usage limit.\n\nPlease try again in a few minutes. If the problem persists, contact the system administrator.", 502, 'Interview Tips Temporarily Unavailable');

} catch (Throwable $e) {
    // Catch-all for unexpected errors.
    log_technical_error('Unhandled exception', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
    ]);

    send_json_error('Something went wrong while generating interview tips.', 500, 'Interview Tips Unavailable');
}

