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
$message = '';
$messageClass = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_invoice'])) {
    $invoice_number = $_POST['invoice_number'];
    $client_project = $_POST['client_project'];
    $invoice_date = $_POST['invoice_date'];
    $due_date = $_POST['due_date'];
    $total_hours = $_POST['total_hours'];
    $hourly_rate = $_POST['hourly_rate'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    
    // Calculate amount due
    $amount_due = $total_hours * $hourly_rate;
    
    $update_sql = "UPDATE invoices SET 
                   invoice_number = ?, client_project = ?, invoice_date = ?, 
                   due_date = ?, total_hours = ?, hourly_rate = ?, 
                   amount_due = ?, status = ?, notes = ?, updated_at = NOW()
                   WHERE invoice_id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssdddssi", $invoice_number, $client_project, $invoice_date,
                            $due_date, $total_hours, $hourly_rate, $amount_due, $status, $notes, $invoice_id);
    
    if ($update_stmt->execute()) {
        $message = "Invoice updated successfully!";
        $messageClass = "success";
    } else {
        $message = "Error updating invoice: " . $conn->error;
        $messageClass = "error";
    }
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Edit Invoice - <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>

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
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
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
                display: flex;
            }
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
           CARD STYLES
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

        /* ===========================
           ALERTS
        ============================ */
        .alert {
            padding: 14px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
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

        /* ===========================
           FORM STYLES
        ============================ */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 15px;
            color: var(--dark);
        }

        body.dark-mode .form-group label {
            color: #e4e6eb;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
            background: white;
        }

        body.dark-mode .form-group input,
        body.dark-mode .form-group select,
        body.dark-mode .form-group textarea {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4b4d;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* ===========================
           READONLY INFO BOX
        ============================ */
        .readonly-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            border-left: 4px solid var(--primary);
        }

        body.dark-mode .readonly-info {
            background: #2d2e2f;
        }

        .readonly-info h4 {
            color: var(--primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .readonly-info p {
            margin-bottom: 8px;
            font-size: 15px;
        }

        body.dark-mode .readonly-info p {
            color: #adb5bd;
        }

        .readonly-info strong {
            color: var(--dark);
        }

        body.dark-mode .readonly-info strong {
            color: #e4e6eb;
        }

        /* ===========================
           CALCULATED FIELD
        ============================ */
        .calculated-field {
            background: #e8f4f8;
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            color: var(--primary);
            border: 2px solid #d1ecf1;
        }

        body.dark-mode .calculated-field {
            background: #1a3a4a;
            border-color: #1e4a5c;
        }

        /* ===========================
           BUTTONS
        ============================ */
        .btn {
            padding: 12px 24px;
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

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background-color: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* ===========================
           RESPONSIVE DESIGN
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

            .mobile-menu-btn {
                display: block;
            }

            .mobile-nav-links {
                display: flex;
            }

            .card {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                padding: 20px;
            }

            .welcome-content h1 {
                font-size: 24px;
            }

            .welcome-content p {
                font-size: 16px;
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
                <div class="welcome-content">
                    <h1> Edit Invoice</h1>
                    <p>Modify invoice details, update billing items, adjust amounts, and manage payment information before finalizing.</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <i class='bx <?php echo $messageClass === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Consultant Information -->
            <div class="card">
                <h2><i class='bx bx-user'></i> Consultant Information</h2>
                <div class="readonly-info">
                    <h4><i class='bx bx-user-circle'></i> Consultant Details</h4>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($invoice['consultant_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($invoice['consultant_email']); ?></p>
                    <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($invoice['created_at'])); ?></p>
                </div>

                <!-- Edit Form -->
                <h2><i class='bx bx-edit-alt'></i> Invoice Details</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="invoice_number">Invoice Number:</label>
                            <input type="text" id="invoice_number" name="invoice_number" 
                                   value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" required>
                                <option value="Pending" <?php echo ($invoice['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Paid" <?php echo ($invoice['status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="Overdue" <?php echo ($invoice['status'] == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                                <option value="Cancelled" <?php echo ($invoice['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="client_project">Client/Project:</label>
                        <input type="text" id="client_project" name="client_project" 
                               value="<?php echo htmlspecialchars($invoice['client_project']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="invoice_date">Invoice Date:</label>
                            <input type="date" id="invoice_date" name="invoice_date" 
                                   value="<?php echo $invoice['invoice_date']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="due_date">Due Date:</label>
                            <input type="date" id="due_date" name="due_date" 
                                   value="<?php echo $invoice['due_date']; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="total_hours">Total Hours:</label>
                            <input type="number" id="total_hours" name="total_hours" step="0.1" min="0"
                                   value="<?php echo $invoice['total_hours']; ?>" required
                                   onchange="calculateAmount()">
                        </div>
                        
                        <div class="form-group">
                            <label for="hourly_rate">Hourly Rate (R):</label>
                            <input type="number" id="hourly_rate" name="hourly_rate" step="0.01" min="0"
                                   value="<?php echo $invoice['hourly_rate']; ?>" required
                                   onchange="calculateAmount()">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="amount_due">Amount Due (Calculated):</label>
                        <input type="text" id="amount_due" class="calculated-field" readonly
                               value="R<?php echo number_format($invoice['amount_due'], 2); ?>">
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes" placeholder="Additional notes or comments..."><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
                    </div>

                    <div style="margin-top: 30px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <button type="submit" name="update_invoice" class="btn btn-primary">
                            <i class='bx bx-save'></i> Update Invoice
                        </button>
                        <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="btn btn-secondary">
                            <i class='bx bx-show'></i> View Invoice
                        </a>
                        <a href="admin_invoices.php" class="btn btn-secondary">
                            <i class='bx bx-arrow-back'></i> Back to Invoices
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function calculateAmount() {
            const hours = parseFloat(document.getElementById('total_hours').value) || 0;
            const rate = parseFloat(document.getElementById('hourly_rate').value) || 0;
            const amount = hours * rate;
            
            document.getElementById('amount_due').value = 'R' + amount.toFixed(2);
        }

        // === Theme & Mobile Menu ===
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
    </script>
</body>
</html>
