<?php
include('config.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to view this page.");
}

$user_id = $_SESSION['user_id'];

// Fetch full name
$sql = "SELECT fullname FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname);
$stmt->fetch();
$stmt->close();

// Fetch all tasklogs with user details
$sql_tasklogs = "SELECT ctl.*, u.fullname, u.email
                 FROM consultant_task_logs ctl
                 JOIN users u ON ctl.user_id = u.user_id
                 ORDER BY ctl.work_date DESC";
$tasklogs_result = $conn->query($sql_tasklogs);

// Session message handling
if (!empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageClass = $_SESSION['messageClass'];
    unset($_SESSION['message'], $_SESSION['messageClass']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Admin - View Tasklogs</title>

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

        /* ===========================
           SIDEBAR STYLES
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

        .side-menu li.section-header {
            padding: 8px 16px;
            font-weight: 600;
            color: var(--white);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
            margin-top: 12px;
        }

        .sidebar.collapsed .side-menu li.section-header span {
            display: none;
        }

        .logout {
            margin-top: auto;
            padding: 16px !important;
            background: rgba(0, 0, 0, 0.2);
        }

        @media (min-width: 769px) {
            .logout {
                display: none;
            }
        }

        /* ===========================
           MAIN CONTENT STYLES
        ============================ */
        .content {
            flex: 1;
            margin-left: 280px;
            transition: var(--transition);
        }

        .sidebar.collapsed ~ .content {
            margin-left: 80px;
        }

        /* ===========================
           NAVBAR STYLES
        ============================ */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: sticky;
            top: 0;
            z-index: 99;
            transition: var(--transition);
        }

        body.dark-mode nav {
            background: #242526;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        nav .bx-menu {
            font-size: 28px;
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
        }

        nav .bx-menu:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 28px;
            color: var(--gray);
            cursor: pointer;
            transition: var(--transition);
            padding: 8px;
            border-radius: 8px;
        }

        .mobile-menu-btn:hover {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
        }

        .form-input {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            border-radius: 25px;
            padding: 10px 18px;
            width: 320px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }

        body.dark-mode .form-input {
            background: linear-gradient(135deg, rgba(58, 59, 60, 0.9), rgba(58, 59, 60, 0.7));
            border-color: rgba(102, 126, 234, 0.2);
        }

        .form-input:focus-within {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2), 0 0 0 3px rgba(102, 126, 234, 0.1);
            border-color: var(--primary);
            transform: translateY(-2px) scale(1.02);
        }

        body.dark-mode .form-input:focus-within {
            background: linear-gradient(135deg, rgba(36, 37, 38, 0.95), rgba(36, 37, 38, 0.85));
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3), 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .form-input input {
            background: transparent;
            border: none;
            outline: none;
            padding: 8px;
            width: 100%;
            font-size: 16px;
            color: inherit;
            transition: all 0.3s ease;
            font-weight: 400;
        }

        .form-input input::placeholder {
            color: var(--gray);
            transition: color 0.3s ease;
            font-style: italic;
        }

        .form-input:focus-within input::placeholder {
            color: rgba(108, 117, 125, 0.6);
            transform: translateX(2px);
        }

        .search-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            cursor: pointer;
            color: var(--white);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .search-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .search-btn:active {
            transform: scale(0.95);
        }

        .search-btn i {
            font-size: 16px;
        }

        .theme-toggle {
            width: 60px;
            height: 30px;
            background: linear-gradient(135deg, var(--light-gray), rgba(102, 126, 234, 0.1));
            border-radius: 50px;
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 3px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .theme-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.2);
        }

        body.dark-mode .theme-toggle {
            background: linear-gradient(135deg, #3a3b3c, rgba(102, 126, 234, 0.2));
            border-color: rgba(102, 126, 234, 0.3);
        }

        .theme-toggle i {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 16px;
            position: absolute;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .theme-toggle .bx-sun {
            left: 3px;
            color: #ffa500;
            z-index: 1;
            background: linear-gradient(135deg, #ffd700, #ffa500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .theme-toggle .bx-moon {
            left: 33px;
            color: #667eea;
            z-index: 1;
            background: linear-gradient(135deg, #667eea, #c9a9eaff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        #theme-toggle:checked + .theme-toggle .bx-sun {
            left: -30px;
            transform: rotate(180deg);
        }

        #theme-toggle:checked + .theme-toggle .bx-moon {
            left: 3px;
            transform: rotate(0deg);
        }

        #theme-toggle:checked + .theme-toggle {
            background: linear-gradient(135deg, #2d3748, #1a202c);
        }

        /* ===========================
           MAIN CONTENT AREA
        ============================ */
        main {
            padding: 24px;
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
        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 10px 24px rgba(0,0,0,0.28);
        }

        .welcome-content h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .welcome-content p {
            opacity: 0.9;
            font-size: 18px;
        }

        /* ===========================
           HEADER & BREADCRUMB
        ============================ */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .breadcrumb {
            list-style: none;
            display: flex;
            gap: 12px;
        }

        .breadcrumb a {
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
        }

        /* ===========================
           ORDERS SECTION
        ============================ */
        .orders {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            height: 100%;
        }

        body.dark-mode .orders {
            background: #242526;
        }

        .orders .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: rgba(102, 126, 234, 0.05);
        }

        body.dark-mode .orders .header {
            background: rgba(102, 126, 234, 0.1);
        }

        .orders .header h3 {
            font-size: 18px;
            font-weight: 600;
        }

        /* ===========================
           TABLE STYLING
        ============================ */
        .table-responsive {
            overflow-x: auto;
            padding: 0 20px 20px;
            max-height: 600px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        body.dark-mode th,
        body.dark-mode td {
            border-color: #3a3b3c;
            color: #e4e6eb;
        }

        th {
            background: rgba(102, 126, 234, 0.08);
            font-weight: 600;
            color: var(--primary);
            position: sticky;
            top: 0;
        }

        body.dark-mode th {
            background: rgba(102, 126, 234, 0.15);
            color: #a7b7ff;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: rgba(102, 126, 234, 0.03);
        }

        body.dark-mode tr:hover {
            background: rgba(102, 126, 234, 0.08);
        }

        /* ===========================
           ALERTS
        ============================ */
        .alert {
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            animation: fadeSlideDown 0.9s ease-in-out;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        @keyframes fadeSlideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===========================
           RESPONSIVE DESIGN
        ============================ */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-280px);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content {
                margin-left: 0;
            }

            nav {
                padding: 16px;
            }

            .mobile-menu-btn {
                display: block;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            th, td {
                padding: 12px 8px;
                font-size: 14px;
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

    <div class="mobile-menu-overlay" id="mobileMenuOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;"></div>

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
            <li><a href="admin_user_management.php"><i class='bx bx-user'></i><span>Users</span></a></li>
            <li><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a></li>
            <li><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
            <li class="section-header"><span>Consultants</span></li>
            <li><a href="admin_view_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li class="active"><a href="admin_view_tasklogs.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
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

    <div class="content">
        <main>
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Tasklogs</h1>
                    <p>Review, monitor, and manage daily task submissions to ensure accurate reporting and productivity tracking.</p>
                </div>
            </div>

            <div class="header">
                <div class="left">
                    <ul class="breadcrumb">
                        <li><a href="admin_dashboard.php">Dashboard</a></li>
                        <li><a href="#" class="active">Tasklogs</a></li>
                    </ul>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="orders">
                <div class="header">
                    <i class='bx bx-file'></i>
                    <h3>All Tasklogs</h3>
                </div>
                <div class="table-responsive">
                    <?php if ($tasklogs_result && $tasklogs_result->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Work Date</th>
                                    <th>Client/Project</th>
                                    <th>Description</th>
                                    <th>Challenges</th>
                                    <th>Support Needed</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $tasklogs_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class='bx bx-user-circle' style="font-size: 24px; color: #667eea;"></i>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($row['fullname']); ?></strong><br>
                                                    <small style="color: #666;"><?php echo htmlspecialchars($row['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($row['work_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['client_project']); ?></td>
                                        <td><?php echo htmlspecialchars($row['summary']); ?></td>
                                        <td><?php echo htmlspecialchars($row['challenges']); ?></td>
                                        <td><?php echo htmlspecialchars($row['support']); ?></td>
                                        <td><?php echo htmlspecialchars($row['notes']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class='bx bx-file' style="font-size: 48px; margin-bottom: 15px;"></i>
                            <p>No tasklogs submitted yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
                document.getElementById('mobileMenuOverlay').style.display =
                    document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none';
            });
        }

        document.getElementById('mobileMenuOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            this.style.display = 'none';
        });

        // Close sidebar when clicking menu items on mobile
        document.querySelectorAll('.side-menu a').forEach(link => {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                    document.getElementById('mobileMenuOverlay').style.display = 'none';
                    if (this.href !== window.location.href && !this.onclick) {
                        e.preventDefault();
                        setTimeout(() => {
                            window.location.href = this.href;
                        }, 300);
                    }
                }
            });
        });

        // Sidebar collapse on tablet
        function handleTabletView() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth <= 992 && window.innerWidth > 768) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        }

        window.addEventListener('resize', handleTabletView);
        handleTabletView();

        // Theme toggle
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');

            if (themeToggle) {
                const currentTheme = localStorage.getItem('theme');
                if (currentTheme) {
                    themeToggle.checked = (currentTheme === 'dark');
                    if (currentTheme === 'dark') document.body.classList.add('dark-mode');
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
            return confirm("Are you sure you want to log out?");
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
