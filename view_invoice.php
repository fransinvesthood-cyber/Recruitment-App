<?php
session_start();
include('config.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get invoice ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_invoices.php");
    exit();
}

$invoice_id = $_GET['id'];

// Fetch invoice details
$invoice_sql = "SELECT i.*, u.fullname as consultant_name, u.email as consultant_email
                FROM invoices i 
                JOIN users u ON i.user_id = u.user_id 
                WHERE i.invoice_id = ?";
$invoice_stmt = $conn->prepare($invoice_sql);
$invoice_stmt->bind_param("i", $invoice_id);
$invoice_stmt->execute();
$invoice_result = $invoice_stmt->get_result();

if ($invoice_result->num_rows === 0) {
    header("Location: admin_invoices.php");
    exit();
}

$invoice = $invoice_result->fetch_assoc();

// Fetch invoice items
$items_sql = "SELECT ii.*, ct.work_date, ct.client_project, ct.description
              FROM invoice_items ii
              JOIN consultant_timesheets ct ON ii.timesheet_id = ct.consult_timesheet_id
              WHERE ii.invoice_id = ?
              ORDER BY ct.work_date";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $invoice_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}

// Calculate totals
$subtotal = $invoice['amount_due'];
$tax_rate = 0.15; // 15% VAT
$tax_amount = $subtotal * $tax_rate;
$total_amount = $subtotal + $tax_amount;

// Get user fullname for welcome message
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT fullname FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$admin_name = $user_data['fullname'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>View Invoice - <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>

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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        /* ===========================
           THEME TOGGLE
        ============================ */
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
           MAIN CONTENT AREA
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .welcome-content h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .welcome-content p {
            opacity: 0.9;
            font-size: 18px;
        }

        .welcome-actions {
            display: flex;
            gap: 10px;
        }

        /* ===========================
           CARD STYLES
        ============================ */
        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 24px;
        }

        body.dark-mode .card {
            background: #242526;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        body.dark-mode .card-header {
            border-color: #3a3b3c;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        body.dark-mode .card-title {
            color: #e4e6eb;
        }

        /* ===========================
           INVOICE SPECIFIC STYLES
        ============================ */
        .invoice-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-gray);
        }

        body.dark-mode .invoice-header {
            border-color: #3a3b3c;
        }

        .company-info h2 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        body.dark-mode .company-info h2 {
            color: #a7b7ff;
        }

        .invoice-details {
            text-align: right;
        }

        .invoice-details h3 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        body.dark-mode .invoice-details h3 {
            color: #a7b7ff;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            margin-top: 10px;
        }

        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-paid { background-color: #d1fae5; color: #065f46; }
        .status-overdue { background-color: #fee2e2; color: #be123c; }
        .status-cancelled { background-color: #f3f4f6; color: #6b7280; }

        body.dark-mode .status-pending { background-color: #92400e; color: #fef3c7; }
        body.dark-mode .status-paid { background-color: #065f46; color: #d1fae5; }
        body.dark-mode .status-overdue { background-color: #be123c; color: #fee2e2; }
        body.dark-mode .status-cancelled { background-color: #6b7280; color: #f3f4f6; }

        .consultant-info {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        body.dark-mode .consultant-info {
            background: #3a3b3c;
        }

        .consultant-info h4 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        body.dark-mode .consultant-info h4 {
            color: #a7b7ff;
        }

        /* ===========================
           TABLE STYLES
        ============================ */
        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 24px;
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
            color: #e4e6eb;
        }

        th {
            background: rgba(102, 126, 234, 0.1);
            font-weight: 600;
            color: var(--primary);
        }

        body.dark-mode th {
            background: rgba(102, 126, 234, 0.2);
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
           TOTALS SECTION
        ============================ */
        .totals-section {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            max-width: 400px;
            margin-left: auto;
        }

        body.dark-mode .totals-section {
            background: #3a3b3c;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        body.dark-mode .totals-row {
            border-color: #4a4b4d;
        }

        .totals-row:last-child {
            border-bottom: 2px solid var(--primary);
            font-weight: bold;
            font-size: 18px;
            color: var(--primary);
        }

        body.dark-mode .totals-row:last-child {
            color: #a7b7ff;
        }

        /* ===========================
           BUTTONS
        ============================ */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-secondary {
            background-color: var(--gray);
            color: white;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-info {
            background-color: var(--info);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        /* ===========================
           RESPONSIVE STYLES
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

            .invoice-header {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .invoice-details {
                text-align: left;
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

            .mobile-menu-btn {
                display: block;
            }

            .mobile-nav-links {
                display: flex;
            }

            .welcome-section {
                flex-direction: column;
                text-align: center;
            }

            .welcome-actions {
                width: 100%;
                justify-content: center;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }

            .totals-section {
                max-width: 100%;
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

        @media print {
            .sidebar, 
            nav, 
            .mobile-nav-links,
            .action-buttons,
            .mobile-menu-overlay {
                display: none !important;
            }
            
            .content {
                margin-left: 0 !important;
            }
            
            .card {
                box-shadow: none !important;
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
            <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Jobs</a>
            <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
            <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
            <a href="schedule_interview.php"><i class='bx bx-group'></i> Interviews</a>
            <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
            <a class="active" href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Invoice Details</h1>
                    <p>Access a detailed summary of charges, billing periods, payment records, and outstanding balances for this invoice.</p>
                </div>
            </div>

            <!-- Invoice Card -->
            <div class="card">
                <!-- Invoice Header -->
                <div class="invoice-header">
                    <div class="company-info">
                        <h2>Investhood IT (Pty) Ltd</h2>
                        <p>136 2nd St, Randjespark<br>
                        Midrand, 1682<br>
                        South Africa</p>
                        <p>Phone: 068 246 0562<br>
                        Email: admin@investhoodit.co.za</p>
                    </div>
                    <div class="invoice-details">
                        <h3>Invoice Details</h3>
                        <p><strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?></p>
                        <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></p>
                        <span class="status-badge status-<?php echo strtolower($invoice['status']); ?>">
                            <?php echo ucfirst($invoice['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Consultant Information -->
                <div class="consultant-info">
                    <h4><i class='bx bx-user'></i> Bill To:</h4>
                    <p><strong><?php echo htmlspecialchars($invoice['consultant_name']); ?></strong></p>
                    <p><?php echo htmlspecialchars($invoice['consultant_email']); ?></p>
                    <p><strong>Project:</strong> <?php echo htmlspecialchars($invoice['client_project']); ?></p>
                </div>

                <!-- Invoice Items Table -->
                <?php if (!empty($items)): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Project</th>
                                <th>Description</th>
                                <th>Hours</th>
                                <th>Rate</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($item['work_date'])); ?></td>
                                <td><?php echo htmlspecialchars($item['client_project']); ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td><?php echo $item['hours_worked']; ?>h</td>
                                <td>R<?php echo number_format($item['hourly_rate'], 2); ?></td>
                                <td>R<?php echo number_format($item['amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 30px; color: var(--gray);">
                    <i class='bx bx-file' style="font-size: 48px; margin-bottom: 15px;"></i>
                    <p>No detailed items available for this invoice.</p>
                </div>
                <?php endif; ?>

                <!-- Totals Section -->
                <div class="totals-section">
                    <div class="totals-row">
                        <span>Total Hours:</span>
                        <span><?php echo $invoice['total_hours']; ?>h</span>
                    </div>
                    <div class="totals-row">
                        <span>Hourly Rate:</span>
                        <span>R<?php echo number_format($invoice['hourly_rate'], 2); ?></span>
                    </div>
                    <div class="totals-row">
                        <span>Subtotal:</span>
                        <span>R<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="totals-row">
                        <span>VAT (15%):</span>
                        <span>R<?php echo number_format($tax_amount, 2); ?></span>
                    </div>
                    <div class="totals-row">
                        <span>Total Amount:</span>
                        <span>R<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                </div>

                <!-- Notes -->
                <?php if (!empty($invoice['notes'])): ?>
                <div style="background: var(--light); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="color: var(--primary); margin-bottom: 10px;"><i class='bx bx-note'></i> Notes:</h4>
                    <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="admin_invoices.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Back to Invoices
                    </a>
                    <a href="edit_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" class="btn btn-primary">
                        <i class='bx bx-edit'></i> Edit Invoice
                    </a>
                    <button onclick="window.print()" class="btn btn-success">
                        <i class='bx bx-printer'></i> Print Invoice
                    </button>
                    <a href="generate_pdf.php?id=<?php echo $invoice['invoice_id']; ?>" class="btn btn-info">
                        <i class='bx bx-download'></i> Download PDF
                    </a>
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

        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        if (mobileMenuOverlay) {
            mobileMenuOverlay.addEventListener('click', function() {
                document.getElementById('sidebar').classList.remove('active');
                this.style.display = 'none';
            });
        }

        // Close sidebar when clicking menu items on mobile
        document.querySelectorAll('.side-menu a').forEach(link => {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                    document.getElementById('mobileMenuOverlay').style.display = 'none';
                    if (this.href !== window.location.href && !this.onclick && !this.target) {
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

        // Show mobile logout button on small screens
        function handleMobileLogout() {
            const mobileLogoutBtn = document.querySelector('.mobile-logout-btn');
            if (mobileLogoutBtn) {
                if (window.innerWidth <= 768) {
                    mobileLogoutBtn.style.display = 'block';
                } else {
                    mobileLogoutBtn.style.display = 'none';
                }
            }
        }
        
        window.addEventListener('resize', handleMobileLogout);
        handleMobileLogout();
    </script>
</body>
</html>
