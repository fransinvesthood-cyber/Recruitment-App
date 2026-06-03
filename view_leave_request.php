<?php
include('config.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in.");
}

$user_id = $_SESSION['user_id'];

// Fetch full name
$sql_user = "SELECT fullname FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$stmt_user->bind_result($fullname);
$stmt_user->fetch();
$stmt_user->close();

$leave_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'];
    $update_sql = "UPDATE consultant_leaves SET status = ? WHERE consult_leave_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $leave_id);
    if ($stmt->execute()) {
        $message = "Status updated successfully.";
        $messageClass = "success";
    } else {
        $message = "Error updating status: " . htmlspecialchars($conn->error);
        $messageClass = "error";
    }
}

$sql = "SELECT cl.*, u.fullname, u.email 
        FROM consultant_leaves cl
        JOIN users u ON cl.user_id = u.user_id
        WHERE cl.consult_leave_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No leave request found with that ID.");
}

$row = $result->fetch_assoc();

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
    <title>Leave Request Details</title>
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

        .side-menu li.section-header {
            padding: 8px 16px;
            font-weight: 600;
            color: var(--white);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
            margin-top: 12px;
            text-decoration: none;
        }

        .sidebar.collapsed .side-menu li.section-header span {
            display: none;
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
           MAIN
        ============================ */
        main {
            padding: 24px;
        }

        .header h1 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 8px;
        }

        /* ===========================
           CARD & TABLE
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

        .card h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        body.dark-mode .card h2 {
            color: #e4e6eb;
        }

        .alert {
            padding: 14px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        body.dark-mode .alert.success {
            background: #0a3a1f;
            color: #d4edda;
        }
        body.dark-mode .alert.error {
            background: #3a1a1a;
            color: #f8d7da;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .details-table th {
            text-align: left;
            padding: 14px 16px;
            font-weight: 600;
            color: var(--dark);
            background: #f8f9fa;
            width: 200px;
        }
        body.dark-mode .details-table th {
            background: #2d2e2f;
            color: #e4e6eb;
        }
        .details-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--light-gray);
            color: var(--gray);
        }
        body.dark-mode .details-table td {
            color: #adb5bd;
            border-color: #3a3b3c;
        }
        .details-table tr {
            transition: background 0.2s;
        }
        .details-table tr:hover {
            background: #f8f9fa;
        }
        body.dark-mode .details-table tr:hover {
            background: #2d2e2f;
        }

        .reason-text {
            line-height: 1.6;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        body.dark-mode .reason-text {
            background: #2d2e2f;
            color: #e4e6eb;
            border-left-color: var(--primary);
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #be123c; }
        body.dark-mode .status-pending { background-color: #92400e; color: #fef3c7; }
        body.dark-mode .status-approved { background-color: #065f46; color: #d1fae5; }
        body.dark-mode .status-rejected { background-color: #be123c; color: #fee2e2; }

        /* ===========================
           FORM
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

        select {
            width: 100%;
            max-width: 300px;
            padding: 14px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background: white;
            transition: var(--transition);
        }
        body.dark-mode select {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4b4d;
        }

        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

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
            font-size: 15px;
            text-decoration: none;
        }
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        @media (max-width: 480px) {
            .action-buttons {
                flex-direction: column;
            }
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
            .details-table th { width: 120px; }
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
            <li><a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i><span>Manage Jobs</span></a></li>
            <li><a href="manage_applications.php"><i class='bx bx-file'></i><span>Manage Applications</span></a></li>
            <li><a href="manage_candidates.php"><i class='bx bx-user'></i><span>Manage Candidates</span></a></li>
            <li><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interview Schedule</span></a></li>
            <li class="section-header"><span>Consultants</span></li>
            <li><a href="#"><i class='bx bx-calendar'></i><span>Manage Leave</span></a></li>
            <li><a href="admin_invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="admin_settings.php"><i class='bx bx-cog'></i><span>Settings</span></a></li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="logout.php" class="logout">
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
            <a href="admin_leaves.php" class="active"><i class='bx bx-calendar'></i> Leaves</a>
            <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="manage_jobs.php"><i class='bx bx-briefcase'></i> Jobs</a>
        </div>

        <main>
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Leave Requests</h1>
                    <p>Review and manage leave requests</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <i class='bx bx-info-circle'></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Leave Details -->
            <div class="card">
                <h2><i class='bx bx-info-circle'></i> Request Information</h2>
                <table class="details-table">
                    <tr>
                        <th><i class='bx bx-user'></i> Consultant Name</th>
                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                    </tr>
                    <tr>
                        <th><i class='bx bx-envelope'></i> Email</th>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                    </tr>
                    <tr>
                        <th><i class='bx bx-category'></i> Leave Type</th>
                        <td><strong><?= htmlspecialchars($row['leave_type']) ?></strong></td>
                    </tr>
                    <tr>
                        <th><i class='bx bx-calendar'></i> Date Range</th>
                        <td>
                            <strong><?= date('F j, Y', strtotime($row['start_date'])) ?></strong> to 
                            <strong><?= date('F j, Y', strtotime($row['end_date'])) ?></strong>
                            <?php 
                            $days = (strtotime($row['end_date']) - strtotime($row['start_date'])) / (60 * 60 * 24) + 1;
                            echo " ({$days} day" . ($days > 1 ? "s" : "") . ")";
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><i class='bx bx-status'></i> Current Status</th>
                        <td>
                            <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><i class='bx bx-calendar-plus'></i> Request Date</th>
                        <td><?= date('F j, Y g:i A', strtotime($row['created_at'] ?? 'now')) ?></td>
                    </tr>
                    <tr>
                        <th><i class='bx bx-message-detail'></i> Reason</th>
                        <td>
                            <div class="reason-text">
                                <?= nl2br(htmlspecialchars($row['reason'])) ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><i class='bx bx-file'></i> Proof</th>
                        <td>
                            <?php if (!empty($row['proof'])): ?>
                                <a href="view_proof.php?consult_leave_id=<?= $leave_id ?>" class="btn btn-sm" target="_blank">
                                    <i class='bx bx-show'></i> View
                                </a>
                            <?php else: ?>
                                <span style="color: var(--gray);">No proof uploaded</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Status Update Form -->
            <div class="card">
                <h2><i class='bx bx-edit'></i> Update Request Status</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="status"><i class='bx bx-check-circle'></i> Change Status:</label>
                        <select name="status" id="status" required>
                            <option value="Pending" <?= $row['status'] === 'Pending' ? 'selected' : '' ?>>
                                Pending Review
                            </option>
                            <option value="Approved" <?= $row['status'] === 'Approved' ? 'selected' : '' ?>>
                                Approve Request
                            </option>
                            <option value="Rejected" <?= $row['status'] === 'Rejected' ? 'selected' : '' ?>>
                                Reject Request
                            </option>
                        </select>
                    </div>
                    <div class="action-buttons">
                        <a href="#" onclick="window.history.back(); return false;" class="btn btn-secondary">
                            <i class='bx bx-arrow-back'></i> Back
                        </a>
                        <button type="submit" class="btn">
                            <i class='bx bx-save'></i> Update Status
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // === Theme & Mobile Menu (identical to consultant dashboard) ===
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