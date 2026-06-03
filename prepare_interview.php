<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // use * for local testing, restrict in production

// ---- API Key (hard-coded for testing) ----
$apiKey = "AIzaSyDqXfsh-v1Vs3mRmzwNH1-xRg4dzz6Qt_Q";
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Missing GEMINI_API_KEY"]);
    exit;
}

// ---- Input from frontend ----
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Invalid request payload"]);
    exit;
}

$position     = trim($data['position'] ?? '');
$department   = trim($data['department'] ?? '');
$company_name = trim($data['company_name'] ?? '');
$experience   = trim($data['experience'] ?? '');

if (!$position || !$department) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Position and Department are required"]);
    exit;
}

// ---- Build prompt ----
$companyText    = $company_name ? " at $company_name" : '';
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
    "contents" => [[ "parts" => [[ "text" => $prompt ]] ]]
];

// Try multiple Gemini models (some may not be available for the current API key/project)
$modelCandidates = [
    "gemini-2.0-flash",
    "gemini-1.5-flash"
];

$lastError = null;
$res = null;
$json = null;

foreach ($modelCandidates as $model) {
    $endpoint = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key=" . $apiKey;

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30
    ]);

    $res = curl_exec($ch);

    if (curl_errno($ch)) {
        $lastError = curl_error($ch);
        curl_close($ch);
        continue;
    }

    curl_close($ch);
    $json = json_decode($res, true);

    // If Gemini responded with an error, try next model
    if (isset($json['error'])) {
        $lastError = $json['error']['message'] ?? 'Gemini API error';
        continue;
    }

    // Success
    break;
}

if ($json === null) {
    http_response_code(502);
    echo json_encode(["ok" => false, "error" => $lastError ?: "Gemini API failed" , "models_tried" => $modelCandidates]);
    exit;
}
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($body),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30
]);

$res = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(502);
    echo json_encode(["ok" => false, "error" => "Curl error: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$json = json_decode($res, true);

// ---- Handle Gemini API errors ----
if (isset($json['error'])) {
    http_response_code(502);
    echo json_encode(["ok" => false, "error" => $json['error']['message'] ?? 'Gemini API error']);
    exit;
}

// ---- Extract candidate content ----
$content = trim($json['candidates'][0]['content']['parts'][0]['text'] ?? '');

// ---- Clean up model fences if present ----
$content = preg_replace('/^```[a-z]*\s*/mi', '', $content);
$content = preg_replace('/```$/m', '', $content);
$content = trim($content);

$payload = json_decode($content, true);

// ---- Validate JSON structure ----
$required = ['custom_tips','common_questions','dos','donts'];
foreach ($required as $key) {
    if (!isset($payload[$key]) || !is_array($payload[$key])) {
        http_response_code(502);
        echo json_encode([
            "ok"   => false,
            "error"=> "Missing or invalid key: $key",
            "raw"  => $content
        ]);
        exit;
    }
}

// ---- Success ----
echo json_encode(["ok" => true, "data" => $payload]);
?>