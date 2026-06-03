<?php
session_start();
include('config.php');

// Check if query parameter is provided, if so fetch jobs from API
if (isset($_GET['query']) && !empty($_GET['query'])) {
    // API Configuration
    $apiKey = "1a415439d1msh0cf954e0b27270bp1e5892jsn97ca1364e756"; // ⚠️ REPLACE THIS with your actual key

    // Prepare API request
    $apiUrl = "https://jsearch.p.rapidapi.com/search";
    $searchQuery = $_GET['query'];
    $queryParams = [
        'query' => $searchQuery . ' in South Africa',
        'page' => '1',
        'num_pages' => '1',
        'date_posted' => 'month'
    ];

    $url = $apiUrl . '?' . http_build_query($queryParams);

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

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Handle successful response
    if ($httpCode == 200) {
        $data = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE && !empty($data['data'])) {
            // Store jobs in session
            $jobs = [];
            foreach ($data['data'] as $job) {
                $title = mysqli_real_escape_string($conn, $job['job_title'] ?? 'No Title');
                $company = mysqli_real_escape_string($conn, $job['employer_name'] ?? 'Unknown Company');
                $location = mysqli_real_escape_string($conn, $job['job_city'] . ', ' . $job['job_country'] ?? 'South Africa');
                $description = mysqli_real_escape_string($conn, $job['job_description'] ?? '');
                $url = mysqli_real_escape_string($conn, $job['job_apply_link'] ?? '');
                $salary = '';

                if (!empty($job['job_min_salary']) || !empty($job['job_max_salary'])) {
                    $salary = ($job['job_min_salary'] ?? '') . ' - ' . ($job['job_max_salary'] ?? '') . ' ' . ($job['job_salary_currency'] ?? '');
                    $salary = mysqli_real_escape_string($conn, trim($salary));
                }

                $source = $job['job_publisher'] ?? 'JSearch';

                if (empty($url)) {
                    continue;
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

            $_SESSION['jobs'] = $jobs;
        } else {
            // JSON error or no jobs found
            $_SESSION['jobs'] = [];
        }
    } else {
        // API error
        $_SESSION['jobs'] = [];
    }
}

if (!isset($_SESSION['jobs']) || empty($_SESSION['jobs'])) {
    echo "<!DOCTYPE html><html><head><title>Error</title><style>body{font-family:Arial;text-align:center;padding:50px;}</style></head><body>";
    echo "<h2>No Jobs Found</h2>";
    echo "<p>The API request was unsuccessful or no jobs were returned.</p>";
    echo "<p><a href='manage_jobs.php'>Back to Manage Jobs</a></p>";
    echo "</body></html>";
    exit();
}

$jobs = $_SESSION['jobs'];

// Handle job insertion if form is submitted
if (isset($_POST['confirm_insert'])) {
    // Insert the selected jobs
    $jobsInserted = 0;
    $jobsSkipped = 0;

    $selectedIndices = isset($_POST['selected_jobs']) ? $_POST['selected_jobs'] : [];

    foreach ($selectedIndices as $index) {
        if (isset($_SESSION['jobs'][$index])) {
            $job = $_SESSION['jobs'][$index];
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

    // Clear session
    unset($_SESSION['jobs']);

    // Show success message and redirect
    echo "<!DOCTYPE html><html><head><title>Success</title><style>body{font-family:Arial;text-align:center;padding:50px;}</style></head><body>";
    echo "body{font-family:Arial;text-align:center;padding:50px; background-color: #f8f9fa; color: #343a40;}";
    echo "body.dark-mode { background-color: #18191a; color: #e4e6eb; }";
    echo "body.dark-mode a { color: #5a85fa; }";
    echo "</style>";
    echo "<script>
            (function() {
                const currentTheme = localStorage.getItem('theme');
                if (currentTheme === 'dark') {
                    document.body.classList.add('dark-mode'); 
                }
            })();
          </script>";
    echo "</head><body>";
    echo "<h2>✅ Jobs Inserted Successfully!</h2>";
    echo "<p><strong>Jobs inserted:</strong> $jobsInserted</p>";
    echo "<p><strong>Jobs skipped (duplicates):</strong> $jobsSkipped</p>";
    echo "<p><a href='manage_jobs.php'>Back to Manage Jobs</a> | <a href='index.php'>View Jobs on Homepage</a></p>";
    echo "</body></html>";
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fetched Jobs - Confirmation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script>
        (function() {
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode'); 
            }
        })();
    </script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .job-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
            transition: transform 0.2s;
        }
        .job-card:hover {
            transform: translateY(-5px);
        }
        .job-title {
            color: #007bff;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .job-company {
            color: #6c757d;
            font-size: 1.1rem;
        }
        .job-location {
            color: #28a745;
            font-size: 1rem;
        }
        .job-description {
            margin-top: 10px;
            color: #495057;
        }
        .job-salary {
            font-weight: bold;
            color: #dc3545;
        }
        /* Modern Button Styles */
        .btn-modern {
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            padding: 14px 28px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .btn-modern:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-modern:hover:before {
            left: 100%;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .btn-confirm {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-size: 1.1rem;
            padding: 16px 32px;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-confirm:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
        }

        .btn-select-all {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-select-all:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
        }

        .btn-deselect-all {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
        }

        .btn-deselect-all:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
        }

        .btn-back {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.3);
            margin-bottom: 15px;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            box-shadow: 0 10px 30px rgba(107, 114, 128, 0.4);
        }

        .btn-view-job {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            font-size: 0.9rem;
            padding: 10px 20px;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
            margin-top: 15px;
        }

        .btn-view-job:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-group-modern {
            display: flex;
            gap: 15px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            margin: 30px 0;
        }

        .btn-group-modern .btn-modern {
            min-width: 160px;
            justify-content: center;
        }

        /* Enhanced checkbox styling */
        .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .form-check-label {
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .form-check-label:hover {
            color: #10b981;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 1.2rem;
        }
        /* --- Dark Mode Styles (New) --- */
        body.dark-mode {
            --bg-body: #18191a;
            --bg-container: #242526;
            --text-primary: #e4e6eb;
            --text-secondary: #b0b3b8;
            --shadow-color: rgba(0, 0, 0, 0.4);
            --job-title-color: #5a85fa; /* Blue for contrast */
            --job-company-color: #b0b3b8;
            --job-location-color: #62c37e; /* Green for contrast */
            --job-salary-color: #ff7d7d; /* Red for contrast */
            --header-gradient: linear-gradient(135deg, #4a56a0 0%, #5a3a7f 100%);
        }

        body.dark-mode {
            background-color: var(--bg-body);
            color: var(--text-primary);
        }

        body.dark-mode .job-card {
            background: var(--bg-container);
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        /* Fixing visibility/contrast */
        body.dark-mode .job-title {
            color: var(--job-title-color);
        }
        body.dark-mode .job-company {
            color: var(--job-company-color);
        }
        body.dark-mode .job-location {
            color: var(--job-location-color);
        }
        body.dark-mode .job-salary {
            color: var(--job-salary-color);
        }
        body.dark-mode .job-description {
            color: var(--text-secondary);
        }
        body.dark-mode .job-description p {
            color: var(--text-secondary);
        }
        body.dark-mode .form-check-label {
            color: var(--text-primary);
        }
        body.dark-mode .form-check-label:hover {
            color: #10b981; /* Keep hover color consistent */
        }
        body.dark-mode .header {
            background: var(--header-gradient);
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-briefcase"></i> Fetched Jobs from JSearch API</h1>
        <p>Review the jobs below and confirm to insert them into the database.</p>
    </div>

    <div class="container">
        <form method="post" action="" id="jobForm">
            <div class="row">
                <?php foreach ($jobs as $index => $job): ?>
                    <div class="col-md-6">
                        <div class="job-card">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="selected_jobs[]" value="<?php echo $index; ?>" id="job_<?php echo $index; ?>" checked>
                                <label class="form-check-label" for="job_<?php echo $index; ?>">
                                    <strong>Select this job</strong>
                                </label>
                            </div>
                            <h5 class="job-title"><i class="fas fa-code"></i> <?php echo htmlspecialchars($job['title']); ?></h5>
                            <p class="job-company"><i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company']); ?></p>
                            <p class="job-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></p>
                            <?php if (!empty($job['salary'])): ?>
                                <p class="job-salary"><i class="fas fa-dollar-sign"></i> <?php echo htmlspecialchars($job['salary']); ?></p>
                            <?php endif; ?>
                            <div class="job-description">
                                <strong>Description:</strong>
                                <p><?php echo htmlspecialchars(substr($job['description'], 0, 200)) . (strlen($job['description']) > 200 ? '...' : ''); ?></p>
                            </div>
                            <a href="<?php echo htmlspecialchars($job['url']); ?>" target="_blank" class="btn-modern btn-view-job">
                                <i class="fas fa-external-link-alt"></i> View Original Job
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="btn-group-modern">
                <button type="submit" name="confirm_insert" value="1" class="btn-modern btn-confirm">
                    <i class="fas fa-check"></i> Fetch Selected Jobs
                </button>
                <button type="button" class="btn-modern btn-select-all" onclick="selectAll()">
                    <i class="fas fa-check-square"></i> Select All
                </button>
                <button type="button" class="btn-modern btn-deselect-all" onclick="deselectAll()">
                    <i class="fas fa-square"></i> Deselect All
                </button>
            </div>
        </form>
        <div class="text-center mt-3">
            <a href="manage_jobs.php" class="btn-modern btn-back">
                <i class="fas fa-arrow-left"></i> Back to Manage Jobs
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function selectAll() {
            const checkboxes = document.querySelectorAll('input[name="selected_jobs[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = true);
        }

        function deselectAll() {
            const checkboxes = document.querySelectorAll('input[name="selected_jobs[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = false);
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
