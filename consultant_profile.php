<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Consultant') {
    header("Location: index.php");
    exit();
}

include('config.php');

$username = $_SESSION['username'];
$fullname = 'Guest';
$email = 'No email available';
$user_id = null;
$phone_number = null;
$address = null;

$stmt = $conn->prepare("SELECT user_id, fullname, email, phone, address FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($db_user_id, $db_fullname, $db_email, $db_phone_number, $db_address);
if ($stmt->fetch()) {
    $fullname = $db_fullname;
    $email = $db_email;
    $user_id = $db_user_id;
    $phone_number = $db_phone_number;
    $address = $db_address;
}
$stmt->close();

$consultant_data = [
    'employee_id' => '3',
    'department' => 'IT Consulting',
    'hire_date' => '2023-01-15',
    'contract_start' => '2023-01-15',
    'contract_end' => '2025-01-14',
    'profile_image' => 'img/deco.jpg'
];

$project_assignments = [
    [
        'project_name' => 'ERP Implementation - ABC Corp',
        'client' => 'ABC Corporation',
        'role' => 'Senior Consultant',
        'start_date' => '2024-03-01',
        'end_date' => '2024-12-31',
        'status' => 'active'
    ],
    [
        'project_name' => 'Digital Transformation - XYZ Ltd',
        'client' => 'XYZ Limited',
        'role' => 'Technical Lead',
        'start_date' => '2024-01-15',
        'end_date' => '2024-06-30',
        'status' => 'completed'
    ]
];

$uploaded_documents = [];
$stmt = $conn->prepare("SELECT document_id, document_type, file_name, upload_date, status FROM consultant_documents WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $uploaded_documents[] = [
        'name' => $row['document_type'],
        'filename' => $row['file_name'],
        'uploaded' => $row['upload_date'],
        'status' => strtolower($row['status'])
    ];
}
$stmt->close();

$contract_end = new DateTime($consultant_data['contract_end']);
$today = new DateTime();
$days_remaining = $today->diff($contract_end)->days;
$contract_alert = $days_remaining <= 60;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Management - Consultant Dashboard</title>
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
            background: var(--white);
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
            white-space: nowrap;
        }
        body.dark-mode .mobile-nav-links {
            background: #242526;
        }
        .mobile-nav-links a {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 4px;
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
           PROFILE HEADER
        ============================ */
        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 25px;
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .profile-details h2 {
            margin: 0 0 10px 0;
            font-size: 2rem;
            font-weight: 700;
        }
        .profile-details p {
            margin: 5px 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        /* ===========================
           CARD & GRID
        ============================ */
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        @media (max-width: 768px) {
            .profile-grid { grid-template-columns: 1fr; }
        }

        .card {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
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

        /* ===========================
           CONTRACT ALERT
        ============================ */
        .contract-alert {
            background: linear-gradient(135deg, var(--danger), #ffa500);
            color: white;
            padding: 16px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        /* ===========================
           FORM & INPUTS
        ============================ */
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 15px;
            color: var(--dark);
        }
        body.dark-mode label {
            color: #e4e6eb;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background: white;
            transition: var(--transition);
        }
        body.dark-mode input,
        body.dark-mode textarea {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4b4d;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
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
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #c82333; }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        @media (max-width: 480px) {
            .btn-group { flex-direction: column; }
        }

        /* ===========================
           UPLOAD & DOCUMENTS
        ============================ */
        .upload-area {
            border: 2px dashed var(--light-gray);
            border-radius: var(--border-radius);
            padding: 40px;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        body.dark-mode .upload-area {
            border-color: #4a4b4d;
            background: #2d2e2f;
        }
        .upload-area:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }
        body.dark-mode .upload-area:hover {
            background: #2d2e2f;
        }
        .upload-area i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .document-list {
            list-style: none;
            padding: 0;
        }
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            margin-bottom: 12px;
            transition: background 0.3s ease;
        }
        body.dark-mode .document-item {
            background: #2d2e2f;
            border-color: #3a3b3c;
        }
        .document-item:hover {
            background: #f8f9fa;
        }
        body.dark-mode .document-item:hover {
            background: #333435;
        }

        .document-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .document-icon {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        body.dark-mode .status-approved { background-color: #065f46; color: #d1fae5; }
        body.dark-mode .status-pending { background-color: #92400e; color: #fef3c7; }

        /* ===========================
           TABLE & MODAL
        ============================ */
        .project-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
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

        .status-active { background-color: #dbeafe; color: #1e40af; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        body.dark-mode .status-active { background-color: #1e40af; color: #dbeafe; }
        body.dark-mode .status-completed { background-color: #065f46; color: #d1fae5; }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background: var(--white);
            margin: 5% auto;
            padding: 30px;
            border-radius: var(--border-radius);
            width: 80%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        body.dark-mode .modal-content {
            background: #242526;
        }
        .close {
            color: var(--gray);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }
        .close:hover { color: var(--dark); }

        .error-message {
            color: var(--danger);
            font-size: 13px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
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
            .profile-info {
                flex-direction: column;
                text-align: center;
            }
            .profile-grid {
                grid-template-columns: 1fr;
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
            <div class="logo-name"><span>Consultant</span></div>
        </a>
        <ul class="side-menu">
            <li><a href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li class="active"><a href="consultant_profile.php"><i class='bx bx-user'></i><span>Profile</span></a></li>
            <li><a href="manage_leave.php"><i class='bx bx-calendar-minus'></i><span>Leave</span></a></li>
            <li><a href="manage_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="manage_task_log.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li><a href="invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="training_management.php"><i class='bx bx-book-reader'></i><span>Training</span></a></li>
            <!--<li><a href="consultant_feedback.php"><i class='bx bx-message-dots'></i><span>Client Feedback</span></a></li>-->
            <li><a href="consultant_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
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
            <a href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a class="active" href="consultant_profile.php" class="active"><i class='bx bx-user'></i> Profile</a>
            <a href="manage_timesheets.php"><i class='bx bx-time-five'></i> Timesheets</a>
            <a href="manage_task_log.php"><i class='bx bx-time-five'></i> Tasklogs</a>
            <a href="manage_leave.php"><i class='bx bx-calendar-minus'></i> Leave</a>
            <a href="invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="training_management.php"><i class='bx bx-book-reader'></i> Training</a>
            <a href="consultant_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>
            <a href="consultant_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Profile</h1>
                <p>Manage your profile, documents, and employment details</p>
            </div>

            <!-- Contract Alert -->
            <?php if ($contract_alert): ?>
            <div class="contract-alert">
                <i class='bx bx-error-circle'></i>
                <strong>Contract Expiry Alert:</strong> Your contract expires in <strong><?= $days_remaining ?></strong> days on <?= date('F j, Y', strtotime($consultant_data['contract_end'])); ?>. Please contact HR for renewal.
            </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-info">
                    <img src="<?= $consultant_data['profile_image'] ?>" alt="Profile" class="profile-avatar">
                    <div class="profile-details">
                        <h2><?= htmlspecialchars($fullname) ?></h2>
                        <p><i class='bx bx-envelope'></i> <?= htmlspecialchars($email) ?></p>
                        <p><i class='bx bx-phone'></i> <?= htmlspecialchars($phone_number) ?></p>
                        <p><i class='bx bx-id-card'></i> Employee ID: <?= htmlspecialchars($user_id) ?></p>
                    </div>
                </div>
            </div>

            <!-- Profile Grid -->
            <div class="profile-grid">
                <!-- Personal Information -->
                <div class="card">
                    <h3><i class='bx bx-user'></i> Personal Information</h3>
                    <form id="personalInfoForm">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($fullname) ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone_number) ?>">
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"><?= htmlspecialchars($address) ?></textarea>
                        </div>
                        <div class="btn-group">
                            <button type="submit" class="btn">
                                <i class='bx bx-save'></i> Update Information
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetForm('personalInfoForm')">
                                <i class='bx bx-rotate-left'></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Employment Details -->
                <div class="card">
                    <h3><i class='bx bx-briefcase'></i> Employment Details</h3>
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" value="<?= $consultant_data['employee_id'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" value="<?= $consultant_data['department'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Hire Date</label>
                        <input type="date" value="<?= $consultant_data['hire_date'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Contract Period</label>
                        <input type="text" value="<?= date('M d, Y', strtotime($consultant_data['contract_start'])) ?> - <?= date('M d, Y', strtotime($consultant_data['contract_end'])) ?>" readonly>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn" onclick="showProjectModal()">
                            <i class='bx bx-briefcase'></i> View Projects
                        </button>
                    </div>
                </div>

                <!-- Document Management -->
                <div class="card">
                    <h3><i class='bx bx-file'></i> Document Management</h3>
                    <p>Upload Documents: ID, Contract, Bank Details, Certificates</p>
                    <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                        <i class='bx bx-cloud-upload'></i>
                        <h4>Upload Documents</h4>
                        <p>Click here or drag and drop files to upload</p>
                        <form id="documentUploadForm" action="upload_consult_documents.php" method="post" enctype="multipart/form-data">
                            <input type="file" name="documents[]" id="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display: none;" onchange="this.form.submit()">
                        </form>
                    </div>
                    <ul class="document-list">
                        <?php foreach ($uploaded_documents as $doc): ?>
                        <li class="document-item">
                            <div class="document-info">
                                <i class='bx bx-file-blank document-icon'></i>
                                <div>
                                    <strong><?= htmlspecialchars($doc['name']) ?></strong>
                                    <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">
                                        Uploaded: <?= date('M d, Y', strtotime($doc['uploaded'])) ?>
                                    </p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="status-badge status-<?= $doc['status'] ?>">
                                    <?= htmlspecialchars($doc['status']) ?>
                                </span>
                                <button class="btn btn-secondary" style="padding:6px 12px; font-size:13px;" onclick="downloadDocument('<?= $doc['filename'] ?>')">
                                    <i class='bx bx-download'></i>
                                </button>
                                <button class="btn btn-danger" style="padding:6px 12px; font-size:13px;" onclick="deleteDocument('<?= $doc['filename'] ?>')">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <!-- Project Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeProjectModal()">&times;</span>
            <h2><i class='bx bx-briefcase'></i> Project Assignments & Client Details</h2>
            <table class="project-table">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Client</th>
                        <th>Role</th>
                        <th>Period</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($project_assignments as $project): ?>
                    <tr>
                        <td><?= htmlspecialchars($project['project_name']) ?></td>
                        <td><?= htmlspecialchars($project['client']) ?></td>
                        <td><?= htmlspecialchars($project['role']) ?></td>
                        <td><?= date('M d, Y', strtotime($project['start_date'])) ?> – <?= date('M d, Y', strtotime($project['end_date'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $project['status'] ?>">
                                <?= htmlspecialchars($project['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // ✅ YOUR EXISTING JS — untouched
        document.querySelectorAll('#personalInfoForm input, #personalInfoForm textarea').forEach(input => {
            input.addEventListener('blur', function () { validateField(this); });
        });

        document.getElementById('personalInfoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let isValid = true;
            this.querySelectorAll('input, textarea').forEach(field => {
                if (!validateField(field)) isValid = false;
            });
            if (isValid) {
                const btn = this.querySelector('button[type="submit"]');
                const text = btn.innerHTML;
                btn.innerHTML = '<i class="bx bx-loader bx-spin"></i> Updating...';
                btn.disabled = true;
                setTimeout(() => {
                    Swal.fire('Success', 'Profile updated successfully!', 'success');
                    btn.innerHTML = text;
                    btn.disabled = false;
                }, 1200);
            }
        });

        function validateField(field) {
            const v = field.value.trim(), n = field.name || field.id;
            let err = '', valid = true;
            const e = field.parentNode.querySelector('.error-message');
            if (e) e.remove();

            switch (n) {
                case 'fullname': if (v.length < 2) { err = 'Full name must be at least 2 characters.'; valid = false; } break;
                case 'email': if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { err = 'Please enter a valid email address.'; valid = false; } break;
                case 'phone': if (!/^[\+]?[0-9\s\-\(\)]{10,}$/.test(v)) { err = 'Please enter a valid phone number.'; valid = false; } break;
                case 'address': if (v.length < 5) { err = 'Address must be at least 5 characters.'; valid = false; } break;
            }

            if (!valid) {
                const div = document.createElement('div');
                div.className = 'error-message';
                div.innerHTML = '<i class="bx bx-error-circle"></i> ' + err;
                field.parentNode.appendChild(div);
                field.style.borderColor = '#dc3545';
            } else {
                field.style.borderColor = '';
            }
            return valid;
        }

        function resetForm(id) {
            document.getElementById(id).reset();
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            document.querySelectorAll('input, textarea').forEach(i => i.style.borderColor = '');
        }

        function downloadDocument(filename) {
            Swal.fire('Info', `Downloading: ${filename}\n(Demo only — real implementation would download.)`, 'info');
        }

        function deleteDocument(filename) {
            Swal.fire({
                title: 'Delete Document',
                text: `Are you sure you want to delete ${filename}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Deleted!', `Document ${filename} deleted.`, 'success');
                }
            });
        }

        function showProjectModal() {
            document.getElementById('projectModal').style.display = 'block';
        }

        function closeProjectModal() {
            document.getElementById('projectModal').style.display = 'none';
        }

        window.onclick = function(e) {
            const m = document.getElementById('projectModal');
            if (e.target === m) m.style.display = 'none';
        };

        const ua = document.querySelector('.upload-area');
        ua.addEventListener('dragover', e => { e.preventDefault(); ua.style.borderColor = '#667eea'; });
        ua.addEventListener('dragleave', e => { e.preventDefault(); ua.style.borderColor = ''; });
        ua.addEventListener('drop', e => {
            e.preventDefault(); ua.style.borderColor = '';
            const files = e.dataTransfer.files;
            if (files.length) document.getElementById('fileInput').files = files;
        });

        // === Sidebar & Theme (dashboard parity) ===
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            const t = localStorage.getItem('theme');
            if (t) themeToggle.checked = (t === 'dark');
            themeToggle.addEventListener('change', () => {
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
            const s = document.getElementById('sidebar');
            if (window.innerWidth <= 992 && window.innerWidth > 768) {
                s.classList.add('collapsed');
            } else {
                s.classList.remove('collapsed');
            }
        }
        window.addEventListener('resize', handleTabletView);
        handleTabletView();

        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }
    </script>
</body>
</html>