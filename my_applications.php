<?php
include('config.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: Unauthorized Access.");
}

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Fetch user's full name
$sql = "SELECT fullname FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname);
$stmt->fetch();
$stmt->close();

// Fetch user's job applications with job and company details
$sql = "SELECT 
            ja.application_id,
            ja.submission_date, 
            ja.application_status, 
            ja.comments, 
            job_postings.position, 
            job_postings.location, 
            companies.company_name,
            ss.profile_data
        FROM job_applications ja
        LEFT JOIN application_snapshots ss ON ja.application_id = ss.application_id
        INNER JOIN job_postings ON ja.job_id = job_postings.job_id
        INNER JOIN companies ON job_postings.company_id = companies.company_id
        WHERE ja.user_id = ?
        ORDER BY ja.submission_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
            display: flex;
            justify-content: center;
            align-items: center;
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

        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .header h2 {
            margin: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 28px;
            font-weight: 700;
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

        /* Dark Mode Welcome Section */
        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d1b4e 100%);
            color: #e2e8f0;
            border: 1px solid rgba(102, 126, 234, 0.3);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }
        body.dark-mode .welcome-section h1 {
            color: #e2e8f0;
        }
        body.dark-mode .welcome-section p {
            color: #a0aec0;
            opacity: 1;
        }

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

        .no-applications {
            text-align: center;
            padding: 40px;
            color: var(--text-color);
            font-size: 18px;
        }

        /* Application Container Styles */
        .applications-container {
            margin-top: 20px;
        }

        .application-container {
            max-width: 1200px;
            margin: 0 auto 30px auto;
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        body.dark-mode .application-container {
            background: #2d3748;
        }

        .application-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        body.dark-mode .application-header {
            border-bottom-color: var(--border-color);
        }

        .application-header h1 {
            font-size: 28px;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        body.dark-mode .application-header h1 {
            color: #a7b7ff;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .status-submitted { background-color: #e9ecef; color: #495057; }
        .status-review { background-color: #fff3cd; color: #856404; }
        .status-shortlisted { background-color: #d1ecf1; color: #0c5460; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-hired { background-color: #d4edda; color: #155724; }

        .application-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        body.dark-mode .detail-section {
            background: #2d3748;
        }

        .detail-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        body.dark-mode .detail-section h3 {
            color: #a7b7ff;
        }

        .detail-item {
            margin-bottom: 12px;
        }

        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        body.dark-mode .detail-label {
            color: #cbd5e0;
        }

        .detail-value {
            font-size: 16px;
            color: var(--text-color);
            margin-top: 4px;
        }

        body.dark-mode .detail-value {
            color: #f7fafc;
        }

        .application-datetime {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #ffffff;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }

        .application-datetime h2 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .application-datetime p {
            opacity: 0.9;
            font-size: 16px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .application-details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container { padding: 25px; margin: 20px; }
            .btn-exit { top: 15px; right: 15px; }
            .theme-toggle-container { right: 70px !important; top: 20px !important; }
            .application-details {
                grid-template-columns: 1fr;
            }
        }

        /* =========================================
           Theme Toggle (Synced Style)
           ========================================= */
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

        .toggle-icon {
            position: absolute;
            top: 5px;
            font-size: 14px;
            color: white;
            z-index: 10;
        }
        .bx-sun { left: 6px; }
        .bx-moon { right: 6px; }


        /* =========================================
           Dark Mode Overrides
           ========================================= */
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

        body.dark-mode .header h2 {
            background: linear-gradient(135deg, #667eea, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Override Bootstrap Table Styles for Dark Mode */
        body.dark-mode .table {
            --bs-table-bg: #2d3748; /* Crucial override for Bootstrap */
            background: #2d3748;
            border-color: #4a5568;
            color: #edf2f7;
        }

        body.dark-mode .table thead th {
            background: #4a5568;
            color: #f7fafc;
            border-bottom: 2px solid #718096;
        }

        body.dark-mode .table tbody tr {
            border-bottom: 1px solid #4a5568;
            background: #2d3748; /* Force dark background on rows */
        }

        body.dark-mode .table tbody tr:hover {
            background: #4a5568;
        }

        body.dark-mode .table tbody td {
            background-color: #2d3748; /* Force dark background on cells */
            color: #e2e8f0;
            border-bottom-color: #4a5568;
        }

        /* Fix visibility of "No applications" text */
        body.dark-mode .no-applications {
            color: #edf2f7 !important;
            background-color: #2d3748 !important;
        }

        /* Dark Mode Badges */
        body.dark-mode .status-submitted { background: #4a5568; color: #e2e8f0; border-color: #718096; }
        body.dark-mode .status-review { background: #744210; color: #fefcbf; border-color: #b7791f; }
        body.dark-mode .status-shortlisted { background: #234e52; color: #b2f5ea; border-color: #319795; }
        body.dark-mode .status-rejected { background: #742a2a; color: #fed7d7; border-color: #e53e3e; }
        body.dark-mode .status-hired { background: #22543d; color: #c6f6d5; border-color: #38a169; }

    </style>
</head>
<body>
    <div class="container">
        <button class="btn-exit" id="exitPage"><i class='bx bx-x'></i></button>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Job Applications</h1>
            <p>Track and manage your job application status</p>
        </div>

        <div class="applications-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()):
                    // Define the class based on application status
                    $statusClass = '';
                    switch ($row['application_status']) {
                        case 'Submitted':
                            $statusClass = 'status-submitted';
                            break;
                        case 'Under Review':
                            $statusClass = 'status-review';
                            break;
                        case 'Shortlisted':
                            $statusClass = 'status-shortlisted';
                            break;
                        case 'Rejected':
                            $statusClass = 'status-rejected';
                            break;
                        case 'Hired':
                            $statusClass = 'status-hired';
                            break;
                    }
                ?>
                    <div class="application-container">
                        <div class="application-header">
                            <h1><?php echo htmlspecialchars($row['position']); ?> at <?php echo htmlspecialchars($row['company_name']); ?></h1>
                            <div class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($row['application_status']); ?>
                            </div>
                        </div>

                        <div class="application-details">
                            <!-- Job Information -->
                            <div class="detail-section">
                                <h3><i class='bx bx-briefcase'></i> Job Information</h3>
                                <div class="detail-item">
                                    <div class="detail-label">Position</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($row['position']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Company</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($row['company_name']); ?></div>
                                </div>
                            </div>

                            <!-- Application Details -->
                            <div class="detail-section">
                                <h3><i class='bx bx-info-circle'></i> Application Details</h3>
                            <div class="detail-item">
                                <div class="detail-label">Location</div>
                                <div class="detail-value"><?php echo htmlspecialchars($row['location'] ?? ($profile_data ? json_decode($profile_data, true)['address'] ?? '—' : '—')); ?></div>
                            </div>
                            <?php if (isset($row['profile_data'])): 
                                $profile = json_decode($row['profile_data'], true);
                            ?>
                            <div class="detail-item">
                                <div class="detail-label">Professional Title</div>
                                <div class="detail-value"><?php echo htmlspecialchars($profile['professional_title'] ?? '—'); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Skills</div>
                                <div class="detail-value"><?php echo htmlspecialchars(($profile['tech_skills'] ?? '') . ($profile['tech_skills'] && $profile['soft_skills'] ? ', ' : '') . ($profile['soft_skills'] ?? '')); ?></div>
                            </div>
                            <?php endif; ?>
                                <div class="detail-item">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($row['application_status']); ?></div>
                                </div>
                            </div>

                            <!-- Application Date -->
                            <div class="detail-section">
                                <h3><i class='bx bx-calendar'></i> Application Date</h3>
                                <div class="detail-item">
                                    <div class="detail-label">Submission Date</div>
                                    <div class="detail-value"><?php echo date('F j, Y', strtotime($row['submission_date'])); ?></div>
                                </div>
                            </div>

                            <!-- Comments -->
                            <?php if (!empty($row['comments'])): ?>
                                <div class="detail-section">
                                    <h3><i class='bx bx-comment'></i> Comments</h3>
                                    <div class="detail-item">
                                        <div class="detail-label">Feedback</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($row['comments']); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-applications">
                    <i class='bx bx-file' style="font-size: 48px; color: #cbd5e0; margin-bottom: 15px;"></i>
                    <h3>No Job Applications</h3>
                    <p>You haven't submitted any job applications yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById("exitPage").addEventListener("click", function() {
            window.location.href = 'applicant.php';
        });

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

<?php
$stmt->close();
$conn->close();
?>