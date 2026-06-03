<?php
include('config.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to view invoices.");
}

$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoice'])) {
    $user_id = $_POST['user_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $sql = "SELECT ct.*, u.fullname, u.email, IFNULL(ap.hourly_rate, 75.00) AS hourly_rate
        FROM consultant_timesheets ct
        JOIN users u ON ct.user_id = u.user_id
        LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
        WHERE ct.user_id = ? AND ct.work_date BETWEEN ? AND ?
        ORDER BY ct.work_date";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $total_hours = 0;
        $total_amount = 0;
        $consultant_name = '';
        $consultant_email = '';
        $hourly_rate = 75.00;
        
        $timesheet_entries = [];
        while ($row = $result->fetch_assoc()) {
            $timesheet_entries[] = $row;
            $total_hours += $row['hours_worked'];
            $consultant_name = $row['fullname'];
            $consultant_email = $row['email'];
            $hourly_rate = $row['hourly_rate'] ?? 75.00;
        }
        
        $total_amount = $total_hours * $hourly_rate;
        $invoice_number = 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $invoice_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+30 days'));
        
        $insert_sql = "INSERT INTO invoices (invoice_number, user_id, client_project, invoice_date, 
                       due_date, total_hours, hourly_rate, amount_due, status, notes) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)";
        
        $client_projects = array_unique(array_column($timesheet_entries, 'client_project'));
        $combined_projects = implode(', ', $client_projects);
        $notes = "Invoice for period: $start_date to $end_date. Consultant: $consultant_name";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sisssddds", $invoice_number, $user_id, $combined_projects, 
                                $invoice_date, $due_date, $total_hours, 
                                $hourly_rate, $total_amount, $notes);
        
        if ($insert_stmt->execute()) {
            $invoice_id = $conn->insert_id;
            foreach ($timesheet_entries as $entry) {
                $item_sql = "INSERT INTO invoice_items (invoice_id, timesheet_id, 
                            hours_worked, hourly_rate, amount) VALUES (?, ?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_sql);
                $item_amount = $entry['hours_worked'] * $hourly_rate;
                $item_stmt->bind_param("iiddd", $invoice_id, $entry['consult_timesheet_id'], 
                                      $entry['hours_worked'], $hourly_rate, $item_amount);
                $item_stmt->execute();
            }
            
            $message = "Invoice generated successfully! Invoice Number: $invoice_number";
            $messageClass = "success";
        } else {
            $message = "Error generating invoice: " . $conn->error;
            $messageClass = "error";
        }
    } else {
        $message = "No approved timesheet entries found for the selected period.";
        $messageClass = "error";
    }
}

$consultants_sql = "SELECT DISTINCT u.user_id, u.fullname FROM users u 
                   JOIN consultant_timesheets ct ON u.user_id = ct.user_id
                   WHERE u.role = 'Consultant'";
$consultants_result = $conn->query($consultants_sql);

$invoices_sql = "SELECT i.*, u.fullname as consultant_name, u.email as consultant_email
                FROM invoices i 
                JOIN users u ON i.user_id = u.user_id 
                ORDER BY i.invoice_date DESC";
$invoices_result = $conn->query($invoices_sql);

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
    <title>Invoice Management</title>
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
        .section-title {
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.7);
            padding: 8px 16px;
            margin: 16px 0 8px 0;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar.collapsed .section-title {
            display: none;
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

        @media (max-width: 768px) {
            .mobile-nav-links {
                display: flex; /* show only on tablets/phones */
            }
        }

        /* ===========================
           MAIN
        ============================ */
        main {
            padding: 24px;
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
        .welcome-section h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .welcome-section p {
            opacity: 0.9;
            font-size: 18px;
        }

        /* ===========================
           CARD & FORM
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

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
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

        select, input[type="date"] {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background: white;
            transition: var(--transition);
        }
        body.dark-mode select,
        body.dark-mode input[type="date"] {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4b4d;
        }

        select:focus, input[type="date"]:focus {
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

        /* ===========================
           TABLE
        ============================ */
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
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-paid { background-color: #d1fae5; color: #065f46; }
        .status-overdue { background-color: #fee2e2; color: #be123c; }
        .status-cancelled { background-color: #f3f4f6; color: #6b7280; }

        body.dark-mode .status-pending { background-color: #92400e; color: #fef3c7; }
        body.dark-mode .status-paid { background-color: #065f46; color: #d1fae5; }
        body.dark-mode .status-overdue { background-color: #be123c; color: #fee2e2; }
        body.dark-mode .status-cancelled { background-color: #6b7280; color: #f3f4f6; }

        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn-sm {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-edit { background: #10b981; color: white; }
        .btn-view { background: #3b82f6; color: white; }
        .btn-delete { background: #ef4444; color: white; }
        body.dark-mode .btn-edit { background: #0e9c68; }
        body.dark-mode .btn-view { background: #2563eb; }
        body.dark-mode .btn-delete { background: #c0392b; }

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
            .form-row {
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
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            
        }

        
        body.dark-mode .sidebar {
            background: var(--dark);
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
            <li><a href="manage_candidates.php"><i class='bx bx-user'></i><span>Candidates</span></a></li>
            <li><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a></li>
            <li><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
            <li class="section-header"><span>Consultants</span></li>
            <li><a href="admin_view_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="admin_view_tasklogs.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li><a href="admin_view_leaves.php"><i class='bx bx-calendar-minus'></i><span>Leaves</span></a></li>
            <li class="active"><a href="admin_invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="admin_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
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
                <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Manage Jobs</a>
                <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
                <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
                <a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a>
                <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
                <a href="manage_leave.php"><i class='bx bx-calendar-check'></i> Manage Leave</a>
                <a class="active" href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
                <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
                <a href="admin_settings.php"><i class='bx bx-cog'></i> Settings</a>
            </div>

        <main>
            <div class="welcome-section">
                <h1> Invoices</h1>
                <p>View, manage, and oversee all invoices generated within the system, including payment status and billing details.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <i class='bx bx-info-circle'></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Invoice Generation Form -->
            <div class="card">
                <h2><i class='bx bx-plus-circle'></i> Generate New Invoice</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="user_id">Select Consultant:</label>
                            <select name="user_id" id="user_id" required>
                                <option value="">Choose a consultant...</option>
                                <?php
                                $consultants_result->data_seek(0);
                                while ($consultant = $consultants_result->fetch_assoc()): ?>
                                    <option value="<?= $consultant['user_id'] ?>">
                                        <?= htmlspecialchars($consultant['fullname']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">Period Start:</label>
                            <input type="date" name="start_date" id="start_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">Period End:</label>
                            <input type="date" name="end_date" id="end_date" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="generate_invoice" class="btn">
                        <i class='bx bx-plus'></i> Generate Invoice
                    </button>
                </form>
            </div>

            <!-- Invoices Table -->
            <div class="card">
                <h2><i class='bx bx-table'></i> All Invoices</h2>
                <div class="table-wrapper">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Consultant</th>
                                <th>Client/Project</th>
                                <th>Hours</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($invoices_result->num_rows > 0): 
                                $invoices_result->data_seek(0);
                                while ($invoice = $invoices_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($invoice['invoice_number']) ?></td>
                                        <td><?= htmlspecialchars($invoice['consultant_name']) ?></td>
                                        <td><?= htmlspecialchars($invoice['client_project']) ?></td>
                                        <td><?= $invoice['total_hours'] ?>h</td>
                                        <td>R<?= number_format($invoice['amount_due'], 2) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($invoice['status']) ?>">
                                                <?= ucfirst($invoice['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($invoice['due_date'])) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_invoice.php?id=<?= $invoice['invoice_id'] ?>" class="btn-sm btn-edit">
                                                    <i class='bx bx-edit'></i> Edit
                                                </a>
                                                <a href="view_invoice.php?id=<?= $invoice['invoice_id'] ?>" class="btn-sm btn-view">
                                                    <i class='bx bx-show'></i> View
                                                </a>
                                                <a href="generate_pdf.php?id=<?= $invoice['invoice_id'] ?>" class="btn-sm btn-view">
                                                    <i class='bx bx-download'></i> PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:20px;">No invoices found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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