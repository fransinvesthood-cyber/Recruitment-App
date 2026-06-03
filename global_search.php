<?php
include('config.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to view this page.");
}

$user_id = $_SESSION['user_id'];

// Get search query
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$jobs_results = [];
$applications_results = [];
$interviews_results = [];

// Search Jobs
if (!empty($search)) {
    $search_escaped = $conn->real_escape_string($search);
    
    // Search job postings
    $job_sql = "SELECT job_postings.job_id, job_postings.position, companies.company_name, 
                 job_postings.location, job_postings.job_description, job_postings.date_posted,
                 departments.department_name
                 FROM job_postings
                 LEFT JOIN companies ON job_postings.company_id = companies.company_id
                 LEFT JOIN departments ON job_postings.department_id = departments.department_id
                 WHERE job_postings.position LIKE '%$search_escaped%' 
                 OR companies.company_name LIKE '%$search_escaped%'
                 OR departments.department_name LIKE '%$search_escaped%'
                 OR job_postings.location LIKE '%$search_escaped%'
                 ORDER BY job_postings.date_posted DESC
                 LIMIT 20";
    $jobs_result = $conn->query($job_sql);
    if ($jobs_result && $jobs_result->num_rows > 0) {
        while ($row = $jobs_result->fetch_assoc()) {
            $jobs_results[] = $row;
        }
    }
    
    // Search user's applications
    $app_sql = "SELECT ja.application_id, ja.job_id, ja.application_status, ja.submission_date,
                jp.position, c.company_name, ja.cover_letter
                FROM job_applications ja
                JOIN job_postings jp ON ja.job_id = jp.job_id
                LEFT JOIN companies c ON jp.company_id = c.company_id
                WHERE ja.user_id = $user_id 
                AND (jp.position LIKE '%$search_escaped%' 
                OR c.company_name LIKE '%$search_escaped%'
                OR ja.application_status LIKE '%$search_escaped%')
                ORDER BY ja.submission_date DESC
                LIMIT 20";
    $apps_result = $conn->query($app_sql);
    if ($apps_result && $apps_result->num_rows > 0) {
        while ($row = $apps_result->fetch_assoc()) {
            $applications_results[] = $row;
        }
    }
    
    // Search user's interviews
    $int_sql = "SELECT i.interview_id, i.interview_date, i.interview_status, i.availability_status,
                jp.position, c.company_name
                FROM interviews i
                JOIN job_postings jp ON i.job_id = jp.job_id
                LEFT JOIN companies c ON jp.company_id = c.company_id
                JOIN users u ON i.user_id = u.user_id
                WHERE u.user_id = $user_id
                AND (jp.position LIKE '%$search_escaped%'
                OR c.company_name LIKE '%$search_escaped%'
                OR i.interview_status LIKE '%$search_escaped%')
                ORDER BY i.interview_date DESC
                LIMIT 20";
    $ints_result = $conn->query($int_sql);
    if ($ints_result && $ints_result->num_rows > 0) {
        while ($row = $ints_result->fetch_assoc()) {
            $interviews_results[] = $row;
        }
    }
}

// Get user info for header
$fullname = $_SESSION['fullname'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - <?php echo htmlspecialchars($search); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="assets/logo1.png" type="image/x-icon">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #4facfe;
            --error-color: #ff6b6b;
            --warning-color: #feca57;
            --text-color: #2d3748;
            --bg-color: #f7fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #e0e7ff 0%, #f0f2f5 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: var(--text-color);
            transition: background 0.3s ease, color 0.3s ease;
        }

        .container {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 1200px;
            margin: 40px auto;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideIn 0.5s ease-out;
            transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Exit Button */
        .btn-exit {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f3f4f6;
            color: #4f46e5;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            backdrop-filter: blur(10px);
        }
        .btn-exit:hover {
            background: #e0e7ff;
            color: #3730a3;
            transform: scale(1.05);
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        .welcome-section h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .welcome-section p {
            opacity: 0.9;
            font-size: 18px;
        }

        /* Search Form */
        .search-form-container {
            max-width: 700px;
            margin: 0 auto 30px auto;
            padding: 0 20px;
        }
        
        .search-wrapper {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .search-wrapper:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 4px 25px rgba(102, 126, 234, 0.3);
        }
        
        .search-wrapper input {
            flex: 1;
            border: none;
            padding: 18px 25px;
            font-size: 16px;
            background: transparent;
            color: var(--text-color);
            outline: none;
        }
        
        .search-wrapper input::placeholder {
            color: #999;
        }
        
        .search-wrapper button {
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            padding: 18px 30px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-wrapper button:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
        }
        
        .search-wrapper button i {
            font-size: 18px;
        }

        /* Navigation Links */
        .nav-menu {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .nav-menu a {
            color: var(--primary-color);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-menu a:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Results Section */
        .results-section {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .results-section:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .results-section h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
        }

        .result-count {
            background: var(--primary-color);
            color: white;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: auto;
        }

        .result-item {
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .result-item:hover {
            background: white;
            border-color: var(--primary-color);
            transform: translateX(5px);
        }

        .result-item h3 {
            margin: 0 0 8px 0;
            color: var(--primary-color);
            font-size: 18px;
        }

        .result-item h3 a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .result-item h3 a:hover {
            color: var(--secondary-color);
        }

        .result-meta {
            color: #666;
            font-size: 14px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .result-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved, .status-scheduled { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-viewed { background: #cce5ff; color: #004085; }
        .status-submitted { background: #e9ecef; color: #495057; }
        .status-under { background: #fff3cd; color: #856404; }
        .status-shortlisted { background: #d1ecf1; color: #0c5460; }
        .status-hired { background: #d4edda; color: #155724; }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-results i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }

        /* Theme Toggle */
        .theme-toggle-container {
            position: absolute;
            top: 28px;
            right: 80px;
            z-index: 100;
        }

        #theme-toggle {
            display: none;
        }

        .theme-label {
            width: 50px;
            height: 26px;
            background-color: #ccc;
            border-radius: 50px;
            display: inline-block;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .theme-label::after {
            content: '';
            width: 20px;
            height: 20px;
            background-color: white;
            border-radius: 50%;
            position: absolute;
            top: 3px;
            left: 3px;
            transition: transform 0.3s;
        }

        #theme-toggle:checked + .theme-label {
            background-color: #6366f1;
        }

        #theme-toggle:checked + .theme-label::after {
            transform: translateX(24px);
        }

        /* Dark Mode */
        body.dark-mode {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            color: #f7fafc;
        }

        body.dark-mode .container {
            background: #2d3748;
            color: #edf2f7;
            border-color: #4a5568;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        body.dark-mode .btn-exit {
            background: #4a5568;
            color: #a7b7ff;
        }
        
        body.dark-mode .btn-exit:hover {
            background: #6366f1;
            color: white;
        }

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d1b4e 100%);
            color: #e2e8f0;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }

        body.dark-mode .search-wrapper {
            background: #4a5568;
            border-color: transparent;
        }
        
        body.dark-mode .search-wrapper input {
            background: transparent;
            color: #f7fafc;
        }
        
        body.dark-mode .search-wrapper input::placeholder {
            color: #a0aec0;
        }

        body.dark-mode .nav-menu a {
            background: #4a5568;
            color: #a7b7ff;
        }

        body.dark-mode .nav-menu a:hover {
            background: var(--primary-color);
            color: white;
        }

        body.dark-mode .results-section {
            background: #2d3748;
            border-color: #4a5568;
        }

        body.dark-mode .results-section h2 {
            color: #a7b7ff;
            border-bottom-color: #4a5568;
        }

        body.dark-mode .result-item {
            background: #4a5568;
            border-color: #4a5568;
        }

        body.dark-mode .result-item:hover {
            background: #6366f1;
            border-color: #6366f1;
        }

        body.dark-mode .result-item h3 a {
            color: #a7b7ff;
        }

        body.dark-mode .result-meta {
            color: #cbd5e0;
        }

        body.dark-mode .no-results {
            color: #cbd5e0;
        }

        body.dark-mode .status-pending { background: #744210; color: #fefcbf; }
        body.dark-mode .status-approved, body.dark-mode .status-scheduled { background: #22543d; color: #c6f6d5; }
        body.dark-mode .status-rejected { background: #742a2a; color: #fed7d7; }
        body.dark-mode .status-viewed { background: #234e52; color: #b2f5ea; }
        body.dark-mode .status-submitted { background: #4a5568; color: #e2e8f0; }
        body.dark-mode .status-shortlisted { background: #234e52; color: #b2f5ea; }
        body.dark-mode .status-hired { background: #22543d; color: #c6f6d5; }
    </style>
</head>
<body>
    <div class="container">
        <button class="btn-exit" id="exitPage" onclick="window.location.href='applicant.php'">
            <i class='bx bx-x'></i>
        </button>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1><i class='bx bx-search'></i> Search Results</h1>
            <p>Find jobs, applications, and interviews</p>
        </div>

        <!-- Search Form -->
        <div class="search-form-container">
            <form method="GET" action="global_search.php" class="search-form">
                <div class="search-wrapper">
                    <input type="text" name="q" placeholder="Search jobs, applications, interviews..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class='bx bx-search'></i> Search</button>
                </div>
            </form>
        </div>

        <!-- Navigation Links -->
        <div class="nav-menu">
            <a href="applicant.php"><i class='bx bx-home'></i> Dashboard</a>
            <a href="my_applications.php"><i class='bx bx-file'></i> My Applications</a>
            <a href="my_interviews.php"><i class='bx bx-calendar'></i> My Interviews</a>
        </div>

        <!-- Results Container -->
        <div class="results-container">
            <?php if (empty($search)): ?>
                <div class="no-results">
                    <i class='bx bx-search-alt'></i>
                    <h3>Search for Results</h3>
                    <p>Please enter a search term to find results across jobs, applications, and interviews.</p>
                </div>
            <?php else: ?>
                <!-- Jobs Section -->
                <div class="results-section">
                    <h2>
                        <i class='bx bx-briefcase'></i> Jobs
                        <span class="result-count"><?php echo count($jobs_results); ?></span>
                    </h2>
                    <?php if (empty($jobs_results)): ?>
                        <p class="no-results">No jobs found matching "<?php echo htmlspecialchars($search); ?>"</p>
                    <?php else: ?>
                        <?php foreach ($jobs_results as $job): ?>
                            <div class="result-item">
                                <h3><a href="job_details.php?job_id=<?php echo $job['job_id']; ?>"><?php echo htmlspecialchars($job['position']); ?></a></h3>
                                <div class="result-meta">
                                    <span><i class='bx bx-building'></i> <?php echo htmlspecialchars($job['company_name'] ?? 'N/A'); ?></span>
                                    <span><i class='bx bx-map'></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                    <span><i class='bx bx-calendar'></i> Posted: <?php echo date('M j, Y', strtotime($job['date_posted'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Applications Section -->
                <div class="results-section">
                    <h2>
                        <i class='bx bx-file-find'></i> My Applications
                        <span class="result-count"><?php echo count($applications_results); ?></span>
                    </h2>
                    <?php if (empty($applications_results)): ?>
                        <p class="no-results">No applications found matching "<?php echo htmlspecialchars($search); ?>"</p>
                    <?php else: ?>
                        <?php foreach ($applications_results as $app): ?>
                            <div class="result-item">
                                <h3><a href="my_applications.php"><?php echo htmlspecialchars($app['position']); ?></a></h3>
                                <div class="result-meta">
                                    <span><i class='bx bx-building'></i> <?php echo htmlspecialchars($app['company_name'] ?? 'N/A'); ?></span>
                                    <span>
                                        <?php 
                                            $statusClass = '';
                                            switch(strtolower($app['application_status'])) {
                                                case 'submitted': $statusClass = 'status-submitted'; break;
                                                case 'under review': $statusClass = 'status-under'; break;
                                                case 'shortlisted': $statusClass = 'status-shortlisted'; break;
                                                case 'rejected': $statusClass = 'status-rejected'; break;
                                                case 'hired': $statusClass = 'status-hired'; break;
                                                default: $statusClass = 'status-pending';
                                            }
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($app['application_status']); ?>
                                        </span>
                                    </span>
                                    <span><i class='bx bx-calendar'></i> Applied: <?php echo date('M j, Y', strtotime($app['submission_date'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Interviews Section -->
                <div class="results-section">
                    <h2>
                        <i class='bx bx-calendar-check'></i> My Interviews
                        <span class="result-count"><?php echo count($interviews_results); ?></span>
                    </h2>
                    <?php if (empty($interviews_results)): ?>
                        <p class="no-results">No interviews found matching "<?php echo htmlspecialchars($search); ?>"</p>
                    <?php else: ?>
                        <?php foreach ($interviews_results as $interview): ?>
                            <div class="result-item">
                                <h3><a href="my_interviews.php"><?php echo htmlspecialchars($interview['position']); ?> Interview</a></h3>
                                <div class="result-meta">
                                    <span><i class='bx bx-building'></i> <?php echo htmlspecialchars($interview['company_name'] ?? 'N/A'); ?></span>
                                    <span>
                                        <?php 
                                            $intStatusClass = '';
                                            switch(strtolower($interview['interview_status'])) {
                                                case 'scheduled': $intStatusClass = 'status-scheduled'; break;
                                                case 'completed': $intStatusClass = 'status-approved'; break;
                                                case 'cancelled': $intStatusClass = 'status-rejected'; break;
                                                default: $intStatusClass = 'status-pending';
                                            }
                                        ?>
                                        <span class="status-badge <?php echo $intStatusClass; ?>">
                                            <?php echo htmlspecialchars($interview['interview_status']); ?>
                                        </span>
                                    </span>
                                    <span><i class='bx bx-calendar'></i> Date: <?php echo date('M j, Y H:i', strtotime($interview['interview_date'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($jobs_results) && empty($applications_results) && empty($interviews_results)): ?>
                    <div class="no-results">
                        <i class='bx bx-search-alt'></i>
                        <h3>No Results Found</h3>
                        <p>No results found for "<?php echo htmlspecialchars($search); ?>"</p>
                        <p>Try different keywords or check your spelling.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // ============================================
        // DARK MODE SYNC LOGIC
        // ============================================
        const themeToggle = document.getElementById('theme-toggle');

        function applyTheme(isEnabled) {
            if (isEnabled) {
                document.body.classList.add('dark-mode');
                themeToggle.checked = true;
            } else {
                document.body.classList.remove('dark-mode');
                themeToggle.checked = false;
            }
        }

        // 1. Check LocalStorage on Load
        window.addEventListener('DOMContentLoaded', () => {
            const savedSetting = localStorage.getItem('darkMode');
            if (savedSetting === 'enabled') {
                applyTheme(true);
            } else {
                applyTheme(false);
            }
        });

        // 2. Listen for Toggle Changes
        themeToggle.addEventListener('change', () => {
            if (themeToggle.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    </script>
</body>
</html>
