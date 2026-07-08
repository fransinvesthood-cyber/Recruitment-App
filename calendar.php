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

// ðŸ”¹ Fetch Interviews
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

// ðŸ”¹ Fetch Approved Leaves
$leave_sql = "SELECT COUNT(*) as count FROM consultant_leaves WHERE status = 'Approved'";
$leave_count = $conn->query($leave_sql)->fetch_assoc()['count'];

// ðŸ”¹ Fetch Simulated Job Deadlines (30 days after posting)
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

// Add created calendar events (from calendar_events table)
$custom_events_sql = "
    SELECT event_id, title, description, event_type, event_date, start_time, end_time
    FROM calendar_events
    ORDER BY event_date ASC, start_time ASC, event_id ASC
";
/*$custom_events_result = $conn->query($custom_events_sql);
if ($custom_events_result) {
    while ($row = $custom_events_result->fetch_assoc()) {
        $start = $row['event_date'];
        $end = $row['event_date'];
        $allDay = empty($row['start_time']);

        if (!empty($row['start_time'])) {
            $start = $row['event_date'] . 'T' . $row['start_time'];
        }
        if (!empty($row['end_time'])) {
            $end = $row['event_date'] . 'T' . $row['end_time'];
        } else {
            $end = null;
        }

        // Use extendedProps to show description on click if needed
        $events[] = [
            'id' => (int)$row['event_id'],
            'title' => $row['title'],
            'start' => $start,
            'end' => $end,
            'allDay' => $allDay,
            'className' => 'event-' . strtolower($row['event_type']),
            'extendedProps' => [
                'description' => $row['description'],
                'event_type' => $row['event_type'],
                'event_date' => $row['event_date'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time']
            ]
        ];
    }
}*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="includes/admin-layout.css">
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
        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 10px 24px rgba(0,0,0,0.28);
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
        .fc-event {
            border: none !important;
            border-radius: 10px !important;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
            padding: 2px 4px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .fc-event:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.16);
        }
        .fc-event-title {
            font-weight: 700;
            font-size: 0.86rem;
            line-height: 1.25;
        }
        .fc-event-time {
            font-size: 0.76rem;
            opacity: 0.95;
        }
        .event-interview { background: linear-gradient(135deg, #3b82f6, #2563eb) !important; color: white !important; }
        .event-training { background: linear-gradient(135deg, #8b5cf6, #7c3aed) !important; color: white !important; }
        .event-meeting { background: linear-gradient(135deg, #10b981, #059669) !important; color: white !important; }
        .event-reminder { background: linear-gradient(135deg, #f59e0b, #d97706) !important; color: white !important; }
        .event-other { background: linear-gradient(135deg, #64748b, #475569) !important; color: white !important; }
        .event-leave     { background: linear-gradient(135deg, #ec4899, #be185d) !important; color: white !important; }
        .event-deadline  { background: linear-gradient(135deg, #ef4444, #dc2626) !important; color: white !important; }

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

        /* MODAL (Create Event) */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal {
            width: 100%;
            max-width: 680px;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        body.dark-mode .modal {
            background: #242526;
            color: #e4e6eb;
        }
        .modal-header {
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
        }
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            display:flex;
            align-items:center;
            gap:8px;
        }
        .modal-close {
            background: rgba(0,0,0,0.2);
            border: none;
            color: var(--white);
            border-radius: 8px;
            cursor: pointer;
            padding: 8px 12px;
            font-size: 14px;
        }
        .modal-body {
            padding: 18px;
        }
        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 700px) {
            .modal-grid { grid-template-columns: 1fr; }
        }
        .field label {
            display:block;
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 6px;
            font-weight: 600;
        }
        body.dark-mode .field label { color: #adb5bd; }
        .field input, .field select, .field textarea {
            width: 100%;
            background: var(--light-gray);
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
            color: inherit;
        }
        body.dark-mode .field input, body.dark-mode .field select, body.dark-mode .field textarea {
            background: #3a3b3c;
            border-color: #3a3b3c;
            color: #e4e6eb;
        }
        .field textarea { min-height: 100px; resize: vertical; }
        .modal-footer {
            padding: 16px 18px;
            display:flex;
            justify-content:flex-end;
            gap: 12px;
            border-top: 1px solid rgba(0,0,0,0.06);
        }
        body.dark-mode .modal-footer { border-top-color: rgba(255,255,255,0.08); }
        .btn {
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
        }
        .btn-secondary { background: var(--light-gray); color: var(--dark); }
        body.dark-mode .btn-secondary { background: #3a3b3c; color:#e4e6eb; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:disabled { opacity: 0.7; cursor:not-allowed; }
        .form-error {
            margin-top: 10px;
            color: var(--danger);
            font-weight: 700;
            display: none;
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
   <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="#" class="logo">
            <i class='bx bx-user-circle'></i>
            <div class="logo-name"><span>Admin</span></div>
        </a>
        <ul class="side-menu main-menu">
            <li><a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li class="section-header"><span>Candidates</span></li>
            <li><a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i><span>Jobs</span></a></li>
            <li><a href="manage_applications.php"><i class='bx bx-file'></i><span>Applications</span></a></li>
            <li><a href="admin_user_management.php"><i class='bx bx-user'></i><span>Users</span></a></li>
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
        <ul class="side-menu bottom-menu">
            <li>
                <a href="logout.php" class="logout" onclick="return confirmLogout();">
                    <i class='bx bx-log-out-circle'></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Mobile Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

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
            <div style="text-align: center; margin-bottom: 20px; display:flex; align-items:center; justify-content: center; gap: 16px; flex-wrap: wrap;">

                <button id="createEventBtn" type="button" class="fc-button fc-button-primary" style="border-radius: 8px; padding: 10px 16px; display:inline-flex; align-items:center; gap:8px;">
                    <i class='bx bx-plus-medical'></i>
                    Create Event
                </button>
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

    <!-- Create Event Modal -->
    <div class="modal-overlay" id="createEventModalOverlay" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="createEventModalTitle">
            <div class="modal-header">
                <div class="modal-title" id="createEventModalTitle"><i class='bx bx-plus-medical'></i> Create Event</div>
                <button type="button" class="modal-close" id="createEventModalClose" aria-label="Close">✕</button>
            </div>
            <form id="createEventForm" method="POST">
                <div class="modal-body">
                    <div class="modal-grid">
                        <div class="field">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" maxlength="255" required />
                        </div>

                        <div class="field">
                            <label for="event_type">Event Type</label>
                            <select id="event_type" name="event_type">
                                <option value="Interview">Interview</option>
                                <option value="Training">Training</option>
                                <option value="Meeting">Meeting</option>
                                <option value="Reminder">Reminder</option>
                                <option value="Other" selected>Other</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="event_date">Event Date *</label>
                            <input type="date" id="event_date" name="event_date" required />
                        </div>

                        <div class="field">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time" />
                        </div>

                        <div class="field">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time" />
                        </div>

                        <div class="field" style="grid-column: 1 / -1;">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" maxlength="1000" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>

                    <div id="createEventError" class="form-error"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="createEventCancelBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createEventSubmitBtn">Save Event</button>
                </div>
            </form>
        </div>
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
                            const isCustomEvent = info.event.extendedProps && info.event.extendedProps.event_type;
                            if (isCustomEvent) {
                                const confirmed = confirm('Delete this event?');
                                if (!confirmed) {
                                    return false;
                                }

                                const formData = new FormData();
                                formData.append('event_id', info.event.id);

                                fetch('delete_event.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data && data.success) {
                                        info.event.remove();
                                        alert('Event deleted successfully.');
                                    } else {
                                        alert((data && data.error) ? data.error : 'Failed to delete event.');
                                    }
                                })
                                .catch(() => {
                                    alert('Failed to delete event.');
                                });

                                return false;
                            }

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

        // ----------------------------
        // Create Event Modal (Calendar)
        // ----------------------------
        function openCreateEventModal(prefillDate) {
            const overlay = document.getElementById('createEventModalOverlay');
            if (!overlay) return;

            const form = document.getElementById('createEventForm');
            const eventDateInput = document.getElementById('event_date');
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');
            const titleInput = document.getElementById('title');
            const typeSelect = document.getElementById('event_type');
            const descInput = document.getElementById('description');
            const errorDiv = document.getElementById('createEventError');

            errorDiv && (errorDiv.style.display = 'none');
            errorDiv && (errorDiv.textContent = '');

            form.reset();

            if (prefillDate) {
                eventDateInput.value = prefillDate;
            } else {
                // default to today
                const d = new Date();
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                eventDateInput.value = `${yyyy}-${mm}-${dd}`;
            }

            // make time empty by default
            startTimeInput.value = '';
            endTimeInput.value = '';

            // default type
            if (typeSelect) typeSelect.value = 'Other';
            if (titleInput) titleInput.value = '';
            if (descInput) descInput.value = '';

            overlay.classList.add('active');

            // focus title for accessibility
            setTimeout(() => titleInput && titleInput.focus(), 50);
        }

        function closeCreateEventModal() {
            const overlay = document.getElementById('createEventModalOverlay');
            if (!overlay) return;
            overlay.classList.remove('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const createBtn = document.getElementById('createEventBtn');
            if (createBtn) {
                createBtn.addEventListener('click', function() {
                    openCreateEventModal();
                });
            }

            const overlay = document.getElementById('createEventModalOverlay');
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) closeCreateEventModal();
                });
            }

            const closeBtn = document.getElementById('createEventModalClose');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeCreateEventModal);
            }

            const cancelBtn = document.getElementById('createEventCancelBtn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeCreateEventModal);
            }


            const form = document.getElementById('createEventForm');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const errorDiv = document.getElementById('createEventError');
                    const submitBtn = document.getElementById('createEventSubmitBtn');
                    if (submitBtn) submitBtn.disabled = true;
                    errorDiv && (errorDiv.style.display = 'none');
                    errorDiv && (errorDiv.textContent = '');

                    const fd = new FormData(form);

                    try {
                        const res = await fetch('save_event.php', {
                            method: 'POST',
                            body: fd
                        });

                        const data = await res.json();
                        if (!data || !data.success) {
                            const msg = (data && data.error) ? data.error : 'Failed to save event';
                            if (errorDiv) {
                                errorDiv.textContent = msg;
                                errorDiv.style.display = 'block';
                            }
                            if (submitBtn) submitBtn.disabled = false;
                            return;
                        }

                        closeCreateEventModal();
                        // hard refresh so PHP-generated events + calendar render are in sync
                        window.location.reload();
                    } catch (err) {
                        const msg = err && err.message ? err.message : 'Network error';
                        if (errorDiv) {
                            errorDiv.textContent = msg;
                            errorDiv.style.display = 'block';
                        }
                        if (submitBtn) submitBtn.disabled = false;
                    }
                });
            }

            // Optional: click on empty date (FullCalendar select isn't enabled here)
        });

    </script>
</body>
</html>

<?php
$conn->close();
?>