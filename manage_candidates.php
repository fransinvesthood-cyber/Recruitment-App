<?php
include('config.php');

// Initialize search variables
$search = "";

// Check if search input is provided
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
}

// Query to get applicants (excluding admins) with search functionality and profile picture
$sql_candidates = "SELECT
                    users.user_id,
                    users.fullname,
                    users.gender,
                    users.dob,
                    users.email,
                    users.phone,
                    applicant_profile.professional_title,
                    applicant_profile.profile_picture
                FROM users
                LEFT JOIN applicant_profile ON users.user_id = applicant_profile.user_id
                WHERE users.role = 'applicant'";

// Apply search filter
if (!empty($search)) {
    $search = $conn->real_escape_string($search); // Prevent SQL injection
    $sql_candidates .= " AND (
        users.fullname LIKE '%$search%' OR
        users.email LIKE '%$search%' OR
        applicant_profile.professional_title LIKE '%$search%'
    )";
}

$candidates_results = $conn->query($sql_candidates);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Candidates</title>
    <style>
        /* ===========================
           GLOBAL RESET & VARIABLES — IDENTICAL TO dashboard
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
           SIDEBAR — IDENTICAL
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
        .side-menu li a:hover, 
        .side-menu li.active a {
            background: rgba(255, 255, 255, 0.15);
        }
        .side-menu li a i {
            font-size: 22px;
            min-width: 24px;
            text-align: center;
        }

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

        .sidebar.collapsed .side-menu li.section-title {
            display: none;
        }

        .logout {
            margin-top: auto;
            padding: 16px !important;
            background: rgba(0, 0, 0, 0.2);
        }

        /* ===========================
           MAIN CONTENT — IDENTICAL
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
           NAVBAR — IDENTICAL
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

        .search-container {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-form {
            display: flex;
            gap: 10px;
        }
        .search-input {
            padding: 10px 16px;
            border-radius: 30px;
            border: 1px solid var(--light-gray);
            background: var(--white);
            font-size: 16px;
            color: inherit;
            width: 280px;
        }
        body.dark-mode .search-input {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4a4c;
        }
        .search-button {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }
        .search-button:hover {
            background: var(--primary-dark);
        }

        /* ===========================
           CANDIDATE CARDS — GRID (like Insights)
        ============================ */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .candidate-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        body.dark-mode .candidate-card {
            background: #242526;
        }
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .card-header {
            padding: 20px;
            display: flex;
            gap: 16px;
            align-items: center;
            border-bottom: 1px solid var(--light-gray);
        }
        body.dark-mode .card-header {
            border-color: #3a3b3c;
        }

        .profile-pic {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-gray);
        }
        body.dark-mode .profile-pic {
            border-color: #3a3b3c;
        }

        .candidate-info h3 {
            margin: 0 0 6px 0;
            font-size: 18px;
            color: var(--dark);
        }
        body.dark-mode .candidate-info h3 {
            color: #e4e6eb;
        }
        .candidate-info p {
            margin: 3px 0;
            font-size: 14px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        body.dark-mode .candidate-info p {
            color: #adb5bd;
        }

        .card-body {
            padding: 20px;
        }

        .professional-title {
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--primary);
            font-size: 15px;
        }

        .contact-info {
            margin-bottom: 16px;
        }

        .btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }
        .btn:hover { 
            background-color: var(--primary-dark); 
        }

        .no-candidates {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
            grid-column: 1 / -1;
        }
        .no-candidates i {
            font-size: 64px;
            margin-bottom: 16px;
            color: var(--light-gray);
        }
        body.dark-mode .no-candidates i {
            color: #3a3b3c;
        }

        /* ===========================
           MOBILE NAV LINKS BAR (like dashboard)
        ============================ */
        .mobile-nav-links {
            display: none; /* hide on desktop by default */
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

        /* ===========================
           MOBILE MENU BUTTON
        ============================ */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 28px;
            color: var(--gray);
            cursor: pointer;
        }

        /* ===============================
           RESPONSIVE DESIGN
        ================================ */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            .logo-name span {
                display: none;
            }
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
            .candidates-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
                padding: 16px;
            }
            .mobile-menu-btn {
                display: block;
            }
            .search-container {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }
            .search-form {
                width: 100%;
            }
            .search-input {
                width: 100%;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            .candidates-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .header h2 {
                font-size: 24px;
            }
            .card-header {
                flex-direction: column;
                text-align: center;
            }
            .profile-pic {
                width: 70px;
                height: 70px;
            }
            .candidate-info h3 {
                font-size: 20px;
            }
            .btn {
                width: 100%;
                justify-content: center;
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

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;"></div>

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
            <li class="active"><a href="manage_candidates.php"><i class='bx bx-user'></i><span>Candidates</span></a></li>
            <li><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a></li>
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
                <a class="active" href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
                <a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a>
                <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
                <a href="admin_client_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>
                <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
                <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
            </div>

        <main>
            <div class="welcome-section">
                <h1>Candidates</h1>
                <p>View and manage candidate profiles while tracking their progress throughout the recruitment process.</p>
            </div>

            <div class="candidates-grid">
                <?php if ($candidates_results->num_rows > 0): ?>
                    <?php while ($row = $candidates_results->fetch_assoc()): ?>
                        <div class="candidate-card">
                            <div class="card-header">
                                <img src="<?php 
                                    if (!empty($row['profile_picture'])) {
                                        echo 'data:image/jpeg;base64,' . base64_encode($row['profile_picture']);
                                    } else {
                                        echo 'img/default_photo.jpg';
                                    }
                                ?>" alt="Profile Picture" class="profile-pic" onerror="this.src='img/default_photo.jpg'">
                                <div class="candidate-info">
                                    <h3><?= htmlspecialchars($row['fullname']) ?></h3>
                                    <p><i class='bx bx-envelope'></i> <?= htmlspecialchars($row['email']) ?></p>
                                    <p><i class='bx bx-phone'></i> <?= htmlspecialchars($row['phone']) ?></p>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($row['professional_title'])): ?>
                                    <div class="professional-title">
                                        <i class='bx bx-briefcase'></i> <?= htmlspecialchars($row['professional_title']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-candidates">
                        <i class='bx bx-user-x'></i>
                        <h3>No candidates found</h3>
                        <p>Try adjusting your search criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileMenuOverlay');

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.style.display = 'none';
            });
        }

        // Tablet auto-collapse
        function handleTabletView() {
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

        // Leave Requests functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle sidebar "Manage Leave" link click
            const manageLeaveLink = document.querySelector('a[href="manage_leave.php"]');
            if (manageLeaveLink) {
                manageLeaveLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    var leaveSection = document.getElementById('leaveRequestsSection');
                    var isVisible = leaveSection.style.display === 'block';
                    leaveSection.style.display = isVisible ? 'none' : 'block';

                    if (!isVisible) {
                        leaveSection.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            }

            // Hide leave requests section
            document.getElementById('hideLeaveRequests').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('leaveRequestsSection').style.display = 'none';
            });

            // Status filter functionality
            document.getElementById('statusFilter').addEventListener('change', function() {
                var selectedStatus = this.value;
                var table = document.getElementById('leaveRequestsTable');
                if (!table) return;

                var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i];
                    var rowStatus = row.getAttribute('data-status');

                    if (selectedStatus === 'all' || rowStatus === selectedStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>