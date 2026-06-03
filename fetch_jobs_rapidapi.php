<?php
session_start();
include('config.php');

// RapidAPI JSearch - Free tier: 1000 requests/month
// Sign up at: https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch
$apiKey = "1a415439d1msh0cf954e0b27270bp1e5892jsn97ca1364e756
"; // Replace with your RapidAPI key

$apiUrl = "https://jsearch.p.rapidapi.com/search";

// Search parameters
$queryParams = [
    'query' => 'Software Developer in South Africa',
    'page' => '1',
    'num_pages' => '3',
    'date_posted' => 'month' // all, today, 3days, week, month
];

$url = $apiUrl . '?' . http_build_query($queryParams);

echo "<h2>Fetching Jobs from JSearch API (RapidAPI)...</h2>";
echo "<hr>";

// Initialize cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    die("<p style='color:red;'>❌ cURL Error: " . $curlError . "</p>");
}

if ($httpCode != 200) {
    die("<p style='color:red;'>❌ API returned HTTP code: $httpCode</p>" .
        "<p>Make sure you've added your RapidAPI key!</p>" .
        "<p>Sign up at: <a href='https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch' target='_blank'>JSearch API</a></p>");
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("JSON decode error: " . json_last_error_msg());
}

echo "API Status: " . ($data['status'] ?? 'Unknown') . "<br>";
echo "Total Jobs Found: " . (count($data['data']) ?? 0) . "<br><br>";

$jobs = [];
if (!empty($data['data'])) {
    foreach ($data['data'] as $job) {
        // Extract job details
        $title = mysqli_real_escape_string($conn, $job['job_title'] ?? 'No Title');
        $company = mysqli_real_escape_string($conn, $job['employer_name'] ?? 'Unknown Company');
        $location = mysqli_real_escape_string($conn, $job['job_city'] . ', ' . $job['job_country'] ?? 'South Africa');
        $description = mysqli_real_escape_string($conn, $job['job_description'] ?? '');
        $url = mysqli_real_escape_string($conn, $job['job_apply_link'] ?? '');
        $salary = '';

        // Build salary string if available
        if (!empty($job['job_min_salary']) || !empty($job['job_max_salary'])) {
            $salary = ($job['job_min_salary'] ?? '') . ' - ' . ($job['job_max_salary'] ?? '') . ' ' . ($job['job_salary_currency'] ?? '');
            $salary = mysqli_real_escape_string($conn, trim($salary));
        }

        $source = $job['job_publisher'] ?? 'JSearch';

        if (empty($url)) {
            continue; // Skip jobs without URL
        }

        $jobs[] = [
            'title' => $title,
            'company' => $company,
            'location' => $location,
            'description' => $description,
            'url' => $url,
            'salary' => $salary,
            'source' => $source
        ];
    }
}

$_SESSION['jobs'] = $jobs;

if (isset($_POST['confirm_insert'])) {
    // Insert the selected jobs
    $jobsInserted = 0;
    $jobsSkipped = 0;

    $selectedIndices = isset($_POST['selected_jobs']) ? $_POST['selected_jobs'] : [];

    foreach ($selectedIndices as $index) {
        if (isset($jobs[$index])) {
            $job = $jobs[$index];
            // Check for duplicates
            $checkQuery = "SELECT external_job_id FROM external_jobs WHERE url = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("s", $job['url']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows == 0) {
                $insertQuery = "INSERT INTO external_jobs (title, company, location, description, url, salary, date_fetched)
                               VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param("ssssss", $job['title'], $job['company'], $job['location'], $job['description'], $job['url'], $job['salary']);

                if ($insertStmt->execute()) {
                    $jobsInserted++;
                    echo "✓ Inserted: " . htmlspecialchars($job['title']) . " at " . htmlspecialchars($job['company']) . "<br>";
                } else {
                    echo "✗ Error: " . $insertStmt->error . "<br>";
                }
                $insertStmt->close();
            } else {
                $jobsSkipped++;
            }
            $checkStmt->close();
        }
    }

    echo "<br><hr>";
    echo "<h3>Summary:</h3>";
    echo "✓ Jobs inserted: <strong>$jobsInserted</strong><br>";
    echo "⊘ Jobs skipped (duplicates): <strong>$jobsSkipped</strong><br>";
    echo "<br><a href='index.php'>View Jobs on Homepage</a>";

    // Clear session
    unset($_SESSION['jobs']);
} else {
    // Redirect to display page
    header("Location: display_fetched_jobs.php");
    exit();
}

$conn->close();
?>
