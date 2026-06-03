<?php
// consultant_dashboard.php
session_start();

// Check if user is logged in and is a Consultant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Consultant') {
    header("Location: index.php");
    exit();
}

include('config.php');

$username = $_SESSION['username'];
$fullname = 'Guest';
$email = 'No email available';
$user_id = null;

// Fetch user data
$stmt = $conn->prepare("SELECT user_id, fullname, email FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($db_user_id, $db_fullname, $db_email);
if ($stmt->fetch()) {
    $fullname = $db_fullname;
    $email = $db_email;
    $user_id = $db_user_id;
}
$stmt->close();

// Total leave requests
$stmt = $conn->prepare("SELECT COUNT(*) AS total_leaves FROM consultant_leaves WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_leaves = $row['total_leaves'] ?? 0;
$stmt->close();

// Total timesheets
$stmt = $conn->prepare("SELECT COUNT(*) AS total_timesheets FROM consultant_timesheets WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_timesheets = $row['total_timesheets'] ?? 0;
$stmt->close();

// Hardcoded demo values (as in original)
$pending_invoices = 3;
$completed_training = 8;

// Unread admin messages
$stmt = $conn->prepare("
    SELECT COUNT(*) as unread_count 
    FROM chat_messages cm 
    JOIN users u ON cm.sender_id = u.user_id 
    WHERE cm.receiver_id = ? AND u.role = 'Admin' AND cm.is_read = FALSE
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_data = $result->fetch_assoc();
$unread_messages = $unread_data['unread_count'] ?? 0;
$stmt->close();

// Additional user data for settings
$phone = '';
$gender = '';
$address = '';
$dob = '';

$stmt = $conn->prepare("SELECT phone, gender, address, dob FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($db_phone, $db_gender, $db_address, $db_dob);
if ($stmt->fetch()) {
    $phone = $db_phone ?? '';
    $gender = $db_gender ?? '';
    $address = $db_address ?? '';
    $dob = $db_dob ?? '';
}
$stmt->close();

// Handle profile edit
$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    $new_fullname = trim($_POST['fullname']);
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']);
    $new_gender = trim($_POST['gender']);
    $new_address = trim($_POST['address']);
    $new_dob = trim($_POST['dob']);

    // Validate inputs
    if (empty($new_fullname) || empty($new_email)) {
        $message = "Full name and email are required.";
        $messageClass = "error";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageClass = "error";
    } elseif (!empty($new_phone) && !preg_match('/^[0-9+\-\s()]+$/', $new_phone)) {
        $message = "Please enter a valid phone number.";
        $messageClass = "error";
    } elseif (!empty($new_dob) && !strtotime($new_dob)) {
        $message = "Please enter a valid date of birth.";
        $messageClass = "error";
    } else {
        // Check if email is already taken by another user
        $check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $new_email, $user_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "Email address is already in use.";
            $messageClass = "error";
        } else {
            // Update profile
            $update_sql = "UPDATE users SET fullname = ?, email = ?, phone = ?, gender = ?, address = ?, dob = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssssi", $new_fullname, $new_email, $new_phone, $new_gender, $new_address, $new_dob, $user_id);

            if ($update_stmt->execute()) {
                $message = "Profile updated successfully!";
                $messageClass = "success";
                // Update session fullname if changed
                $_SESSION['fullname'] = $new_fullname;
                $fullname = $new_fullname;
                $email = $new_email;
                $phone = $new_phone;
                $gender = $new_gender;
                $address = $new_address;
                $dob = $new_dob;
            } else {
                $message = "Error updating profile: " . $conn->error;
                $messageClass = "error";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $messageClass = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
        $messageClass = "error";
    } elseif (strlen($new_password) < 8) {
        $message = "New password must be at least 8 characters long.";
        $messageClass = "error";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $message = "New password must contain at least one uppercase letter.";
        $messageClass = "error";
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $message = "New password must contain at least one lowercase letter.";
        $messageClass = "error";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $message = "New password must contain at least one number.";
        $messageClass = "error";
    } elseif (!preg_match('/[\W_]/', $new_password)) {
        $message = "New password must contain at least one special character.";
        $messageClass = "error";
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($current_password, $hashed_password)) {
            // Update password
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_hashed_password, $user_id);

            if ($update_stmt->execute()) {
                $message = "Password changed successfully!";
                $messageClass = "success";
            } else {
                $message = "Error updating password: " . $conn->error;
                $messageClass = "error";
            }
            $update_stmt->close();
        } else {
            $message = "Current password is incorrect.";
            $messageClass = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Consultant Dashboard</title>
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
        .form-input {
            display: flex;
            align-items: center;
            background: var(--light-gray);
            border-radius: 30px;
            padding: 8px 16px;
            width: 300px;
        }
        body.dark-mode .form-input {
            background: #3a3b3c;
        }
        .form-input input {
            background: transparent;
            border: none;
            outline: none;
            padding: 8px;
            width: 100%;
            font-size: 16px;
            color: inherit;
        }
        .search-btn {
            background: transparent;
            border: none;
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
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            gap: 16px;
            transition: var(--transition);
        }
        body.dark-mode .insights li {
            background: #242526;
        }
        .insights li:hover {
            transform: translateY(-5px);
        }
        .insights li i {
            font-size: 28px;
            color: var(--primary);
            background: rgba(102, 126, 234, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .info h3 {
            font-size: 24px;
            margin-bottom: 6px;
        }
        .info p {
            color: var(--gray);
            font-size: 16px;
        }
        body.dark-mode .info p {
            color: #adb5bd;
        }

        /* ===========================
           DASHBOARD GRID (CARDS)
        ============================ */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .dashboard-card {
            background: var(--white);
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-decoration: none;
            color: inherit;
            display: block;
            transition: var(--transition);
        }
        body.dark-mode .dashboard-card {
            background: #242526;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        .dashboard-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        body.dark-mode .dashboard-card h3 {
            color: #e4e6eb;
        }
        .dashboard-card p {
            color: var(--gray);
            font-size: 0.95rem;
        }
        body.dark-mode .dashboard-card p {
            color: #adb5bd;
        }

        /* ===========================
           RESPONSIVE: TABLET
        ============================ */
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
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        /* ===========================
           RESPONSIVE: MOBILE
        ============================ */
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
                padding: 16px;
            }
            .mobile-menu-btn {
                display: block;
            }
            .search-container {
                width: 100%;
                margin-top: 12px;
            }
            .form-input {
                width: 100%;
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .insights {
                grid-template-columns: 1fr;
            }
            .quick-actions {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .quick-action-btn {
                width: 100%;
                justify-content: center;
            }

            .mobile-logout-btn {
                display: block;
                font-size: 28px;
                padding: 12px;
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
            .dashboard-card {
                padding: 20px;
            }
            .mobile-nav-links {
                padding: 8px;
                gap: 4px;
            }
            .mobile-nav-links a {
                padding: 6px 8px;
                font-size: 12px;
            }
        }

        /* ===========================
           MOBILE NAV LINKS BAR (like dashboard)
        ============================ */
        .mobile-nav-links {
            display: flex;
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
           MOBILE MENU BUTTON & OVERLAY
        ============================ */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 28px;
            color: var(--gray);
            cursor: pointer;
        }
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
            <li class="active"><a href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li><a href="consultant_profile.php"><i class='bx bx-user'></i><span>Profile</span></a></li>
            <li><a href="manage_leave.php"><i class='bx bx-calendar-minus'></i><span>Leave</span></a></li>
            <li><a href="manage_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="manage_task_log.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li><a href="invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="training_management.php"><i class='bx bx-book-reader'></i><span>Training</span></a></li>
            <!--<li><a href="consultant_feedback.php"><i class='bx bx-message-dots'></i><span>Client Feedback</span></a></li>-->
            <li><a href="consultant_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
            <li><a href="consultant_settings.php" onclick="openSettingsCards(); return false;"><i class='bx bx-cog'></i><span>Settings</span></a></li>
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
        <!-- Navbar -->
        <nav>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class='bx bx-menu'></i>
            </button>
            <div class="search-container">
                <div class="form-input">
                    <input type="search" placeholder="Search...">
                    <button class="search-btn" type="submit"><i class='bx bx-search'></i></button>
                </div>
            </div>
            <input type="checkbox" id="theme-toggle" hidden>
            <label for="theme-toggle" class="theme-toggle"></label>
            <a href="logout.php" class="mobile-logout-btn" onclick="return confirmLogout();"><i class='bx bx-log-out-circle'></i></a>
        </nav>

        <!-- Mobile Nav Links -->
        <div class="mobile-nav-links">
            <a class="active" href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="consultant_profile.php"><i class='bx bx-user'></i> Profile</a>
            <a href="manage_timesheets.php"><i class='bx bx-time-five'></i> Timesheets</a>
            <a href="manage_task_log.php"><i class='bx bx-time-five'></i> Tasklogs</a>
            <a href="manage_leave.php"><i class='bx bx-calendar-minus'></i> Leave</a>
            <a href="invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="training_management.php"><i class='bx bx-book-reader'></i> Training</a>
            <!--<a href="consultant_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>-->
            <a href="consultant_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Welcome, <?= htmlspecialchars($fullname); ?>!</h1>
                    <p>Here's your dashboard overview</p>
                </div>
            </div>

            <!-- Insights -->
            <ul class="insights">
                <li>
                    <i class='bx bx-calendar-minus'></i>
                    <span class="info">
                        <h3><?= $total_leaves; ?></h3>
                        <p>Leave Requests</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-time-five'></i>
                    <span class="info">
                        <h3><?= $total_timesheets; ?></h3>
                        <p>Timesheets</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-receipt'></i>
                    <span class="info">
                        <h3><?= $pending_invoices; ?></h3>
                        <p>Pending Invoices</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-check-circle'></i>
                    <span class="info">
                        <h3><?= $completed_training; ?></h3>
                        <p>Completed Training</p>
                    </span>
                </li>
            </ul>

            <!-- Dashboard Cards Grid -->
            <div class="dashboard-grid">
                <a href="manage_leave.php" class="dashboard-card">
                    <h3>Leave Management</h3>
                    <p>Request and track leave.</p>
                </a>
                <a href="manage_timesheets.php" class="dashboard-card">
                    <h3>Timesheets</h3>
                    <p>Log your work hours.</p>
                </a>
                <a href="invoices.php" class="dashboard-card">
                    <h3>Invoices</h3>
                    <p>Create and track invoices.</p>
                </a>
                <a href="training_management.php" class="dashboard-card">
                    <h3>Training</h3>
                    <p>View training modules.</p>
                </a>
                <a href="task_log.php" class="dashboard-card">
                    <h3>Task Logs</h3>
                    <p>Upload task updates.</p>
                </a>
                <a href="consultant_profile.php" class="dashboard-card">
                    <h3>Profile</h3>
                    <p>Update your info.</p>
                </a>
                <a href="consultant_feedback.php" class="dashboard-card">
                    <h3>Feedback</h3>
                    <p>Provide feedback.</p>
                </a>
                <a href="consultant_chat.php" class="dashboard-card">
                    <h3>Chat</h3>
                    <p>Chat with Admin.</p>
                </a>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mobileMenuOverlay').style.display = 
                document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none';
        });
        document.getElementById('mobileMenuOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            this.style.display = 'none';
        });

        // Tablet sidebar collapse
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