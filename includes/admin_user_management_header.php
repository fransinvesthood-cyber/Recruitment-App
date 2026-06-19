<?php
// Admin User Management - shared header/shell
// Expects: $fullname, $message (optional), $messageClass (optional)
// Must be included AFTER config.php + session_start() + RBAC checks.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #667eea;
            --secondary: #c9a9eaff;
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }
        body {
            background-color: #f5f7fb;
            color: #333;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }
        .sidebar.collapsed { width: 80px; }
        .content {
            flex: 1;
            margin-left: 280px;
            transition: var(--transition);
        }
        .sidebar.collapsed ~ .content { margin-left: 80px; }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 24px 20px;
            text-decoration: none;
            color: #fff;
            font-size: 22px;
            font-weight: 700;
        }
        .logo i { font-size: 32px; }
        .logo-name span { white-space: nowrap; transition: var(--transition); }
        .sidebar.collapsed .logo-name span { display: none; }
        .side-menu {
            list-style: none;
            padding: 0 15px;
            flex: 1;
            overflow-y: auto;
        }
        .side-menu li { margin: 8px 0; }
        .side-menu li a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-size: 16px;
        }
        .side-menu li a i { font-size: 22px; min-width: 24px; text-align: center; }
        .side-menu li a:hover,
        .side-menu li.active a { background: rgba(255,255,255,0.15); }
        .sidebar.collapsed .side-menu li a span { display: none; }
        .sidebar.collapsed .side-menu li.section-header span { display: none; }
        .section-header {
            padding: 8px 16px;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }
        .logout { margin-top: auto; padding: 16px !important; background: rgba(0,0,0,0.2); }
        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .content { margin-left: 80px; }
            .logo-name span, .side-menu li a span { display: none; }
            .side-menu li a { justify-content: center; padding: 16px; }
            .section-header { display: none; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .content { margin-left: 0; }
        }
        .page-shell { background: #fff; border-radius: 16px; padding: 18px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
        .welcome-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .welcome-content { flex: 1; min-width: 250px; }
.welcome-content h1 { font-size: 28px; margin-bottom: 8px; font-weight: 800; }

        .welcome-content p { opacity: 0.9; font-size: 18px; }
        .welcome-section { text-align: center; }
        .welcome-content { margin: 0 auto; }

        .table thead th { position: sticky; top: 0; background: #0d6efd10; backdrop-filter: blur(6px); }
        .card-metric { border: 1px solid rgba(13,110,253,0.15); background: linear-gradient(135deg, rgba(13,110,253,0.06), rgba(13,110,253,0.01)); }
    </style>
</head>
<body>
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
            <li><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a></li>
            <li><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
            <li class="section-header"><span>Consultants</span></li>
            <li><a href="admin_view_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="admin_view_tasklogs.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li><a href="admin_view_leaves.php"><i class='bx bx-calendar-minus'></i><span>Leaves</span></a></li>
            <li><a href="admin_invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="admin_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
            <li class="active"><a href="admin_settings.php"><i class='bx bx-cog'></i><span>Settings</span></a></li>
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

    <div class="content">
        <div class="container py-4">
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>User Management</h1>
                    <p>Manage user accounts, monitor user activity, update roles and permissions, and maintain secure access across the system.</p>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo ($messageClass ?? '') === 'success' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Page content continues in including file -->









