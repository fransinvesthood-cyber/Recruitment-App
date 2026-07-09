<?php
include('config.php');
session_start();

$message = '';
$messageClass = '';

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

// Count total number of jobs
$sql_job_count = "SELECT COUNT(*) AS total_jobs FROM job_postings";
$job_count_results = $conn->query($sql_job_count);
$total_jobs = ($job_count_results->num_rows > 0) ? $job_count_results->fetch_assoc()['total_jobs'] : 0;

//Show Job Titles
$sql_job_title = "SELECT position FROM job_postings";
$job_title_results = $conn->query($sql_job_title);

// Count total number of external jobs
$sql_external_job_count = "SELECT COUNT(*) AS total_external_jobs FROM external_jobs";
$external_job_count_results = $conn->query($sql_external_job_count);
$total_external_jobs = ($external_job_count_results->num_rows > 0) ? $external_job_count_results->fetch_assoc()['total_external_jobs'] : 0;

// Count total number of applications
$sql_apps_count = "SELECT COUNT(*) AS total_apps FROM job_applications";
$apps_count_results = $conn->query($sql_apps_count);
$total_apps = ($apps_count_results->num_rows > 0) ? $apps_count_results->fetch_assoc()['total_apps'] : 0;

// Get job listings with department and company info
$sql_jobs = "SELECT job_postings.position, companies.company_name, job_postings.job_status,
                    departments.department_name
             FROM job_postings
             INNER JOIN departments ON job_postings.department_id = departments.department_id
             INNER JOIN companies ON job_postings.company_id = companies.company_id
             ORDER BY job_postings.date_posted DESC";
$jobs_results = $conn->query($sql_jobs);

// Get candidates' applications with user details and profile picture (only submitted applications)
$sql_candidates = "SELECT 
                        ja.application_id, 
                        ja.submission_date, 
                        ja.position, 
                        ja.application_status, 
                        u.user_id,
                        u.fullname, 
                        u.email, 
                        ap.profile_picture
                  FROM job_applications ja
                  INNER JOIN users u ON ja.user_id = u.user_id
                  LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
                  WHERE ja.application_status = 'Submitted'";
$candidates_results = $conn->query($sql_candidates);

// Fetch counts for different application statuses
$result = $conn->query("SELECT
    SUM(CASE WHEN application_status = 'Shortlisted' THEN 1 ELSE 0 END) AS shortlisted_count,
    SUM(CASE WHEN application_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count,
    SUM(CASE WHEN application_status = 'Hired' THEN 1 ELSE 0 END) AS hired_count,
    SUM(CASE WHEN application_status = 'Submitted' THEN 1 ELSE 0 END) AS submitted_count
    FROM job_applications");
$row = $result->fetch_assoc();
$shortlisted_count = $row['shortlisted_count'];
$rejected_count = $row['rejected_count'];
$hired_count = $row['hired_count'];
$submitted_count = $row['submitted_count'];

// Prepare data for pie chart
$pie_labels = ['Shortlisted', 'Rejected', 'Hired', 'Submitted'];
$pie_data = [$shortlisted_count, $rejected_count, $hired_count, $submitted_count];

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

// Fetch notifications for the current user
$notifications_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param("i", $user_id);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();
$notifications = [];
$unread_count = 0;
while ($row = $notifications_result->fetch_assoc()) {
    $notifications[] = $row;
    if (!$row['is_read']) {
        $unread_count++;
    }
}
// Fetch application trends - counts by date for the last 30 days
$application_trends_sql = "SELECT 
    DATE(submission_date) as app_date, 
    COUNT(*) as count 
FROM job_applications 
WHERE submission_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(submission_date)
ORDER BY app_date ASC";
$application_trends_result = $conn->query($application_trends_sql);

$chart_labels = [];
$chart_data = [];
while ($row = $application_trends_result->fetch_assoc()) {
    $chart_labels[] = date('M d', strtotime($row['app_date']));
    $chart_data[] = $row['count'];
}

// If no data, provide empty arrays
if (empty($chart_labels)) {
    $chart_labels = ['No data'];
    $chart_data = [0];
}

// Fetch deadlines (job deadlines)
$sql_deadlines = "SELECT position, date_posted FROM job_postings WHERE date_posted >= CURDATE() ORDER BY date_posted ASC LIMIT 5";
$deadlines_result = $conn->query($sql_deadlines);

// Fetch interviews today
$sql_interviews_today = "SELECT u.fullname AS candidate_name, i.interview_date, i.interviewer FROM interviews i JOIN users u ON i.user_id = u.user_id WHERE DATE(i.interview_date) = CURDATE()";
$interviews_today_result = $conn->query($sql_interviews_today);

// Fetch leaves today
$sql_leaves_today = "SELECT u.fullname, cl.leave_type FROM consultant_leaves cl JOIN users u ON cl.user_id = u.user_id WHERE CURDATE() BETWEEN cl.start_date AND cl.end_date";
$leaves_today_result = $conn->query($sql_leaves_today);

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="search_functionality.js"></script>
    <title>Admin Dashboard</title>

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

        .nav-center {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
            justify-content: flex-end;
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
            margin-right: 8px;
            padding: 8px;
            border-radius: 12px;
            transition: var(--transition);
        }

        .notification-bell:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .notification-bell i {
            font-size: 24px;
            color: var(--gray);
            transition: var(--transition);
        }

        .notification-bell:hover i {
            color: var(--primary);
            transform: scale(1.1);
        }

        .notification-bell.has-notifications i {
            animation: bellBounce 2s infinite;
        }

        @keyframes bellBounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-5px);
            }
            60% {
                transform: translateY(-3px);
            }
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .notification-dropdown {
            position: absolute;
            top: 50px;
            right: 0;
            width: 450px;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            z-index: 1000;
            display: none;
            max-height: 600px;
            overflow-y: auto;
        }

        body.dark-mode .notification-dropdown {
            background: #242526;
        }

        .notification-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            font-weight: 600;
            color: var(--primary);
        }

        body.dark-mode .notification-header {
            border-color: #3a3b3c;
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .notification-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        body.dark-mode .notification-item {
            border-color: #3a3b3c;
        }

        .notification-item.unread {
            background: rgba(102, 126, 234, 0.03);
            border-left: 3px solid var(--primary);
        }

        .notification-message {
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 5px;
            padding-right: 30px;
        }

        body.dark-mode .notification-message {
            color: #e4e6eb;
        }

        .notification-time {
            font-size: 12px;
            color: var(--gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            display: inline-block;
            margin-left: 8px;
        }

        .mark-read-btn {
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            padding: 2px;
            border-radius: 3px;
            transition: var(--transition);
            font-size: 14px;
        }

        .mark-read-btn:hover {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
        }

        .notification-footer {
            padding: 10px 20px;
            border-top: 1px solid var(--light-gray);
            text-align: center;
        }

        body.dark-mode .notification-footer {
            border-color: #3a3b3c;
        }

        .notification-footer a {
            color: var(--primary);
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
        }

        .notification-footer a:hover {
            text-decoration: underline;
        }

        .notification-empty {
            padding: 40px 20px;
            text-align: center;
            color: var(--gray);
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

        .nav-icons-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            flex-shrink: 0;
            min-width: fit-content;
        }

        @media (max-width: 768px) {
            .nav-icons-group {
                gap: 4px;
            }

            .notification-bell,
            .calendar-bell {
                padding: 6px;
            }

            .theme-toggle {
                width: 50px;
                height: 28px;
            }

            .theme-toggle i {
                font-size: 14px;
            }

            #theme-toggle:checked + .theme-toggle .bx-sun {
                left: -25px;
            }

            #theme-toggle:checked + .theme-toggle .bx-moon {
                left: 3px;
            }
        }

        /* Mobile Menu Button - Ensure Visibility */
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
            padding: 8px 8px 8px 16px;
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
            padding: 10px 12px;
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
            padding: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            flex-shrink: 0;
        }

        .search-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .search-btn:active {
            transform: scale(0.95);
        }

        .search-btn i {
            font-size: 18px;
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

        /* Calendar Dropdown Styles */
        .calendar-bell {
            position: relative;
            cursor: pointer;
            margin-right: 8px;
            padding: 8px;
            border-radius: 12px;
            transition: var(--transition);
        }

        .calendar-bell:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .calendar-bell i {
            font-size: 24px;
            color: var(--gray);
            transition: var(--transition);
        }

        .calendar-bell:hover i {
            color: var(--primary);
            transform: scale(1.1);
        }

        .calendar-dropdown {
            position: absolute;
            top: 50px;
            right: 0;
            width: 450px;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            z-index: 1000;
            display: none;
            max-height: 550px;
            overflow-y: auto;
        }

        body.dark-mode .calendar-dropdown {
            background: #242526;
        }

        .calendar-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            font-weight: 600;
            color: var(--primary);
        }

        body.dark-mode .calendar-header {
            border-color: #3a3b3c;
        }

        .calendar-content {
            padding: 10px;
        }

        .calendar-section {
            margin-bottom: 15px;
        }

        .calendar-section h4 {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 8px;
            padding: 8px 10px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        body.dark-mode .calendar-section h4 {
            background: rgba(102, 126, 234, 0.2);
            color: #adb5bd;
        }

        .calendar-section h4 i {
            font-size: 16px;
            color: var(--primary);
        }

        .calendar-item {
            padding: 10px 12px;
            border-radius: 8px;
            transition: var(--transition);
            margin-bottom: 6px;
            background: rgba(102, 126, 234, 0.03);
            border-left: 3px solid var(--primary);
        }

        body.dark-mode .calendar-item {
            background: rgba(102, 126, 234, 0.1);
            border-left-color: #667eea;
        }

        .calendar-item:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .calendar-item-title {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 3px;
        }

        body.dark-mode .calendar-item-title {
            color: #e4e6eb;
        }

        .calendar-item-time {
            font-size: 12px;
            color: var(--gray);
        }

        .calendar-item-empty {
            padding: 15px;
            text-align: center;
            color: var(--gray);
            font-size: 13px;
            font-style: italic;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: 1px solid rgba(102, 126, 234, 0.3);
        }

        .welcome-content {
            flex: 1;
            min-width: 250px;
        }

        .welcome-content h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .welcome-content p {
            opacity: 0.9;
            font-size: 18px;
        }

        .welcome-quick-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .welcome-quick-actions .quick-action-btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 16px;
            font-size: 14px;
        }

        .welcome-quick-actions .quick-action-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .welcome-section {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .welcome-quick-actions {
                justify-content: flex-start;
                width: 100%;
            }
            
            .welcome-quick-actions .quick-action-btn {
                flex: 1;
                min-width: 120px;
                justify-content: center;
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
           QUICK ACTIONS
        ============================ */
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .quick-actions-mobile {
            display: none;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .quick-action-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            white-space: nowrap;
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        }

        .notification-badge {
            background-color: var(--danger);
            color: var(--white);
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75rem;
            animation: pulse 2s infinite;
            min-width: 20px;
            text-align: center;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        /* ===========================
           INSIGHTS CARDS
        ============================ */
        .insights {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .insights li {
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            gap: 16px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .insights li::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.95;
            z-index: 0;
        }

        .insights li > * {
            position: relative;
            z-index: 1;
        }

        /* Colorful card backgrounds */
        .insights li.card-internal-jobs {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .insights li.card-external-jobs {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .insights li.card-applications {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .insights li.card-shortlisted {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .insights li.card-rejected {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .insights li.card-leave-requests {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: brown;
        }

        body.dark-mode .insights li {
            background: #242526 !important;
        }

        body.dark-mode .insights li::before {
            display: none;
        }

        .insights li:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .insights li i {
            font-size: 28px;
            background: rgba(255, 255, 255, 0.25);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .insights li.card-leave-requests i {
            background: rgba(0, 0, 0, 0.1);
        }

        .info h3 {
            font-size: 24px;
            margin-bottom: 6px;
        }

        .info p {
            font-size: 16px;
            opacity: 0.9;
        }

        body.dark-mode .info p {
            color: #adb5bd;
        }

        /* ===========================
           ORDERS SECTION
        ============================ */
        .bottom-data {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .orders.full-width {
            grid-column: 1 / -1;
        }

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

        .orders .header a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .orders .header select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid var(--light-gray);
            background: var(--white);
            font-size: 14px;
        }

        body.dark-mode .orders .header select {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #3a3b3c;
        }

        /* ===========================
           TABLE STYLING
        ============================ */
        .table-responsive {
            overflow-x: auto;
            padding: 0 20px 20px;
            max-height: 350px;
            border-radius: var(--border-radius);
            background: var(--white);
            box-shadow: var(--box-shadow);
        }

        body.dark-mode .table-responsive {
            background: #242526;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode table {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        th, td {
            padding: 16px 18px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
            transition: all 0.2s ease;
        }

        body.dark-mode th,
        body.dark-mode td {
            border-color: #3a3b3c;
            color: #e4e6eb;
        }

        th {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(102, 126, 234, 0.05));
            font-weight: 700;
            color: var(--primary);
            position: sticky;
            top: 0;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--primary);
        }

        body.dark-mode th {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(102, 126, 234, 0.1));
            color: #a7b7ff;
            border-bottom-color: #a7b7ff;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:nth-child(even) {
            background: rgba(102, 126, 234, 0.02);
        }

        body.dark-mode tr:nth-child(even) {
            background: rgba(102, 126, 234, 0.05);
        }

        tr:hover {
            background: rgba(102, 126, 234, 0.06);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode tr:hover {
            background: rgba(102, 126, 234, 0.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        td:first-child {
            border-left: 4px solid transparent;
        }

        tr:hover td:first-child {
            border-left-color: var(--primary);
        }

        /* Profile picture in table */
        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-gray);
        }

        body.dark-mode .profile-pic {
            border-color: #3a3b3c;
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

        .status-shortlisted {
            background: #d1fae5;
            color: #065f46;
        }

        .status-hired {
            background: #28a745;
            color: #fff;
        }

        .status-submitted {
            background: #ffc107;
            color: #212529;
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

        body.dark-mode .status-shortlisted {
            background: #1b5f47;
            color: #d1fae5;
        }

        body.dark-mode .status-hired {
            background: #155724;
            color: #d4edda;
        }

        body.dark-mode .status-submitted {
            background: #856404;
            color: #fff3cd;
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
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
           RESPONSIVE: DESKTOP
        ============================ */
        @media (min-width: 769px) {
            .quick-actions {
                display: none;
            }
            .mobile-nav-links {
                display: none;
            }
            .mobile-logout-btn {
                display: none;
            }
        }

        /* ===============================
           RESPONSIVE DESIGN - TABLETS
        ================================ */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            
            .logo-name span {
                display: none;
            }
            
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
            
            .insights {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }

            .mobile-logout-btn {
                display: none;
            }
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

        /* ===============================
           RESPONSIVE DESIGN - MOBILE
        ================================ */
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
                font-size: 28px;
                padding: 12px;
            }

            .search-container {
                width: 100%;
                margin-top: 12px;
            }

            .form-input {
                width: 100%;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .quick-actions {
                display: none;
            }

            .quick-actions-mobile {
                display: flex;
                flex-direction: column;
                width: 100%;
            }

            .quick-action-btn {
                width: 100%;
                justify-content: center;
                padding: 14px;
                font-size: 1rem;
            }

            .insights {
                grid-template-columns: 1fr;
            }

            .bottom-data {
                grid-template-columns: 1fr;
            }

            .leave-stats {
                flex-direction: column;
                gap: 10px;
            }

            .mobile-nav-links {
                display: flex;
            }
        }

        @media (max-width: 480px) {
            .welcome-content h1 {
                font-size: 24px;
            }
            
            .welcome-content p {
                font-size: 16px;
            }
            
            .insights li {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .insights li i {
                align-self: flex-start;
            }
            
            .orders .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .orders .header > div,
            .orders .header > a {
                width: 100%;
            }
            
            .btn, .btn-sm {
                width: 100%;
                justify-content: center;
            }
            
            th, td {
                padding: 12px 8px;
                font-size: 14px;
            }
            
            .profile-pic {
                width: 36px;
                height: 36px;
            }
        }

        /* Search Results Dropdown */
        .search-results-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            z-index: 1000;
            display: none;
            max-height: 500px;
            overflow-y: auto;
            margin-top: 8px;
            border: 1px solid var(--light-gray);
        }

        body.dark-mode .search-results-dropdown {
            background: #242526;
            border-color: #3a3b3c;
        }

        .search-results-dropdown.active {
            display: block;
        }

        .search-loading {
            padding: 20px;
            text-align: center;
            color: var(--gray);
        }

        .search-loading i {
            font-size: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .search-result-section {
            border-bottom: 1px solid var(--light-gray);
        }

        body.dark-mode .search-result-section {
            border-color: #3a3b3c;
        }

        .search-result-section:last-child {
            border-bottom: none;
        }

        .search-result-header {
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(102, 126, 234, 0.05);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        body.dark-mode .search-result-header {
            background: rgba(102, 126, 234, 0.1);
            color: #adb5bd;
        }

        .search-result-header i {
            color: var(--primary);
        }

        .search-result-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--light-gray);
        }

        body.dark-mode .search-result-item {
            border-color: #3a3b3c;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background: rgba(102, 126, 234, 0.08);
        }

        body.dark-mode .search-result-item:hover {
            background: rgba(102, 126, 234, 0.15);
        }

        .search-result-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .search-result-icon.job {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .search-result-icon.candidate {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .search-result-icon.application {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .search-result-icon.user {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .search-result-icon.leave {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }

        .search-result-icon i {
            font-size: 18px;
        }

        .search-result-details {
            flex: 1;
            min-width: 0;
        }

        .search-result-title {
            font-weight: 500;
            color: var(--dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        body.dark-mode .search-result-title {
            color: #e4e6eb;
        }

        .search-result-meta {
            font-size: 12px;
            color: var(--gray);
            margin-top: 2px;
        }

        .search-no-results {
            padding: 30px 20px;
            text-align: center;
            color: var(--gray);
        }

        .search-no-results i {
            font-size: 36px;
            margin-bottom: 10px;
            display: block;
            opacity: 0.5;
        }

        /* Make search container relative for dropdown positioning */
        .search-container {
            position: relative;
        }

        .form-input {
            position: relative;
        }
   /* Modal Background */
.modal{
    display:none;
    position:fixed;
    z-index:9999;
    left:0;
    top:0;
    width:100%;
    height:100%;
    overflow:auto;
    background:rgba(0,0,0,.6);
}

/* Light Mode */
.modal-content{
    background:#ffffff;
    color:#222;
    width:80%;
    max-width:1100px;
    margin:40px auto;
    border-radius:12px;
    padding:20px;
    box-shadow:0 10px 35px rgba(0,0,0,.25);
    animation:fadeIn .3s;
}

.close{
    float:right;
    font-size:28px;
    cursor:pointer;
    color:#555;
    transition:.3s;
}

.close:hover{
    color:#ff4d4d;
}

/* Table */
.table-container{
    max-height:500px;
    overflow-y:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th{
    background:#1976d2;
    color:#fff;
    padding:12px;
    position:sticky;
    top:0;
}

table td{
    padding:12px;
    border-bottom:1px solid #ddd;
}

table tr:hover{
    background:#f5f5f5;
}

/* Status Badges */
.badge{
    padding:5px 12px;
    border-radius:20px;
    color:#fff;
    font-size:12px;
    font-weight:600;
}

.submitted{background:#0d6efd;}
.shortlisted{background:#198754;}
.rejected{background:#dc3545;}
.hired{background:#6f42c1;}
.pending{background:#ffc107;color:#000;}
.approved{background:#198754;}

/* ========================= */
/* DARK MODE */
/* ========================= */

body.dark-mode .modal{
    background:rgba(0,0,0,.8);
}

body.dark-mode .modal-content{
    background:#1b1f27;
    color:#f1f1f1;
    border:1px solid #333;
}

body.dark-mode h2{
    color:#fff;
}

body.dark-mode .close{
    color:#bbb;
}

body.dark-mode .close:hover{
    color:#fff;
}

body.dark-mode table{
    color:#fff;
}

body.dark-mode table th{
    background:#2d8cff;
}

body.dark-mode table td{
    border-bottom:1px solid #404040;
}

body.dark-mode table tr{
    background:#1b1f27;
}

body.dark-mode table tr:hover{
    background:#2a303c;
}

body.dark-mode input,
body.dark-mode select{
    background:#2a303c;
    color:#fff;
    border:1px solid #555;
}

body.dark-mode .table-container::-webkit-scrollbar{
    width:8px;
}

body.dark-mode .table-container::-webkit-scrollbar-track{
    background:#222;
}

body.dark-mode .table-container::-webkit-scrollbar-thumb{
    background:#666;
    border-radius:5px;
}

/* Animation */

@keyframes fadeIn{

    from{
        opacity:0;
        transform:translateY(-20px);
    }

    to{
        opacity:1;
        transform:translateY(0);
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
            <li class="active"><a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li class="section-header"><span>Candidates</span></li>
            <li><a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i><span>Jobs</span></a></li>
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

    <div class="mobile-menu-overlay" id="mobileMenuOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; display: none;"></div>
    

    <div class="content">
        <nav>
            <button class="mobile-menu-btn" id="mobileMenuBtn"><i class='bx bx-menu'></i></button>
            <div class="search-container">
                <div class="form-input" style="position: relative;">
                    <button class="search-btn" type="button" id="searchBtn"><i class='bx bx-search'></i></button>
                    <input type="search" id="globalSearchInput" placeholder="Search jobs, candidates, applications..." autocomplete="off">
                    <!-- Search Results Dropdown -->
                    <div id="searchResults" class="search-results-dropdown" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 9999;"></div>
                </div>
            </div>
            <div class="nav-icons-group">
<a class="notification-bell" href="admin_contact_messages.php" id="adminMessagesLink" style="text-decoration:none; color:inherit;" aria-label="Contact Messages">
                    <i class='bx bx-message-dots'></i>
                </a>

                <div class="notification-bell" id="notificationBell">
                    <i class='bx bx-bell'></i>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <i class='bx bx-bell'></i> Notifications
                            <a href="#" id="markAllReadBtn" style="float: right; font-size: 12px; color: #667eea; text-decoration: none;">Mark all read</a>
                        </div>
                        <div id="notificationList">
                            <?php if (empty($notifications)): ?>
                                <div class="notification-empty">
                                    <i class='bx bx-bell-off'></i>
                                    <p>No notifications yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>"
                                         data-id="<?php echo $notification['notification_id']; ?>"
                                         data-type="<?php echo htmlspecialchars($notification['type'] ?? 'general'); ?>"
                                         data-reference="<?php echo htmlspecialchars($notification['reference_id'] ?? ''); ?>">
                                        <div class="notification-message">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="unread-dot"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="notification-time">
                                            <?php echo date('M d, H:i', strtotime($notification['created_at'])); ?>
                                            <div style="display:flex; align-items:center; gap:8px; justify-content:flex-end;">
                                            <button class="delete-notification-btn" data-id="<?php echo $notification['notification_id']; ?>" title="Delete notification" aria-label="Delete notification">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                            <button class="mark-read-btn" data-id="<?php echo $notification['notification_id']; ?>" title="Mark as read">
                                                <i class='bx bx-check'></i>
                                            </button>
                                        </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                            <div class="notification-footer">
                            <a href="#" id="viewAllNotificationsBtn" style="display:inline-flex; gap:6px; align-items:center;">View all notifications</a>
                        </div>

                        <!-- Notifications modal (loaded on demand) -->
                        <div id="notificationsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:2000; align-items:center; justify-content:center;">
                            <div style="width:92vw; max-width:980px; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 18px 60px rgba(0,0,0,0.35); margin:auto;">

                                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 18px; background:rgba(102,126,234,0.08); border-bottom:1px solid #eef2f7;">
                                    <div style="font-weight:900; color:#667eea;">All Notifications</div>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <button type="button" id="closeNotificationsModalBtn" style="border:none; cursor:pointer; background:#e9ecef; color:#333; border-radius:10px; padding:8px 12px; font-weight:800;">Close</button>
                                    </div>
                                </div>
                                <div id="notificationsModalContent" style="min-height:200px;">
                                    <!-- ajax content -->
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
                <div class="calendar-bell" id="calendarBell">
                    <i class='bx bx-calendar'></i>
                    <div class="calendar-dropdown" id="calendarDropdown">
                        <div class="calendar-header">
                            <i class='bx bx-calendar'></i> Calendar Overview
                        </div>
                        <div class="calendar-content">
                            <div class="calendar-grid" id="calendarGrid"></div>
                            <div class="calendar-events">
                                <div class="calendar-section">
                                    <h4><i class='bx bx-time-five'></i> Today's Events</h4>
                                    <div id="todayEvents">
                                        <?php
                                        $hasEvents = false;
                                        if ($interviews_today_result->num_rows > 0) {
                                            $hasEvents = true;
                                            while ($row = $interviews_today_result->fetch_assoc()): ?>
                                                <div class="calendar-item event-interview">
                                                    <div class="calendar-item-title"><?php echo htmlspecialchars($row['candidate_name']); ?> Interview</div>
                                                    <div class="calendar-item-time"><?php echo date('H:i', strtotime($row['interview_date'])); ?> - <?php echo htmlspecialchars($row['interviewer']); ?></div>
                                                </div>
                                            <?php endwhile;
                                        }
                                        if ($leaves_today_result->num_rows > 0) {
                                            $hasEvents = true;
                                            while ($row = $leaves_today_result->fetch_assoc()): ?>
                                                <div class="calendar-item event-leave">
                                                    <div class="calendar-item-title"><?php echo htmlspecialchars($row['fullname']); ?> Leave</div>
                                                    <div class="calendar-item-time"><?php echo htmlspecialchars($row['leave_type']); ?> Leave</div>
                                                </div>
                                            <?php endwhile;
                                        }
                                        if (!$hasEvents): ?>
                                            <div class="calendar-item-empty">No events today</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="calendar-section">
                                    <h4><i class='bx bx-calendar-check'></i> Upcoming Deadlines</h4>
                                    <div id="upcomingDeadlines">
                                        <?php if ($deadlines_result->num_rows > 0): ?>
                                            <?php while ($row = $deadlines_result->fetch_assoc()): ?>
                                                <div class="calendar-item event-deadline">
                                                    <div class="calendar-item-title"><?php echo htmlspecialchars($row['position']); ?> Deadline</div>
                                                    <div class="calendar-item-time"><?php echo date('M d, Y', strtotime($row['date_posted'])); ?></div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="calendar-item-empty">No upcoming deadlines</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="checkbox" id="theme-toggle" hidden>
                <label for="theme-toggle" class="theme-toggle">
                    <i class='bx bx-sun'></i>
                    <i class='bx bx-moon'></i>
                </label>
            </div>
            <a href="logout.php" class="mobile-logout-btn" onclick="return confirmLogout();"><i class='bx bx-log-out-circle'></i></a>
        </nav>
        <main>
            <div class="mobile-nav-links">
                <a class="active" href="admin_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
                <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Manage Jobs</a>
                <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
                <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
                <a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a>
                <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
                <a href="admin_client_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>
                <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
                <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
            </div>

            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Welcome, <?php echo htmlspecialchars($fullname); ?>.</h1>
                    <p>Here's your dashboard overview</p>
                </div>
                <div class="welcome-quick-actions">
                    <a href="add_job.php" class="quick-action-btn">
                        <i class='bx bx-plus-circle'></i> Add Job
                    </a>
                    <a href="manage_applications.php" class="quick-action-btn">
                        <i class='bx bx-file'></i> Applications
                    </a>
                    <a href="schedule_interview.php" class="quick-action-btn">
                        <i class='bx bx-calendar-event'></i> Schedule Interview
                    </a>
                    <a href="admin_view_leaves.php" class="quick-action-btn">
                        <i class='bx bx-calendar-minus'></i> Leaves
                    </a>
                    <a href="admin_view_timesheets.php" class="quick-action-btn">
                        <i class='bx bx-time-five'></i> Timesheets
                    </a>
                </div>
            </div>

            <div class="header">
                <div class="left">
                    <ul class="breadcrumb">
                        <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
                    </ul>
                </div>
            </div>

            <div class="quick-actions">
                <a href="reports.php" class="quick-action-btn">
                    <i class='bx bx-file'></i> Reports
                </a>
                <a href="#" class="quick-action-btn" id="leaveRequestsBtn">
                    <i class='bx bx-calendar-minus'></i> Leave Requests
                    <?php if ($pending_leave_count > 0): ?>
                        <span class="notification-badge"><?php echo $pending_leave_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="add_job.php" class="quick-action-btn">
                    <i class='bx bx-plus'></i> Add Jobs
                </a>
                <a href="schedule_interview.php" class="quick-action-btn">
                    <i class='bx bx-user'></i> Interviews
                </a>
                <a href="admin_invoices.php" class="quick-action-btn">
                    <i class='bx bx-receipt'></i> Manage Invoices
                </a>
                <a href="admin_client_feedback.php" class="quick-action-btn">
                    <i class='bx bx-message-dots'></i> View Feedback
                </a>
            </div>

            <ul class="insights">

            <li class="card-internal-jobs"
                onclick="openDashboardModal('internal')">
                <i class='bx bx-calendar-check'></i>
                <span class="info">
                    <h3><?= $total_jobs ?></h3>
                    <p>Internal Jobs</p>
                </span>
            </li>

            <li class="card-external-jobs"
                onclick="openDashboardModal('external')">
                <i class='bx bx-cloud-download'></i>
                <span class="info">
                    <h3><?= $total_external_jobs ?></h3>
                    <p>External Jobs</p>
                </span>
            </li>

            <li class="card-applications"
                onclick="openDashboardModal('applications')">
                <i class='bx bx-bell'></i>
                <span class="info">
                    <h3><?= $total_apps ?></h3>
                    <p>Job Applications</p>
                </span>
            </li>

            <li class="card-shortlisted"
                onclick="openDashboardModal('shortlisted')">
                <i class='bx bx-list-check'></i>
                <span class="info">
                    <h3><?= $shortlisted_count ?></h3>
                    <p>Shortlisted</p>
                </span>
            </li>

            <li class="card-rejected"
                onclick="openDashboardModal('rejected')">
                <i class='bx bx-x-circle'></i>
                <span class="info">
                    <h3><?= $rejected_count ?></h3>
                    <p>Rejected</p>
                </span>
            </li>

            <li class="card-leave-requests"
                onclick="openDashboardModal('leave')">
                <i class='bx bx-calendar-minus'></i>
                <span class="info">
                    <h3><?= $total_leave_requests ?></h3>
                    <p>Leave Requests</p>
                </span>
            </li>

            </ul>
            
            <div id="dashboardModal" class="modal">

                <div class="modal-content">

                    <span class="close" onclick="closeDashboardModal()">&times;</span>

                    <h2 id="modalTitle"></h2>

                    <div id="modalBody">

                        Loading...

                    </div>

                </div>

            </div>

            <!-- Charts Row -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                <!-- Application Trends Chart -->
                <div class="orders">
                    <div class="header">
                        <i class='bx bx-line-chart'></i>
                        <h3>Application Trends (Last 30 Days)</h3>
                    </div>
                    <div style="padding: 20px; height: 400px;">
                        <canvas id="applicationTrendsChart"></canvas>
                    </div>
                </div>

                <!-- Application Status Distribution Pie Chart -->
                <div class="orders">
                    <div class="header">
                        <i class='bx bx-pie-chart-alt-2'></i>
                        <h3>Application Status Distribution</h3>
                    </div>
                    <div style="padding: 20px; height: 400px;">
                        <canvas id="applicationStatusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="bottom-data">
                <div class="orders" id="leaveRequestsSection" style="display: none;">
                    <div class="header">
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

                <div class="orders" id="applicationsSection">
                    <div class="header">
                        <i class='bx bx-receipt'></i>
                        <h3>Applications</h3>
                        <a href="manage_applications.php">view all ></a>
                    </div>
                    <div class="table-responsive">
                        <?php
                            if ($candidates_results->num_rows > 0) {
                                echo "<table>
                                        <thead>
                                            <tr>
                                                <th>Picture</th>
                                                <th>Full Name</th>
                                                <th>Position</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>";
                                
                                while ($row = $candidates_results->fetch_assoc()) {
                                    echo "<tr>
                                            <td>
                                                <img src='fetch_profile_pic.php?user_id=" . $row['user_id'] . "' 
                                                    alt='Profile Picture' class='profile-pic'
                                                    onerror=\"this.onerror=null; this.src='img/default_photo.jpg';\">
                                            </td>
                                            <td>" . htmlspecialchars($row["fullname"]) . "</td>
                                            <td>" . htmlspecialchars($row["position"]) . "</td>
                                            <td>
                                                <span class='status-badge status-" . strtolower($row["application_status"]) . "'>" . 
                                                htmlspecialchars($row["application_status"]) . "</span>
                                            </td>
                                            <td>
                                                <a href='manage_applications.php?application_id=" . $row["application_id"] . "' class='btn btn-sm'>
                                                    View
                                                </a>
                                            </td>
                                        </tr>";
                                }
                                echo "</tbody></table>";
                            } else {
                                echo "<div style='text-align: center; padding: 40px; color: #666;'>
                                        <i class='bx bx-file' style='font-size: 48px; margin-bottom: 15px;'></i>
                                        <p>No applications received</p>
                                      </div>";
                            }
                        ?>
                    </div>
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

        // Leave Requests functionality
        document.getElementById('leaveRequestsBtn').addEventListener('click', function(e) {
            e.preventDefault();
            var leaveSection = document.getElementById('leaveRequestsSection');
            var applicationsSection = document.getElementById('applicationsSection');
            var jobsSection = document.getElementById('jobsSection');
            var isVisible = leaveSection.style.display === 'block';
            leaveSection.style.display = isVisible ? 'none' : 'block';

            // Hide/show other sections
            if (!isVisible) {
                applicationsSection.style.display = 'none';
                jobsSection.style.display = 'none';
                leaveSection.scrollIntoView({ behavior: 'smooth' });
            } else {
                applicationsSection.style.display = 'block';
                jobsSection.style.display = 'block';
            }
        });

        // Hide leave requests section
        document.getElementById('hideLeaveRequests').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('leaveRequestsSection').style.display = 'none';
            // Refresh the page to reload applications and jobs tables
            location.reload();
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
                    // Update charts when theme changes
                    updateChartTheme();
                });
            }
        });

        // Function to update chart colors when theme changes
        function updateChartTheme() {
            const isDarkMode = document.body.classList.contains('dark-mode');
            const textColor = isDarkMode ? '#e4e6eb' : '#1a1a1a';
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.2)';
            const chartBgColor = isDarkMode ? 'rgba(36, 37, 38, 0.5)' : 'rgba(255, 255, 255, 0.9)';

            // Update line chart
            const lineChart = Chart.getChart('applicationTrendsChart');
            if (lineChart) {
                lineChart.options.scales.x.ticks.color = textColor;
                lineChart.options.scales.y.ticks.color = textColor;
                lineChart.options.scales.y.grid.color = gridColor;
                lineChart.options.plugins.legend.labels.color = textColor;
                lineChart.update();
            }

            // Update pie chart
            const pieChart = Chart.getChart('applicationStatusChart');
            if (pieChart) {
                pieChart.options.plugins.legend.labels.color = textColor;
                pieChart.update();
            }
        }

        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }

        // Application Trends Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('applicationTrendsChart').getContext('2d');

            // Get PHP data
            const chartLabels = <?php echo json_encode($chart_labels); ?>;
            const chartData = <?php echo json_encode($chart_data); ?>;

            // Determine if dark mode
            const isDarkMode = document.body.classList.contains('dark-mode');
            const textColor = isDarkMode ? '#e4e6eb' : '#1a1a1a';
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.2)';
            const chartBgColor = isDarkMode ? 'rgba(36, 37, 38, 0.5)' : 'rgba(255, 255, 255, 0.9)';

            // Define colorful palette
            const colors = {
                primary: '#667eea',
                secondary: '#f5576c',
                success: '#10b981',
                warning: '#f59e0b',
                info: '#06b6d4',
                purple: '#8b5cf6',
                pink: '#ec4899',
                danger: '#dc3545'
            };

            // Create vibrant gradient background
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(102, 126, 234, 0.4)');
            gradient.addColorStop(0.5, 'rgba(139, 92, 246, 0.2)');
            gradient.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

            // Create point colors array for gradient effect on points
            const pointColors = chartData.map((_, i) => {
                const colorKeys = Object.values(colors);
                return colorKeys[i % colorKeys.length];
            });

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Applications',
                        data: chartData,
                        borderColor: colors.primary,
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: pointColors,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointStyle: 'circle',
                        // Add shadow effect using segment
                        segment: {
                            borderColor: ctx => {
                                const chart = ctx.chart;
                                const {ctx: chartCtx, chartArea} = chart;
                                if (!chartArea) return colors.primary;

                                // Create gradient from primary to secondary
                                const gradient = chartCtx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                                gradient.addColorStop(0, colors.primary);
                                gradient.addColorStop(0.5, colors.purple);
                                gradient.addColorStop(1, colors.secondary);
                                return gradient;
                            }
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' application(s)';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: textColor,
                                maxRotation: 45,
                                minRotation: 45
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Application Status Distribution Pie Chart
            const pieCtx = document.getElementById('applicationStatusChart').getContext('2d');
            const pieLabels = <?php echo json_encode($pie_labels); ?>;
            const pieData = <?php echo json_encode($pie_data); ?>;

            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: [
                            colors.success,    // Shortlisted - green
                            colors.danger,     // Rejected - red
                            colors.primary,    // Hired - blue/purple
                            colors.info        // Submitted - cyan
                        ],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        });

        // Notification bell functionality
        document.addEventListener('DOMContentLoaded', function() {
            const notificationBell = document.getElementById('notificationBell');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationBadge = document.getElementById('notificationBadge');
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            const viewAllNotificationsBtn = document.getElementById('viewAllNotificationsBtn');

            // Show badge if there are unread notifications
            if (<?php echo $unread_count; ?> > 0) {
                notificationBadge.textContent = <?php echo $unread_count; ?>;
                notificationBadge.style.display = 'flex';
                notificationBell.classList.add('has-notifications');
            }

            // Toggle dropdown on bell click
            notificationBell.addEventListener('click', function(e) {
                e.stopPropagation();
                const isVisible = notificationDropdown.style.display === 'block';
                notificationDropdown.style.display = isVisible ? 'none' : 'block';
            });

            // Prevent dropdown from closing when clicking inside it
            notificationDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationBell.contains(e.target)) {
                    notificationDropdown.style.display = 'none';
                }
            });

            // Calendar dropdown toggle
            const calendarBell = document.getElementById('calendarBell');
            const calendarDropdown = document.getElementById('calendarDropdown');

            if (calendarBell && calendarDropdown) {
                calendarBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isVisible = calendarDropdown.style.display === 'block';
                    calendarDropdown.style.display = isVisible ? 'none' : 'block';
                });

                calendarDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });

                document.addEventListener('click', function(e) {
                    if (!calendarBell.contains(e.target)) {
                        calendarDropdown.style.display = 'none';
                    }
                    // Also close notification dropdown when clicking outside
                    if (!notificationBell.contains(e.target)) {
                        notificationDropdown.style.display = 'none';
                    }
                });

                // Generate mini calendar grid
                const calendarGrid = document.getElementById('calendarGrid');
                if (calendarGrid) {
                    const today = new Date();
                    const currentMonth = today.getMonth();
                    const currentYear = today.getFullYear();
                    
                    // Get event dates from PHP (interviews, leaves, deadlines)
                    const eventDates = {
                        interviews: [],
                        leaves: [],
                        deadlines: []
                    };
                    
                    // Get month name
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    
                    // Build calendar HTML
                    let calendarHTML = '<div style="text-align: center; padding: 10px; font-weight: 600; color: var(--primary);">' + monthNames[currentMonth] + ' ' + currentYear + '</div>';
                    calendarHTML += '<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; font-size: 11px; padding: 0 10px;">';
                    
                    // Day headers
                    const days = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
                    for (let d of days) {
                        calendarHTML += '<div style="font-weight: 600; color: var(--gray); padding: 4px 0;">' + d + '</div>';
                    }
                    
                    // Get first day of month and total days
                    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
                    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
                    
                    // Empty cells before first day
                    for (let i = 0; i < firstDay; i++) {
                        calendarHTML += '<div></div>';
                    }
                    
                    // Days of month
                    const todayDate = today.getDate();
                    const isToday = (day) => day === todayDate;
                    
                    for (let day = 1; day <= daysInMonth; day++) {
                        const isTodayClass = isToday(day) ? 'background: var(--primary); color: white; border-radius: 50%;' : 'border-radius: 4px;';
                        const hasEvent = (day === todayDate) ? 'background: rgba(102, 126, 234, 0.2); border: 1px solid var(--primary);' : '';
                        calendarHTML += '<div style="padding: 6px 2px; cursor: default; ' + isTodayClass + ' ' + hasEvent + '">' + day + '</div>';
                    }
                    
                    calendarHTML += '</div>';
                    
                    // Legend
                    calendarHTML += '<div style="display: flex; justify-content: center; gap: 15px; padding: 10px; font-size: 10px; flex-wrap: wrap;">';
                    calendarHTML += '<div style="display: flex; align-items: center; gap: 4px;"><div style="width: 8px; height: 8px; background: #3498db; border-radius: 2px;"></div>Interview</div>';
                    calendarHTML += '<div style="display: flex; align-items: center; gap: 4px;"><div style="width: 8px; height: 8px; background: #9b59b6; border-radius: 2px;"></div>Leave</div>';
                    calendarHTML += '<div style="display: flex; align-items: center; gap: 4px;"><div style="width: 8px; height: 8px; background: #e74c3c; border-radius: 2px;"></div>Deadline</div>';
                    calendarHTML += '</div>';
                    
                    calendarGrid.innerHTML = calendarHTML;
                }
            }

            // Mark all notifications as read
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                fetch('mark_notifications_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'user_id=<?php echo $user_id; ?>&action=mark_all_read'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        notificationBadge.style.display = 'none';
                        // Remove unread class and dots from all items
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                            const dot = item.querySelector('.unread-dot');
                            if (dot) dot.remove();
                        });
                        // Hide mark read buttons
                        document.querySelectorAll('.mark-read-btn').forEach(btn => {
                            btn.style.display = 'none';
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
            });

            // Handle delete notifications
            document.querySelectorAll('.delete-notification-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const notificationId = this.getAttribute('data-id');
                    const notificationItem = this.closest('.notification-item');

                    if (!confirm('Delete this notification?')) return;

                    fetch('delete_notifications.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'notification_id=' + encodeURIComponent(notificationId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.success) {
                            // If item was unread, decrement badge
                            if (notificationItem && notificationItem.classList.contains('unread')) {
                                notificationItem.classList.remove('unread');
                                const dot = notificationItem.querySelector('.unread-dot');
                                if (dot) dot.remove();

                                const currentCount = parseInt(notificationBadge.textContent) || 0;
                                if (currentCount > 1) {
                                    notificationBadge.textContent = currentCount - 1;
                                } else {
                                    notificationBadge.style.display = 'none';
                                }
                            }
                            if (notificationItem) notificationItem.remove();
                        } else {
                            alert((data && data.message) ? data.message : 'Failed to delete notification');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to delete notification');
                    });
                });
            });

            // Handle individual mark as read buttons
            document.querySelectorAll('.mark-read-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const notificationId = this.getAttribute('data-id');
                    const notificationItem = this.closest('.notification-item');

                    fetch('mark_notifications_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'user_id=<?php echo $user_id; ?>&notification_id=' + notificationId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            notificationItem.classList.remove('unread');
                            const dot = notificationItem.querySelector('.unread-dot');
                            if (dot) dot.remove();
                            this.style.display = 'none';

                            // Update badge count
                            const currentCount = parseInt(notificationBadge.textContent) || 0;
                            if (currentCount > 1) {
                                notificationBadge.textContent = currentCount - 1;
                            } else {
                                notificationBadge.style.display = 'none';
                            }
                        }
                    })
                    .catch(error => console.error('Error:', error));
                });
            });


            // Open all-notifications modal
            const viewAllNotificationsBtn2 = document.getElementById('viewAllNotificationsBtn');
            const notificationsModal = document.getElementById('notificationsModal');
            const notificationsModalContent = document.getElementById('notificationsModalContent');
            const closeNotificationsModalBtn = document.getElementById('closeNotificationsModalBtn');

            let currentModalPage = 1;

            function loadModalPage(pageNum) {
                currentModalPage = pageNum;
                if (!notificationsModalContent) return;
                notificationsModalContent.innerHTML = "<div style='padding:24px; color:#6c757d; text-align:center;'><i class='bx bx-loader bx-spin' style='font-size:28px;'></i> Loading...</div>";

                const url = 'notifications_modal.php?page=' + encodeURIComponent(pageNum) + '&per_page=20';
                fetch(url)
                    .then(r => r.text())
                    .then(html => {
                        notificationsModalContent.innerHTML = html;
                        // re-attach modal pager handler
                        // (modal content script handles buttons itself on insert)
                    })
                    .catch(() => {
                        notificationsModalContent.innerHTML = "<div style='padding:24px; color:#dc3545;'>Failed to load notifications.</div>";
                    });
            }

            if (viewAllNotificationsBtn2 && notificationsModal && notificationsModalContent) {
                viewAllNotificationsBtn2.addEventListener('click', function(e) {
                    e.preventDefault();
                    notificationsModal.style.display = 'flex';
                    loadModalPage(1);
                });
            }

            if (closeNotificationsModalBtn && notificationsModal) {
                closeNotificationsModalBtn.addEventListener('click', function() {
                    notificationsModal.style.display = 'none';
                });
            }

            if (notificationsModal) {
                notificationsModal.addEventListener('click', function(e) {
                    if (e.target === notificationsModal) {
                        notificationsModal.style.display = 'none';
                    }
                });
            }

            // Handle notification item clicks for navigation
            document.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', function() {

                    const notificationType = this.getAttribute('data-type');
                    const referenceId = this.getAttribute('data-reference');

                    // Mark as read if unread
                    if (this.classList.contains('unread')) {
                        const notificationId = this.getAttribute('data-id');
                        fetch('mark_notifications_read.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'user_id=<?php echo $user_id; ?>&notification_id=' + notificationId
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.classList.remove('unread');
                                const dot = this.querySelector('.unread-dot');
                                if (dot) dot.remove();
                                const markBtn = this.querySelector('.mark-read-btn');
                                if (markBtn) markBtn.style.display = 'none';

                                // Update badge count
                                const currentCount = parseInt(notificationBadge.textContent) || 0;
                                if (currentCount > 1) {
                                    notificationBadge.textContent = currentCount - 1;
                                } else {
                                    notificationBadge.style.display = 'none';
                                }
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }

                    // Navigate based on notification type
                    switch(notificationType) {
                        case 'application':
                            if (referenceId) {
                                window.location.href = 'manage_applications.php?application_id=' + referenceId;
                            } else {
                                window.location.href = 'manage_applications.php';
                            }
                            break;
                        case 'job':
                            if (referenceId) {
                                window.location.href = 'manage_jobs.php?job_id=' + referenceId;
                            } else {
                                window.location.href = 'manage_jobs.php';
                            }
                            break;
                        case 'interview':
                            if (referenceId) {
                                window.location.href = 'schedule_interview.php?interview_id=' + referenceId;
                            } else {
                                window.location.href = 'schedule_interview.php';
                            }
                            break;
                        case 'leave':
                            if (referenceId) {
                                window.location.href = 'view_leave_request.php?id=' + referenceId;
                            } else {
                                document.getElementById('leaveRequestsBtn').click();
                            }
                            break;
                        default:
                            // Stay on dashboard for general notifications
                            break;
                    }
                });
            });


        });

        //Open ashboard modal for clickable cards
        function openDashboardModal(type){

            const titles = {

                internal:'Internal Jobs',

                external:'External Jobs',

                applications:'Job Applications',

                shortlisted:'Shortlisted Applicants',

                rejected:'Rejected Applicants',

                leave:'Leave Requests'

            };

            document.getElementById("modalTitle").innerHTML=titles[type];

            document.getElementById("modalBody").innerHTML="Loading...";

            document.getElementById("dashboardModal").style.display="block";

            fetch("dashboard_details.php?type="+type)

            .then(res=>res.text())

            .then(data=>{

                document.getElementById("modalBody").innerHTML=data;

            });

        }

        function closeDashboardModal(){

            document.getElementById("dashboardModal").style.display="none";

        }
            </script>
</body>
</html>

