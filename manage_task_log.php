<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Task Logs</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- SweetAlert2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css"> 

    <style>
        /* ===========================
           GLOBAL RESET & VARIABLES (copied from dashboard)
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
           MAIN CONTENT AREA
        ============================ */
        main {
            padding: 24px;
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
           FORM STYLING (aligned with dashboard aesthetics)
        ============================ */
        .form-container {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-top: 0;
        }
        body.dark-mode .form-container {
            background: #242526;
        }

        .header {
            margin-bottom: 24px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 8px;
        }
        .breadcrumb {
            list-style: none;
            display: flex;
            justify-content: center;
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
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background: white;
            transition: var(--transition);
            margin-bottom: 6px;
        }
        body.dark-mode input,
        body.dark-mode textarea,
        body.dark-mode select {
            background: #3a3b3c;
            border-color: #4a4b4d;
            color: #e4e6eb;
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

        .button-container {
            text-align: center;
            margin-top: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-top: 24px;
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

        .tools {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        /* ===========================
           MODAL (updated to match theme)
        ============================ */
        #leaveWarningModal {
            display: none;
            position: fixed;
            top: 20%;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 9999;
            max-width: 450px;
            width: 90%;
            text-align: center;
        }
        body.dark-mode #leaveWarningModal {
            background: #2d2e2f;
            color: #e4e6eb;
        }
        #leaveWarningModal h3 {
            color: var(--danger);
            margin-bottom: 16px;
            font-size: 20px;
        }
        #warningMessage {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        #leaveWarningModal button {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
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
            .form-container {
                padding: 16px;
            }
            .header h1 {
                font-size: 22px;
            }
            .tools {
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
            .tools {
                gap: 10px;
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
            <li class="active"><a href="manage_task_log.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
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
            <a class="active" href="manage_task_log.php"><i class='bx bx-time-five'></i> Tasklogs</a>
            <a href="manage_leave.php"><i class='bx bx-calendar-minus'></i> Leave</a>
            <a href="invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="training_management.php"><i class='bx bx-book-reader'></i> Training</a>
            <!--<a href="consultant_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>-->
            <a href="consultant_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Tasklogs</h1>
                <p>Record and manage your daily work activities. Track progress, submit reports, and monitor task status efficiently.</p>
            </div>

            <div class="form-container">
                <form id="tasklogForm" method="POST">
                    <div class="form-group">
                        <label for="work_date">Date:</label>
                        <input type="date" name="work_date" id="work_date" required>
                        <div class="error-message" id="dateError"></div>
                    </div>

                    <div class="form-group">
                        <label for="client_project">Client/Project:</label>
                        <input type="text" name="client_project" id="client_project" placeholder="e.g. ABC Corp - Website Design" required>
                        <div class="error-message" id="projectError"></div>
                    </div>

                    <div class="form-group">
                        <label for="summary">Description of Work:</label>
                        <textarea name="summary" id="summary" rows="3" placeholder="Brief description of work done" required></textarea>
                        <div class="error-message" id="summaryError"></div>
                    </div>

                    <div class="form-group">
                        <label for="challenges">Challenges Faced:</label>
                        <textarea name="challenges" id="challenges" rows="2" placeholder="Any obstacles or difficulties encountered"></textarea>
                        <div class="error-message" id="challengesError"></div>
                    </div>

                    <div class="form-group">
                        <label for="support">Support Needed:</label>
                        <textarea name="support" id="support" rows="2" placeholder="Any assistance or resources needed"></textarea>
                        <div class="error-message" id="supportError"></div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea name="notes" id="notes" rows="2" placeholder="Anything worth documenting for later review"></textarea>
                        <div class="error-message" id="notesError"></div>
                    </div>

                    <div class="button-container">
                        <button type="submit" class="btn" id="submitBtn">
                            <i class='bx bx-save'></i> Submit Task Log
                        </button>
                    </div>
                </form>

                <h3 style="margin: 24px 0 16px; color: var(--dark);">Your Task Log Entries</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client/Project</th>
                                <th>Description</th>
                                <th>Challenges</th>
                                <th>Support Needed</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" style="text-align:center; padding: 20px;">No task log entries found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tools">
                    <button class="btn" onclick="generateWeeklyReport()">
                        <i class='bx bx-calendar'></i> Generate Weekly Report
                    </button>
                    <button class="btn" onclick="setReminder()">
                        <i class='bx bx-bell'></i> Set Reminder
                    </button>
                    <a href="consultant_dashboard.php" class="btn">
                        <i class='bx bx-arrow-back'></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </main>
    </div>

    <!-- Serious Leave Warning Modal -->
    <div id="leaveWarningModal">
        <h3><i class='bx bx-calendar-minus'></i> Conflict Detected!</h3>
        <p id="warningMessage"></p>
        <button onclick="document.getElementById('leaveWarningModal').style.display = 'none';">Close</button>
    </div>

    <script>
        // --- Keep ALL your existing JS logic BELOW ---
        // DO NOT MODIFY THIS SECTION – only styling changed above
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('tasklogForm');
            const dateInput = document.getElementById('work_date');
            const projectInput = document.getElementById('client_project');
            const supportInput = document.getElementById('support');
            const challengesInput = document.getElementById('challenges');
            const summaryInput = document.getElementById('summary');
            const notesInput = document.getElementById('notes');
            const submitBtn = document.getElementById('submitBtn');

            let validationState = {
                date: false,
                project: false,
                summary: false,
                // Optional fields - default to true since they're not required by PHP
                support: true,
                challenges: true,
                notes: true
            };

            dateInput.addEventListener('change', validateDate);
            projectInput.addEventListener('input', validateProject);
            supportInput.addEventListener('input', validateSupport);
            challengesInput.addEventListener('input', validateChallenges);
            summaryInput.addEventListener('input', validateSummary);
            notesInput.addEventListener('input', validateNotes);

            document.getElementById("exitPage").addEventListener("click", function () {
                window.history.back();
            });

            let leaveConflict = false;

            function validateDate() {
                const value = dateInput.value.trim();
                
                if (!value) {
                    showError('work_date', 'dateError', 'Date is required');
                    validationState.date = false;
                    leaveConflict = false;
                } else {
                    // Simple validation - just check if date is not empty
                    // The backend will handle holiday/weekend/leave validation
                    showSuccess('work_date', 'dateError');
                    validationState.date = true;
                    leaveConflict = false;
                }
                updateButtonState();
            }

            function showLeaveWarningModal(date, leaveType) {
                const modal = document.getElementById('leaveWarningModal');
                const msg = document.getElementById('warningMessage');
                msg.innerHTML = `
                    You cannot submit the task log for <strong>${date}</strong>.<br><br>
                    You are currently on <strong>${leaveType}</strong>.<br><br>
                    Please select another working day.
                `;
                modal.style.display = 'block';
            }

            function validateProject() {
                const value = projectInput.value.trim();
                if (!value) {
                    showError('client_project', 'projectError', 'Client/Project is required');
                    validationState.project = false;
                } else if (value.length < 2) {
                    showError('client_project', 'projectError', 'Must be at least 2 characters');
                    validationState.project = false;
                } else {
                    showSuccess('client_project', 'projectError');
                    validationState.project = true;
                }
                updateButtonState();
            }

            function validateSummary() {
                const value = summaryInput.value.trim();
                if (!value) {
                    showError('summary', 'summaryError', 'Summary is required');
                    validationState.summary = false;
                } else if (value.length < 10) {
                    showError('summary', 'summaryError', 'At least 10 characters required');
                    validationState.summary = false;
                } else {
                    showSuccess('summary', 'summaryError');
                    validationState.summary = true;
                }
                updateButtonState();
            }

            function validateChallenges() {
                const value = challengesInput.value.trim();
                if (!value) {
                    showError('challenges', 'challengesError', 'Challenges is required');
                    validationState.challenges = false;
                } else if (value.length < 10) {
                    showError('challenges', 'challengesError', 'At least 10 characters required');
                    validationState.challenges = false;
                } else {
                    showSuccess('challenges', 'challengesError');
                    validationState.challenges = true;
                }
                updateButtonState();
            }

            function validateSupport() {
                const value = supportInput.value.trim();
                if (!value) {
                    showError('support', 'supportError', 'Support is required');
                    validationState.support = false;
                } else if (value.length < 10) {
                    showError('support', 'supportError', 'At least 10 characters required');
                    validationState.support = false;
                } else {
                    showSuccess('support', 'supportError');
                    validationState.support = true;
                }
                updateButtonState();
            }

            function validateNotes() {
                const value = notesInput.value.trim();
                if (!value) {
                    showError('notes', 'notesError', 'Notes is required');
                    validationState.notes = false;
                } else if (value.length < 10) {
                    showError('notes', 'notesError', 'At least 10 characters required');
                    validationState.notes = false;
                } else {
                    showSuccess('notes', 'notesError');
                    validationState.notes = true;
                }
                updateButtonState();
            }

            function updateButtonState() {
                const allValid = Object.values(validationState).every(Boolean);
                submitBtn.disabled = !allValid;
                if (allValid) {
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                } else {
                    submitBtn.style.opacity = '0.6';
                    submitBtn.style.cursor = 'not-allowed';
                }
            }

            function showError(inputId, errorId, message) {
                const input = document.getElementById(inputId);
                const error = document.getElementById(errorId);
                input.classList.remove('success');
                input.classList.add('error');
                error.textContent = message;
                error.style.display = 'block';
            }

            function showSuccess(inputId, errorId) {
                const input = document.getElementById(inputId);
                const error = document.getElementById(errorId);
                input.classList.remove('error');
                input.classList.add('success');
                error.style.display = 'none';
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                console.log('Form submit clicked');
                console.log('validationState:', validationState);
                validateDate();
                validateProject();
                validateSummary();
                console.log('validationState after validation:', validationState);

                const allValid = Object.values(validationState).every(Boolean);
                console.log('allValid:', allValid);
                if (!allValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please fix all errors before submitting.',
                        confirmButtonColor: '#2980b9'
                    });
                    return;
                }

                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Saving...';
                submitBtn.disabled = true;

                const formData = new FormData(form);

                fetch('submit_task_log.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Submitted!',
                            text: data.message || 'Task log has been submitted successfully.',
                            confirmButtonColor: '#2980b9'
                        }).then(() => {
                            form.reset();
                            clearAllValidations();
                            updateButtonState();
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Submission Failed',
                            text: data.error || 'Something went wrong.',
                            confirmButtonColor: '#e74c3c'
                        });
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Failed to connect to server. Please try again.',
                        confirmButtonColor: '#e74c3c'
                    });
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });

            function clearAllValidations() {
                ['work_date', 'client_project', 'summary', 'challenges', 'support', 'notes'].forEach(id => {
                    const input = document.getElementById(id);
                    const errorId = id === 'work_date' ? 'dateError' : 
                                   id === 'client_project' ? 'projectError' :
                                   id === 'summary' ? 'summaryError' :
                                   id === 'challenges' ? 'challengesError' :
                                   id === 'support' ? 'supportError' : 'notesError';
                    const error = document.getElementById(errorId);
                    if (input) input.classList.remove('success', 'error');
                    if (error) error.style.display = 'none';
                });
                validationState = {
                    date: false,
                    project: false,
                    summary: false,
                    challenges: false,
                    support: false,
                    notes: false
                };
            }
        });

        function setReminder() {
            Swal.fire({
                icon: 'info',
                title: 'Reminder Set',
                text: 'A weekly reminder has been set for task log submission.',
                confirmButtonColor: '#2980b9'
            });
        }

        function generateWeeklyReport() {
            Swal.fire({
                icon: 'info',
                title: 'Coming Soon',
                text: 'Weekly report generation will be available soon.',
                confirmButtonColor: '#2980b9'
            });
        }

        // Theme toggle (copied from dashboard)
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

        // Mobile menu toggle
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

        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }
    </script>
</body>
</html>