<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Suppress notices/warnings for clean JSON
error_reporting(E_ERROR | E_PARSE);

$apiKey = "AIzaSyDwUTfAYIg9IQoVLAgVDzaJCU-c-3DoDrM";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Try multiple Gemini models (some may not be available for the current API key/project)
$modelCandidates = [
    "gemini-2.0-flash",
    "gemini-1.5-flash"
];

$endpointCandidates = array_map(function($m) use ($apiKey) {
    return "https://generativelanguage.googleapis.com/v1/models/{$m}:generateContent?key={$apiKey}";
}, $modelCandidates);

$endpoint = null;
$prompt = $data["prompt"] ?? "Generate 1 sample interview question in JSON.";

// Prepare payload with randomness (temperature, top_p, top_k)
$payload = [
    "contents" => [
        ["parts" => [["text" => $prompt]]]
    ],
    "generationConfig" => [
        "temperature" => 0.9,   // Higher = more creative / random (default ~0.7)
        "top_p"       => 0.95,  // Nucleus sampling
        "top_k"       => 40,    // Limits the sampling pool
        "maxOutputTokens" => 2048 // Prevents truncation
    ]
];

$ch = null;
$decoded = null;
$lastError = null;

foreach ($endpointCandidates as $endpoint) {
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $lastError = curl_error($ch);
        curl_close($ch);
        continue;
    }

    curl_close($ch);
    $decoded = json_decode($response, true);

    if (isset($decoded['error'])) {
        $lastError = $decoded['error']['message'] ?? 'Gemini API error';
        $decoded = null;
        continue;
    }

    break;
}

if ($decoded === null) {
    echo json_encode(["ok" => false, "error" => $lastError ?: "Gemini API failed", "models_tried" => $modelCandidates]);
    exit;
}

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(["ok" => false, "error" => curl_error($ch)]);
    exit;
}
curl_close($ch);

// Decode Gemini raw response
$decoded = json_decode($response, true);
$output = ["ok" => true];

// Extract text from Gemini
$text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? "";
$text = trim($text);

// Determine whether this is question generation or feedback
if (stripos($prompt, "generate") !== false && stripos($prompt, "question") !== false) {
    // --- Question generation ---
    $questions = json_decode($text, true);

    // Fallback: extract JSON array if normal decode fails
    if (!is_array($questions)) {
        if (preg_match('/\[(.*)\]/s', $text, $matches)) {
            $jsonText = '[' . $matches[1] . ']';
            $questions = json_decode($jsonText, true);
        }
    }

    // Ensure array
    if (!is_array($questions)) {
        $questions = [];
    }

    $output['questions'] = $questions;
} elseif (stripos($prompt, "feedback") !== false) {
    // --- Feedback generation ---
    $output['feedback'] = $text ?: "No feedback returned.";
} else {
    // Default: just return raw text
    $output['raw'] = $text;
}

echo json_encode($output);
?>