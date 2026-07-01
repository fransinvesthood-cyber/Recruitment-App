<?php
// admin_chat.php
session_start();

// Check if user is logged in and is an Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

include('config.php');

$user_id = $_SESSION['user_id'];
$selected_consultant_id = $_GET['consultant_id'] ?? null;

// Get admin's full name
$stmt = $conn->prepare("SELECT fullname FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
$admin_name = $admin_data['fullname'];
$stmt->close();

// Get all consultants who have active conversations or all consultants
$consultants_sql = "
    SELECT DISTINCT u.user_id, u.fullname, u.email,
           cc.last_activity,
           (SELECT COUNT(*) FROM chat_messages cm
            WHERE cm.sender_id = u.user_id AND cm.receiver_id = ? AND cm.is_read = FALSE) as unread_count
    FROM users u
    LEFT JOIN chat_conversations cc ON (u.user_id = cc.consultant_id AND cc.admin_id = ?)
    WHERE u.role = 'Consultant'
    ORDER BY cc.last_activity DESC, u.fullname ASC
";
$stmt = $conn->prepare($consultants_sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$consultants_result = $stmt->get_result();
$consultants = $consultants_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get selected consultant info if specified
$selected_consultant = null;
if ($selected_consultant_id) {
    foreach ($consultants as $consultant) {
        if ($consultant['user_id'] == $selected_consultant_id) {
            $selected_consultant = $consultant;
            break;
        }
    }
}

// For single chat layout like consultant_chat.php, use first consultant if none selected
if (!$selected_consultant && !empty($consultants)) {
    $selected_consultant = $consultants[0];
    $selected_consultant_id = $selected_consultant['user_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css">

    <style>
        /* ===========================
           GLOBAL RESET & VARIABLES (from consultant_chat.php)
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
           SIDEBAR (copied from consultant_chat.php)
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
        .welcome-section h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .welcome-section p {
            opacity: 0.9;
            font-size: 18px;
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

        /* ===========================
           MAIN
        ============================ */
        main {
            padding: 24px;
        }

        /* ===========================
           CHAT LAYOUT (with consultants sidebar)
        ============================ */
        .chat-layout {
            display: flex;
            height: 70vh;
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }
        body.dark-mode .chat-layout {
            background: #242526;
        }

        .consultants-sidebar {
            width: 320px;
            background: #f8f9fa;
            border-right: 1px solid var(--light-gray);
            display: flex;
            flex-direction: column;
        }
        body.dark-mode .consultants-sidebar {
            background: #2d2e2f;
            border-right-color: #3a3b3c;
        }

        .sidebar-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            text-align: center;
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .search-box {
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
        }
        body.dark-mode .search-box {
            border-bottom-color: #3a3b3c;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 20px;
            font-size: 14px;
            outline: none;
            background: white;
        }
        body.dark-mode .search-input {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4b4d;
        }
        body.dark-mode .search-input::placeholder {
            color: #adb5bd;
        }

        .search-input:focus {
            border-color: var(--primary);
        }

        .consultants-list {
            flex: 1;
            overflow-y: auto;
        }

        .consultant-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        body.dark-mode .consultant-item {
            border-bottom-color: #3a3b3c;
        }

        .consultant-item:hover {
            background: var(--light-gray);
            text-decoration: none;
            color: inherit;
        }
        body.dark-mode .consultant-item:hover {
            background: #3a3b3c;
        }

        .consultant-item.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .consultant-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .consultant-item.active .consultant-avatar {
            background: rgba(255, 255, 255, 0.2);
        }

        .consultant-info {
            flex: 1;
            min-width: 0;
        }

        .consultant-name {
            font-weight: 600;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .consultant-status {
            font-size: 0.85rem;
            opacity: 0.7;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .unread-badge {
            background: var(--danger);
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
            animation: pulse 2s infinite;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-header h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .chat-header .status {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            margin-left: auto;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        body.dark-mode .chat-messages {
            background: #2d2e2f;
        }

        .message {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .message.own {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
        }

        .message-content {
            max-width: 70%;
            background: white;
            padding: 12px 16px;
            border-radius: 18px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            position: relative;
        }
        body.dark-mode .message-content {
            background: #3a3b3c;
            color: #e4e6eb;
        }

        .message.own .message-content {
            background: var(--primary);
            color: white;
        }

        .message-text {
            margin: 0;
            line-height: 1.5;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
            text-align: right;
        }

        /* Voice Note Styles */
        .voice-note-container {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 250px;
        }

        .voice-note-play-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.2);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .message.own .voice-note-play-btn {
            background: rgba(255, 255, 255, 0.3);
        }

        .voice-note-play-btn:hover {
            transform: scale(1.1);
        }

        .voice-note-play-btn i {
            font-size: 18px;
            color: var(--primary);
        }

        .message.own .voice-note-play-btn i {
            color: white;
        }

        .voice-note-waveform {
            flex: 1;
            height: 30px;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .voice-note-bar {
            width: 3px;
            background: rgba(102, 126, 234, 0.5);
            border-radius: 2px;
            transition: all 0.2s ease;
        }

        .message.own .voice-note-bar {
            background: rgba(255, 255, 255, 0.6);
        }

        .voice-note-duration {
            font-size: 0.8rem;
            opacity: 0.8;
            white-space: nowrap;
        }

        .chat-input-container {
            padding: 20px;
            background: var(--white);
            border-top: 1px solid var(--light-gray);
        }
        body.dark-mode .chat-input-container {
            background: #242526;
            border-top-color: #3a3b3c;
        }

        .chat-input-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 14px 16px;
            border: 1px solid var(--light-gray);
            border-radius: 25px;
            font-size: 15px;
            resize: none;
            outline: none;
            background: white;
            transition: var(--transition);
            min-height: 48px;
            max-height: 100px;
        }
        body.dark-mode .chat-input {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4b4d;
        }

        .chat-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .voice-record-btn, .send-button {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: none;
        }

        .voice-record-btn {
            background: #f8f9fa;
            color: var(--primary);
            border: 1px solid var(--light-gray);
        }
        body.dark-mode .voice-record-btn {
            background: #3a3b3c;
            border-color: #4a4b4d;
            color: var(--primary);
        }

        .voice-record-btn:hover {
            background: #e9ecef;
            transform: scale(1.05);
        }

        .voice-record-btn.recording {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
            animation: recordPulse 1.5s infinite;
        }

        .send-button {
            background: var(--primary);
            color: white;
        }

        .send-button:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .recording-indicator {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: #fef2f2;
            border: 1px solid var(--danger);
            border-radius: 25px;
            margin-bottom: 10px;
        }
        body.dark-mode .recording-indicator {
            background: #4b0a0a;
            border-color: #dc3545;
        }

        .recording-indicator.active {
            display: flex;
        }

        .recording-dot {
            width: 12px;
            height: 12px;
            background: var(--danger);
            border-radius: 50%;
            animation: recordPulse 1.5s infinite;
        }

        .recording-time {
            font-weight: 600;
            color: var(--danger);
            font-family: monospace;
        }

        .cancel-recording {
            margin-left: auto;
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 20px;
        }

        .no-chat-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100%;
            color: var(--gray);
            text-align: center;
        }
        body.dark-mode .no-chat-selected {
            color: #adb5bd;
        }

        .no-chat-selected i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        @keyframes recordPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
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
            .chat-layout { height: 65vh; }
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
            main {
                padding: 16px;
            }
            .chat-layout {
                flex-direction: column;
                height: 60vh;
                margin: 0;
                border-radius: 8px;
            }
            .consultants-sidebar {
                width: 100%;
                height: 200px;
            }
            .consultants-list {
                display: flex;
                overflow-x: auto;
                overflow-y: hidden;
            }
            .consultant-item {
                min-width: 250px;
            }
            .message-content { max-width: 85%; }
            .chat-header {
                flex-wrap: wrap;
                gap: 10px;
                padding: 16px;
            }
            .chat-header h2 { font-size: 1.2rem; }
            .chat-messages {
                padding: 16px;
            }
            .chat-input-container {
                padding: 16px;
            }
            .chat-input {
                font-size: 14px;
                padding: 12px 16px;
            }
            .voice-record-btn, .send-button {
                width: 40px;
                height: 40px;
            }
            .voice-record-btn i, .send-button i {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .chat-layout { height: 55vh; }
            .message-content { max-width: 90%; }
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
            <li><a href="admin_view_leaves.php"><i class='bx bx-calendar-minus'></i><span>Leaves</span></a></li>
            <li><a href="admin_invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li class="active"><a href="admin_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
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
            <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
            <a href="schedule_interview.php"><i class='bx bx-group'></i> Interviews</a>
            <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
            <a class="active" href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Chats</h1>
                <p>Manage and communicate with your consultants. Select a consultant from the sidebar to start chatting.</p>
            </div>

            <div class="chat-layout">
                <div class="consultants-sidebar">
                    <div class="sidebar-header">
                        <h3><i class='bx bx-chat'></i> Consultants</h3>
                    </div>
                    
                    <div class="search-box">
                        <input type="text" class="search-input" id="consultantSearch" placeholder="Search consultants...">
                    </div>

                    <div class="consultants-list" id="consultantsList">
                        <?php foreach ($consultants as $consultant): ?>
                            <a href="admin_chat.php?consultant_id=<?php echo $consultant['user_id']; ?>" 
                               class="consultant-item <?php echo ($selected_consultant_id == $consultant['user_id']) ? 'active' : ''; ?>"
                               data-name="<?php echo strtolower($consultant['fullname']); ?>">
                                <div class="consultant-avatar">
                                    <?php echo strtoupper(substr($consultant['fullname'], 0, 1)); ?>
                                </div>
                                <div class="consultant-info">
                                    <div class="consultant-name"><?php echo htmlspecialchars($consultant['fullname']); ?></div>
                                    <div class="consultant-status">
                                        <?php if ($consultant['last_activity']): ?>
                                            Last active: <?php echo date('M j, g:i A', strtotime($consultant['last_activity'])); ?>
                                        <?php else: ?>
                                            No conversation yet
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($consultant['unread_count'] > 0): ?>
                                    <div class="unread-badge"><?php echo $consultant['unread_count']; ?></div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="chat-area">
                    <?php if ($selected_consultant): ?>
                        <div class="chat-header">
                            <div class="consultant-avatar">
                                <?php echo strtoupper(substr($selected_consultant['fullname'], 0, 1)); ?>
                            </div>
                            <div>
                                <h2><?php echo htmlspecialchars($selected_consultant['fullname']); ?></h2>
                                <small style="opacity: 0.8;"><?php echo htmlspecialchars($selected_consultant['email']); ?></small>
                            </div>
                            <div class="status" id="connectionStatus">Connected</div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <!-- Messages will be loaded here -->
                        </div>

                        <div class="chat-input-container">
                            <div class="recording-indicator" id="recordingIndicator">
                                <div class="recording-dot"></div>
                                <span class="recording-time" id="recordingTime">0:00</span>
                                <span style="color: var(--gray);">Recording voice note...</span>
                                <button type="button" class="cancel-recording" id="cancelRecording">
                                    <i class='bx bx-x'></i>
                                </button>
                            </div>
                            
                            <form class="chat-input-form" id="chatForm">
                                <button type="button" class="voice-record-btn" id="voiceRecordBtn" title="Record voice note">
                                    <i class='bx bx-microphone' id="micIcon"></i>
                                </button>
                                <textarea 
                                    class="chat-input" 
                                    id="messageInput" 
                                    placeholder="Type your message to <?php echo htmlspecialchars($selected_consultant['fullname']); ?>..." 
                                    rows="1"
                                    maxlength="1000"
                                ></textarea>
                                <button type="submit" class="send-button" id="sendButton">
                                    <i class='bx bx-send'></i>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="no-chat-selected">
                            <i class='bx bx-chat'></i>
                            <h3>Select a Consultant to Start Chatting</h3>
                            <p>Choose a consultant from the sidebar to view and respond to their messages.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php if ($selected_consultant): ?>
    <script>
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const chatForm = document.getElementById('chatForm');
        const sendButton = document.getElementById('sendButton');
        const connectionStatus = document.getElementById('connectionStatus');
        const voiceRecordBtn = document.getElementById('voiceRecordBtn');
        const micIcon = document.getElementById('micIcon');
        const recordingIndicator = document.getElementById('recordingIndicator');
        const recordingTime = document.getElementById('recordingTime');
        const cancelRecording = document.getElementById('cancelRecording');
        
        const adminId = <?php echo $user_id; ?>;
        const consultantId = <?php echo $selected_consultant['user_id']; ?>;
        const adminName = '<?php echo htmlspecialchars($admin_name); ?>';

        let lastMessageId = 0;
        let mediaRecorder = null;
        let audioChunks = [];
        let recordingStartTime = null;
        let recordingInterval = null;
        let currentAudio = null;

        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // Handle form submission
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });

        // Handle Enter key
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Voice recording functionality
        voiceRecordBtn.addEventListener('click', function() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                stopRecording();
            } else {
                startRecording();
            }
        });

        cancelRecording.addEventListener('click', function() {
            cancelVoiceRecording();
        });

        async function startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = (event) => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    sendVoiceNote(audioBlob);
                    stream.getTracks().forEach(track => track.stop());
                };

                mediaRecorder.start();
                voiceRecordBtn.classList.add('recording');
                micIcon.className = 'bx bx-stop';
                recordingIndicator.classList.add('active');
                
                recordingStartTime = Date.now();
                recordingInterval = setInterval(updateRecordingTime, 100);

            } catch (error) {
                console.error('Error accessing microphone:', error);
                alert('Could not access microphone. Please check your permissions.');
            }
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                voiceRecordBtn.classList.remove('recording');
                micIcon.className = 'bx bx-microphone';
                recordingIndicator.classList.remove('active');
                clearInterval(recordingInterval);
            }
        }

        function cancelVoiceRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                mediaRecorder.stream.getTracks().forEach(track => track.stop());
                audioChunks = [];
                voiceRecordBtn.classList.remove('recording');
                micIcon.className = 'bx bx-microphone';
                recordingIndicator.classList.remove('active');
                clearInterval(recordingInterval);
            }
        }

        function updateRecordingTime() {
            const elapsed = Date.now() - recordingStartTime;
            const seconds = Math.floor(elapsed / 1000);
            const minutes = Math.floor(seconds / 60);
            const displaySeconds = seconds % 60;
            recordingTime.textContent = `${minutes}:${displaySeconds.toString().padStart(2, '0')}`;
            
            // Auto-stop after 2 minutes
            if (seconds >= 120) {
                stopRecording();
            }
        }

        function sendVoiceNote(audioBlob) {
            const formData = new FormData();
            formData.append('action', 'send_voice_note');
            formData.append('receiver_id', consultantId);
            formData.append('voice_note', audioBlob, 'voice_note.webm');

            sendButton.disabled = true;

            fetch('chat_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadMessages();
                } else {
                    alert('Failed to send voice note: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
            })
            .finally(() => {
                sendButton.disabled = false;
            });
        }

        // Send message function
        function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            sendButton.disabled = true;
            
            fetch('chat_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=send_message&receiver_id=${consultantId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    loadMessages();
                } else {
                    alert('Failed to send message: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
            })
            .finally(() => {
                sendButton.disabled = false;
                messageInput.focus();
            });
        }

        // Load messages function
        function loadMessages() {
            fetch(`chat_handler.php?action=get_messages&other_user_id=${consultantId}&last_message_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        addMessageToChat(message);
                        lastMessageId = Math.max(lastMessageId, parseInt(message.message_id));
                    });
                    scrollToBottom();
                }
                
                connectionStatus.textContent = 'Connected';
                connectionStatus.style.background = 'rgba(16, 185, 129, 0.8)';
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                connectionStatus.textContent = 'Connection Error';
                connectionStatus.style.background = 'rgba(239, 68, 68, 0.8)';
            });
        }

        // Add message to chat
        function addMessageToChat(message) {
            const isOwn = parseInt(message.sender_id) === adminId;
            const messageTime = new Date(message.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const senderName = isOwn ? adminName : message.fullname;
            
            const messageElement = document.createElement('div');
            messageElement.className = `message ${isOwn ? 'own' : ''}`;
            
            let contentHTML = '';
            
            if (message.message_type === 'voice') {
                const voiceNoteUrl = message.voice_note_path;
                const duration = message.voice_note_duration || '0:00';
                contentHTML = `
                    <div class="voice-note-container">
                        <button class="voice-note-play-btn" onclick="playVoiceNote(this, '${voiceNoteUrl}')">
                            <i class='bx bx-play'></i>
                        </button>
                        <div class="voice-note-waveform">
                            ${generateWaveform()}
                        </div>
                        <span class="voice-note-duration">${duration}</span>
                    </div>
                `;
            } else {
                contentHTML = `<p class="message-text">${escapeHtml(message.message)}</p>`;
            }
            
            messageElement.innerHTML = `
                <div class="message-avatar">
                    ${senderName.charAt(0).toUpperCase()}
                </div>
                <div class="message-content">
                    ${contentHTML}
                    <div class="message-time">${messageTime}</div>
                </div>
            `;
            
            chatMessages.appendChild(messageElement);
        }

        // Generate waveform bars
        function generateWaveform() {
            const bars = 40;
            let html = '';
            for (let i = 0; i < bars; i++) {
                const height = Math.random() * 20 + 10;
                html += `<div class="voice-note-bar" style="height: ${height}px;"></div>`;
            }
            return html;
        }

        // Play voice note
        window.playVoiceNote = function(button, url) {
            const icon = button.querySelector('i');
            
            // Stop current audio if playing
            if (currentAudio && !currentAudio.paused) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
                document.querySelectorAll('.voice-note-play-btn i').forEach(i => {
                    i.className = 'bx bx-play';
                });
            }
            
            // If clicking the same button, just stop
            if (currentAudio && currentAudio.src.endsWith(url) && currentAudio.paused) {
                currentAudio = new Audio(url);
            }
            
            currentAudio = new Audio(url);
            
            currentAudio.onplay = function() {
                icon.className = 'bx bx-pause';
            };
            
            currentAudio.onended = function() {
                icon.className = 'bx bx-play';
            };
            
            currentAudio.onerror = function() {
                alert('Error playing voice note');
                icon.className = 'bx bx-play';
            };
            
            if (icon.classList.contains('bx-play')) {
                currentAudio.play();
            } else {
                currentAudio.pause();
                icon.className = 'bx bx-play';
            }
        };

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Load initial messages
        loadMessages();

        // Poll for new messages every 2 seconds
        setInterval(loadMessages, 2000);

        // Mark messages as read when window gains focus
        window.addEventListener('focus', function() {
            fetch('chat_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&other_user_id=${consultantId}`
            });
        });
    </script>
    <?php endif; ?>

    <script>
        // Search functionality
        document.getElementById('consultantSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const consultantItems = document.querySelectorAll('.consultant-item');
            
            consultantItems.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }

        // === Theme & Mobile Menu (identical to consultant_chat.php) ===
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

        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mobileMenuOverlay').style.display = 
                document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none';
        });
        document.getElementById('mobileMenuOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            this.style.display = 'none';
        });

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
    </script>
</body>
</html>
