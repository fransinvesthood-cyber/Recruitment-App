<?php
// Complete RapidAPI Connection Test
// This file tests your RapidAPI connection and shows detailed debugging info

// STEP 1: Enter your RapidAPI key here
$apiKey = "1a415439d1msh0cf954e0b27270bp1e5892jsn97ca1364e756
"; // ⚠️ REPLACE THIS with your actual key

?>
<!DOCTYPE html>
<html>
<head>
    <title>RapidAPI JSearch Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .success { background: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50; margin: 10px 0; }
        .error { background: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 10px 0; }
        .warning { background: #fff3e0; padding: 15px; border-left: 4px solid #ff9800; margin: 10px 0; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0; }
        .job-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            background: #fafafa;
        }
        .job-title { font-size: 18px; font-weight: bold; color: #2196F3; margin-bottom: 5px; }
        .job-company { color: #666; margin-bottom: 5px; }
        .job-location { color: #999; font-size: 14px; }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px 10px 0;
        }
        .btn:hover { background: #45a049; }
        .step {
            background: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #2196F3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th {
            background: #4CAF50;
            color: white;
            padding: 10px;
            text-align: left;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        table tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 RapidAPI JSearch Connection Test</h1>
        <p>This tool will test your RapidAPI connection and fetch sample jobs.</p>
        
        <?php
        // Check if API key is set
        if ($apiKey == "YOUR_RAPIDAPI_KEY_HERE" || empty($apiKey)) {
            echo '<div class="error">';
            echo '<h3>⚠️ API Key Not Set!</h3>';
            echo '<p><strong>Follow these steps to get your FREE API key:</strong></p>';
            echo '<div class="step">';
            echo '<p><strong>Step 1:</strong> Go to <a href="https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch" target="_blank">JSearch API on RapidAPI</a></p>';
            echo '<p><strong>Step 2:</strong> Click "Sign Up" (top right) or "Subscribe to Test"</p>';
            echo '<p><strong>Step 3:</strong> Choose the <strong>FREE plan</strong> (Basic - 1000 requests/month)</p>';
            echo '<p><strong>Step 4:</strong> After subscribing, click "Code Snippets" tab</p>';
            echo '<p><strong>Step 5:</strong> Copy your API key from the code example</p>';
            echo '<p><strong>Step 6:</strong> Paste it in this file at line 6 where it says: <code>$apiKey = "YOUR_RAPIDAPI_KEY_HERE";</code></p>';
            echo '</div>';
            echo '<p><a href="https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch" target="_blank" class="btn">Get FREE API Key →</a></p>';
            echo '</div>';
            exit;
        }
        
        echo '<div class="success">';
        echo '<p>✅ API Key is set! Testing connection...</p>';
        echo '</div>';
        
        // Test configuration
        echo '<h2>📋 Test Configuration</h2>';
        echo '<table>';
        echo '<tr><th>Setting</th><th>Value</th></tr>';
        echo '<tr><td>API Key</td><td>' . substr($apiKey, 0, 10) . '...' . substr($apiKey, -5) . ' (hidden for security)</td></tr>';
        echo '<tr><td>Test Query</td><td>Software Developer in South Africa</td></tr>';
        echo '<tr><td>API Endpoint</td><td>https://jsearch.p.rapidapi.com/search</td></tr>';
        echo '<tr><td>cURL Enabled</td><td>' . (function_exists('curl_version') ? '✅ Yes' : '❌ No') . '</td></tr>';
        
        if (function_exists('curl_version')) {
            $curlVersion = curl_version();
            echo '<tr><td>cURL Version</td><td>' . $curlVersion['version'] . '</td></tr>';
            echo '<tr><td>SSL Version</td><td>' . $curlVersion['ssl_version'] . '</td></tr>';
        }
        echo '</table>';
        
        // Prepare API request
        $apiUrl = "https://jsearch.p.rapidapi.com/search";
        $queryParams = [
            'query' => 'Software Developer in South Africa',
            'page' => '1',
            'num_pages' => '1',
            'date_posted' => 'month'
        ];
        
        $url = $apiUrl . '?' . http_build_query($queryParams);
        
        echo '<h2>🌐 Making API Request...</h2>';
        echo '<div class="info">';
        echo '<p><strong>Request URL:</strong> ' . htmlspecialchars($url) . '</p>';
        echo '</div>';
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "X-RapidAPI-Host: jsearch.p.rapidapi.com",
                "X-RapidAPI-Key: $apiKey"
            ],
        ]);
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        // Display response info
        echo '<h2>📊 Response Information</h2>';
        echo '<table>';
        echo '<tr><th>Metric</th><th>Value</th></tr>';
        echo '<tr><td>HTTP Status Code</td><td><strong>' . $httpCode . '</strong></td></tr>';
        echo '<tr><td>Response Time</td><td>' . $responseTime . ' ms</td></tr>';
        
        if ($curlError) {
            echo '<tr><td>cURL Error</td><td style="color:red;">' . htmlspecialchars($curlError) . ' (Code: ' . $curlErrno . ')</td></tr>';
        } else {
            echo '<tr><td>cURL Error</td><td style="color:green;">None ✅</td></tr>';
        }
        echo '</table>';
        
        // Handle errors
        if ($curlError) {
            echo '<div class="error">';
            echo '<h3>❌ Connection Error</h3>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($curlError) . '</p>';
            echo '<p><strong>Error Code:</strong> ' . $curlErrno . '</p>';
            echo '<p><strong>Possible Solutions:</strong></p>';
            echo '<ul>';
            echo '<li>Check your internet connection</li>';
            echo '<li>Verify your server allows outgoing HTTPS connections</li>';
            echo '<li>Contact your hosting provider if the issue persists</li>';
            echo '</ul>';
            echo '</div>';
            exit;
        }
        
        if ($httpCode != 200) {
            echo '<div class="error">';
            echo '<h3>❌ API Error (HTTP ' . $httpCode . ')</h3>';
            
            if ($httpCode == 401 || $httpCode == 403) {
                echo '<p><strong>Authentication Failed!</strong></p>';
                echo '<p>Your API key is invalid or you haven\'t subscribed to the API.</p>';
                echo '<p><strong>Solutions:</strong></p>';
                echo '<ul>';
                echo '<li>Make sure you\'ve subscribed to the FREE plan</li>';
                echo '<li>Copy your API key from the "Code Snippets" section</li>';
                echo '<li>Check that you copied the entire key without spaces</li>';
                echo '</ul>';
                echo '<p><a href="https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch" target="_blank" class="btn">Check API Subscription →</a></p>';
            } elseif ($httpCode == 429) {
                echo '<p><strong>Rate Limit Exceeded!</strong></p>';
                echo '<p>You\'ve used all your free requests for this month (1000 limit).</p>';
            } else {
                echo '<p>Unexpected error occurred.</p>';
            }
            
            echo '<p><strong>Raw Response:</strong></p>';
            echo '<pre>' . htmlspecialchars(substr($response, 0, 500)) . '</pre>';
            echo '</div>';
            exit;
        }
        
        // Success! Parse JSON
        echo '<div class="success">';
        echo '<h3>✅ Connection Successful!</h3>';
        echo '<p>Successfully connected to RapidAPI JSearch API!</p>';
        echo '</div>';
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo '<div class="error">';
            echo '<h3>❌ JSON Parse Error</h3>';
            echo '<p>' . json_last_error_msg() . '</p>';
            echo '<pre>' . htmlspecialchars(substr($response, 0, 500)) . '</pre>';
            echo '</div>';
            exit;
        }
        
        // Display API response summary
        echo '<h2>📈 API Response Summary</h2>';
        echo '<table>';
        echo '<tr><th>Metric</th><th>Value</th></tr>';
        echo '<tr><td>Status</td><td>' . ($data['status'] ?? 'Unknown') . '</td></tr>';
        echo '<tr><td>Total Jobs Found</td><td><strong>' . count($data['data'] ?? []) . '</strong></td></tr>';
        echo '<tr><td>Request ID</td><td>' . ($data['request_id'] ?? 'N/A') . '</td></tr>';
        echo '</table>';
        
        // Display jobs
        if (!empty($data['data'])) {
            echo '<h2>💼 Sample Jobs Found (' . count($data['data']) . ' results)</h2>';
            
            $jobCount = 0;
            foreach ($data['data'] as $job) {
                $jobCount++;
                if ($jobCount > 10) break; // Show only first 10 jobs
                
                echo '<div class="job-card">';
                echo '<div class="job-title">' . ($jobCount) . '. ' . htmlspecialchars($job['job_title'] ?? 'No Title') . '</div>';
                echo '<div class="job-company">🏢 ' . htmlspecialchars($job['employer_name'] ?? 'Unknown Company') . '</div>';
                echo '<div class="job-location">📍 ' . htmlspecialchars($job['job_city'] ?? '') . ', ' . htmlspecialchars($job['job_country'] ?? '') . '</div>';
                
                if (!empty($job['job_min_salary']) || !empty($job['job_max_salary'])) {
                    echo '<div class="job-location">💰 ' . 
                         htmlspecialchars($job['job_min_salary'] ?? '') . ' - ' . 
                         htmlspecialchars($job['job_max_salary'] ?? '') . ' ' . 
                         htmlspecialchars($job['job_salary_currency'] ?? '') . '</div>';
                }
                
                echo '<div class="job-location">🌐 Source: ' . htmlspecialchars($job['job_publisher'] ?? 'Unknown') . '</div>';
                
                if (!empty($job['job_apply_link'])) {
                    echo '<a href="' . htmlspecialchars($job['job_apply_link']) . '" target="_blank" class="btn">View Job →</a>';
                }
                echo '</div>';
            }
            
            echo '<div class="success">';
            echo '<h3>🎉 Test Successful!</h3>';
            echo '<p>Your RapidAPI connection is working perfectly!</p>';
            echo '<p><strong>Next Steps:</strong></p>';
            echo '<ol>';
            echo '<li>Use <code>fetch_jobs_rapidapi.php</code> to fetch and store jobs in your database</li>';
            echo '<li>Or use <code>auto_fetch_rapidapi.php</code> to fetch jobs from multiple searches</li>';
            echo '<li>Jobs will appear on your <code>index.php</code> page automatically</li>';
            echo '</ol>';
            echo '<p><a href="fetch_jobs_rapidapi.php" class="btn">Fetch Jobs Now →</a></p>';
            echo '</div>';
            
            // Show raw JSON sample
            echo '<h2>📄 Raw JSON Response (First Job)</h2>';
            echo '<details>';
            echo '<summary style="cursor:pointer; padding:10px; background:#f5f5f5; border-radius:5px;">Click to view raw JSON</summary>';
            echo '<pre>' . htmlspecialchars(json_encode($data['data'][0] ?? [], JSON_PRETTY_PRINT)) . '</pre>';
            echo '</details>';
            
        } else {
            echo '<div class="warning">';
            echo '<h3>⚠️ No Jobs Found</h3>';
            echo '<p>The API request was successful, but no jobs were returned.</p>';
            echo '<p>Try a different search query or location.</p>';
            echo '</div>';
        }
        ?>
        
        <hr style="margin: 30px 0;">
        <h2>📚 Useful Links</h2>
        <p>
            <a href="https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch" target="_blank" class="btn">RapidAPI Dashboard</a>
            <a href="https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch/tutorials" target="_blank" class="btn">API Documentation</a>
        </p>
        
    </div>
</body>
</html>