<?php
include('config.php');
session_start();
// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to view your profile.");
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

//Base query with JOIN to get department name and company name
$query = "SELECT job_postings.*, departments.department_name, companies.company_name 
          FROM job_postings
          INNER JOIN departments ON job_postings.department_id = departments.department_id
          INNER JOIN companies ON job_postings.company_id = companies.company_id
          WHERE 1";
//Apply search filters if any
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $query .= " AND (job_postings.position LIKE ? 
                OR job_postings.location LIKE ? 
                OR companies.company_name LIKE ? 
                OR departments.department_name LIKE ?)";
}
//Add ordering
$query .= " ORDER BY job_postings.date_posted DESC";
$stmt = $conn->prepare($query);
if (isset($_GET['search'])) {
    $searchParam = "%{$search}%";
    $stmt->bind_param("ssss", $searchParam, $searchParam, $searchParam, $searchParam);
}
$stmt->execute();
$result = $stmt->get_result();

// Query for external jobs
$externalQuery = "SELECT * FROM external_jobs WHERE 1";
// Apply search filters for external jobs
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $externalQuery .= " AND (title LIKE ? OR company LIKE ? OR location LIKE ? OR description LIKE ?)";
}
$externalQuery .= " ORDER BY date_fetched DESC";
$externalStmt = $conn->prepare($externalQuery);
if (isset($_GET['search'])) {
    $searchParam = "%{$search}%";
    $externalStmt->bind_param("ssss", $searchParam, $searchParam, $searchParam, $searchParam);
}
$externalStmt->execute();
$externalResult = $externalStmt->get_result();

// Count external jobs
$external_jobs_count = $externalResult->num_rows;

// Count total leave requests by status
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

// Fetch leave requests
$leave_sql = "SELECT cl.*, u.fullname, u.email
              FROM consultant_leaves cl
              JOIN users u ON cl.user_id = u.user_id
              ORDER BY
                CASE
                    WHEN cl.status = 'Pending' THEN 1
                    WHEN cl.status = 'Approved' THEN 2
                    WHEN cl.status = 'Rejected' THEN 3
                END,
                cl.start_date DESC";
$leave_result = $conn->query($leave_sql);

// Session message handling
$message = '';
$messageClass = '';
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
    <title>Manage Jobs - Admin Dashboard</title>
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
            background-color: #16181b;
            color: #e4e6eb;
        }
        body.dark-mode .content,
        body.dark-mode main {
            background-color: #16181b;
        }
     /* ===========================
           SIDEBAR STYLES
        ============================ */
        .sidebar{
            position:fixed;
            top:0;
            left:0;

            width:280px;
            height:100vh;

            display:flex;
            flex-direction:column;

            background:linear-gradient(180deg,var(--primary),var(--secondary));

            overflow:hidden;

            z-index:100;

            transition:.3s;
        }

        .bottom-menu{

            border-top:1px solid rgba(255,255,255,.15);

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

        .side-menu{
            list-style:none;
            margin:0;
            padding:0 15px;
        }

        /* Main menu fills remaining space */
        .main-menu{
            flex:1;
            overflow-y:auto;
            overflow-x:hidden;
            min-height:0;
        }

        /* Logout stays at bottom */
        .bottom-menu{
            margin-top:auto;
            flex:0;
            padding:15px;
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

        .logout{
            display:flex;
            align-items:center;
            gap:14px;
            padding:16px !important;
            background:rgba(0,0,0,.18);
            border-radius:10px;
            transition:.3s;
        }

        .logout:hover{
            background:#d32f2f !important;
            color:#fff;
        }
        .main-menu::-webkit-scrollbar{
            width:7px;
        }

        .main-menu::-webkit-scrollbar-thumb{
            background:rgba(255,255,255,.35);
            border-radius:20px;
        }

        .main-menu::-webkit-scrollbar-thumb:hover{
            background:rgba(255,255,255,.55);
        }

        .main-menu::-webkit-scrollbar-track{
            background:transparent;
        }
    
        body.dark-mode .bottom-menu{
            border-top:1px solid #3b3b3b;
        }

        body.dark-mode .logout{
            background:#2d2d2d;
        }

        body.dark-mode .logout:hover{
            background:#c62828 !important;
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
            justify-content: space-between; /* This handles the spacing */
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
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 28px;
            color: var(--gray);
            cursor: pointer;
        }
        
        /* Theme Toggle */
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

        /* ===========================
           MOBILE NAV LINKS BAR
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

        /* MOBILE MENU OVERLAY */
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
        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 10px 24px rgba(0,0,0,0.28);
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
           MANAGE JOBS SPECIFIC STYLES
        ============================ */
        .jobs-wrapper {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--box-shadow);
        }
        body.dark-mode .jobs-wrapper {
            background: #242526;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-title {
            font-size: 28px;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        body.dark-mode .page-title {
            color: #e4e6eb;
        }
        .search-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            color: white;
        }
        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: white;
            color: var(--dark);
        }
        body.dark-mode .search-input {
            background: #3a3b3c;
            color: #e4e6eb;
        }
        .btn, .add-job-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover, .add-job-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        .add-job-btn {
            background: #10b981;
            border-color: #10b981;
        }
        .add-job-btn:hover {
            background: #059669;
        }
        .job-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        .job-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--light-gray);
            transition: all 0.3s ease;
        }
        body.dark-mode .job-card {
            background: #3a3b3c;
            border-color: #4a4a4a;
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            border-color: var(--primary);
        }
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .job-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        body.dark-mode .job-title {
            color: #e4e6eb;
        }
        .job-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            background: #10b981;
            color: white;
        }
        .job-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 15px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-size: 14px;
        }
        body.dark-mode .meta-item {
            color: #adb5bd;
        }
        .meta-item i {
            color: var(--primary);
            font-size: 16px;
        }
        .job-description {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary);
        }
        body.dark-mode .job-description {
            background: #1e1f22;
        }
        .job-description h5 {
            color: #374151;
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        body.dark-mode .job-description h5 {
            color: #e4e6eb;
        }
        .job-description p {
            color: #6b7280;
            line-height: 1.5;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        body.dark-mode .job-description p {
            color: #adb5bd;
        }
        .job-actions {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--light-gray);
        }
        body.dark-mode .job-actions {
            border-top: 1px solid #4a4a4a;
        }
        .action-btn {
            padding: 6px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: none;
            cursor: pointer;
        }
        .btn-edit { background: #3b82f6; color: white; }
        .btn-edit:hover { background: #2563eb; }
        .btn-delete { background: #ef4444; color: white; }
        .btn-delete:hover { background: #dc2626; }
        .btn-share { background: #0077b5; color: white; }
        .btn-share:hover { background: #005885; }
        .btn-pnet { background: #ff6600; color: white; }
        .btn-pnet:hover { background: #e55a00; }
        .btn-indeed { background: #2557a7; color: white; }
        .btn-indeed:hover { background: #1a3f73; }
        .btn-twitter { background: #1da1f2; color: white; }
        .btn-twitter:hover { background: #0d8bd9; }
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        body.dark-mode .no-results {
            color: #adb5bd;
        }
        .no-results i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        .no-results h3 {
            color: #374151;
            margin-bottom: 10px;
        }
        body.dark-mode .no-results h3 {
            color: #e4e6eb;
        }
        .salary-tag {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        .alert {
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            animation: fadeSlideDown 0.5s ease-in-out;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        body.dark-mode .alert-success {
            background: #1e2923;
            color: #a7f3d0;
        }
        body.dark-mode .alert-danger {
            background: #291f1f;
            color: #fecaca;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        /* ===========================
           LEAVE REQUESTS SECTION
        ============================ */
        .leave-stats {
            display: flex;
            gap: 15px;
            margin: 15px 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            min-width: 100px;
            color: purple;
        }

        body.dark-mode .stat-item {
            color: #fff;
        }

        .stat-item strong {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .stat-item.pending {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .stat-item.approved {
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }

        .stat-item.rejected {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }

        body.dark-mode .stat-item.pending {
            background: #7c6130;
            color: #fff;
        }

        body.dark-mode .stat-item.approved {
            background: #1b5f47;
            color: #d1fae5;
        }

        body.dark-mode .stat-item.rejected {
            background: #7d2a35;
            color: #fee2e2;
        }

        /* Responsive table for leave requests */
        .responsive-table {
            width: 100%;
            table-layout: fixed;
        }

        .responsive-table th,
        .responsive-table td {
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 25%; } /* Employee */
        .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 15%; } /* Leave Type */
        .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 12%; } /* Start Date */
        .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 12%; } /* End Date */
        .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 10%; } /* Duration */
        .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 12%; } /* Status */
        .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 14%; } /* Actions */

        .responsive-table .btn {
            padding: 4px 8px;
            font-size: 0.8rem;
        }

        .responsive-table .btn i {
            margin-right: 2px;
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

        /* ===============================
           RESPONSIVE DESIGN
        ================================ */
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
            .job-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
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
            .mobile-menu-btn {
                display: block;
            }
            .mobile-nav-links {
                display: flex;
            }
            .form-input {
                width: 100%;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .search-form {
                flex-direction: column;
                width: 100%;
                align-items: stretch;
            }
            .search-input {
                width: 100%;
                min-width: unset;
            }
            .btn, .add-job-btn {
                width: 100%;
                justify-content: center;
            }
            .job-grid {
                grid-template-columns: 1fr;
            }
            .job-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (max-width: 480px) {
            .job-title {
                font-size: 18px;
            }
            .search-input {
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

    <div class="sidebar" id="sidebar">
        <a href="#" class="logo">
            <i class='bx bx-user-circle'></i>
            <div class="logo-name"><span>Admin</span></div>
        </a>
        <ul class="side-menu main-menu">
            <li><a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li class="section-header"><span>Candidates</span></li>
            <li class="active"><a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i><span>Jobs</span></a></li>
            <li><a href="manage_applications.php"><i class='bx bx-file'></i><span>Applications</span></a></li>
            <li><a href="admin_user_management.php"><i class='bx bx-user'></i><span>Users</span></a></li>
            <li><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a></li>
            <li><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
            <li class="section-header"><span>Consultants</span></li>
            <li><a href="admin_view_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="admin_view_tasklogs.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li><a href="admin_view_leaves.php"><i class='bx bx-calendar-minus'></i><span>Leaves</span></a></li>
            <li><a href="admin_invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="admin_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
            <li><a href="admin_settings.php"><i class='bx bx-cog'></i><span>Settings</span></a></li>
            <li><a href="admin_contact_messages.php"><i class='bx bx-message-dots'></i><span>Contact Messages</span></a></li>
        </ul>


        <ul class="side-menu bottom-menu">
            <li>
                <a href="logout.php" class="logout" onclick="return confirmLogout();">
                    <i class='bx bx-log-out-circle'></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>



    <div class="content">
        <nav>
            <button class="mobile-menu-btn" id="mobileMenuBtn"><i class='bx bx-menu'></i></button>
        </nav>

        <div class="mobile-nav-links">
                <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
                <a class="active" href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Manage Jobs</a>
                <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
                <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
                <a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a>
                <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
                <a href="admin_client_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>
                <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
                <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
            </div>

        <main>
            <div class="welcome-section">
                <h1> Manage Jobs</h1>
                <p>Control job postings, review performance metrics, and ensure all listings are accurate and up to date.</p>
            </div>

            <div class="jobs-wrapper">

                <?php if (!empty($message)): ?>
                    <div class="alert <?= htmlspecialchars($messageClass) ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-number"><?= $result->num_rows + $external_jobs_count ?></div>
                        <div class="stat-label">Total Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php
                            $active_jobs = $conn->query("SELECT COUNT(*) as count FROM job_postings WHERE job_status = 'Active'")->fetch_assoc()['count'];
                            echo $active_jobs;
                            ?>
                        </div>
                        <div class="stat-label">Internal Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $external_jobs_count ?></div>
                        <div class="stat-label">External Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php
                            $recent_jobs = $conn->query("SELECT COUNT(*) as count FROM job_postings WHERE date_posted >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
                            echo $recent_jobs;
                            ?>
                        </div>
                        <div class="stat-label">Posted This Week</div>
                    </div>
                </div>

                <div class="search-section">
                    <form method="GET" action="" class="search-form">
                        <input type="text"
                               class="search-input"
                               name="search"
                               placeholder="Search by job title, company, department, or location..."
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                        <button type="submit" class="btn">
                            <i class='bx bx-search'></i>
                            Search
                        </button>
                        <a href="add_job.php" class="add-job-btn">
                            <i class='bx bx-plus'></i>
                            Add New Job
                        </a>
                    </form>
                    <form method="GET" action="display_fetched_jobs.php" class="search-form" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2);">
                        <input type="text"
                               class="search-input"
                               name="query"
                               placeholder="Enter job title to fetch from API (e.g., Software Developer)"
                               value="<?= isset($_GET['query']) ? htmlspecialchars($_GET['query']) : '' ?>">
                        <button type="submit" class="add-job-btn" style="background-color: #ff6b35; border-color: #ff6b35;">
                            <i class='bx bx-cloud-download'></i>
                            Fetch Jobs from API
                        </button>
                    </form>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <div class="page-header">
                        <h1 class="page-title">
                            <i class='bx bx-briefcase'></i>
                            Internal Jobs
                        </h1>
                    </div>
                    <div class="job-grid">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php 
                            // (LinkedIn/Twitter/PNet logic remains unchanged – omitted for brevity but kept in final file)
                            $job_title = htmlspecialchars($row['position']);
                            $company_name = htmlspecialchars($row['company_name']);
                            $location = htmlspecialchars($row['location']);
                            $department = htmlspecialchars($row['department_name']);
                            $salary = 'R' . number_format($row['salary']);
                            $job_description = htmlspecialchars($row['job_description']);
                            $requirements = htmlspecialchars($row['requirements']);
                            $skills = htmlspecialchars($row['skills']);
                            $linkedin_text = "🚀 We're Hiring! 🚀\nPosition: {$job_title}\nCompany: {$company_name}\nDepartment: {$department}\nLocation: {$location}\nSalary: {$salary}\nJob Description:\n{$job_description}\nRequirements:\n{$requirements}\nSkills Required:\n{$skills}\n#Hiring #JobOpportunity #" . str_replace(' ', '', $department) . " #Career #Jobs";
                            $linkedin_url = "https://www.linkedin.com/feed/?shareActive=true&text=" . urlencode($linkedin_text);
                            $indeed_url = "https://www.indeed.com/hire";
                            $twitter_text = "🚀 We're Hiring: {$job_title} at {$company_name} in {$location}. Salary: {$salary}. #Hiring #JobAlert #" . str_replace(' ', '', $department);
                            $twitter_url = "https://twitter.com/intent/tweet?text=" . urlencode($twitter_text);
                            $indeed_content = "Job Title: {$job_title}\nCompany: {$company_name}\nDepartment: {$department}\nLocation: {$location}\nSalary: {$salary}\nJob Description:\n{$job_description}\nRequirements:\n{$requirements}\nSkills Required:\n{$skills}";
                            $pnet_content = "Position: {$job_title}\nCompany: {$company_name}\nDepartment: {$department}\nLocation: {$location}\nPackage: {$salary}\nJob Description:\n{$job_description}\nRequirements:\n{$requirements}\nSkills Required:\n{$skills}\nApply now to join our dynamic team!";
                            ?>
                            <div class="job-card">
                                <div class="job-header">
                                    <h3 class="job-title"><?= $job_title ?></h3>
                                    <span class="job-status"><?= htmlspecialchars($row['job_status']) ?></span>
                                </div>
                                <div class="job-meta">
                                    <div class="meta-item"><i class='bx bx-buildings'></i><?= $company_name ?></div>
                                    <div class="meta-item"><i class='bx bx-category'></i><?= $department ?></div>
                                    <div class="meta-item"><i class='bx bx-location-plus'></i><?= $location ?></div>
                                    <div class="meta-item"><i class='bx bx-calendar'></i><?= date('M d, Y', strtotime($row['date_posted'])) ?></div>
                                </div>
                                <div class="job-description">
                                    <h5>Job Description</h5>
                                    <p><?= $job_description ?></p>
                                </div>
                                <div class="job-description">
                                    <h5>Requirements</h5>
                                    <p><?= $requirements ?></p>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin: 15px 0;">
                                    <span class="salary-tag"><i class='bx bx-money'></i><?= $salary ?></span>
                                </div>
                                <div class="job-actions">
                                    <a href="<?= $linkedin_url ?>" class="action-btn btn-share" target="_blank"><i class='bx bxl-linkedin'></i> LinkedIn</a>
                                    <button class="action-btn btn-pnet" onclick="copyToPNet('pnet-content-<?= $row['job_id'] ?>')"><i class='bx bx-briefcase-alt-2'></i> Copy for PNet</button>
                                    <button class="action-btn btn-indeed" onclick="copyToIndeed('indeed-content-<?= $row['job_id'] ?>')"><i class='bx bx-briefcase'></i> Copy for Indeed</button>
                                    <a href="<?= $twitter_url ?>" class="action-btn btn-twitter" target="_blank"><i class='bx bxl-twitter'></i> Twitter</a>
                                    <a href="edit_job.php?job_id=<?= $row['job_id'] ?>" class="action-btn btn-edit"><i class='bx bx-edit'></i> Edit</a>
                                    <a href="delete_job.php?job_id=<?= $row['job_id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this job posting?')"><i class='bx bx-trash'></i> Delete</a>
                                    <textarea id="pnet-content-<?= $row['job_id'] ?>" style="position: absolute; left: -9999px;"><?= $pnet_content ?></textarea>
                                    <textarea id="indeed-content-<?= $row['job_id'] ?>" style="position: absolute; left: -9999px;"><?= $indeed_content ?></textarea>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class='bx bx-search-alt'></i>
                        <h3>No Job Listings Found</h3>
                        <p>Try adjusting your search criteria or add a new job posting.</p>
                        <a href="add_job.php" class="add-job-btn" style="margin-top: 20px;"><i class='bx bx-plus'></i> Add Your First Job</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($externalResult->num_rows > 0): ?>
                <div class="jobs-wrapper" style="margin-top: 40px;">
                    <div class="page-header">
                        <h1 class="page-title">
                            <i class='bx bx-cloud-download'></i>
                            External Jobs
                        </h1>
                    </div>
                    <div class="job-grid">
                        <?php while ($row = $externalResult->fetch_assoc()): ?>
                            <div class="job-card" style="border-left: 4px solid #ff6b35;">
                                <div class="job-header">
                                    <h3 class="job-title"><i class='bx bx-link-external' style="color: #ff6b35; margin-right: 8px;"></i><?= htmlspecialchars($row['title']) ?></h3>
                                    <span class="job-status" style="background-color: #ff6b35;">External</span>
                                </div>
                                <div class="job-meta">
                                    <div class="meta-item"><i class='bx bx-buildings'></i><?= htmlspecialchars($row['company']) ?></div>
                                    <div class="meta-item"><i class='bx bx-location-plus'></i><?= htmlspecialchars($row['location']) ?></div>
                                    <div class="meta-item"><i class='bx bx-calendar'></i><?= date('M d, Y', strtotime($row['date_fetched'])) ?></div>
                                </div>
                                <div class="job-description">
                                    <h5>Job Description</h5>
                                    <p><?= htmlspecialchars($row['description']) ?></p>
                                </div>
                                <?php if (!empty($row['salary'])): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin: 15px 0;">
                                        <span class="salary-tag"><i class='bx bx-money'></i><?= htmlspecialchars($row['salary']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="job-actions">
                                    <a href="<?= htmlspecialchars($row['url']) ?>" class="action-btn btn-share" target="_blank"><i class='bx bx-link-external'></i> View Job</a>
                                    <button class="action-btn btn-edit" onclick="copyJobLink('<?= htmlspecialchars($row['url']) ?>')"><i class='bx bx-copy'></i> Copy Link</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Leave Requests Management Section -->
            <div class="jobs-wrapper" id="leaveRequestsSection" style="margin-top: 40px; display: none;">
                <div class="page-header">
                    <i class='bx bx-calendar-minus'></i>
                    <h3>Leave Requests Management</h3>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <span class="status-filter">
                            <select id="statusFilter" class="btn btn-sm">
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </span>
                        <a href="#" id="hideLeaveRequests" class="btn btn-sm btn-danger">
                            <i class='bx bx-x'></i> Close
                        </a>
                    </div>
                </div>

                <div class="leave-stats">
                    <div class="stat-item" style="background: #fef3c7; border-left: 4px solid #f59e0b;">
                        <strong><?php echo $pending_leave_count; ?></strong> Pending
                    </div>
                    <div class="stat-item" style="background: #d1fae5; border-left: 4px solid #10b981;">
                        <strong><?php echo $leave_counts['approved_leave_count']; ?></strong> Approved
                    </div>
                    <div class="stat-item" style="background: #fee2e2; border-left: 4px solid #ef4444;">
                        <strong><?php echo $leave_counts['rejected_leave_count']; ?></strong> Rejected
                    </div>
                </div>

                <div class="table-responsive">
                    <?php
                    if ($leave_result && $leave_result->num_rows > 0) {
                        echo "<table id='leaveRequestsTable' class='responsive-table'>
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
                                <tbody>";
                        while ($row = $leave_result->fetch_assoc()) {
                            $start_date = new DateTime($row['start_date']);
                            $end_date = new DateTime($row['end_date']);
                            $duration = $start_date->diff($end_date)->days + 1;

                            echo "<tr data-status='" . strtolower($row['status']) . "'>
                                    <td>
                                        <div style='display: flex; align-items: center; gap: 8px;'>
                                            <i class='bx bx-user-circle' style='font-size: 24px; color: #667eea;'></i>
                                            <div>
                                                <strong>" . htmlspecialchars($row['fullname']) . "</strong><br>
                                                <small style='color: #666;'>" . htmlspecialchars($row['email']) . "</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>" . htmlspecialchars($row['leave_type']) . "</td>
                                    <td>" . $start_date->format('M d, Y') . "</td>
                                    <td>" . $end_date->format('M d, Y') . "</td>
                                    <td><strong>" . $duration . "</strong> day" . ($duration > 1 ? 's' : '') . "</td>
                                    <td>
                                        <span class='status-badge status-" . strtolower($row['status']) . "'>" . htmlspecialchars($row['status']) . "</span>
                                    </td>
                                    <td>
                                        <div style='display: flex; gap: 5px; flex-wrap: wrap;'>
                                            <a href='view_leave_request.php?id=" . $row['consult_leave_id'] . "' class='btn btn-sm'>
                                                <i class='bx bx-show'></i> View
                                            </a>";

                            if ($row['status'] == 'Pending') {
                                echo "      <a href='approve_leave.php?id=" . $row['consult_leave_id'] . "' class='btn btn-sm' style='background-color: #10b981;'>
                                                <i class='bx bx-check'></i> Approve
                                            </a>
                                            <a href='reject_leave.php?id=" . $row['consult_leave_id'] . "' class='btn btn-sm btn-danger'>
                                                <i class='bx bx-x'></i> Reject
                                            </a>";
                            }

                            echo "      </div>
                                    </td>
                                  </tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        echo "<div style='text-align: center; padding: 40px; color: #666;'>
                                <i class='bx bx-calendar-x' style='font-size: 48px; margin-bottom: 15px;'></i>
                                <p>No leave requests found.</p>
                              </div>";
                    }
                    ?>
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
            return confirm("Are you sure you want to log out?");
        }

        // Leave Requests functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle sidebar "Manage Leave" link click
            const manageLeaveLink = document.querySelector('a[href="manage_leave.php"]');
            if (manageLeaveLink) {
                manageLeaveLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    var leaveSection = document.getElementById('leaveRequestsSection');
                    var isVisible = leaveSection.style.display === 'block';
                    leaveSection.style.display = isVisible ? 'none' : 'block';

                    if (!isVisible) {
                        leaveSection.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            }

            // Hide leave requests section
            document.getElementById('hideLeaveRequests').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('leaveRequestsSection').style.display = 'none';
            });

            // Status filter functionality
            document.getElementById('statusFilter').addEventListener('change', function() {
                var selectedStatus = this.value;
                var table = document.getElementById('leaveRequestsTable');
                if (!table) return;

                var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i];
                    var rowStatus = row.getAttribute('data-status');

                    if (selectedStatus === 'all' || rowStatus === selectedStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });

        // === Keep your existing copy functions ===
        function copyToPNet(textareaId) {
            const textarea = document.getElementById(textareaId);
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            try {
                document.execCommand('copy');
                const button = document.querySelector(`button[onclick="copyToPNet('${textareaId}')"]`);
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="bx bx-check"></i> Copied!';
                button.style.backgroundColor = '#10b981';
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '#ff6600';
                }, 2000);
                alert('Job content copied for PNet! Now:\n1. Go to PNet.co.za\n2. Click "Post a Job" or "e-Recruiting"\n3. Sign in to your employer account\n4. Paste the content into the job form\n5. Complete and publish your job posting');
                window.open('https://www.pnet.co.za/e-recruiting/', '_blank');
            } catch (err) {
                alert('Unable to copy text. Please manually select and copy the job details.');
            }
        }

        function copyToIndeed(textareaId) {
            const textarea = document.getElementById(textareaId);
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            try {
                document.execCommand('copy');
                const button = document.querySelector(`button[onclick="copyToIndeed('${textareaId}')"]`);
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="bx bx-check"></i> Copied!';
                button.style.backgroundColor = '#10b981';
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '#2557a7';
                }, 2000);
                alert('Job content copied! Now:\n1. Go to Indeed.com/hire\n2. Click "Post a Job"\n3. Paste the content into the job form\n4. Complete and publish your job posting');
                window.open('https://www.indeed.com/hire', '_blank');
            } catch (err) {
                alert('Unable to copy text. Please manually select and copy the job details.');
            }
        }

        function copyJobLink(url) {
            navigator.clipboard.writeText(url).then(function() {
                alert('Job link copied to clipboard!');
            }).catch(function(err) {
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert('Job link copied to clipboard!');
                } catch (err) {
                    alert('Unable to copy link. Please manually copy: ' + url);
                }
                document.body.removeChild(textArea);
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>