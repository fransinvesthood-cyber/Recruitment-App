<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Consultant') {
    header("Location: index.php");
    exit();
}

include('config.php');

$username = $_SESSION['username'];
$user_id = null;

$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($db_user_id);
$stmt->fetch();
$stmt->close();

$user_id = $db_user_id;

// Handle video link click - record start time
if (isset($_GET['start_training'])) {
    $training_module_id = 1; 

    $stmt = $conn->prepare("SELECT id FROM training_progress WHERE user_id = ? AND module_id = ?");
    $stmt->bind_param("ii", $user_id, $training_module_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO training_progress (user_id, module_id, start_date) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $user_id, $training_module_id);
        $stmt->execute();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Management</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css">

    <style>
        /* ===========================
           GLOBAL RESET & VARIABLES (from dashboard)
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

        /* ===========================
           WELCOME SECTION
        ============================ */
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

        /* ===========================
           SIDEBAR (copied from dashboard)
        ============================ */
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
        body.dark-mode .sidebar {
            background: #242526;
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
        .side-menu li.active a {
            background: rgba(255, 255, 255, 0.15);
        }
        .side-menu li a:hover {
            background: rgba(255, 255, 255, 0.1);
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

        /* ===========================
           MAIN CONTENT & NAVBAR
        ============================ */
        .content {
            flex: 1;
            margin-left: 280px;
            transition: var(--transition);
        }
        .sidebar.collapsed ~ .content {
            margin-left: 80px;
        }

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
        nav .bx-menu {
            font-size: 28px;
            cursor: pointer;
            color: var(--gray);
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

        /* ===========================
           MOBILE NAV LINKS BAR (like dashboard)
        ============================ */
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

        /* ===========================
           MAIN
        ============================ */
        main {
            padding: 24px;
        }

        .header {
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 8px;
        }
        .breadcrumb {
            list-style: none;
            display: flex;
            gap: 8px;
            font-size: 14px;
            color: var(--gray);
        }
        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }
        .breadcrumb a.active {
            color: var(--gray);
            font-weight: 500;
        }

        /* ===========================
           CARD STYLING
        ============================ */
        .card {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 24px;
        }
        body.dark-mode .card {
            background: #242526;
        }

        .card h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        body.dark-mode .card h3 {
            color: #e4e6eb;
        }
        .card h3 i {
            color: var(--primary);
        }

        .external-link {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 15px;
            margin-top: 15px;
        }
        .external-link:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* ===========================
           TABLE STYLING
        ============================ */
        .table-wrapper {
            overflow-x: auto;
            margin-top: 16px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }
        body.dark-mode table {
            background: #242526;
        }
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }
        body.dark-mode th,
        body.dark-mode td {
            border-color: #3a3b3c;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        body.dark-mode th {
            background: #2d2e2f;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover {
            background: #f8f9fa;
        }
        body.dark-mode tr:hover {
            background: #2d2e2f;
        }

        .expired {
            color: var(--danger);
            font-weight: bold;
        }

        /* ===========================
           ALERTS
        ============================ */
        .alert {
            padding: 14px 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.error {
            background: #fee2e2;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        body.dark-mode .alert.error {
            background: #4b0a0a;
            color: #fee2e2;
        }

        /* ===========================
           BUTTONS
        ============================ */
        .btn {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 15px;
        }
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* ===========================
           RESPONSIVE
        ============================ */
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
            nav {
                padding: 16px 20px;
            }
            .mobile-nav-links {
                display: block;
            }
            .card {
                padding: 20px;
            }
            .external-link,
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .header h1 { font-size: 24px; }
            .breadcrumb { flex-wrap: wrap; }
        }

        /* Mobile Menu Overlay */
        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar.active ~ .mobile-menu-overlay {
            display: block;
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

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="#" class="logo">
            <i class='bx bx-user-circle'></i>
            <div class="logo-name"><span>Consultant</span></div>
        </a>
        <ul class="side-menu">
            <li><a href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li><a href="consultant_profile.php"><i class='bx bx-user'></i><span>Profile</span></a></li>
            <li><a href="manage_leave.php"><i class='bx bx-calendar-minus'></i><span>Leave</span></a></li>
            <li><a href="manage_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="manage_task_log.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li><a href="invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li class="active"><a href="training_management.php"><i class='bx bx-book-reader'></i><span>Training</span></a></li>
            <!--<li><a href="consultant_feedback.php"><i class='bx bx-message-dots'></i><span>Client Feedback</span></a></li>-->
            <li><a href="consultant_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="logout.php" class="logout" onclick="return confirm('Are you sure you want to log out?');">
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
            <a href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="consultant_profile.php"><i class='bx bx-user'></i> Profile</a>
            <a href="manage_timesheets.php"><i class='bx bx-time-five'></i> Timesheets</a>
            <a href="manage_task_log.php"><i class='bx bx-time-five'></i> Tasklogs</a>
            <a href="manage_leave.php"><i class='bx bx-calendar-minus'></i> Leave</a>
            <a href="invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a class="active" href="training_management.php"><i class='bx bx-book-reader'></i> Training</a>
            <!--<a href="consultant_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>-->
            <a href="consultant_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Training</h1>
                <p>Complete assigned courses, monitor your learning progress, and stay up to date with required certifications.</p>
            </div>

            <div class="profile-container">
                <div class="card">
                    <h3><i class='bx bx-video'></i> Start Training Module</h3>
                    <p>Click the button below to begin your training session.</p>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>?start_training=1" onclick="return confirmStartTraining()" target="_blank" class="external-link">
                        <i class='bx bx-play-circle'></i> Watch Training Video
                    </a>
                </div>

                <div class="card full-width">
                    <h3><i class='bx bx-table'></i> Your Certifications</h3>
                    <div class="table-wrapper">
                        <table class="certifications-table">
                            <thead>
                                <tr>
                                    <th>Certification Name</th>
                                    <th>Status</th>
                                    <th>Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $certifications = [
                                    ['name' => 'Advanced Project Management', 'status' => 'Completed', 'expiry' => '2026-12-31'],
                                    ['name' => 'Certified Scrum Master', 'status' => 'Expired', 'expiry' => '2023-12-31'],
                                    ['name' => 'Data Privacy Compliance', 'status' => 'Pending', 'expiry' => '-']
                                ];

                                foreach ($certifications as $cert) {
                                    $rowClass = ($cert['status'] === 'Expired') ? 'expired' : '';
                                    echo "<tr class='$rowClass'>
                                            <td>{$cert['name']}</td>
                                            <td>{$cert['status']}</td>
                                            <td>" . ($cert['expiry'] ?? '-') . "</td>
                                          </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $expiredCount = count(array_filter($certifications, fn($c) => $c['status'] === 'Expired'));
                    if ($expiredCount > 0): ?>
                        <div class="alert error">
                            <i class='bx bx-error-circle'></i>
                            <span>You have <strong><?= $expiredCount ?></strong> expired certification(s). Please renew them as soon as possible.</span>
                        </div>
                    <?php endif; ?>

                    <a href="consultant_dashboard.php" class="btn" style="margin-top:20px;">
                        <i class='bx bx-arrow-back'></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // ✅ YOUR EXISTING JS — untouched
        document.addEventListener('DOMContentLoaded', function() {
            const menuBar = document.getElementById('mobileMenuBtn') || document.querySelector('.bx-menu');
            const sidebar = document.getElementById('sidebar');

            if (menuBar && sidebar) {
                if (localStorage.getItem('sidebar') === 'closed') {
                    sidebar.classList.add('collapsed');
                }
                menuBar.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    document.getElementById('mobileMenuOverlay').style.display = 
                        sidebar.classList.contains('active') ? 'block' : 'none';
                    // Tablet collapse handled separately below
                });
            }

            // Theme toggle
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                const currentTheme = localStorage.getItem('theme');
                if (currentTheme) {
                    themeToggle.checked = (currentTheme === 'dark');
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

            // Tablet sidebar collapse
            function handleTabletView() {
                const sidebar = document.getElementById('sidebar');
                if (window.innerWidth <= 992 && window.innerWidth > 768) {
                    sidebar.classList.add('collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                    sidebar.classList.remove('active');
                    document.getElementById('mobileMenuOverlay')?.style.display = 'none';
                }
            }
            window.addEventListener('resize', handleTabletView);
            handleTabletView();
        });

        function confirmStartTraining() {
            return confirm("Are you sure you want to start this training module?");
        }
    </script>
</body>
</html>