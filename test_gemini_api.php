<?php
// test_gemini_api.php - Create this file to test your API

$apiKey = 'AIzaSyCUK1TMulDce1zXKzVGb5Sq8uSUMFBg2tY';

// Test with a simple message
$url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Hello, can you help me with career advice?']
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 100
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h2>API Test Results</h2>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($curlError) {
    echo "<p><strong>cURL Error:</strong> $curlError</p>";
}

echo "<h3>Raw Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        echo "<h3>Generated Text:</h3>";
        echo "<p>" . htmlspecialchars($data['candidates'][0]['content']['parts'][0]['text']) . "</p>";
        echo "<p style='color: green;'><strong>✅ API is working correctly!</strong></p>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ API Error</strong></p>";
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['error']['message'])) {
        echo "<p><strong>Error Message:</strong> " . htmlspecialchars($errorData['error']['message']) . "</p>";
    }
}
?>