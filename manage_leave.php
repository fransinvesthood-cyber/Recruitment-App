<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login_signup.php');
    exit();
}

$user_id  = $_SESSION['user_id'];
$fullname = 'Guest';
$message  = "";
$success  = false;

// Get user's fullname
$stmt = $conn->prepare("SELECT fullname FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($db_fullname);
if ($stmt->fetch()) {
    $fullname = $db_fullname;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = $_POST['leave_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date   = $_POST['end_date'] ?? '';
    $reason     = $_POST['reason'] ?? '';

    $proofData = null;
    $proofFilename = null;
    $proofMime = null;

    if (isset($_FILES['proof']) && $_FILES['proof']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['proof'];
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $maxFileSize = 5 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = "Error uploading file.";
        } elseif ($file['size'] > $maxFileSize) {
            $message = "File size exceeds 5MB limit.";
        } else {
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExt, $allowedExtensions)) {
                $message = "Invalid file type. Allowed: " . implode(', ', $allowedExtensions);
            } else {
                $proofFilename = $file['name'];
                $proofMime = $file['type'];
                $proofData = file_get_contents($file['tmp_name']);
            }
        }
    }

    if (empty($message)) {
        if ($proofData !== null) {
            $stmt = $conn->prepare(
                "INSERT INTO consultant_leaves
                (user_id, leave_type, start_date, end_date, reason, proof, proof_filename, proof_mimetype, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
            );
            $null = NULL;
            $stmt->bind_param('issssbss', $user_id, $leave_type, $start_date, $end_date, $reason, $null, $proofFilename, $proofMime);
            $stmt->send_long_data(5, $proofData);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO consultant_leaves
                (user_id, leave_type, start_date, end_date, reason, status)
                VALUES (?, ?, ?, ?, ?, 'Pending')"
            );
            $stmt->bind_param('issss', $user_id, $leave_type, $start_date, $end_date, $reason);
        }

        if ($stmt->execute()) {
            $success = true;
            $message = 'Leave request submitted successfully.';
        } else {
            $message = 'Error submitting leave request.';
        }
    }

    if (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    }
}

$result = $conn->query(
    "SELECT * FROM consultant_leaves
     WHERE user_id = $user_id
     ORDER BY start_date DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave - Consultant Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css"> 

    <style>
        /* ===========================
           GLOBAL RESET & VARIABLES (identical to dashboard)
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
           MAIN CONTENT
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
           NAVBAR
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
           MAIN CONTENT AREA
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
           PROFILE CARD (renamed to match dashboard aesthetic)
        ============================ */
        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
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
        input[type="date"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background: white;
            transition: var(--transition);
        }
        body.dark-mode input,
        body.dark-mode textarea,
        body.dark-mode select,
        body.dark-mode input[type="file"] {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4b4d;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .error-message {
            color: var(--danger);
            font-size: 13px;
            margin-top: 4px;
            display: none;
        }
        .form-note {
            font-size: 13px;
            color: var(--gray);
            margin-top: 4px;
        }
        body.dark-mode .form-note {
            color: #adb5bd;
        }

        .btn {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
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
            box-shadow: none;
        }

        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

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

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-rejected { background-color: #fee2e2; color: #be123c; }

        body.dark-mode .status-approved { background-color: #065f46; color: #d1fae5; }
        body.dark-mode .status-pending { background-color: #92400e; color: #fef3c7; }
        body.dark-mode .status-rejected { background-color: #be123c; color: #fee2e2; }

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
            main {
                padding: 16px;
            }
            .card {
                padding: 16px;
            }
            .header h1 {
                font-size: 22px;
            }
            .btn-group {
                flex-direction: column;
                gap: 8px;
            }
            .table-wrapper {
                margin-top: 16px;
            }
            th, td {
                padding: 12px 8px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .btn {
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
            <div class="logo-name"><span>Consultant</span></div>
        </a>
        <ul class="side-menu">
            <li><a href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li><a href="consultant_profile.php"><i class='bx bx-user'></i><span>Profile</span></a></li>
            <li class="active"><a href="manage_leave.php"><i class='bx bx-calendar-minus'></i><span>Leave</span></a></li>
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
            <a href="consultant_profile.php"><i class='bx bx-user'></i> Profile</a>
            <a href="manage_timesheets.php"><i class='bx bx-time-five'></i> Timesheets</a>
            <a href="manage_task_log.php"><i class='bx bx-time-five'></i> Tasklogs</a>
            <a class="active" href="manage_leave.php"><i class='bx bx-calendar-minus'></i> Leave</a>
            <a href="invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="training_management.php"><i class='bx bx-book-reader'></i> Training</a>
            <!--<a href="consultant_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>-->
            <a href="consultant_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Leave Requests</h1>
                <p>Request time off, monitor approvals, and manage your leave balance in one convenient place.</p>
            </div>

            <!-- Leave Form -->
            <div class="card">
                <h3><i class='bx bx-calendar-minus'></i> Submit Leave Request</h3>
                <form method="POST" id="leaveForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="leaveType">Leave Type:</label>
                        <select name="leave_type" id="leaveType" required>
                            <option value="">-- Select Leave Type --</option>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Casual Leave">Casual Leave</option>
                            <option value="Study Leave">Study Leave</option>
                            <option value="Annual Leave">Annual Leave</option>
                            <option value="Maternity Leave">Maternity Leave</option>
                            <option value="Paternity Leave">Paternity Leave</option>
                            <option value="Others">Others</option>
                        </select>
                        <div class="error-message" id="typeError"></div>
                    </div>
                    <div class="form-group">
                        <label for="startDate">Start Date:</label>
                        <input type="date" name="start_date" id="startDate" required>
                        <div class="error-message" id="startDateError"></div>
                    </div>
                    <div class="form-group">
                        <label for="endDate">End Date:</label>
                        <input type="date" name="end_date" id="endDate" required>
                        <div class="error-message" id="endDateError"></div>
                    </div>
                    <div class="form-group">
                        <label for="reason">Reason:</label>
                        <textarea name="reason" id="reason" rows="4" placeholder="Please provide a detailed reason..."></textarea>
                        <div class="error-message" id="reasonError"></div>
                    </div>
                    <div class="form-group">
                        <label for="proof">Upload Proof (Sick Notes, Timetables, etc...):</label>
                        <input type="file" name="proof" id="proof" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="form-note">Accepted formats: PDF, JPG, PNG, DOC (Max: 5MB)</small>
                        <div class="error-message" id="proofError"></div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn" id="submitBtn" disabled>
                            <i class='bx bx-save'></i> Submit Request
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class='bx bx-rotate-left'></i> Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Leave Table -->
            <div class="card">
                <h3><i class='bx bx-table'></i> Your Leave Requests</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Reason</th>
                                <th>Proof</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['leave_type']) ?></td>
                                        <td><?= htmlspecialchars($row['start_date']) ?></td>
                                        <td><?= htmlspecialchars($row['end_date']) ?></td>
                                        <td><?= htmlspecialchars(substr($row['reason'], 0, 30)) . (strlen($row['reason']) > 30 ? '...' : '') ?></td>
                                        <td>
                                            <?php if (!empty($row['proof_filename'])): ?>
                                                <a href="view_proof.php?consult_leave_id=<?= $row['consult_leave_id'] ?>" target="_blank" class="btn" style="padding:6px 12px; font-size:13px; margin-right:5px;">
                                                    View
                                                </a>
                                                <a href="download_proof.php?consult_leave_id=<?= $row['consult_leave_id'] ?>" class="btn btn-secondary" style="padding:6px 12px; font-size:13px;">
                                                    Download
                                                </a>
                                            <?php else: ?>
                                                <span style="color:var(--gray);">No proof</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:20px;">No leave requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="btn-group">
                    <a href="consultant_dashboard.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // ✅ YOUR EXISTING JS LOGIC — untouched
        document.addEventListener('DOMContentLoaded', function () {
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            const reason = document.getElementById('reason');
            const submitBtn = document.getElementById('submitBtn');
            const leaveType = document.getElementById('leaveType');

            let isStartDateValid = false, isEndDateValid = false, isReasonValid = false, isLeaveTypeValid = false;

            const today = new Date().toISOString().split('T')[0];
            startDate.setAttribute('min', today);

            function validateStartDate() {
                const value = startDate.value;
                const error = document.getElementById('startDateError');
                if (!value) {
                    startDate.classList.add('error'); startDate.classList.remove('success');
                    error.textContent = 'Start date is required'; error.style.display = 'block';
                    isStartDateValid = false;
                } else {
                    const selectedDate = new Date(value);
                    const todayDate = new Date(today);
                    if (selectedDate < todayDate) {
                        startDate.classList.add('error'); startDate.classList.remove('success');
                        error.textContent = 'Start date cannot be in the past'; error.style.display = 'block';
                        isStartDateValid = false;
                    } else {
                        startDate.classList.remove('error'); startDate.classList.add('success'); error.style.display = 'none';
                        isStartDateValid = true;
                    }
                }
                validateForm();
            }

            function validateEndDate() {
                const start = new Date(startDate.value);
                const end = new Date(endDate.value);
                const error = document.getElementById('endDateError');
                if (!endDate.value) {
                    endDate.classList.add('error'); endDate.classList.remove('success');
                    error.textContent = 'End date is required'; error.style.display = 'block';
                    isEndDateValid = false;
                } else if (end < start) {
                    endDate.classList.add('error'); endDate.classList.remove('success');
                    error.textContent = 'End date cannot be before start date'; error.style.display = 'block';
                    isEndDateValid = false;
                } else {
                    endDate.classList.remove('error'); endDate.classList.add('success'); error.style.display = 'none';
                    isEndDateValid = true;
                }
                validateForm();
            }

            function validateReason() {
                const value = reason.value.trim();
                const error = document.getElementById('reasonError');
                if (value.length < 10) {
                    reason.classList.add('error'); reason.classList.remove('success');
                    error.textContent = 'Reason must be at least 10 characters'; error.style.display = 'block';
                    isReasonValid = false;
                } else {
                    reason.classList.remove('error'); reason.classList.add('success'); error.style.display = 'none';
                    isReasonValid = true;
                }
                validateForm();
            }

            function validateLeaveType() {
                isLeaveTypeValid = leaveType.value !== '';
                validateForm();
            }

            function validateForm() {
                submitBtn.disabled = !(isStartDateValid && isEndDateValid && isReasonValid && isLeaveTypeValid);
            }

            startDate.addEventListener('change', validateStartDate);
            endDate.addEventListener('change', validateEndDate);
            reason.addEventListener('input', validateReason);
            leaveType.addEventListener('change', validateLeaveType);

            document.getElementById('leaveForm').addEventListener('submit', function(e){
                e.preventDefault();
                const formData = new FormData(this);
                fetch('', {method:'POST', body:formData, headers:{'X-Requested-With':'XMLHttpRequest'}})
                .then(res => res.json())
                .then(data => {
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.message
                    }).then(() => { if(data.success) location.reload(); });
                })
                .catch(err => console.error(err));
            });

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

            // Tablet collapse
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
        });

        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>