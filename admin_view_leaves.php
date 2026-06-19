<?php
include('config.php');
session_start();

$message = '';
$messageClass = '';

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

// Get filter status from URL parameter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build the query based on filter
$where_clause = "";
if ($filter_status !== 'all' && in_array($filter_status, ['Pending', 'Approved', 'Rejected'])) {
    $where_clause = "WHERE cl.status = '" . $conn->real_escape_string($filter_status) . "'";
}

// Fetch all leave requests with user details
$sql_leaves = "SELECT cl.*, u.fullname, u.email
               FROM consultant_leaves cl
               JOIN users u ON cl.user_id = u.user_id
               $where_clause
               ORDER BY
                 CASE
                     WHEN cl.status = 'Pending' THEN 1
                     WHEN cl.status = 'Approved' THEN 2
                     WHEN cl.status = 'Rejected' THEN 3
                 END,
                 cl.start_date DESC";
$leaves_result = $conn->query($sql_leaves);

// Fetch counts for statistics
$leave_count_sql = "SELECT 
    COUNT(*) AS total_leave_requests,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_leave_count,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_leave_count,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_leave_count
    FROM consultant_leaves";
$leave_count_result = $conn->query($leave_count_sql);
$leave_counts = $leave_count_result->fetch_assoc();
$total_leave_requests = $leave_counts['total_leave_requests'] ?? 0;
$pending_leave_count = $leave_counts['pending_leave_count'] ?? 0;
$approved_leave_count = $leave_counts['approved_leave_count'] ?? 0;
$rejected_leave_count = $leave_counts['rejected_leave_count'] ?? 0;

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
    <title>Admin - Manage Leave Requests</title>

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

        .search-container {
            display: flex;
            align-items: center;
            gap: 15px;
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

        .mobile-logout-btn {
            display: none;
            font-size: 28px;
            padding: 12px;
            color: var(--gray);
            text-decoration: none;
        }

        .mobile-logout-btn:hover {
            color: var(--danger);
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
           STATS CARDS
        ============================ */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        body.dark-mode .stat-card {
            background: #242526;
        }

        .stat-card i {
            font-size: 32px;
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card.total i {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
        }

        .stat-card.pending i {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .stat-card.approved i {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .stat-card.rejected i {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        body.dark-mode .stat-info p {
            color: #adb5bd;
        }

        /* ===========================
           FILTER TABS
        ============================ */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            background: var(--white);
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            text-decoration: none;
            color: var(--gray);
            font-weight: 500;
            transition: var(--transition);
        }

        body.dark-mode .filter-tab {
            background: #242526;
            border-color: #3a3b3c;
            color: #adb5bd;
        }

        .filter-tab:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-tab.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        /* ===========================
           ORDERS SECTION
        ============================ */
        .orders {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ===========================
           TABLE STYLING
        ============================ */
        .table-responsive {
            overflow-x: auto;
            padding: 0 20px 20px;
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
           STATUS BADGES
        ============================ */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
            display: inline-block;
        }

        .status-pending { 
            background: #fef3c7; 
            color: #92400e; 
        }
        
        .status-approved { 
            background: #d1fae5; 
            color: #065f46; 
        }
        
        .status-rejected { 
            background: #fee2e2; 
            color: #be123c; 
        }

        body.dark-mode .status-pending { 
            background: #7c6130; 
            color: #fff; 
        }
        
        body.dark-mode .status-approved { 
            background: #1b5f47; 
            color: #d1fae5; 
        }
        
        body.dark-mode .status-rejected { 
            background: #7d2a35; 
            color: #fee2e2; 
        }

        /* ===========================
           BUTTONS
        ============================ */
        .btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .btn:hover { 
            background-color: var(--primary-dark); 
        }

        .btn-danger { 
            background: var(--danger); 
        }
        
        .btn-danger:hover { 
            background: #c82333; 
        }

        .btn-success { 
            background: #10b981; 
        }
        
        .btn-success:hover { 
            background: #059669; 
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .btn-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        /* ===========================
           EMPTY STATE
        ============================ */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 18px;
        }

        /* ===========================
           MOBILE NAV LINKS
        ============================ */
        .mobile-nav-links {
            display: none;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            background: var(--white);
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
           RESPONSIVE
        ============================ */
        @media (min-width: 769px) {
            .mobile-nav-links {
                display: none;
            }
        }

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

            .mobile-logout-btn {
                display: block;
            }

            .search-container {
                display: none;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .mobile-nav-links {
                display: flex;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .filter-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 10px;
            }

            .filter-tab {
                white-space: nowrap;
            }

            th, td {
                padding: 10px 8px;
                font-size: 14px;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn-group .btn {
                width: 100%;
                justify-content: center;
            }
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
            <li><a href="admin_view_tasklogs.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li class="active"><a href="admin_view_leaves.php"><i class='bx bx-calendar-minus'></i><span>Leaves</span></a></li>
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
            <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Jobs</a>
            <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
            <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
            <a href="admin_settings.php"><i class='bx bx-cog'></i> Settings</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Leaves</h1>
                    <p>Review, approve, and track employee leave requests while monitoring leave balances and schedules.</p>
                </div>
            </div>

            <!-- Message Alert -->
            <?php if ($message): ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <i class='bx bx-info-circle'></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card total" onclick="filterLeaves('all')">
                    <i class='bx bx-calendar-check'></i>
                    <div class="stat-info">
                        <h3><?php echo $total_leave_requests; ?></h3>
                        <p>Total Requests</p>
                    </div>
                </div>
                <div class="stat-card pending" onclick="filterLeaves('Pending')">
                    <i class='bx bx-time'></i>
                    <div class="stat-info">
                        <h3><?php echo $pending_leave_count; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card approved" onclick="filterLeaves('Approved')">
                    <i class='bx bx-check-circle'></i>
                    <div class="stat-info">
                        <h3><?php echo $approved_leave_count; ?></h3>
                        <p>Approved</p>
                    </div>
                </div>
                <div class="stat-card rejected" onclick="filterLeaves('Rejected')">
                    <i class='bx bx-x-circle'></i>
                    <div class="stat-info">
                        <h3><?php echo $rejected_leave_count; ?></h3>
                        <p>Rejected</p>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                    <i class='bx bx-list-ul'></i> All
                </a>
                <a href="?status=Pending" class="filter-tab <?php echo $filter_status === 'Pending' ? 'active' : ''; ?>">
                    <i class='bx bx-time'></i> Pending
                </a>
                <a href="?status=Approved" class="filter-tab <?php echo $filter_status === 'Approved' ? 'active' : ''; ?>">
                    <i class='bx bx-check'></i> Approved
                </a>
                <a href="?status=Rejected" class="filter-tab <?php echo $filter_status === 'Rejected' ? 'active' : ''; ?>">
                    <i class='bx bx-x'></i> Rejected
                </a>
            </div>

            <!-- Leave Requests Table -->
            <div class="orders">
                <div class="header">
                    <h3><i class='bx bx-calendar-minus'></i> Leave Requests</h3>
                    <a href="download_leave_requests.php" class="btn btn-sm">
                        <i class='bx bx-download'></i> Export
                    </a>
                </div>

                <div class="table-responsive">
                    <?php if ($leaves_result && $leaves_result->num_rows > 0): ?>
                        <table id="leavesTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $leaves_result->fetch_assoc()): ?>
                                    <?php 
                                    $start_date = new DateTime($row['start_date']);
                                    $end_date = new DateTime($row['end_date']);
                                    $duration = $start_date->diff($end_date)->days + 1;
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <i class='bx bx-user-circle' style='font-size: 28px; color: #667eea;'></i>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($row['fullname']); ?></strong><br>
                                                    <small style="color: #666;"><?php echo htmlspecialchars($row['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                        <td><?php echo $start_date->format('M d, Y'); ?></td>
                                        <td><?php echo $end_date->format('M d, Y'); ?></td>
                                        <td><strong><?php echo $duration; ?></strong> day<?php echo $duration > 1 ? 's' : ''; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view_leave_request.php?id=<?php echo $row['consult_leave_id']; ?>" class="btn btn-sm" title="View Details">
                                                    <i class='bx bx-show'></i>
                                                </a>
                                                <?php if ($row['status'] === 'Pending'): ?>
                                                    <a href="approve_leave.php?id=<?php echo $row['consult_leave_id']; ?>" class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Are you sure you want to approve this leave request?');">
                                                        <i class='bx bx-check'></i>
                                                    </a>
                                                    <a href="reject_leave.php?id=<?php echo $row['consult_leave_id']; ?>" class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Are you sure you want to reject this leave request?');">
                                                        <i class='bx bx-x'></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-calendar-x'></i>
                            <p>No leave requests found.</p>
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
                const overlay = document.getElementById('mobileMenuOverlay');
                overlay.style.display = document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none';
            });
        }
        
        document.getElementById('mobileMenuOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            this.style.display = 'none';
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

        // Filter function
        function filterLeaves(status) {
            window.location.href = '?status=' + status;
        }

        // Search function
        function searchLeaves() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#leavesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        }

        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchLeaves();
            }
        });

        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }
    </script>
</body>
</html>
