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

// Fetch summary metrics
$sql_pending = "SELECT COUNT(*) as total_pending FROM consultant_timesheets WHERE status = 'Pending'";
$pending_result = $conn->query($sql_pending);
$pending_count = $pending_result->fetch_assoc()['total_pending'];

$sql_approved_week = "SELECT COUNT(*) as total_approved_week FROM consultant_timesheets WHERE status = 'Approved' AND work_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
$approved_week_result = $conn->query($sql_approved_week);
$approved_week_count = $approved_week_result->fetch_assoc()['total_approved_week'];

$sql_rejected = "SELECT COUNT(*) as total_rejected FROM consultant_timesheets WHERE status = 'Rejected'";
$rejected_result = $conn->query($sql_rejected);
$rejected_count = $rejected_result->fetch_assoc()['total_rejected'];

$sql_missing_submissions = "SELECT COUNT(*) as total_missing FROM users WHERE user_id NOT IN (SELECT DISTINCT user_id FROM consultant_timesheets)";
$missing_result = $conn->query($sql_missing_submissions);
$missing_count = $missing_result->fetch_assoc()['total_missing'];

$sql_billable_hours = "SELECT SUM(hours_worked) as total_billable_hours FROM consultant_timesheets WHERE billable = 1";
$billable_result = $conn->query($sql_billable_hours);
$billable_hours = $billable_result->fetch_assoc()['total_billable_hours'] ?? 0;

// Get filter parameters from GET request
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_consultant = isset($_GET['consultant']) ? $_GET['consultant'] : '';
$filter_project = isset($_GET['project']) ? $_GET['project'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search_consultant = isset($_GET['search']) ? trim($_GET['search']) : '';

// Quick filters
$quick_filter = isset($_GET['quick_filter']) ? $_GET['quick_filter'] : '';

$current_date = date('Y-m-d');
$current_week_start = date('Y-m-d', strtotime('monday this week'));
$current_month_start = date('Y-m-01');

// Fetch all consultants for dropdown
$sql_consultants = "SELECT DISTINCT u.user_id, u.fullname 
                    FROM consultant_timesheets ct 
                    JOIN users u ON ct.user_id = u.user_id 
                    ORDER BY u.fullname ASC";
$consultants_result = $conn->query($sql_consultants);

// Fetch all unique projects for dropdown
$sql_projects = "SELECT DISTINCT client_project FROM consultant_timesheets WHERE client_project IS NOT NULL AND client_project != '' ORDER BY client_project ASC";
$projects_result = $conn->query($sql_projects);

// Build the main SQL query with filters
$sql_timesheets = "SELECT ct.*, u.fullname, u.email
                   FROM consultant_timesheets ct
                   JOIN users u ON ct.user_id = u.user_id
                   WHERE 1=1";

// Apply status filter
if (!empty($filter_status)) {
    $sql_timesheets .= " AND ct.status = '" . $conn->real_escape_string($filter_status) . "'";
}

// Apply consultant filter
if (!empty($filter_consultant)) {
    $sql_timesheets .= " AND ct.user_id = " . intval($filter_consultant);
}

// Apply project filter
if (!empty($filter_project)) {
    $sql_timesheets .= " AND ct.client_project = '" . $conn->real_escape_string($filter_project) . "'";
}

// Apply date range filter
if (!empty($filter_date_from)) {
    $sql_timesheets .= " AND ct.work_date >= '" . $conn->real_escape_string($filter_date_from) . "'";
}
if (!empty($filter_date_to)) {
    $sql_timesheets .= " AND ct.work_date <= '" . $conn->real_escape_string($filter_date_to) . "'";
}

// Apply search by consultant name
if (!empty($search_consultant)) {
    $sql_timesheets .= " AND u.fullname LIKE '%" . $conn->real_escape_string($search_consultant) . "%'";
}

// Apply quick filters
if ($quick_filter === 'pending') {
    $sql_timesheets .= " AND ct.status = 'Pending'";
} elseif ($quick_filter === 'this_week') {
    $sql_timesheets .= " AND ct.work_date >= '" . $conn->real_escape_string($current_week_start) . "'";
} elseif ($quick_filter === 'this_month') {
    $sql_timesheets .= " AND ct.work_date >= '" . $conn->real_escape_string($current_month_start) . "'";
}

$sql_timesheets .= " ORDER BY ct.work_date DESC";

$timesheets_result = $conn->query($sql_timesheets);

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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.tailwindcss.min.css">
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.tailwindcss.min.js"></script>
    <title>Admin - View Timesheets</title>

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
            max-width: 1030px;
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
           SUMMARY DASHBOARD
        ============================ */
        .summary-dashboard {
            margin-bottom: 24px;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            max-width: 1030px;
        }

        .summary-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        body.dark-mode .summary-card {
            background: #242526;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .card-icon i {
            color: var(--white);
        }

        .summary-card:nth-child(1) .card-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .summary-card:nth-child(2) .card-icon {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .summary-card:nth-child(3) .card-icon {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .summary-card:nth-child(4) .card-icon {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
        }

        .summary-card:nth-child(5) .card-icon {
            background: linear-gradient(135deg, #14b8a6, #0d9488);
        }

        .card-content h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }

        body.dark-mode .card-content h3 {
            color: #e4e6eb;
        }

        .card-content p {
            font-size: 13px;
            color: var(--gray);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        body.dark-mode .card-content p {
            color: #b0b3b8;
        }

        @media (max-width: 1200px) {
            .summary-cards {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .summary-cards {
                grid-template-columns: 1fr;
            }
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
            overflow-x: auto;
            overflow-y: visible;
            box-shadow: var(--box-shadow);
            height: 100%;
            width: 84%;
            max-width: 1230px;
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
        .table-container {
            position: relative;
            padding: 0 50px 20px;
            overflow: visible !important;
        }
        
        .table-scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            z-index: 1000;
            display: flex !important;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        .table-scroll-btn:hover {
            background: #5a67d8;
            transform: translateY(-50%) scale(1.1);
        }
        
        .table-scroll-btn.left {
            left: 5px;
        }
        
        .table-scroll-btn.right {
            right: 5px;
        }
        
        .table-responsive {
            overflow-x: auto;
            overflow-y: visible;
            max-height: 600px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            position: relative;
        }
        
        /* Enhanced scrollbar styling - always visible */
        .table-responsive {
            scrollbar-width: thin;
            scrollbar-color: #667eea #f1f1f1;
        }
        
        .table-responsive::-webkit-scrollbar {
            height: 12px;
            width: 12px;
            position: relative;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #667eea, #5a67d8);
            border-radius: 6px;
            border: 2px solid #f1f1f1;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(90deg, #5a67d8, #4c51bf);
        }
        
        /* Scroll indicators */
        .table-responsive::before,
        .table-responsive::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 30px;
            pointer-events: none;
            z-index: 10;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .table-responsive::before {
            left: 0;
            background: linear-gradient(to right, rgba(255,255,255,0.9), transparent);
        }
        
        .table-responsive::after {
            right: 0;
            background: linear-gradient(to left, rgba(255,255,255,0.9), transparent);
        }
        
        body.dark-mode .table-responsive::before {
            background: linear-gradient(to right, rgba(36,37,38,0.9), transparent);
        }
        
        body.dark-mode .table-responsive::after {
            background: linear-gradient(to left, rgba(36,37,38,0.9), transparent);
        }
        
        .table-responsive.can-scroll-left::before {
            opacity: 1;
        }
        
        .table-responsive.can-scroll-right::after {
            opacity: 1;
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
           ACTION BUTTONS
        ============================ */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .btn-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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

        /* ===========================
           DATATABLES CUSTOM STYLES
        ============================ */
        .dataTables_wrapper {
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dataTables_length {
            margin-bottom: 15px;
            color: #666;
        }

        .dataTables_length select {
            padding: 6px 12px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            background-color: white;
            color: #333;
            margin: 0 5px;
        }

        .dataTables_filter {
            margin-bottom: 15px;
            color: #666;
        }

        .dataTables_filter input {
            padding: 8px 12px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            margin-left: 8px;
            width: 200px;
        }

        .dataTables_filter input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .dataTables_info {
            color: #666;
            padding-top: 15px !important;
        }

        .dataTables_paginate {
            padding-top: 15px !important;
        }

        .dataTables_paginate .paginate_button {
            padding: 6px 12px !important;
            margin: 0 3px !important;
            border: 1px solid #e9ecef !important;
            border-radius: 6px !important;
            color: #333 !important;
            background: white !important;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dataTables_paginate .paginate_button:hover {
            background: #667eea !important;
            color: white !important;
            border-color: #667eea !important;
        }

        .dataTables_paginate .paginate_button.current {
            background: #667eea !important;
            color: white !important;
            border-color: #667eea !important;
        }

        .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .dataTables_paginate .paginate_button.disabled:hover {
            background: white !important;
            color: #333 !important;
            border-color: #e9ecef !important;
        }

        /* DataTables column visibility button */
        .dt-button {
            padding: 8px 16px !important;
            border: 1px solid #e9ecef !important;
            border-radius: 6px !important;
            background: white !important;
            color: #333 !important;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .dt-button:hover {
            background: #667eea !important;
            color: white !important;
            border-color: #667eea !important;
        }

        /* Dark mode support for DataTables */
        body.dark-mode .dataTables_length,
        body.dark-mode .dataTables_filter,
        body.dark-mode .dataTables_info {
            color: #b0b3b8;
        }

        body.dark-mode .dataTables_length select,
        body.dark-mode .dataTables_filter input {
            background-color: #3a3b3c;
            border-color: #4a4b4c;
            color: #e4e6eb;
        }

        body.dark-mode .dataTables_paginate .paginate_button {
            background: #3a3b3c !important;
            border-color: #4a4b4c !important;
            color: #e4e6eb !important;
        }

        body.dark-mode .dataTables_paginate .paginate_button:hover {
            background: #667eea !important;
            border-color: #667eea !important;
            color: white !important;
        }

        body.dark-mode .dt-button {
            background: #3a3b3c !important;
            border-color: #4a4b4c !important;
            color: #e4e6eb !important;
        }

        /* Custom styling for table to work with DataTables */
        #timesheetTable {
            width: 100% !important;
        }

        .table-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
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
            <li class="active"><a href="admin_view_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
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

    <div class="content">
        <main>
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Timesheets</h1>
                    <p>Review, approve, and manage submitted timesheets while tracking employee work hours and attendance.</p>
                </div>
            </div>

            <!-- Summary Dashboard -->
            <div class="summary-dashboard">
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class='bx bx-time-five'></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $pending_count; ?></h3>
                            <p>Pending Timesheets</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class='bx bx-check-circle'></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $approved_week_count; ?></h3>
                            <p>Approved This Week</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class='bx bx-x-circle'></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $rejected_count; ?></h3>
                            <p>Rejected</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class='bx bx-user-x'></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $missing_count; ?></h3>
                            <p>Missing Submissions</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class='bx bx-dollar-circle'></i>
                        </div>
                        <div class="card-content">
                            <h3><?php echo $billable_hours; ?></h3>
                            <p>Total Billable Hours</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="header">
                <div class="left">
                    <ul class="breadcrumb">
                        <li><a href="admin_dashboard.php">Dashboard</a></li>
                        <li><a href="#" class="active">Timesheets</a></li>
                    </ul>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar" style="background: var(--white); padding: 20px; border-radius: var(--border-radius); margin-bottom: 24px; box-shadow: var(--box-shadow); max-width: 1030px;">
                <form method="GET" action="" id="filterForm">
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                        <!-- Search by Consultant Name -->
                        <div style="flex: 1; min-width: 200px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--gray); font-size: 14px;">Search Consultant</label>
                            <div class="form-input" style="width: 100%;">
                                <input type="text" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($search_consultant); ?>">
                                <button type="submit" class="search-btn"><i class='bx bx-search'></i></button>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div style="min-width: 150px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--gray); font-size: 14px;">Status</label>
                            <select name="status" style="width: 100%; padding: 10px 12px; border: 1px solid var(--light-gray); border-radius: 8px; font-size: 14px; background: var(--white); color: #333;">
                                <option value="">All Status</option>
                                <option value="Pending" <?php echo ($filter_status === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo ($filter_status === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo ($filter_status === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>

                        <!-- Consultant Filter -->
                        <div style="min-width: 180px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--gray); font-size: 14px;">Consultant</label>
                            <select name="consultant" style="width: 100%; padding: 10px 12px; border: 1px solid var(--light-gray); border-radius: 8px; font-size: 14px; background: var(--white); color: #333;">
                                <option value="">All Consultants</option>
                                <?php if ($consultants_result && $consultants_result->num_rows > 0): ?>
                                    <?php while ($consultant = $consultants_result->fetch_assoc()): ?>
                                        <option value="<?php echo $consultant['user_id']; ?>" <?php echo ($filter_consultant == $consultant['user_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($consultant['fullname']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Project Filter -->
                        <div style="min-width: 180px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--gray); font-size: 14px;">Project</label>
                            <select name="project" style="width: 100%; padding: 10px 12px; border: 1px solid var(--light-gray); border-radius: 8px; font-size: 14px; background: var(--white); color: #333;">
                                <option value="">All Projects</option>
                                <?php if ($projects_result && $projects_result->num_rows > 0): ?>
                                    <?php while ($project = $projects_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($project['client_project']); ?>" <?php echo ($filter_project === $project['client_project']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['client_project']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div style="min-width: 140px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--gray); font-size: 14px;">From Date</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid var(--light-gray); border-radius: 8px; font-size: 14px;">
                        </div>

                        <!-- Date To -->
                        <div style="min-width: 140px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--gray); font-size: 14px;">To Date</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid var(--light-gray); border-radius: 8px; font-size: 14px;">
                        </div>

                        <!-- Filter Buttons -->
                        <div style="display: flex; gap: 8px; align-items: flex-end;">
                            <button type="submit" style="padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 5px;">
                                <i class='bx bx-filter'></i> Filter
                            </button>
                            <a href="admin_view_timesheets.php" style="padding: 10px 20px; background: var(--light-gray); color: var(--gray); border: none; border-radius: 8px; cursor: pointer; font-weight: 500; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                                <i class='bx bx-reset'></i> Reset
                            </a>
                        </div>
                    </div>

                    <!-- Quick Filters -->
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--light-gray);">
                        <span style="font-weight: 600; color: var(--gray); font-size: 14px; margin-right: 5px;">Quick Filters:</span>
                        <button type="button" onclick="applyQuickFilter('pending')" style="padding: 6px 14px; background: <?php echo ($quick_filter === 'pending') ? 'var(--warning)' : 'var(--light-gray)'; ?>; color: <?php echo ($quick_filter === 'pending') ? '#000' : 'var(--gray)'; ?>; border: none; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <i class='bx bx-time'></i> Pending Only
                        </button>
                        <button type="button" onclick="applyQuickFilter('this_week')" style="padding: 6px 14px; background: <?php echo ($quick_filter === 'this_week') ? 'var(--info)' : 'var(--light-gray)'; ?>; color: <?php echo ($quick_filter === 'this_week') ? '#fff' : 'var(--gray)'; ?>; border: none; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <i class='bx bx-calendar-week'></i> This Week
                        </button>
                        <button type="button" onclick="applyQuickFilter('this_month')" style="padding: 6px 14px; background: <?php echo ($quick_filter === 'this_month') ? 'var(--success)' : 'var(--light-gray)'; ?>; color: <?php echo ($quick_filter === 'this_month') ? '#fff' : 'var(--gray)'; ?>; border: none; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <i class='bx bx-calendar'></i> This Month
                        </button>
                    </div>
                </form>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="orders">
                <div class="header">
                    <i class='bx bx-time-five'></i>
                    <h3>All Timesheets</h3>
                </div>
                <div class="container">
                    <div class="table-container">
                    <button class="table-scroll-btn left" onclick="scrollTable('left')"><i class='bx bx-chevron-left'></i></button>
                    <button class="table-scroll-btn right" onclick="scrollTable('right')"><i class='bx bx-chevron-right'></i></button>
                    <div class="table-responsive" id="timesheetTable">
                    <?php if ($timesheets_result && $timesheets_result->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Work Date</th>
                                    <th>Client Project</th>
                                    <th>Hours Worked</th>
                                    <th>Billable</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Rejection Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $timesheets_result->fetch_assoc()): ?>
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
                                        <td><?php echo htmlspecialchars($row['hours_worked']); ?> hrs</td>
                                        <td><?php echo $row['billable'] ? 'Yes' : 'No'; ?></td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'Rejected' && !empty($row['rejection_reason'])): ?>
                                                <span style="color: #dc3545; font-size: 0.85rem;" title="<?php echo htmlspecialchars($row['rejection_reason']); ?>">
                                                    <i class='bx bx-info-circle'></i> <?php echo htmlspecialchars(substr($row['rejection_reason'], 0, 30)) . (strlen($row['rejection_reason']) > 30 ? '...' : ''); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 0.85rem;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'Pending'): ?>
                                                <div class="action-buttons" style="display: flex; gap: 8px;">
                                                    <button class="btn-action btn-approve" onclick="updateTimesheetStatus(<?php echo $row['consult_timesheet_id']; ?>, 'Approved')" style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                                                        <i class='bx bx-check'></i> Approve
                                                    </button>
                                                    <button class="btn-action btn-reject" onclick="openRejectModal(<?php echo $row['consult_timesheet_id']; ?>)" style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                                                        <i class='bx bx-x'></i> Reject
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 0.85rem;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class='bx bx-time-five' style="font-size: 48px; margin-bottom: 15px;"></i>
                            <p>No timesheets submitted yet.</p>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 25px; border-radius: 12px; max-width: 450px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <h3 style="margin-bottom: 15px; color: #dc3545;"><i class='bx bx-x-circle'></i> Reject Timesheet</h3>
            <p style="margin-bottom: 15px; color: #666;">Please provide a reason for rejecting this timesheet:</p>
            <textarea id="rejectionReason" rows="4" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; resize: vertical; margin-bottom: 20px;" placeholder="Enter reason for rejection..."></textarea>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeRejectModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 6px; cursor: pointer;">Cancel</button>
                <button onclick="submitRejection()" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer;">Reject</button>
            </div>
        </div>
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

        // Update timesheet status (Approve/Reject)
        function updateTimesheetStatus(timesheetId, status) {
            if (!confirm('Are you sure you want to ' + status.toLowerCase() + ' this timesheet?')) {
                return;
            }

            fetch('update_timesheet_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'timesheet_id=' + timesheetId + '&status=' + status
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the timesheet status.');
            });
        }

        // Variables for reject modal
        let currentTimesheetId = null;

        // Open reject modal
        function openRejectModal(timesheetId) {
            currentTimesheetId = timesheetId;
            document.getElementById('rejectionReason').value = '';
            document.getElementById('rejectModal').style.display = 'flex';
        }

        // Close reject modal
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            currentTimesheetId = null;
        }

        // Submit rejection
        function submitRejection() {
            const rejectionReason = document.getElementById('rejectionReason').value.trim();
            
            if (!rejectionReason) {
                alert('Please provide a reason for rejection.');
                return;
            }

            if (!confirm('Are you sure you want to reject this timesheet?')) {
                return;
            }

            fetch('update_timesheet_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'timesheet_id=' + currentTimesheetId + '&status=Rejected&rejection_reason=' + encodeURIComponent(rejectionReason)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeRejectModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while rejecting the timesheet.');
            });
        }

        // Close modal when clicking outside
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });

        // Table horizontal scroll functionality
        function scrollTable(direction) {
            const tableContainer = document.getElementById('timesheetTable');
            const scrollAmount = 300;
            
            if (direction === 'left') {
                tableContainer.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            } else if (direction === 'right') {
                tableContainer.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            }
        }

        // Quick filter function
        function applyQuickFilter(filterType) {
            const form = document.getElementById('filterForm');
            const url = new URL(window.location.href);

            // Check if this filter is already active
            if (url.searchParams.get('quick_filter') === filterType) {
                // If already active, remove it
                url.searchParams.delete('quick_filter');
            } else {
                // Set the new quick filter
                url.searchParams.set('quick_filter', filterType);
            }

            // Redirect to the new URL
            window.location.href = url.toString();
        }

        // Initialize DataTables
        $(document).ready(function() {
            $('#timesheetTable table').DataTable({
                "paging": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "lengthMenu": [10, 25, 50, 100],
                "pageLength": 25,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": [8] }, // Actions column not sortable
                    { "type": "date", "targets": [1] } // Work Date column for proper sorting
                ],
                "language": {
                    "search": "Search timesheets:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ timesheets",
                    "infoEmpty": "No timesheets available",
                    "infoFiltered": "(filtered from _MAX_ total timesheets)",
                    "zeroRecords": "No matching timesheets found"
                }
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
