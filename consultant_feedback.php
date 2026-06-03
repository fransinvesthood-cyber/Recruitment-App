<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultant Dashboard - Client Feedback</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css">

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
           SIDEBAR
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

        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
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
        .card h2 i {
            color: var(--primary);
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

        input[type="text"],
        input[type="email"],
        textarea,
        select {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background: white;
            transition: var(--transition);
        }
        body.dark-mode input,
        body.dark-mode textarea,
        body.dark-mode select {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4b4d;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        /* Buttons */
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
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap; /* Allows wrapping on very small screens */
        }

        /* Star Rating */
        .star-rating.colorful .star {
            font-size: 2rem;
            cursor: pointer;
            transition: transform 0.2s, color 0.2s;
            color: #d1d5db; /* Default gray */
        }
        body.dark-mode .star-rating.colorful .star {
            color: #4a4b4d; /* Dark mode gray */
        }
        .star-rating.colorful .star:hover {
            transform: scale(1.2);
        }
        .star-rating.colorful .star.active {
            color: #f97316; /* Orange */
            text-shadow: 0 0 5px rgba(249, 115, 22, 0.4);
        }
        .rating-label {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 4px;
            font-style: italic;
        }
        body.dark-mode .rating-label {
            color: #adb5bd;
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
            .card {
                padding: 20px;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
            .btn-group {
                flex-direction: column;
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

    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <div class="sidebar" id="sidebar">
        <a href="#" class="logo">
            <i class='bx bx-user-circle'></i>
            <div class="logo-name"><span>Consultant</span></div>
        </a>
        <ul class="side-menu">
            <li><a href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li><a href="consultant_profile.php"><i class='bx bx-user'></i><span>Manage Profile</span></a></li>
            <li><a href="manage_leave.php"><i class='bx bx-calendar-minus'></i><span>Leave Requests</span></a></li>
            <li><a href="manage_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="manage_task_log.php"><i class='bx bx-file'></i><span>Task Logs</span></a></li>
            <li><a href="invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="training_management.php"><i class='bx bx-book-reader'></i><span>Training Records</span></a></li>
            <li class="active"><a href="consultant_feedback.php"><i class='bx bx-message-dots'></i><span>Client Feedback</span></a></li>
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

    <div class="content">
        <nav>
            
            <div></div> <input type="checkbox" id="theme-toggle" hidden>
            <label for="theme-toggle" class="theme-toggle"></label>
        </nav>

        <div class="mobile-nav-links">
            <a href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="consultant_profile.php"><i class='bx bx-user'></i> Profile</a>
            <a href="manage_timesheets.php"><i class='bx bx-time-five'></i> Timesheets</a>
            <a href="manage_task_log.php"><i class='bx bx-time-five'></i> Tasklogs</a>
            <a href="manage_leave.php"><i class='bx bx-calendar-minus'></i> Leave</a>
            <a href="invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="training_management.php"><i class='bx bx-book-reader'></i> Training</a>
            <a class="active" href="consultant_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>
            <a href="consultant_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <div class="header">
                <h1>Client Feedback</h1>
                <ul class="breadcrumb">
                    <li><a href="consultant_dashboard.php">Dashboard</a></li>
                    <li><a href="#" class="active">Feedback</a></li>
                </ul>
            </div>

            <div class="card">
                <h2><i class='bx bx-message-dots'></i> Submit Client Feedback</h2>
                <form id="clientFeedbackForm" method="POST" action="submit_feedback.php">
                    <div class="form-group">
                        <label for="consultantName">Consultant Name</label>
                        <input type="text" name="client_name" id="consultantName" placeholder="Enter consultant name" required>
                    </div>
                    <div class="form-group">
                        <label for="projectName">Client/Project</label>
                        <input type="text" name="client_project" id="projectName" placeholder="Enter the project name" required>
                    </div>

                    <div class="form-group">
                        <label>Communication Quality</label>
                        <div class="star-rating colorful" data-rating="communication">
                            <span class="star" data-value="1">★</span>
                            <span class="star" data-value="2">★</span>
                            <span class="star" data-value="3">★</span>
                            <span class="star" data-value="4">★</span>
                            <span class="star" data-value="5">★</span>
                        </div>
                        <div class="rating-label" id="communicationLabel">Click to rate</div>
                        <input type="hidden" name="communication" id="communication">
                    </div>

                    <div class="form-group">
                        <label>Professionalism</label>
                        <div class="star-rating colorful" data-rating="professionalism">
                            <span class="star" data-value="1">★</span>
                            <span class="star" data-value="2">★</span>
                            <span class="star" data-value="3">★</span>
                            <span class="star" data-value="4">★</span>
                            <span class="star" data-value="5">★</span>
                        </div>
                        <div class="rating-label" id="professionalismLabel">Click to rate</div>
                        <input type="hidden" name="professionalism" id="professionalism">
                    </div>

                    <div class="form-group">
                        <label>Collaboration & Support</label>
                        <div class="star-rating colorful" data-rating="collaboration">
                            <span class="star" data-value="1">★</span>
                            <span class="star" data-value="2">★</span>
                            <span class="star" data-value="3">★</span>
                            <span class="star" data-value="4">★</span>
                            <span class="star" data-value="5">★</span>
                        </div>
                        <div class="rating-label" id="collaborationLabel">Click to rate</div>
                        <input type="hidden" name="collaboration" id="collaboration">
                    </div>

                    <div class="form-group">
                        <label for="comments">Additional Comments</label>
                        <textarea name="comments" id="comments" placeholder="Any other feedback..." rows="5"></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn">
                            <i class='bx bx-send'></i> Submit Feedback
                        </button>
                        <button type="reset" class="btn btn-secondary" id="resetBtn">
                            <i class='bx bx-rotate-left'></i> Reset
                        </button>
                        <a href="consultant_dashboard.php" class="btn btn-secondary">
                            <i class='bx bx-arrow-back'></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // --- Star Rating Logic ---
        const ratingLabels = {
            communication: ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'],
            professionalism: ['Unprofessional', 'Below Average', 'Average', 'Professional', 'Highly Professional'],
            collaboration: ['Poor', 'Needs Improvement', 'Average', 'Good', 'Excellent']
        };

        document.querySelectorAll('.star-rating').forEach(group => {
            const stars = group.querySelectorAll('.star');
            const type = group.getAttribute('data-rating');
            const label = document.getElementById(type + 'Label');

            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    const val = index + 1;
                    document.getElementById(type).value = val;
                    label.textContent = ratingLabels[type][index];
                    stars.forEach((s, i) => s.classList.toggle('active', i < val));
                });

                star.addEventListener('mouseenter', () => {
                    stars.forEach((s, i) => s.style.color = i <= index ? '#f97316' : '');
                });
            });

            group.addEventListener('mouseleave', () => {
                const val = document.getElementById(type).value;
                stars.forEach((s, i) => {
                    s.style.color = ''; // Remove inline style to revert to CSS
                    s.classList.toggle('active', i < val); // Re-apply class based on value
                });
            });
        });

        document.getElementById('resetBtn').addEventListener('click', () => {
            document.querySelectorAll('.star').forEach(s => {
                s.classList.remove('active');
                s.style.color = '';
            });
            document.querySelectorAll('.rating-label').forEach(l => l.textContent = 'Click to rate');
            document.querySelectorAll('input[type="hidden"]').forEach(i => i.value = '');
        });

        // --- Form Submission Logic ---
        document.getElementById('clientFeedbackForm').addEventListener('submit', function(e) {
            e.preventDefault(); 
            const form = e.target;
            const formData = new FormData(form);

            fetch('submit_consultant_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // For demo/frontend purposes, verify response here
                return new Promise(resolve => setTimeout(resolve, 500)); 
            })
            .then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Feedback submitted successfully!',
                    timer: 2000,
                    showConfirmButton: false
                });

                form.reset();
                document.querySelectorAll('.star').forEach(s => s.classList.remove('active'));
                document.querySelectorAll('.rating-label').forEach(l => l.textContent = 'Click to rate');
                document.querySelectorAll('input[type="hidden"]').forEach(i => i.value = '');
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Feedback submission failed. Please try again.'
                });
                console.error(err);
            });
        });

        // --- Theme & Sidebar Logic ---
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

            // Mobile menu
            const mobileBtn = document.getElementById('mobileMenuBtn');
            if(mobileBtn) {
                mobileBtn.addEventListener('click', function() {
                    document.getElementById('sidebar').classList.toggle('active');
                    document.getElementById('mobileMenuOverlay').style.display = 
                        document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none';
                });
            }

            document.getElementById('mobileMenuOverlay').addEventListener('click', function() {
                document.getElementById('sidebar').classList.remove('active');
                this.style.display = 'none';
            });
            
            // Tablet View
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
        });

        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }
    </script>
</body>
</html>