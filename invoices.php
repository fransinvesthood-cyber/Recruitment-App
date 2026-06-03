<?php
session_start();
include('config.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$current_user_id = $_SESSION['user_id'];
$current_user_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Consultant';
$hourly_rate = 75.00;
if (isset($conn)) {
    $rate_sql = "SELECT hourly_rate FROM applicant_profile WHERE user_id = ?";
    if ($rate_stmt = $conn->prepare($rate_sql)) {
        $rate_stmt->bind_param("i", $current_user_id);
        if ($rate_stmt->execute()) {
            $rate_result = $rate_stmt->get_result();
            if ($rate_row = $rate_result->fetch_assoc()) {
                $hourly_rate = $rate_row['hourly_rate'] ?? $hourly_rate;
            }
        }
        $rate_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Invoice</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

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
           FORM CONTAINER / CARD
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
            font-size: 24px;
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

        .btn-exit {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--danger);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            transition: var(--transition);
        }
        .btn-exit:hover {
            background: #c0392b;
            transform: scale(1.05);
        }

        /* ===========================
           INVOICE FILTERS
        ============================ */
        .invoice-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: var(--border-radius);
        }
        body.dark-mode .invoice-filters {
            background: #2d2e2f;
        }

        .filter-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 15px;
            color: var(--dark);
        }
        body.dark-mode .filter-group label {
            color: #e4e6eb;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background: white;
            transition: var(--transition);
        }
        body.dark-mode .filter-group input,
        body.dark-mode .filter-group select {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4b4d;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        /* ===========================
           RATE DISPLAY
        ============================ */
        .rate-display {
            background: #e8f4f8;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            border-left: 4px solid var(--primary);
        }
        body.dark-mode .rate-display {
            background: #2c3e50;
            border-left: 4px solid var(--primary);
        }

        .rate-display h4 {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 12px;
        }
        .rate-display p {
            margin-bottom: 12px;
            font-size: 16px;
        }
        body.dark-mode .rate-display h4,
        body.dark-mode .rate-display p {
            color: #e4e6eb;
        }

        .rate-input {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .rate-input input {
            flex: 1;
            padding: 12px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
        }
        body.dark-mode .rate-input input {
            background: #3a3b3c;
            color: #e4e6eb;
        }

        /* ===========================
           BUTTONS
        ============================ */
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

        .btn-success { background: var(--success); }
        .btn-success:hover { background: #219a52; }
        .btn-warning { background: var(--warning); color: black; }
        .btn-warning:hover { background: #e0a800; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }

        .invoice-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 24px 0;
        }

        /* ===========================
           INVOICE CONTENT (HEADER, SUMMARY, TABLE, TOTALS)
        ============================ */
        .invoice-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        @media (max-width: 768px) {
            .invoice-header { grid-template-columns: 1fr; }
        }

        .company-info, .invoice-info {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        body.dark-mode .company-info,
        body.dark-mode .invoice-info {
            background: #2d2e2f;
        }

        .company-info h3,
        .invoice-info h3 {
            color: var(--primary);
            margin-bottom: 16px;
            font-size: 18px;
        }
        .company-info p,
        .invoice-info p {
            margin: 5px 0;
            color: var(--dark);
        }
        body.dark-mode .company-info p,
        body.dark-mode .invoice-info p {
            color: #e4e6eb;
        }

        .invoice-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
        }
        .summary-card.success { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .summary-card.warning { background: linear-gradient(135deg, #f39c12, #f1c40f); }
        .summary-card.info { background: linear-gradient(135deg, #8e44ad, #9b59b6); }

        .summary-card h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 30px;
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

        .billable-yes { color: var(--success); font-weight: bold; }
        .billable-no { color: var(--danger); font-weight: bold; }

        .invoice-totals {
            background: var(--white);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }
        body.dark-mode .invoice-totals {
            background: #2d2e2f;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--light-gray);
            color: var(--dark);
        }
        body.dark-mode .totals-row {
            color: #e4e6eb;
            border-color: #3a3b3c;
        }

        .totals-row:last-child {
            border-bottom: 2px solid var(--primary);
            font-weight: bold;
            font-size: 18px;
            color: var(--primary);
        }

        /* ===========================
           STATES: LOADING, NO DATA
        ============================ */
        .loading, .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        body.dark-mode .loading,
        body.dark-mode .no-data {
            color: #adb5bd;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            .invoice-actions {
                flex-direction: column;
            }
            .invoice-summary {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .btn {
                width: 100%;
                justify-content: center;
            }
            .rate-input {
                flex-direction: column;
            }
            .rate-input input {
                width: 100%;
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
            <li><a href="manage_leave.php"><i class='bx bx-calendar-minus'></i><span>Leave</span></a></li>
            <li><a href="manage_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="manage_task_log.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li class="active"><a href="invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
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
            <a href="manage_leave.php"><i class='bx bx-calendar-minus'></i> Leave</a>
            <a class="active" href="invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="training_management.php"><i class='bx bx-book-reader'></i> Training</a>
            <!--<a href="consultant_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>-->
            <a href="consultant_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Invoices</h1>
                <p>Generate and submit invoices, track payment progress, and keep a complete record of your billing history.</p>
            </div>

            <div class="card">
                <h2><i class='bx bx-receipt'></i> Generate Invoice</h2>

                <!-- Invoice Filters -->
                <div class="invoice-filters">
                    <div class="filter-group">
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date">
                    </div>
                    <div class="filter-group">
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date">
                    </div>
                    <div class="filter-group">
                        <label for="client_filter">Client/Project:</label>
                        <select id="client_filter" name="client_filter">
                            <option value="">All Clients</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="billable_filter">Billable Only:</label>
                        <select id="billable_filter" name="billable_filter">
                            <option value="">All</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>

                <!-- Hourly Rate -->
                <div class="rate-display">
                    <h4><i class='bx bx-money'></i> Current Hourly Rate</h4>
                    <p>Current Rate: <strong>R<span id="currentRate"><?php echo number_format($hourly_rate, 2); ?></span>/hour</strong></p>
                    <div class="rate-input">
                        <input type="number" id="newRate" placeholder="Enter new rate" min="0" step="0.01">
                        <button class="btn btn-secondary" onclick="updateRate(<?php echo $current_user_id; ?>)">
                            <i class='bx bx-refresh'></i> Update
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="invoice-actions">
                    <button class="btn" onclick="generateInvoice(<?php echo $current_user_id; ?>)">
                        <i class='bx bx-refresh'></i> Generate Invoice
                    </button>
                    <button class="btn btn-success" onclick="exportToPDF()" id="exportPDFBtn" disabled>
                        <i class='bx bx-file-pdf'></i> Export to PDF
                    </button>
                    <button class="btn btn-warning" onclick="exportToCSV()" id="exportCSVBtn" disabled>
                        <i class='bx bx-file-export'></i> Export to CSV
                    </button>
                    <button class="btn btn-secondary" onclick="sendInvoice()" id="sendInvoiceBtn" disabled>
                        <i class='bx bx-send'></i> Send Invoice
                    </button>
                </div>

                <!-- Loading -->
                <div id="loadingState" class="loading" style="display: none;">
                    <div class="spinner"></div>
                    <p>Generating invoice...</p>
                </div>

                <!-- Invoice Content -->
                <div id="invoiceContent" style="display: none;">
                    <!-- Header -->
                    <div class="invoice-header">
                        <div class="company-info">
                            <h3><i class='bx bx-buildings'></i> Company Information</h3>
                            <p><strong>Investhood IT (Pty) Ltd</strong></p>
                            <p>136 2nd St, Randjespark</p>
                            <p>Midrand, 1682</p>
                            <p>South Africa</p>
                            <p>Phone: 068 246 0562</p>
                            <p>Email: admin@investhoodit.co.za</p>
                        </div>
                        <div class="invoice-info">
                            <h3><i class='bx bx-receipt'></i> Invoice Details</h3>
                            <p><strong>Invoice #:</strong> <span id="invoiceNumber"></span></p>
                            <p><strong>Date:</strong> <span id="invoiceDate"></span></p>
                            <p><strong>Period:</strong> <span id="invoicePeriod"></span></p>
                            <p><strong>Consultant:</strong> <span id="consultantName"><?php echo htmlspecialchars($current_user_name); ?></span></p>
                            <p><strong>Status:</strong> <span id="invoiceStatus">Draft</span></p>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="invoice-summary">
                        <div class="summary-card">
                            <h4>Total Hours</h4>
                            <p class="value" id="totalHours">0.0</p>
                        </div>
                        <div class="summary-card success">
                            <h4>Billable Hours</h4>
                            <p class="value" id="billableHours">0.0</p>
                        </div>
                        <div class="summary-card warning">
                            <h4>Non-Billable Hours</h4>
                            <p class="value" id="nonBillableHours">0.0</p>
                        </div>
                        <div class="summary-card info">
                            <h4>Total Amount</h4>
                            <p class="value" id="totalAmount">R0.00</p>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-wrapper">
                        <table id="timesheetTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client/Project</th>
                                    <th>Hours</th>
                                    <th>Rate</th>
                                    <th>Billable</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="timesheetTableBody">
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                    <div class="invoice-totals">
                        <div class="totals-row">
                            <span>Subtotal (Billable Hours):</span>
                            <span id="subtotalAmount">R0.00</span>
                        </div>
                        <div class="totals-row">
                            <span>Tax (15% VAT):</span>
                            <span id="taxAmount">R0.00</span>
                        </div>
                        <div class="totals-row">
                            <span>Total Amount Due:</span>
                            <span id="finalAmount">R0.00</span>
                        </div>
                    </div>
                </div>

                <!-- No Data -->
                <div id="noDataState" class="no-data" style="display: none;">
                    <i class='bx bx-receipt' style="font-size: 48px;"></i>
                    <h3>No Timesheet Data Found</h3>
                    <p>No timesheet entries found for the selected period.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // ✅ Your existing JS logic — untouched
        document.addEventListener('DOMContentLoaded', function() {
            loadClientOptions(<?php echo $current_user_id; ?>);
            setDefaultDates();
            document.getElementById('exitPage').addEventListener('click', function() {
                window.history.back();
            });
            let currentInvoiceData = [];
        });

        function setDefaultDates() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            document.getElementById('start_date').value = firstDay.toISOString().split('T')[0];
            document.getElementById('end_date').value = lastDay.toISOString().split('T')[0];
        }

        function updateRate(userId) {
            const newRateInput = document.getElementById('newRate');
            const newRate = newRateInput.value;
            if (!newRate || isNaN(newRate) || parseFloat(newRate) <= 0) {
                Swal.fire('Error', 'Please enter a valid rate', 'error');
                return;
            }
            fetch('update_hourly_rate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, new_rate: parseFloat(newRate) })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('currentRate').textContent = parseFloat(newRate).toFixed(2);
                    newRateInput.value = '';
                    Swal.fire('Success', 'Hourly rate updated successfully', 'success');
                } else {
                    Swal.fire('Error', data.message || 'Failed to update rate.', 'error');
                }
            })
            .catch(error => {
                console.error('Error updating rate:', error);
                Swal.fire('Error', 'An error occurred while updating the rate.', 'error');
            });
        }

        function loadClientOptions(userId) {
            fetch('fetch_client_options.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('client_filter');
                select.innerHTML = '<option value="">All Clients</option>';
                if (data.success && data.clients) {
                    data.clients.forEach(client => {
                        const option = document.createElement('option');
                        option.value = client;
                        option.textContent = client;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading client options:', error));
        }

        function generateInvoice(userId) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const clientFilter = document.getElementById('client_filter').value;
            const billableFilter = document.getElementById('billable_filter').value;
            if (!startDate || !endDate) {
                Swal.fire('Error', 'Please select both start and end dates', 'error');
                return;
            }
            if (new Date(startDate) > new Date(endDate)) {
                Swal.fire('Error', 'Start date cannot be after end date', 'error');
                return;
            }

            document.getElementById('loadingState').style.display = 'block';
            document.getElementById('invoiceContent').style.display = 'none';
            document.getElementById('noDataState').style.display = 'none';

            const requestData = {
                user_id: userId,
                start_date: startDate,
                end_date: endDate,
                client_filter: clientFilter,
                billable_filter: billableFilter
            };

            fetch('fetch_invoice_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (data.data && data.data.length > 0) {
                        currentInvoiceData = data.data;
                        populateInvoice(data.data, startDate, endDate);
                        document.getElementById('loadingState').style.display = 'none';
                        document.getElementById('invoiceContent').style.display = 'block';
                        enableExportButtons();
                    } else {
                        document.getElementById('loadingState').style.display = 'none';
                        document.getElementById('noDataState').style.display = 'block';
                        disableExportButtons();
                        Swal.fire('Info', 'No timesheet entries found for the selected criteria.', 'info');
                    }
                } else {
                    throw new Error(data.message || 'Failed to fetch invoice data.');
                }
            })
            .catch(error => {
                console.error('Error fetching invoice data:', error);
                document.getElementById('loadingState').style.display = 'none';
                Swal.fire('Error', 'Failed to generate invoice: ' + error.message, 'error');
                document.getElementById('noDataState').style.display = 'block';
                disableExportButtons();
            });
        }

        function populateInvoice(data, startDate, endDate) {
            const hourlyRate = parseFloat(document.getElementById('currentRate').textContent);
            let totalHours = 0, billableHours = 0, nonBillableHours = 0, subtotalAmount = 0;
            data.forEach(entry => {
                const hours = parseFloat(entry.hours_worked);
                totalHours += hours;
                if (entry.billable === 'Yes') {
                    billableHours += hours;
                    subtotalAmount += hours * hourlyRate;
                } else {
                    nonBillableHours += hours;
                }
            });

            const taxRate = 0.15;
            const taxAmount = subtotalAmount * taxRate;
            const finalAmount = subtotalAmount + taxAmount;

            document.getElementById('invoiceNumber').textContent = 'INV-' + Date.now();
            document.getElementById('invoiceDate').textContent = new Date().toLocaleDateString();
            document.getElementById('invoicePeriod').textContent = `${startDate} to ${endDate}`;
            document.getElementById('totalHours').textContent = totalHours.toFixed(1);
            document.getElementById('billableHours').textContent = billableHours.toFixed(1);
            document.getElementById('nonBillableHours').textContent = nonBillableHours.toFixed(1);
            document.getElementById('totalAmount').textContent = 'R' + finalAmount.toFixed(2);

            const tableBody = document.getElementById('timesheetTableBody');
            tableBody.innerHTML = '';
            data.forEach(entry => {
                const hours = parseFloat(entry.hours_worked);
                const amount = entry.billable === 'Yes' ? (hours * hourlyRate).toFixed(2) : '0.00';
                const row = `<tr>
                    <td>${entry.work_date}</td>
                    <td>${entry.client_project}</td>
                    <td>${hours.toFixed(1)}</td>
                    <td>R${hourlyRate.toFixed(2)}</td>
                    <td><span class="billable-${entry.billable.toLowerCase()}">${entry.billable}</span></td>
                    <td>${entry.description}</td>
                    <td>R${amount}</td>
                </tr>`;
                tableBody.insertAdjacentHTML('beforeend', row);
            });

            document.getElementById('subtotalAmount').textContent = 'R' + subtotalAmount.toFixed(2);
            document.getElementById('taxAmount').textContent = 'R' + taxAmount.toFixed(2);
            document.getElementById('finalAmount').textContent = 'R' + finalAmount.toFixed(2);
        }

        function enableExportButtons() {
            document.getElementById('exportPDFBtn').disabled = false;
            document.getElementById('exportCSVBtn').disabled = false;
            document.getElementById('sendInvoiceBtn').disabled = false;
        }

        function disableExportButtons() {
            document.getElementById('exportPDFBtn').disabled = true;
            document.getElementById('exportCSVBtn').disabled = true;
            document.getElementById('sendInvoiceBtn').disabled = true;
        }

        // --- PDF & CSV EXPORT (unchanged) ---
        function exportToPDF() {
            if (!currentInvoiceData || currentInvoiceData.length === 0) {
                Swal.fire('Error', 'No data available to export.', 'error');
                return;
            }
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const primaryColor = [41, 128, 185];
            let currentY = 20;
            doc.setFontSize(20);
            doc.setTextColor(...primaryColor);
            doc.setFont("helvetica", "bold");
            doc.text("Investhood IT (Pty) Ltd", 14, currentY);
            currentY += 8;
            doc.setFontSize(10);
            doc.setTextColor(50, 50, 50);
            doc.setFont("helvetica", "normal");
            doc.text("136 2nd St, Randjespark", 14, currentY);
            doc.text("Midrand, 1682, South Africa", 14, currentY + 5);
            doc.text("Email: admin@investhoodit.co.za", 14, currentY + 10);
            doc.text("Phone: 068 246 0562", 14, currentY + 15);
            const invoiceNum = document.getElementById('invoiceNumber').textContent;
            const invoiceDate = document.getElementById('invoiceDate').textContent;
            const consultantName = document.getElementById('consultantName').textContent;
            doc.setFontSize(26);
            doc.setTextColor(127, 140, 141);
            doc.text("INVOICE", 140, 20);
            doc.setFontSize(10);
            doc.setTextColor(50, 50, 50);
            doc.text(`Invoice #: ${invoiceNum}`, 140, 35);
            doc.text(`Date: ${invoiceDate}`, 140, 40);
            doc.text(`Consultant: ${consultantName}`, 140, 45);
            currentY += 30;
            doc.setFillColor(245, 247, 250);
            doc.setDrawColor(220, 220, 220);
            doc.rect(14, currentY, 182, 25, 'FD');
            const totalHours = document.getElementById('totalHours').textContent;
            const totalAmount = document.getElementById('totalAmount').textContent;
            const billableHours = document.getElementById('billableHours').textContent;
            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100);
            doc.text("Total Hours", 20, currentY + 8);
            doc.setFontSize(14);
            doc.setTextColor(0, 0, 0);
            doc.text(totalHours, 20, currentY + 18);
            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100);
            doc.text("Billable Hours", 80, currentY + 8);
            doc.setFontSize(14);
            doc.setTextColor(39, 174, 96);
            doc.text(billableHours, 80, currentY + 18);
            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100);
            doc.text("Total Amount", 140, currentY + 8);
            doc.setFontSize(14);
            doc.setTextColor(...primaryColor);
            doc.text(totalAmount, 140, currentY + 18);
            currentY += 35;
            const hourlyRate = parseFloat(document.getElementById('currentRate').textContent.replace(/,/g, ''));
            const tableBody = currentInvoiceData.map(entry => {
                const hours = parseFloat(entry.hours_worked);
                const amount = entry.billable === 'Yes' ? (hours * hourlyRate).toFixed(2) : '0.00';
                return [
                    entry.work_date,
                    entry.client_project,
                    hours.toFixed(1),
                    `R${hourlyRate.toFixed(2)}`,
                    entry.billable,
                    entry.description,
                    `R${amount}`
                ];
            });
            doc.autoTable({
                startY: currentY,
                head: [['Date', 'Client', 'Hours', 'Rate', 'Billable', 'Description', 'Amount']],
                body: tableBody,
                theme: 'striped',
                headStyles: { fillColor: primaryColor, textColor: [255,255,255], fontStyle: 'bold' },
                styles: { fontSize: 9, cellPadding: 3 },
                columnStyles: {
                    0: { cellWidth: 25 },
                    1: { cellWidth: 30 },
                    2: { cellWidth: 15, halign: 'center' },
                    3: { cellWidth: 20 },
                    4: { cellWidth: 15, halign: 'center' },
                    5: { cellWidth: 'auto' },
                    6: { cellWidth: 25, halign: 'right' }
                }
            });
            let finalY = doc.lastAutoTable.finalY + 10;
            if (finalY > 260) {
                doc.addPage();
                finalY = 20;
            }
            const subtotal = document.getElementById('subtotalAmount').textContent;
            const tax = document.getElementById('taxAmount').textContent;
            const finalTotal = document.getElementById('finalAmount').textContent;
            doc.setFontSize(10);
            doc.setTextColor(0, 0, 0);
            const rightMargin = 196;
            doc.text(`Subtotal:`, 140, finalY);
            doc.text(subtotal, rightMargin, finalY, { align: 'right' });
            doc.text(`VAT (15%):`, 140, finalY + 7);
            doc.text(tax, rightMargin, finalY + 7, { align: 'right' });
            doc.setDrawColor(...primaryColor);
            doc.setLineWidth(0.5);
            doc.line(140, finalY + 12, rightMargin, finalY + 12);
            doc.setFontSize(12);
            doc.setFont("helvetica", "bold");
            doc.setTextColor(...primaryColor);
            doc.text(`Total Due:`, 140, finalY + 18);
            doc.text(finalTotal, rightMargin, finalY + 18, { align: 'right' });
            doc.save(`Invoice_${invoiceNum}.pdf`);
        }

        function exportToCSV() {
            if (!currentInvoiceData || currentInvoiceData.length === 0) {
                Swal.fire('Error', 'No data available to export.', 'error');
                return;
            }
            const hourlyRate = parseFloat(document.getElementById('currentRate').textContent.replace(/,/g, ''));
            const invoiceNum = document.getElementById('invoiceNumber').textContent || 'DRAFT';
            let csvContent = "Date,Client/Project,Hours,Rate,Billable,Description,Amount\n";
            let totalHours = 0, billableHours = 0, subtotalAmount = 0;
            currentInvoiceData.forEach(entry => {
                const hours = parseFloat(entry.hours_worked);
                totalHours += hours;
                let amount = 0;
                if (entry.billable === 'Yes') {
                    billableHours += hours;
                    amount = hours * hourlyRate;
                    subtotalAmount += amount;
                }
                const cleanDescription = `"${entry.description.replace(/"/g, '""')}"`;
                const cleanClient = `"${entry.client_project.replace(/"/g, '""')}"`;
                const row = [
                    entry.work_date,
                    cleanClient,
                    hours.toFixed(2),
                    hourlyRate.toFixed(2),
                    entry.billable,
                    cleanDescription,
                    amount.toFixed(2)
                ];
                csvContent += row.join(",") + "\n";
            });
            const taxRate = 0.15;
            const taxAmount = subtotalAmount * taxRate;
            const finalAmount = subtotalAmount + taxAmount;
            csvContent += "\n";
            csvContent += `,,Total Hours,${totalHours.toFixed(2)},,,Subtotal,${subtotalAmount.toFixed(2)}\n`;
            csvContent += `,,,,,,VAT (15%),${taxAmount.toFixed(2)}\n`;
            csvContent += `,,,,,,Total Due,${finalAmount.toFixed(2)}\n`;
            const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = `Invoice_${invoiceNum}.csv`;
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function sendInvoice() {
            Swal.fire('Info', 'Send invoice functionality needs to be implemented.', 'info');
        }

        // === Theme & Mobile Menu (copied from dashboard) ===
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

        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>