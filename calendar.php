<?php
include('config.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch full name for welcome
$user_id = $_SESSION['user_id'];
$fullname = '';
$sql = "SELECT fullname FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname);
$stmt->fetch();
$stmt->close();

// 🔹 Fetch Interviews
$interview_sql = "
    SELECT 
        i.interview_id,
        i.interview_date,
        u.fullname AS candidate_name,
        jp.position
    FROM interviews i
    JOIN users u ON i.user_id = u.user_id
    JOIN job_postings jp ON i.job_id = jp.job_id
    WHERE i.interview_status IN ('Scheduled', 'Rescheduled')
";
$interview_result = $conn->query($interview_sql);
$total_interviews = $interview_result ? $interview_result->num_rows : 0;

// 🔹 Fetch Approved Leaves
$leave_sql = "SELECT COUNT(*) as count FROM consultant_leaves WHERE status = 'Approved'";
$leave_count = $conn->query($leave_sql)->fetch_assoc()['count'];

// 🔹 Fetch Simulated Job Deadlines (30 days after posting)
$deadline_sql = "
    SELECT COUNT(*) as count 
    FROM job_postings 
    WHERE job_status = 'Active' 
      AND DATE_ADD(date_posted, INTERVAL 30 DAY) >= CURDATE()
";
$deadline_count = $conn->query($deadline_sql)->fetch_assoc()['count'];

// Prepare events for FullCalendar
$events = [];

// Add interviews
if ($interview_result) {
    while ($row = $interview_result->fetch_assoc()) {
        $interview_date = new DateTime($row['interview_date']);
        $events[] = [
            'title' => "Interview: {$row['candidate_name']} - {$row['position']}",
            'start' => $interview_date->format('Y-m-d\TH:i:s'),
            'url' => 'view_interview.php?id=' . $row['interview_id'],
            'className' => 'event-interview'
        ];
    }
}

// Add approved leaves
$leave_sql = "
    SELECT cl.start_date, cl.end_date, u.fullname, cl.leave_type
    FROM consultant_leaves cl
    JOIN users u ON cl.user_id = u.user_id
    WHERE cl.status = 'Approved'
";
$leave_result = $conn->query($leave_sql);
if ($leave_result) {
    while ($row = $leave_result->fetch_assoc()) {
        $events[] = [
            'title' => "Leave: {$row['fullname']} ({$row['leave_type']})",
            'start' => $row['start_date'],
            'end' => date('Y-m-d', strtotime($row['end_date'] . ' +1 day')),
            'className' => 'event-leave'
        ];
    }
}

// Add job deadlines (simulated)
$deadline_sql = "
    SELECT position, DATE_ADD(date_posted, INTERVAL 30 DAY) AS closing_date
    FROM job_postings 
    WHERE job_status = 'Active' AND DATE_ADD(date_posted, INTERVAL 30 DAY) >= CURDATE()
";
$deadline_result = $conn->query($deadline_sql);
if ($deadline_result) {
    while ($row = $deadline_result->fetch_assoc()) {
        $events[] = [
            'title' => "Deadline: {$row['position']}",
            'start' => $row['closing_date'],
            'className' => 'event-deadline'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Calendar - Admin Dashboard</title>
    <style>
        /* ===========================
           GLOBAL RESET & VARIABLES (from admin_dashboard.php)
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

        /* SIDEBAR */
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

        /* MAIN CONTENT */
        .content {
            flex: 1;
            margin-left: 280px;
            transition: var(--transition);
        }
        .sidebar.collapsed ~ .content {
            margin-left: 80px;
        }

        /* NAVBAR */
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
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 28px;
            color: var(--gray);
            cursor: pointer;
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

        /* MAIN */
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

        /* INSIGHTS */
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
            flex-direction: column;
            align-items: center;
            gap: 12px;
            transition: var(--transition);
        }
        body.dark-mode .insights li {
            background: #242526;
        }
        .insights li:hover {
            transform: translateY(-5px);
        }
        .insights i {
            font-size: 32px;
            color: var(--primary);
            background: rgba(102, 126, 234, 0.1);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .info h3 {
            font-size: 24px;
            margin: 0;
        }
        .info p {
            color: var(--gray);
            font-size: 16px;
        }
        body.dark-mode .info p {
            color: #adb5bd;
        }

        /* CALENDAR */
        #calendar {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
        }
        body.dark-mode #calendar {
            background: #242526;
            color: #e4e6eb;
        }
        /* FullCalendar overrides */
        .fc {
            font-family: inherit !important;
        }
        .fc-toolbar-title,
        .fc-daygrid-day-number,
        .fc-timegrid-slot-label,
        .fc-col-header-cell {
            color: var(--dark) !important;
        }
        body.dark-mode .fc-toolbar-title,
        body.dark-mode .fc-daygrid-day-number,
        body.dark-mode .fc-timegrid-slot-label,
        body.dark-mode .fc-col-header-cell {
            color: #e4e6eb !important;
        }
        .fc-button {
            background: var(--light-gray) !important;
            border-color: var(--light-gray) !important;
            color: var(--dark) !important;
            border-radius: 6px !important;
        }
        body.dark-mode .fc-button {
            background: #3a3b3c !important;
            border-color: #3a3b3c !important;
            color: #e4e6eb !important;
        }
        .fc-button-primary {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
        }
        .fc-h-event {
            border: none !important;
            cursor: pointer;
        }
        .event-interview { background: #3498db !important; color: white !important; }
        .event-leave     { background: #9b59b6 !important; color: white !important; }
        .event-deadline  { background: #e74c3c !important; color: white !important; }

        /* MOBILE MENU OVERLAY */
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

        @media (max-width: 768px) {
            .mobile-nav-links {
                display: flex; /* show only on tablets/phones */
            }
        }

        /* RESPONSIVE */
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
            .mobile-menu-btn {
                display: block;
            }
            .form-input {
                width: 100%;
            }
            nav {
                padding: 16px;
            }
            .insights {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .welcome-section h1 {
                font-size: 24px;
            }
            .insights i {
                width: 50px;
                height: 50px;
                font-size: 26px;
            }
            .info h3 {
                font-size: 20px;
            }
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

    <!-- Mobile Overlay -->
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
            <li class="active"><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
            <li class="section-header"><span>Consultants</span></li>
            <li><a href="admin_view_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="admin_view_tasklogs.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li><a href="admin_view_leaves.php"><i class='bx bx-calendar-minus'></i><span>Leaves</span></a></li>
            <li><a href="admin_invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="admin_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
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
                <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Manage Jobs</a>
                <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
                <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
                <a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a>
                <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
                <a href="admin_client_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>
                <a class="active" href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
                <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
            </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1> Calendar</h1>
                <p>View and manage scheduled interviews, meetings, deadlines, and important recruitment events in one place.</p>
            </div>

            <!-- Insights -->
            <ul class="insights">
                <li>
                    <i class='bx bx-group'></i>
                    <span class="info">
                        <h3><?= $total_interviews; ?></h3>
                        <p>Scheduled Interviews</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-calendar-minus'></i>
                    <span class="info">
                        <h3><?= $leave_count; ?></h3>
                        <p>Approved Leaves</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-calendar-event'></i>
                    <span class="info">
                        <h3><?= $deadline_count; ?></h3>
                        <p>Job Deadlines</p>
                    </span>
                </li>
            </ul>

            <!-- Calendar Title -->
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="font-size: 24px; font-weight: 600; color: var(--dark);">
                    <i class='bx bx-calendar'></i> Calendar Overview
                </h2>
            </div>

            <!-- Calendar Container -->
            <div id="calendar">
                <div style="text-align: center; padding: 50px; color: var(--gray);">
                    <i class='bx bx-calendar' style="font-size: 48px; margin-bottom: 20px;"></i>
                    <h3>Loading Calendar...</h3>
                    <p>If you see this message, the calendar is loading. Please wait or refresh the page.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>

    <script>
        // Mobile menu
        document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mobileMenuOverlay').style.display =
                document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none';
        });
        document.getElementById('mobileMenuOverlay')?.addEventListener('click', function() {
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

        // Theme toggle
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                const currentTheme = localStorage.getItem('theme');
                if (currentTheme) {
                    themeToggle.checked = (currentTheme === 'dark');
                    if (currentTheme === 'dark') {
                        document.body.classList.add('dark-mode');
                    }
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

            // Initialize calendar after ensuring FullCalendar is loaded
            function initializeCalendar() {
                console.log('Initializing calendar...');

                // Check if FullCalendar is available
                if (typeof FullCalendar === 'undefined') {
                    console.error('FullCalendar library not loaded');
                    document.getElementById('calendar').innerHTML = `
                        <div style="text-align: center; padding: 50px; color: var(--gray);">
                            <i class='bx bx-error' style="font-size: 48px; margin-bottom: 20px; color: #e74c3c;"></i>
                            <h3>Calendar Library Not Loaded</h3>
                            <p>Please check your internet connection and refresh the page.</p>
                        </div>
                    `;
                    return;
                }

                const events = <?= json_encode($events); ?>;
                console.log('Events loaded:', events);

                const calendarEl = document.getElementById('calendar');
                if (!calendarEl) {
                    console.error('Calendar element not found');
                    return;
                }

                try {
                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                        events: events,
                        editable: false,
                        selectable: false,
                        dayMaxEvents: true,
                        eventDisplay: 'block',
                        height: 600,
                        nowIndicator: true,
                        eventClick: function(info) {
                            if (info.event.url) {
                                window.open(info.event.url, '_self');
                                return false; // Prevent default behavior
                            }
                        }
                    });

                    calendar.render();
                    console.log('Calendar rendered successfully');

                    // Clear loading message
                    const loadingDiv = calendarEl.querySelector('.loading-message');
                    if (loadingDiv) {
                        loadingDiv.remove();
                    }

                } catch (error) {
                    console.error('Error initializing calendar:', error);
                    calendarEl.innerHTML = `
                        <div style="text-align: center; padding: 50px; color: var(--gray);">
                            <i class='bx bx-error' style="font-size: 48px; margin-bottom: 20px; color: #e74c3c;"></i>
                            <h3>Calendar Initialization Failed</h3>
                            <p>Error: ${error.message}</p>
                        </div>
                    `;
                }
            }

            // Wait for FullCalendar to load, then initialize
            if (typeof FullCalendar !== 'undefined') {
                initializeCalendar();
            } else {
                // Wait for script to load
                const checkFullCalendar = setInterval(() => {
                    if (typeof FullCalendar !== 'undefined') {
                        clearInterval(checkFullCalendar);
                        initializeCalendar();
                    }
                }, 100);

                // Timeout after 10 seconds
                setTimeout(() => {
                    clearInterval(checkFullCalendar);
                    if (typeof FullCalendar === 'undefined') {
                        console.error('FullCalendar failed to load within timeout');
                        document.getElementById('calendar').innerHTML = `
                            <div style="text-align: center; padding: 50px; color: var(--gray);">
                                <i class='bx bx-error' style="font-size: 48px; margin-bottom: 20px; color: #e74c3c;"></i>
                                <h3>Calendar Library Failed to Load</h3>
                                <p>Please check your internet connection and refresh the page.</p>
                            </div>
                        `;
                    }
                }, 10000);
            }
        });

        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>