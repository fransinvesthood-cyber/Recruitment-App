<?php
// gemini_proxy.php - Create this as a separate file

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Include config for API key
require_once 'config.php';

// Set content type and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get API key from config
$apiKey = GEMINI_API_KEY;

// Test if API key is valid (basic check)
if (strlen($apiKey) < 30) {
    echo json_encode(['error' => 'Invalid API key format']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$input = file_get_contents('php://input');
if (!$input) {
    echo json_encode(['error' => 'No input received']);
    exit;
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

$message = trim($data['message'] ?? '');
if (empty($message)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// Use the correct Gemini API endpoint
$url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $message]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 200, // You can lower this to 200–400 for testing
        'topP' => 0.95,
        'topK' => 40
    ],
    'safetySettings' => [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ]
    ]
];

// Check if cURL is available
if (!function_exists('curl_init')) {
    echo json_encode(['error' => 'cURL is not enabled on this server']);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode([
        'error' => 'cURL Error',
        'details' => $curlError
    ]);
    exit;
}

if ($httpCode !== 200) {
    // Decode the error response for more details
    $errorResponse = json_decode($response, true);
    echo json_encode([
        'error' => 'HTTP Error',
        'code' => $httpCode,
        'message' => $errorResponse['error']['message'] ?? 'Unknown error',
        'details' => $errorResponse,
        'raw_response' => substr($response, 0, 500)
    ]);
    exit;
}

if ($httpCode === 429) {
    $errorResponse = json_decode($response, true);
    $retryDelay = 60; // fallback
    if (!empty($errorResponse['error']['details'])) {
        foreach ($errorResponse['error']['details'] as $detail) {
            if (($detail['@type'] ?? '') === 'type.googleapis.com/google.rpc.RetryInfo') {
                $retryDelay = (int) filter_var($detail['retryDelay'], FILTER_SANITIZE_NUMBER_INT);
            }
        }
    }
    sleep($retryDelay);
    // Retry request logic here...
}


// Validate the response
$responseData = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'error' => 'Invalid JSON response from Gemini API',
        'raw_response' => substr($response, 0, 500)
    ]);
    exit;
}

// Return the Gemini response
echo $response;
exit;
?>