<?php
session_start();
include('config.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get interview ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: calendar.php");
    exit();
}

$interview_id = $_GET['id'];

// Fetch interview details
$interview_sql = "
    SELECT
        i.*,
        u.fullname AS candidate_name,
        u.email AS candidate_email,
        jp.position,
        jp.company_name
    FROM interviews i
    JOIN users u ON i.user_id = u.user_id
    JOIN job_postings jp ON i.job_id = jp.job_id
    WHERE i.interview_id = ?
";
$interview_stmt = $conn->prepare($interview_sql);
$interview_stmt->bind_param("i", $interview_id);
$interview_stmt->execute();
$interview_result = $interview_stmt->get_result();

if ($interview_result->num_rows === 0) {
    header("Location: calendar.php");
    exit();
}

$interview = $interview_result->fetch_assoc();

// Format the interview date
$interview_date = new DateTime($interview['interview_date']);
$formatted_date = $interview_date->format('l, F j, Y \a\t g:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>View Interview - <?php echo htmlspecialchars($interview['candidate_name']); ?></title>
    <style>
        /* ===========================
           GLOBAL RESET & VARIABLES
        ============================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #c9a9eaff;
            --dark: #18191a;
            --darker: #121314;
            --light: #f8f9fa;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --white: #ffffff;
            --black: #000000;
            --border-radius: 12px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        body {
            background-color: #f5f7fb;
            color: #333;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        body.dark-mode {
            background-color: var(--dark);
            color: #e4e6eb;
        }

        /* SIDEBAR */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: var(--white);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        /* --- FORCE DARK MODE BACKGROUND --- */
        body.dark-mode .sidebar {
            background-color: var(--dark) !important;
            background-image: none !important;
        }
        .sidebar.collapsed {
            width: 80px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 24px 20px;
            text-decoration: none;
            color: var(--white);
            font-size: 22px;
            font-weight: 700;
        }
        .logo i {
            font-size: 32px;
        }
        .logo-name span {
            white-space: nowrap;
            transition: var(--transition);
        }
        .sidebar.collapsed .logo-name span {
            display: none;
        }
        .side-menu {
            list-style: none;
            padding: 0 15px;
            flex: 1;
            overflow-y: auto;
        }
        .side-menu li {
            margin: 8px 0;
        }
        .side-menu li a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-size: 16px;
        }
        .side-menu li a:hover,
        .side-menu li.active a {
            background: rgba(255, 255, 255, 0.15);
        }
        .side-menu li a i {
            font-size: 22px;
            min-width: 24px;
            text-align: center;
        }
        .logout {
            margin-top: auto;
            padding: 16px !important;
            background: rgba(0, 0, 0, 0.2);
        }
        .section-title {
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.7);
            padding: 8px 16px;
            margin: 16px 0 8px 0;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar.collapsed .section-title {
            display: none;
        }

        /* MAIN CONTENT */
        .content {
            flex: 1;
            margin-left: 280px;
            transition: var(--transition);
        }
        .sidebar.collapsed ~ .content {
            margin-left: 80px;
        }

        /* NAVBAR */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 30px;
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 99;
        }
        body.dark-mode nav {
            background: #242526;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .theme-toggle {
            width: 50px;
            height: 24px;
            background: var(--light-gray);
            border-radius: 50px;
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 2px;
        }
        body.dark-mode .theme-toggle {
            background: #3a3b3c;
        }
        .theme-toggle::before {
            content: '';
            width: 20px;
            height: 20px;
            background: var(--white);
            border-radius: 50%;
            transition: var(--transition);
        }
        #theme-toggle:checked + .theme-toggle::before {
            transform: translateX(26px);
            background: var(--primary);
        }

        /* MAIN */
        main {
            padding: 24px;
        }

        /* WELCOME SECTION */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            box-shadow: var(--box-shadow);
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

        /* INTERVIEW DETAILS */
        .interview-container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
        }
        body.dark-mode .interview-container {
            background: #242526;
        }

        .interview-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-gray);
        }
        body.dark-mode .interview-header {
            border-bottom-color: #4a4a4a;
        }

        .interview-header h1 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        body.dark-mode .interview-header h1 {
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

        .status-scheduled { background-color: #d4edda; color: #155724; }
        .status-rescheduled { background-color: #fff3cd; color: #856404; }
        .status-completed { background-color: #d1ecf1; color: #0c5460; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }

        .interview-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .detail-section {
            background: var(--light-gray);
            padding: 20px;
            border-radius: 8px;
        }
        body.dark-mode .detail-section {
            background: #3a3b3c;
        }

        .detail-section h3 {
            color: var(--primary);
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
            color: var(--gray);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        body.dark-mode .detail-label {
            color: #adb5bd;
        }

        .detail-value {
            font-size: 16px;
            color: var(--dark);
            margin-top: 4px;
        }
        body.dark-mode .detail-value {
            color: #e4e6eb;
        }

        .interview-datetime {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }

        .interview-datetime h2 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .interview-datetime p {
            opacity: 0.9;
            font-size: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--warning);
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* MOBILE NAV LINKS */
        .mobile-nav-links {
            display: none;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            background: var(--white);
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        body.dark-mode .mobile-nav-links {
            background: #242526;
        }
        .mobile-nav-links a {
            display: inline-block;
            padding: 8px 12px;
            background: var(--light-gray);
            border-radius: 8px;
            text-decoration: none;
            color: var(--gray);
            font-size: 14px;
            transition: var(--transition);
            white-space: nowrap;
        }
        body.dark-mode .mobile-nav-links a {
            background: #3a3b3c;
            color: #adb5bd;
        }
        .mobile-nav-links a:hover,
        .mobile-nav-links a.active {
            background: var(--primary);
            color: white;
        }

        @media (max-width: 768px) {
            .mobile-nav-links {
                display: flex;
            }
            .interview-details {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            .logo-name span,
            .side-menu li a span {
                display: none;
            }
            .side-menu li a {
                justify-content: center;
                padding: 16px;
            }
            .content {
                margin-left: 80px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <script>
        (function() {
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="#" class="logo">
            <i class='bx bx-user-circle'></i>
            <div class="logo-name"><span>Admin</span></div>
        </a>
        <ul class="side-menu">
            <li><a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li class="section-header"><span>Candidates</span></li>
            <li><a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i><span>Jobs</span></a></li>
            <li><a href="manage_applications.php"><i class='bx bx-file'></i><span>Applications</span></a></li>
            <li><a href="manage_candidates.php"><i class='bx bx-user'></i><span>Candidates</span></a></li>
            <li><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a></li>
            <li class="active"><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
            <li class="section-header"><span>Consultants</span></li>
            <li><a href="admin_view_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="admin_view_tasklogs.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li><a href="admin_view_leaves.php"><i class='bx bx-calendar-minus'></i><span>Leaves</span></a></li>
            <li><a href="admin_invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="admin_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
            <li><a href="admin_settings.php"><i class='bx bx-cog'></i><span>Settings</span></a></li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="logout.php" class="logout" onclick="return confirmLogout();">
                    <i class='bx bx-log-out-circle'></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <!-- Mobile Nav Links -->
        <div class="mobile-nav-links">
            <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Manage Jobs</a>
            <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
            <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
            <a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a>
            <a class="active" href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
            <a href="manage_leave.php"><i class='bx bx-calendar-check'></i> Manage Leave</a>
            <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Interview Details</h1>
                <p>View and manage interview details</p>
            </div>

            <div class="interview-container">
                <!-- Interview Details -->
                <div class="interview-details">
                    <!-- Candidate Information -->
                    <div class="detail-section">
                        <h3><i class='bx bx-user'></i> Candidate Information</h3>
                        <div class="detail-item">
                            <div class="detail-label">Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($interview['candidate_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($interview['candidate_email']); ?></div>
                        </div>
                    </div>

                    <!-- Job Information -->
                    <div class="detail-section">
                        <h3><i class='bx bx-briefcase'></i> Job Information</h3>
                        <div class="detail-item">
                            <div class="detail-label">Position</div>
                            <div class="detail-value"><?php echo htmlspecialchars($interview['position']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Company</div>
                            <div class="detail-value"><?php echo htmlspecialchars($interview['company_name']); ?></div>
                        </div>
                    </div>

                    <!-- Interview Details -->
                    <div class="detail-section">
                        <h3><i class='bx bx-info-circle'></i> Interview Details</h3>
                        <div class="detail-item">
                            <div class="detail-label">Interviewer(s)</div>
                            <div class="detail-value"><?php echo htmlspecialchars($interview['interviewer']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Location</div>
                            <div class="detail-value"><?php echo htmlspecialchars($interview['company_address']); ?></div>
                        </div>
                    </div>

                    <!-- Status Information -->
                    <div class="detail-section">
                        <h3><i class='bx bx-time'></i> Status Information</h3>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value"><?php echo ucfirst($interview['interview_status']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Scheduled Date</div>
                            <div class="detail-value"><?php echo $interview_date->format('M d, Y \a\t g:i A'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="calendar.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Back to Calendar
                    </a>
                    <a href="schedule_interview.php" class="btn btn-primary">
                        <i class='bx bx-plus'></i> Schedule New Interview
                    </a>
                    <?php if ($interview['interview_status'] === 'Scheduled' || $interview['interview_status'] === 'Rescheduled'): ?>
                    <a href="reschedule_interview.php?id=<?php echo $interview['interview_id']; ?>" class="btn btn-warning">
                        <i class='bx bx-edit'></i> Reschedule
                    </a>
                    <a href="complete_interview.php?id=<?php echo $interview['interview_id']; ?>" class="btn btn-success">
                        <i class='bx bx-check'></i> Mark Complete
                    </a>
                    <a href="cancel_interview.php?id=<?php echo $interview['interview_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this interview?');">
                        <i class='bx bx-x'></i> Cancel Interview
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Theme toggle
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                const currentTheme = localStorage.getItem('theme');
                if (currentTheme) {
                    themeToggle.checked = (currentTheme === 'dark');
                    if (currentTheme === 'dark') {
                        document.body.classList.add('dark-mode');
                    }
                }
                themeToggle.addEventListener('change', function() {
                    if (this.checked) {
                        document.body.classList.add('dark-mode');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        document.body.classList.remove('dark-mode');
                        localStorage.setItem('theme', 'light');
                    }
                });
            }
        });

        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>