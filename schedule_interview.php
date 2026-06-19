<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Interview - Modern</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css">
    <style>
        :root{--primary:#667eea;--primary-dark:#5a67d8;--secondary:#c9a9ea;--dark:#18191a;--darker:#121314;--light:#f8f9fa;--gray:#6c757d;--light-gray:#e9ecef;--success:#28a745;--danger:#dc3545;--warning:#ffc107;--info:#17a2b8;--white:#ffffff;--border-radius:12px;--box-shadow:0 4px 12px rgba(0,0,0,0.1);--transition:all 0.3s ease;}
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;}
        body{background:#f5f7fb;color:#333;display:flex;min-height:100vh;overflow-x:hidden;}
        body.dark-mode{background:var(--dark);color:#e4e6eb;}
        .sidebar{width:280px;background:linear-gradient(180deg,var(--primary),var(--secondary));color:var(--white);height:100vh;position:fixed;top:0;left:0;z-index:100;display:flex;flex-direction:column;}
        .sidebar.collapsed{width:80px;}
        .logo{display:flex;align-items:center;gap:12px;padding:24px 20px;text-decoration:none;color:var(--white);font-size:22px;font-weight:700;}
        .logo i{font-size:32px;}
        .logo-name span{white-space:nowrap;transition:var(--transition);}
        .sidebar.collapsed .logo-name span{display:none;}
        .side-menu{list-style:none;padding:0 15px;flex:1;overflow-y:auto;}
        .side-menu li{margin:6px 0;}
        .side-menu li a{display:flex;align-items:center;gap:14px;padding:12px 16px;color:var(--white);text-decoration:none;border-radius:8px;transition:var(--transition);font-size:15px;}
        .side-menu li a:hover,.side-menu li.active a{background:rgba(255,255,255,0.15);}
        .side-menu li a i{font-size:20px;min-width:24px;text-align:center;}
        .logout{margin-top:auto;padding:16px!important;background:rgba(0,0,0,0.2);}
        .section-title{font-weight:700;font-size:12px;text-transform:uppercase;color:rgba(255,255,255,0.7);padding:8px 16px;margin:12px 0 6px 0;letter-spacing:0.5px;border-bottom:1px solid rgba(255,255,255,0.1);}
        .sidebar.collapsed .section-title{display:none;}
        .content{flex:1;margin-left:280px;transition:var(--transition);}
        .sidebar.collapsed~.content{margin-left:80px;}
        nav{display:flex;justify-content:space-between;align-items:center;padding:16px 30px;background:var(--white);box-shadow:0 2px 10px rgba(0,0,0,0.1);position:sticky;top:0;z-index:99;}
        body.dark-mode nav{background:#242526;}
        body.dark-mode .sidebar {
            background: var(--darker) !important;
            background-image: none !important;
        }
        body.dark-mode .sidebar * {
            color: #e4e6eb !important;
        }
        body.dark-mode .sidebar a:hover,
        body.dark-mode .sidebar li.active a {
            background: rgba(255,255,255,0.1) !important;
        }
        body.dark-mode .section-title {
            color: rgba(228,230,235,0.8) !important;
            border-bottom-color: rgba(228,230,235,0.2) !important;
        }
        body.dark-mode .logout {
            background: rgba(255,255,255,0.1) !important;
        }
        .mobile-menu-btn{display:none;background:none;border:none;font-size:28px;color:var(--gray);cursor:pointer;}
        .theme-toggle-label{display:flex;align-items:center;gap:8px;cursor:pointer;}
        #theme-toggle{display:none;}
        .theme-toggle{width:50px;height:24px;background:var(--light-gray);border-radius:50px;position:relative;cursor:pointer;display:flex;align-items:center;padding:2px;}
        body.dark-mode .theme-toggle{background:#3a3b3c;}
        .theme-toggle::before{content:'';width:20px;height:20px;background:var(--white);border-radius:50%;transition:var(--transition);}
        #theme-toggle:checked+.theme-toggle::before{transform:translateX(26px);background:var(--primary);}
        main{padding:20px;}
        .welcome-section{background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);color:var(--white);padding:20px;border-radius:var(--border-radius);margin-bottom:20px;box-shadow:var(--box-shadow);}
        .welcome-section h1{font-size:24px;margin-bottom:6px;}
        .welcome-section p{opacity:0.9;font-size:16px;}
        .header-section{display:flex;justify-content:flex-end;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
        .btn{padding:10px 20px;background:var(--primary);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:var(--transition);display:inline-flex;align-items:center;gap:8px;text-decoration:none;font-size:14px;}
        .btn:hover{background:var(--primary-dark);transform:translateY(-1px);}
        .btn:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
        .btn-secondary{background:#6c757d;} .btn-secondary:hover{background:#5a6268;}
        .btn-success{background:var(--success);} .btn-success:hover{background:#218838;}
        .btn-danger{background:var(--danger);} .btn-danger:hover{background:#c82333;}
        .btn-warning{background:var(--warning);color:#212529;} .btn-warning:hover{background:#e0a800;}
        .btn-sm{padding:6px 14px;font-size:13px;}

        /* SPLIT LAYOUT */
        .split-layout{display:grid;grid-template-columns:340px 1fr;gap:20px;min-height:calc(100vh - 280px);}
        .panel{background:var(--white);border-radius:var(--border-radius);box-shadow:var(--box-shadow);display:flex;flex-direction:column;overflow:hidden;}
        body.dark-mode .panel{background:#242526;}
        .panel-header{padding:16px 20px;border-bottom:1px solid var(--light-gray);display:flex;justify-content:space-between;align-items:center;}
        body.dark-mode .panel-header{border-bottom-color:#3a3b3c;}
        .panel-header h3{font-size:16px;color:var(--primary);display:flex;align-items:center;gap:8px;}
        body.dark-mode .panel-header h3{color:#a7b7ff;}
        .panel-body{padding:16px;flex:1;overflow-y:auto;max-height:calc(100vh - 360px);}
        .search-box{width:100%;padding:10px 14px;border:1px solid var(--light-gray);border-radius:8px;font-size:14px;margin-bottom:12px;background:var(--white);color:var(--dark);}
        body.dark-mode .search-box{background:#3a3b3c;border-color:#4a4a4a;color:#e4e6eb;}
        .search-box:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(102,126,234,0.2);}

        /* APPLICANT CARDS */
        .applicant-card{padding:14px;border-radius:10px;border:2px solid transparent;background:#f8f9fa;margin-bottom:10px;cursor:pointer;transition:var(--transition);}
        body.dark-mode .applicant-card{background:#2d2d2e;}
        .applicant-card:hover{border-color:var(--primary);transform:translateY(-2px);box-shadow:var(--box-shadow);}
        .applicant-card.active{border-color:var(--primary);background:rgba(102,126,234,0.08);}
        .applicant-card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;}
        .applicant-name{font-weight:700;font-size:15px;color:var(--dark);}
        body.dark-mode .applicant-name{color:#e4e6eb;}
        .applicant-position{font-size:13px;color:var(--gray);margin-bottom:8px;}
        .match-badge{font-size:12px;font-weight:700;padding:2px 8px;border-radius:10px;background:#e9ecef;}
        .match-high{background:#d4edda;color:#155724;}
        .match-medium{background:#fff3cd;color:#856404;}
        .match-low{background:#f8d7da;color:#721c24;}
        .applicant-meta{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
        .status-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;}
        .status-shortlisted{background:#d1ecf1;color:#0c5460;}
        .match-bar{height:5px;background:var(--light-gray);border-radius:3px;overflow:hidden;}
        .match-fill{height:100%;border-radius:3px;transition:width 0.5s ease;}
        .match-fill.high{background:var(--success);} .match-fill.medium{background:var(--warning);} .match-fill.low{background:var(--danger);}
        .empty-state{text-align:center;padding:30px 20px;color:var(--gray);} .empty-state i{font-size:36px;margin-bottom:8px;display:block;}
        .applicant-count{font-size:12px;color:var(--gray);}

        /* DETAILS CARD */
        .details-card{padding:20px;} .details-card h4{font-size:18px;margin-bottom:14px;color:var(--primary);display:flex;align-items:center;gap:8px;}
        body.dark-mode .details-card h4{color:#a7b7ff;}
        .summary-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:16px;}
        .summary-item{background:#f8f9fa;padding:12px;border-radius:8px;}
        body.dark-mode .summary-item{background:#2d2d2e;}
        .summary-label{font-size:11px;color:var(--gray);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;}
        .summary-value{font-size:16px;font-weight:700;color:var(--dark);}
        body.dark-mode .summary-value{color:#e4e6eb;}
        .skills-list{display:flex;flex-wrap:wrap;gap:6px;}
        .skill-tag{font-size:12px;padding:4px 10px;background:#d4edda;color:#155724;border-radius:12px;display:inline-block;}
        .skill-tag.missing{background:#f8d7da;color:#721c24;text-decoration:line-through;}
        #detailsCard{display:none;}

        /* FORM */
        .form-card{padding:20px;border-top:1px solid var(--light-gray);}
        body.dark-mode .form-card{border-top-color:#3a3b3c;}
        .form-card h4{font-size:18px;margin-bottom:16px;color:var(--primary);display:flex;align-items:center;gap:8px;}
        body.dark-mode .form-card h4{color:#a7b7ff;}
        .form-group{margin-bottom:14px;}
        label{display:block;margin-bottom:5px;font-weight:600;font-size:13px;color:var(--dark);} body.dark-mode label{color:#e4e6eb;}
        .required{color:var(--danger);}
        input,select,textarea{width:100%;padding:10px 12px;border:1px solid var(--light-gray);border-radius:8px;font-size:14px;background:var(--white);color:var(--dark);transition:var(--transition);}
        body.dark-mode input,body.dark-mode select,body.dark-mode textarea{background:#3a3b3c;border-color:#4a4a4a;color:#e4e6eb;}
        input:focus,select:focus,textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(102,126,234,0.2);}
        input.error,select.error{border-color:var(--danger);background:#fee2e2;}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
        .type-toggle{display:flex;gap:6px;margin-bottom:10px;}
        .type-btn{flex:1;padding:8px;border:2px solid var(--light-gray);background:var(--white);border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:6px;transition:var(--transition);}
        body.dark-mode .type-btn{background:#3a3b3c;border-color:#4a4a4a;color:#e4e6eb;}
        .type-btn.active{border-color:var(--primary);background:rgba(102,126,234,0.1);color:var(--primary);}
        .type-btn:hover:not(.active){border-color:var(--primary-dark);}
        .availability-result{margin-top:8px;padding:10px 14px;border-radius:8px;font-size:13px;display:flex;align-items:flex-start;gap:8px;}
        .availability-result ul{margin:4px 0 0 16px;}
        .availability-result ul li{font-size:12px;margin-bottom:2px;}
        .form-actions{display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;}
        .spinner{width:16px;height:16px;border:2px solid #f3f3f3;border-top:2px solid var(--primary);border-radius:50%;animation:spin 1s linear infinite;display:none;}
        @keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}

        /* TOASTS */
        .toast-container{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;}
        .toast{padding:12px 18px;border-radius:8px;color:#fff;font-size:14px;display:flex;align-items:center;gap:10px;box-shadow:var(--box-shadow);animation:slideIn 0.3s ease;min-width:260px;}
        @keyframes slideIn{from{transform:translateX(100%);opacity:0;}to{transform:translateX(0);opacity:1;}}
        .toast-success{background:var(--success);} .toast-error{background:var(--danger);} .toast-warning{background:var(--warning);color:#212529;} .toast-info{background:var(--info);}

        /* MOBILE */
        .mobile-nav-links{display:none;flex-wrap:wrap;justify-content:center;gap:8px;background:var(--white);padding:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
        body.dark-mode .mobile-nav-links{background:#242526;}
        .mobile-nav-links a{display:inline-block;padding:8px 12px;background:var(--light-gray);border-radius:8px;text-decoration:none;color:var(--gray);font-size:13px;transition:var(--transition);white-space:nowrap;}
        body.dark-mode .mobile-nav-links a{background:#3a3b3c;color:#adb5bd;}
        .mobile-nav-links a:hover,.mobile-nav-links a.active{background:var(--primary);color:#fff;}
        .mobile-menu-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;}
        .sidebar.active~.mobile-menu-overlay{display:block;}

        @media(max-width:768px){
            .mobile-nav-links{display:flex;}
            .sidebar{transform:translateX(-100%);}
            .sidebar.active{transform:translateX(0);width:280px!important;}
            .content{margin-left:0;}
            .split-layout{grid-template-columns:1fr;}
            .panel-body{max-height:none;}
            .form-row{grid-template-columns:1fr;}
            .form-actions{flex-direction:column;}
            .btn{width:100%;justify-content:center;}
        }
        @media(max-width:480px){
            .welcome-section h1{font-size:20px;}
            .welcome-section p{font-size:14px;}
            main{padding:12px;}
            .details-card,.form-card{padding:14px;}
            .summary-grid{grid-template-columns:1fr 1fr;}
        }
    </style>
</head>
<body>
    <script>(function(){const t=localStorage.getItem('theme');if(t==='dark')document.body.classList.add('dark-mode');})();</script>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="#" class="logo"><i class='bx bx-user-circle'></i><div class="logo-name"><span>Admin</span></div></a>
        <ul class="side-menu">
            <li><a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li class="section-title"><span>Candidates</span></li>
            <li><a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i><span>Jobs</span></a></li>
            <li><a href="manage_applications.php"><i class='bx bx-file'></i><span>Applications</span></a></li>
            <li><a href="admin_user_management.php"><i class='bx bx-user'></i><span>Users</span></a></li>
            <li class="active"><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a></li>
            <li><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
            <li class="section-title"><span>Consultants</span></li>
            <li><a href="admin_view_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="admin_view_tasklogs.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li><a href="admin_view_leaves.php"><i class='bx bx-calendar-minus'></i><span>Leaves</span></a></li>
            <li><a href="admin_invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="admin_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
            <li><a href="admin_settings.php"><i class='bx bx-cog'></i><span>Settings</span></a></li>
        </ul>
        <ul class="side-menu">
            <li><a href="logout.php" class="logout" onclick="return confirmLogout();"><i class='bx bx-log-out-circle'></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="mobile-nav-links">
            <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Manage Jobs</a>
            <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
            <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
            <a class="active" href="schedule_interview.php"><i class='bx bx-group'></i> Interviews</a>
            <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
            <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
            <a href="admin_settings.php"><i class='bx bx-cog'></i> Settings</a>
        </div>
        <nav>
            <button class="mobile-menu-btn" id="mobileMenuBtn"><i class='bx bx-menu'></i></button>
            <div style="display:flex;align-items:center;gap:12px;margin-left:auto;">
                <a href="scheduled_interviews.php" class="btn btn-secondary btn-sm">
                    <i class='bx bx-list-ul'></i> View Scheduled
                </a>
            </div>
        </nav>
        <main>
            <div class="welcome-section">
                <h1><i class='bx bx-group'></i> Schedule Interview</h1>
                <p>Create and organize interview appointments. Select a shortlisted candidate, review their profile, and schedule with smart conflict detection.</p>
            </div>

            <div class="split-layout">
                <!-- LEFT: Applicants List -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class='bx bx-user-check'></i> Shortlisted Applicants <span class="applicant-count" id="applicantCount">0</span></h3>
                    </div>
                    <div class="panel-body">
                        <input type="text" class="search-box" id="applicantSearch" placeholder="Search by name or position..." oninput="filterApplicants()">
                        <div id="applicantList">
                            <div class="empty-state"><i class='bx bx-loader-alt bx-spin'></i><p>Loading applicants...</p></div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Details + Form -->
                <div class="panel">
                    <!-- No Selection State -->
                    <div id="noSelectionCard" class="panel-body">
                        <div class="empty-state" style="padding-top:80px;">
                            <i class='bx bx-user-pin' style="font-size:64px;"></i>
                            <p style="font-size:15px;color:var(--gray);">Select a candidate from the list to view their profile and schedule an interview.</p>
                        </div>
                    </div>

                    <!-- Details Card -->
                    <div id="detailsCard" class="details-card" style="display:none;">
                        <h4><i class='bx bx-detail'></i> Applicant Profile</h4>
                        <div class="summary-grid" id="summaryGrid"></div>
                        <div id="skillsSection"></div>
                    </div>

                    <!-- Form Card -->
                    <div id="formCard" class="form-card" style="display:none;">
                        <h4><i class='bx bx-calendar-plus'></i> Schedule Interview</h4>
                        <form id="scheduleForm" action="process_interview.php" method="POST" onsubmit="return validateAndSubmit(event)">
                            <input type="hidden" name="user_id" id="formUserId">
                            <div class="form-group">
                                <label>Applicant Name</label>
                                <input type="text" id="displayName" readonly style="background:var(--light-gray);cursor:not-allowed;">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="position">Position <span class="required">*</span></label>
                                    <select name="job_id" id="position" required>
                                        <option value="">-- Select Position --</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Interview Duration</label>
                                    <select name="duration_minutes" id="duration_minutes">
                                        <option value="15">15 minutes</option>
                                        <option value="30" selected>30 minutes</option>
                                        <option value="45">45 minutes</option>
                                        <option value="60">1 hour</option>
                                        <option value="90">1.5 hours</option>
                                        <option value="120">2 hours</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Interview Type</label>
                                <input type="hidden" name="interview_type" id="interviewType" value="In-person">
                                <div class="type-toggle">
                                    <button type="button" class="type-btn active" id="btnInPerson" onclick="setInterviewType('In-person')"><i class='bx bx-building'></i> In-person</button>
                                    <button type="button" class="type-btn" id="btnOnline" onclick="setInterviewType('Online')"><i class='bx bx-video'></i> Online</button>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group" id="addressGroup">
                                    <label for="company_address">Company Address <span class="required">*</span></label>
                                    <input type="text" name="company_address" id="company_address" placeholder="Enter full address" required>
                                </div>
                                <div class="form-group" id="meetingLinkGroup" style="display:none;">
                                    <label for="meeting_link">Meeting Link <span class="required">*</span></label>
                                    <input type="url" name="meeting_link" id="meeting_link" placeholder="https://meet.example.com/...">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="interviewerInput">Interviewer(s) <span class="required">*</span></label>
                                <input type="text" name="interviewer" id="interviewerInput" placeholder="e.g. John Doe, Jane Smith" required>
                            </div>
                            <div class="form-group">
                                <label for="interview_date">Date & Time <span class="required">*</span></label>
                                <input type="text" name="interview_date" id="interview_date" placeholder="Pick date & time..." required>
                                <div class="availability-result" id="availabilityResult" style="background:var(--light-gray);color:var(--gray);">
                                    <i class='bx bx-time-five'></i> Select date & interviewers to check availability
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn" id="submitBtn" disabled>
                                    <i class='bx bx-calendar-plus'></i> Schedule Interview
                                    <div class="spinner" id="submitSpinner"></div>
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="handleReschedule()">
                                    <i class='bx bx-refresh'></i> Reschedule
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="handleCancel()">
                                    <i class='bx bx-x'></i> Cancel Interview
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>
    <script src="js/schedule_interview.js"></script>
</body>
</html>
