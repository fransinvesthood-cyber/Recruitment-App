<?php
include 'config.php';
session_start();

// Search logic
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Fetch Interviews
$sql = "SELECT i.interview_id, u.user_id, u.fullname AS applicant, j.position AS job,
            i.interviewer, i.interview_date, i.interview_status, i.availability_status,
            i.interview_type, i.meeting_link, i.duration_minutes, i.company_address
        FROM interviews i
        JOIN users u ON i.user_id = u.user_id
        JOIN job_postings j ON i.job_id = j.job_id";

if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    $sql .= " WHERE u.fullname LIKE '%$safe_search%' 
              OR j.position LIKE '%$safe_search%' 
              OR i.interview_status LIKE '%$safe_search%'";
}

$sql .= " ORDER BY i.interview_date ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Interviews</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css">
    <style>
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
        body.dark-mode .sidebar { background: var(--dark); }
        .sidebar.collapsed { width: 80px; }
        .logo {
            display: flex; align-items: center; gap: 12px; padding: 24px 20px;
            text-decoration: none; color: var(--white); font-size: 22px; font-weight: 700;
        }
        .logo i { font-size: 32px; }
        .logo-name span { white-space: nowrap; transition: var(--transition); }
        .sidebar.collapsed .logo-name span { display: none; }
        .side-menu { list-style: none; padding: 0 15px; flex: 1; overflow-y: auto; }
        .side-menu li { margin: 8px 0; }
        .side-menu li a {
            display: flex; align-items: center; gap: 14px; padding: 14px 16px;
            color: var(--white); text-decoration: none; border-radius: 8px;
            transition: var(--transition); font-size: 16px;
        }
        .side-menu li a:hover, .side-menu li.active a { background: rgba(255, 255, 255, 0.15); }
        .side-menu li a i { font-size: 22px; min-width: 24px; text-align: center; }

        .side-menu li.section-title {
            padding: 8px 16px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 16px 0 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar.collapsed .side-menu li.section-title { display: none; }
        .logout { margin-top: auto; padding: 16px !important; background: rgba(0,0,0,0.2); }

        /* CONTENT & NAVBAR */
        .content { flex: 1; margin-left: 280px; transition: var(--transition); }
        .sidebar.collapsed ~ .content { margin-left: 80px; }

        nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px 30px; background: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); position: sticky; top: 0; z-index: 99;
        }
        body.dark-mode nav { background: #242526; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3); }

        .theme-toggle {
            width: 50px; height: 24px; background: var(--light-gray);
            border-radius: 50px; position: relative; cursor: pointer;
            display: flex; align-items: center; padding: 2px;
        }
        body.dark-mode .theme-toggle { background: #3a3b3c; }
        .theme-toggle::before {
            content: ''; width: 20px; height: 20px; background: var(--white);
            border-radius: 50%; transition: var(--transition);
        }
        #theme-toggle:checked + .theme-toggle::before {
            transform: translateX(26px); background: var(--primary);
        }

        /* MAIN LAYOUT */
        main { padding: 24px; }

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
        .welcome-section h1 { font-size: 28px; margin-bottom: 8px; }
        .welcome-section p { opacity: 0.9; font-size: 18px; }

        .header-section {
            display: flex; justify-content: flex-end; align-items: center;
            margin-bottom: 24px; flex-wrap: wrap; gap: 16px;
        }
        
        .table-container {
            background: var(--white);
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow-x: auto;
        }
        body.dark-mode .table-container { background: #242526; }

        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-input {
            padding: 10px 15px; border: 1px solid var(--light-gray);
            border-radius: 8px; width: 300px;
            background: var(--light); color: var(--dark);
        }
        body.dark-mode .search-input { background: #3a3b3c; border-color: #555; color: #fff; }
        
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--light-gray); }
        body.dark-mode th, body.dark-mode td { border-color: #3a3b3c; }
        th { background: rgba(102, 126, 234, 0.08); font-weight: 600; color: var(--primary); }
        body.dark-mode th { background: rgba(102, 126, 234, 0.15); }
        tr:hover { background: rgba(102, 126, 234, 0.03); }
        body.dark-mode tr:hover { background: rgba(102, 126, 234, 0.08); }

        .btn {
            padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer;
            font-size: 13px; font-weight: 500; text-decoration: none;
            display: inline-flex; align-items: center; gap: 5px; transition: var(--transition);
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-warning { background: var(--warning); color: #333; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }

        .action-forms { margin-top: 10px; padding: 10px; background: var(--light); border-radius: 8px; border: 1px solid var(--light-gray); }
        body.dark-mode .action-forms { background: #333; border-color: #444; }
        .form-input-sm {
            width: 100%; padding: 8px; margin: 5px 0 10px;
            border: 1px solid #ccc; border-radius: 4px;
        }
        body.dark-mode .form-input-sm { background: #444; color: #fff; border-color: #555; }

        /* Mobile Nav Links */
        .mobile-nav-links {
            display: none;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            background: var(--white);
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        body.dark-mode .mobile-nav-links { background: #242526; }
        .mobile-nav-links a {
            padding: 8px 12px; background: var(--light-gray); border-radius: 8px;
            text-decoration: none; color: var(--gray); font-size: 14px;
        }
        body.dark-mode .mobile-nav-links a { background: #3a3b3c; color: #adb5bd; }
        .mobile-nav-links a.active { background: var(--primary); color: white; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .logo-name span, .side-menu li a span { display: none; }
            .side-menu li a { justify-content: center; padding: 16px; }
            .content { margin-left: 80px; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .content { margin-left: 0; }
            .mobile-menu-btn { display: block; font-size: 28px; background: none; border: none; cursor: pointer; color: var(--gray); }
            .mobile-nav-links { display: flex; }
        }
        .mobile-menu-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 999;
        }
    </style>
</head>
<body>
    <script>
        (function() {
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark') document.body.classList.add('dark-mode');
        })();
    </script>

    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

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
            <li><a href="admin_user_management.php"><i class='bx bx-user'></i><span>Users</span></a></li>
            <li class="active"><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a></li>
            <li><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
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
                <a href="logout.php" class="logout" onclick="return confirm('Are you sure you want to log out?')">
                    <i class='bx bx-log-out-circle'></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="content">
        <div class="mobile-nav-links">
            <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Jobs</a>
            <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
            <a class="active" href="schedule_interview.php"><i class='bx bx-group'></i> Interviews</a>
            <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
        </div>

        <main>
            <div class="welcome-section">
                <h1> Scheduled Interviews</h1>
                <p>View and manage upcoming interviews, including candidate details, interview dates, times, and assigned interviewers.</p>
            </div>

            <div class="header-section">
                <a href="schedule_interview.php" class="btn btn-primary">
                    <i class='bx bx-arrow-back'></i> Back
                </a>
            </div>

            <div class="table-container">
                <form method="GET" action="" class="search-box">
                    <input type="search" name="search" class="search-input" 
                           placeholder="Search by Name, Position, or Status..." 
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary"><i class='bx bx-search'></i> Search</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Position</th>
                            <th>Type</th>
                            <th>When</th>
                            <th>Duration</th>
                            <th>Interviewer(s)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['applicant']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['job']) ?></td>
                                    <td>
                                        <span class="badge" style="background:<?= $row['interview_type']==='Online'?'#e3f2fd;color:#1976d2':'#f3e5f5;color:#7b1fa2' ?>;padding:4px 8px;border-radius:4px;font-size:12px;">
                                            <?= htmlspecialchars($row['interview_type'] ?? 'In-person') ?>
                                        </span>
                                        <?php if ($row['interview_type']==='Online' && !empty($row['meeting_link'])): ?>
                                            <br><a href="<?= htmlspecialchars($row['meeting_link']) ?>" target="_blank" style="font-size:11px;">Join Link</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['interview_date'])) ?></td>
                                    <td><?= ($row['duration_minutes'] ?? 30) ?> min</td>
                                    <td><?= htmlspecialchars($row['interviewer']) ?></td>
                                    <td>
                                        <form action="update_interview_status.php" method="POST" style="display:flex; gap:5px;">
                                            <input type="hidden" name="interview_id" value="<?= $row['interview_id'] ?>">
                                            <select name="interview_status" class="form-input-sm" style="width: auto; margin:0;" onchange="if(confirm('Update status?')) this.form.submit()">
                                                <option value="Scheduled"   <?= $row['interview_status'] == 'Scheduled'  ? 'selected' : '' ?>>Scheduled</option>
                                                <option value="Rescheduled" <?= $row['interview_status'] == 'Rescheduled'? 'selected' : '' ?>>Rescheduled</option>
                                                <option value="Completed"   <?= $row['interview_status'] == 'Completed'  ? 'selected' : '' ?>>Completed</option>
                                                <option value="Cancelled"   <?= $row['interview_status'] == 'Cancelled'  ? 'selected' : '' ?>>Cancelled</option>
                                                <option value="No-show"     <?= $row['interview_status'] == 'No-show'    ? 'selected' : '' ?>>No-show</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <div style="display:flex; flex-direction:column; gap:5px; align-items:flex-start;">
                                            <button class="btn btn-warning" onclick="toggleForm('reschedule', <?= $row['interview_id'] ?>)">
                                                <i class='bx bx-time-five'></i> Reschedule
                                            </button>
                                            <button class="btn btn-danger" onclick="toggleForm('cancel', <?= $row['interview_id'] ?>)">
                                                <i class='bx bx-x-circle'></i> Cancel
                                            </button>
                                            <form action="complete_interview.php" method="POST" onsubmit="return confirm('Mark as completed?');">
                                                <input type="hidden" name="interview_id" value="<?= $row['interview_id'] ?>">
                                                <button type="submit" class="btn btn-success">
                                                    <i class='bx bx-check-circle'></i> Complete
                                                </button>
                                            </form>
                                        </div>

                                        <div id="rescheduleForm<?= $row['interview_id'] ?>" class="action-forms" style="display:none;">
                                            <form action="reschedule_interview.php" method="POST">
                                                <input type="hidden" name="interview_id" value="<?= $row['interview_id'] ?>">
                                                <small>New Date:</small>
                                                <input type="datetime-local" name="new_date" class="form-input-sm" required>
                                                <small>Reason:</small>
                                                <input type="text" name="reschedule_reason" class="form-input-sm" required>
                                                <small>Interviewer:</small>
                                                <input type="text" name="interviewer" value="<?= htmlspecialchars($row['interviewer']) ?>" class="form-input-sm">
                                                <button type="submit" class="btn btn-success">Confirm</button>
                                            </form>
                                        </div>

                                        <div id="cancelForm<?= $row['interview_id'] ?>" class="action-forms" style="display:none;">
                                            <form action="cancel_interview.php" method="POST">
                                                <input type="hidden" name="interview_id" value="<?= $row['interview_id'] ?>">
                                                <small>Reason:</small>
                                                <input type="text" name="cancellation_reason" class="form-input-sm" required>
                                                <button type="submit" class="btn btn-danger">Confirm</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:30px;">
                                    <i class='bx bx-calendar-x' style="font-size:40px; color:var(--gray);"></i>
                                    <p>No interviews found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileMenuOverlay');

        if(mobileBtn) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
            });
        }
        if(overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.style.display = 'none';
            });
        }

        function toggleForm(type, id) {
            const formId = type + 'Form' + id;
            const form = document.getElementById(formId);
            const otherType = (type === 'reschedule') ? 'cancel' : 'reschedule';
            const otherForm = document.getElementById(otherType + 'Form' + id);
            if(otherForm) otherForm.style.display = 'none';
            if (form.style.display === 'none') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }

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

        function handleTabletView() {
            if (window.innerWidth <= 992 && window.innerWidth > 768) {
                document.getElementById('sidebar').classList.add('collapsed');
            } else {
                document.getElementById('sidebar').classList.remove('collapsed');
            }
        }
        window.addEventListener('resize', handleTabletView);
        handleTabletView();

        // =============================================
        // SUCCESS POPUP MODAL (SweetAlert2)
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const success = urlParams.get('success');

            const successModals = {
                'interview_scheduled': {
                    title: 'Interview scheduled successfully!',
                    text: 'The interview has been scheduled successfully.'
                },
                'interview_rescheduled': {
                    title: 'Interview rescheduled successfully!',
                    text: 'The interview has been rescheduled and the candidate has been notified.'
                },
                'interview_cancelled': {
                    title: 'Interview cancelled successfully!',
                    text: 'The interview has been cancelled and the candidate has been notified.'
                },
                'interview_completed': {
                    title: 'Interview completed successfully!',
                    text: 'The interview has been marked as completed and a confirmation has been sent.'
                },
                'status_updated': {
                    title: 'Status updated successfully!',
                    text: 'The interview status has been updated.'
                }
            };

            if (success && successModals[success]) {
                Swal.fire({
                    icon: 'success',
                    title: successModals[success].title,
                    text: successModals[success].text,
                    confirmButtonColor: '#667eea',
                    confirmButtonText: 'OK',
                    customClass: {
                        popup: 'swal-popup-custom',
                        icon: 'swal-icon-custom',
                        title: 'swal-title-custom',
                        confirmButton: 'swal-confirm-custom'
                    },
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then(() => {
                    // Clean the URL - remove the success parameter without refreshing
                    const url = new URL(window.location);
                    url.searchParams.delete('success');
                    window.history.replaceState({}, '', url);
                });
            }
        });
    </script>

    <style>
        /* SweetAlert2 Custom Styles - Matches Application Submitted Popup Styling */
        .swal-popup-custom {
            border-radius: 16px !important;
            padding: 2rem 2rem 1.5rem !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15) !important;
        }
        .swal-icon-custom {
            font-size: 28px !important;
        }
        .swal-title-custom {
            font-size: 22px !important;
            font-weight: 700 !important;
            color: #2d3748 !important;
        }
        .swal-confirm-custom {
            background: linear-gradient(135deg, #667eea, #5a67d8) !important;
            border-radius: 10px !important;
            padding: 10px 30px !important;
            font-weight: 600 !important;
            font-size: 16px !important;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3) !important;
            border: none !important;
            transition: all 0.3s ease !important;
        }
        .swal-confirm-custom:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
        }
        /* Dark mode support */
        body.dark-mode .swal-popup-custom {
            background: #242526 !important;
        }
        body.dark-mode .swal-title-custom {
            color: #e4e6eb !important;
        }
        body.dark-mode .swal-icon-custom {
            color: #4ade80 !important;
        }
    </style>
</body>
</html>
