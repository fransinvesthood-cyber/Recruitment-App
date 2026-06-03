<?php
include('config.php');

if (isset($_GET['job_id'])) {
    $job_id = $_GET['job_id'];

    //Fetch job details along with department and company name
    $sql = "SELECT 
                job_postings.*, 
                departments.department_name, 
                companies.company_name 
            FROM job_postings
            INNER JOIN departments ON job_postings.department_id = departments.department_id
            INNER JOIN companies ON job_postings.company_id = companies.company_id
            WHERE job_postings.job_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();
    $stmt->close();

    // Decode existing minimum_criteria so we can prefill the editing form
    $existing_criteria = json_decode($job['minimum_criteria'] ?? '{}', true) ?: [];
    $criteria_skills_val = isset($existing_criteria['required_skills']) ? implode(', ', $existing_criteria['required_skills']) : '';

    // Fetch existing job qualifications
    $qual_stmt = $conn->prepare("SELECT GROUP_CONCAT(qualification SEPARATOR ', ') as quals FROM job_qualifications WHERE job_id = ?");
    $qual_stmt->bind_param("i", $job_id);
    $qual_stmt->execute();
    $qual_result = $qual_stmt->get_result();
    $criteria_qualifications_val = $qual_result->fetch_assoc()['quals'] ?? $existing_criteria['required_qualification'] ?? '';

$criteria_min_experience_val = $existing_criteria['min_years_experience'] ?? 0;
    $criteria_province_val = $existing_criteria['province'] ?? '';
    $criteria_city_val = $existing_criteria['city_town'] ?? '';
    $criteria_min_age_val = $existing_criteria['min_age'] ?? 0;
    $criteria_max_age_val = $existing_criteria['max_age'] ?? 0;

}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $job_id = $_POST['job_id'];
    $position = $_POST['position'];
    $department_name = $_POST['department_name'];
    $company_name = $_POST['company_name'];
    $location = $_POST['location'];
    $job_description = $_POST['job_description'];
    $duties = $_POST['duties'];
    $requirements = $_POST['requirements'];
    $skills = $_POST['skills'];
    $salary = $_POST['salary'];
    $deadline = $_POST['deadline'] ?? null;

    // Screening criteria (required: at least one)
    $criteria_skills = trim($_POST['criteria_skills'] ?? '');
    $criteria_qualifications = trim($_POST['criteria_qualifications'] ?? '');

    // Update job_qualifications table
    $delete_stmt = $conn->prepare("DELETE FROM job_qualifications WHERE job_id = ?");
    $delete_stmt->bind_param("i", $job_id);
    $delete_stmt->execute();

    if (!empty($criteria_qualifications)) {
        $quals_arr = array_filter(array_map('trim', explode(',', $criteria_qualifications)));
        $insert_stmt = $conn->prepare("INSERT INTO job_qualifications (job_id, qualification) VALUES (?, ?)");
        foreach ($quals_arr as $q) {
            $insert_stmt->bind_param("is", $job_id, $q);
            $insert_stmt->execute();
        }
        $insert_stmt->close();
    }
    $delete_stmt->close();

$criteria_min_experience = isset($_POST['criteria_min_experience']) ? intval($_POST['criteria_min_experience']) : 0;


    // New screening criteria: location and age
    $criteria_province = trim($_POST['criteria_province'] ?? '');
    $criteria_city = trim($_POST['criteria_city'] ?? '');
    $criteria_min_age = isset($_POST['criteria_min_age']) ? intval($_POST['criteria_min_age']) : 0;
$criteria_max_age = isset($_POST['criteria_max_age']) ? intval($_POST['criteria_max_age']) : 0;

    // Validate: require at least one screening criterion (matching add_job.php)
    if (empty($criteria_skills) && empty($criteria_qualification) && $criteria_min_experience <= 0 
        && empty($criteria_province) && empty($criteria_city) 
        && $criteria_min_age <= 0 && $criteria_max_age <= 0) {
        echo "<script>alert('Please provide at least one screening criterion (skills, qualification, experience, location, or age range).'); window.history.back();</script>";
        exit();
    }

    $minimum_criteria_arr = [];
    if (!empty($criteria_skills)) {
        $skillsArr = array_filter(array_map('trim', explode(',', $criteria_skills)));
        if (!empty($skillsArr)) $minimum_criteria_arr['required_skills'] = $skillsArr;
    }
    if (!empty($criteria_qualification)) $minimum_criteria_arr['required_qualification'] = $criteria_qualification;
    if ($criteria_min_experience > 0) $minimum_criteria_arr['min_years_experience'] = $criteria_min_experience;
    
    // Add new screening criteria
    if (!empty($criteria_province)) $minimum_criteria_arr['province'] = $criteria_province;
    if (!empty($criteria_city)) $minimum_criteria_arr['city_town'] = $criteria_city;
    if ($criteria_min_age > 0) $minimum_criteria_arr['min_age'] = $criteria_min_age;
    if ($criteria_max_age > 0) $minimum_criteria_arr['max_age'] = $criteria_max_age;
    
    $minimum_criteria = json_encode($minimum_criteria_arr);

    //Get department_id based on department name
    $stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_name = ?");
    $stmt->bind_param("s", $department_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $department_id = $row['department_id'];
    } else {
        //Insert new department if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO departments (department_name) VALUES (?)");
        $stmt->bind_param("s", $department_name);
        if ($stmt->execute()) {
            $department_id = $stmt->insert_id;
        } else {
            echo "<script>alert('Error updating department.');</script>";
            exit();
        }
    }
    $stmt->close();

    //Get company_id based on company name
    $stmt = $conn->prepare("SELECT company_id FROM companies WHERE company_name = ?");
    $stmt->bind_param("s", $company_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $company_id = $row['company_id'];
    } else {
        //Insert new company if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO companies (company_name) VALUES (?)");
        $stmt->bind_param("s", $company_name);
        if ($stmt->execute()) {
            $company_id = $stmt->insert_id;
        } else {
            echo "<script>alert('Error updating company.');</script>";
            exit();
        }
    }
    $stmt->close();

    //Update job posting with department_id and company_id
    $stmt = $conn->prepare("UPDATE job_postings
                            SET position = ?, department_id = ?, company_id = ?, location = ?,
                                job_description = ?, duties = ?, requirements = ?, skills = ?, minimum_criteria = ?, salary = ?
                            WHERE job_id = ?");
    $stmt->bind_param("siisssssssi", $position, $department_id, $company_id, $location,
                      $job_description, $duties, $requirements, $skills, $minimum_criteria, $salary, $job_id);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Job listing updated successfully!!'); window.location.href='manage_jobs.php';</script>";
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job Listing | Admin Portal</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
        body.dark-mode .sidebar {
            background: #242526 !important;
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
        .logout { margin-top: auto; padding: 16px !important; background: rgba(0, 0, 0, 0.2); }

        /* CONTENT */
        .content { flex: 1; margin-left: 280px; transition: var(--transition); }
        .sidebar.collapsed ~ .content { margin-left: 80px; }

        /* NAVBAR */
        nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px 30px; background: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky; top: 0; z-index: 99;
        }
        body.dark-mode nav { background: #242526; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3); }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 28px; color: var(--gray); cursor: pointer; }
        
        /* THEME TOGGLE */
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
        #theme-toggle:checked + .theme-toggle::before { transform: translateX(26px); background: var(--primary); }

        /* MAIN CONTENT AREA */
        main { padding: 24px; }

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

        .welcome-content h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .welcome-content p {
            opacity: 0.9;
            font-size: 18px;
        }

        /* FORM STYLES */
        .form-container-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            width: 100%;
            margin: 0;
        }
        body.dark-mode .form-container-card { background: #242526; }
        
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; border-bottom: 2px solid var(--light-gray);
            padding-bottom: 15px;
        }
        body.dark-mode .page-header { border-bottom-color: #3a3b3c; }
        
        .page-title {
            font-size: 24px; font-weight: 600; color: var(--dark);
            display: flex; align-items: center; gap: 10px;
        }
        body.dark-mode .page-title { color: #e4e6eb; }

        .form-section { margin-bottom: 25px; }
        .section-title {
            font-size: 18px; font-weight: 600; color: var(--primary);
            margin-bottom: 20px; padding-bottom: 8px;
            border-bottom: 1px solid var(--light-gray);
        }
        body.dark-mode .section-title { border-bottom-color: #3a3b3c; color: #a7b7ff; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        
        label {
            display: block; margin-bottom: 8px; font-weight: 600;
            color: var(--gray); font-size: 14px;
        }
        body.dark-mode label { color: #b0b3b8; }

        input[type="text"], input[type="number"], textarea {
            width: 100%; padding: 12px; border: 1px solid var(--light-gray);
            border-radius: 8px; font-size: 15px; transition: all 0.3s ease;
            background-color: var(--light); color: var(--dark);
        }
        body.dark-mode input[type="text"],
        body.dark-mode input[type="number"],
        body.dark-mode textarea {
            background-color: #3a3b3c; border-color: #555; color: #e4e6eb;
        }
        
        input:focus, textarea:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background-color: var(--white);
        }
        body.dark-mode input:focus, body.dark-mode textarea:focus {
            background-color: #3a3b3c;
        }
        
        textarea { min-height: 100px; resize: vertical; }

        .submit-container { 
            text-align: center; /* Centered Button */
            margin-top: 30px; 
        }
        .btn-submit {
            background: var(--primary); color: white; border: none;
            padding: 12px 60px; /* Increased padding */
            font-size: 16px; font-weight: 600;
            border-radius: 8px; cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-submit:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-back {
            background: transparent; color: var(--gray); border: 1px solid var(--light-gray);
            padding: 11px 20px; font-size: 16px; font-weight: 600;
            border-radius: 8px; cursor: pointer; margin-right: 10px;
            text-decoration: none; display: inline-block;
        }
        .btn-back:hover { background: var(--light-gray); }

        /* Custom confirmation dialog */
        .custom-confirm {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5);
            z-index: 1000; justify-content: center; align-items: center;
        }
        .confirm-box {
            background: var(--white); padding: 30px; border-radius: 16px;
            max-width: 400px; width: 90%; text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        body.dark-mode .confirm-box { background: #242526; }
        .confirm-box h3 { margin-bottom: 15px; color: var(--dark); }
        body.dark-mode .confirm-box h3 { color: #e4e6eb; }
        .confirm-box p { margin-bottom: 25px; color: var(--gray); }
        body.dark-mode .confirm-box p { color: #b0b3b8; }
        
        .confirm-buttons { display: flex; gap: 15px; justify-content: center; }
        .confirm-btn {
            padding: 10px 20px; border-radius: 8px; font-weight: 600;
            cursor: pointer; border: none;
        }
        .confirm-yes { background: var(--primary); color: white; }
        .confirm-no { background: var(--light-gray); color: var(--dark); }
        body.dark-mode .confirm-no { background: #3a3b3c; color: #e4e6eb; }

        /* Mobile Nav Links */
        .mobile-nav-links {
            display: none; flex-wrap: wrap; justify-content: center; gap: 8px;
            background: var(--white); padding: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        body.dark-mode .mobile-nav-links { background: #242526; }
        .mobile-nav-links a {
            padding: 8px 12px; background: var(--light-gray); border-radius: 8px;
            text-decoration: none; color: var(--gray); font-size: 14px;
        }
        body.dark-mode .mobile-nav-links a { background: #3a3b3c; color: #adb5bd; }
        .mobile-nav-links a.active { background: var(--primary); color: white; }

        /* Mobile Overlay */
        .mobile-menu-overlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;
        }

        /* Responsive */
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
            .mobile-menu-btn { display: block; }
            .mobile-nav-links { display: flex; }
            .form-row { grid-template-columns: 1fr; }
            nav { padding: 16px; }
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
            <li class="active"><a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i><span>Manage Jobs</span></a></li>
            <li><a href="manage_applications.php"><i class='bx bx-file'></i><span>Manage Applications</span></a></li>
            <li><a href="manage_candidates.php"><i class='bx bx-user'></i><span>Manage Candidates</span></a></li>
            <li><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interview Schedule</span></a></li>
            <li><a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a></li>
            <li><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
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

    <div class="content">
        <nav>
            <button class="mobile-menu-btn" id="mobileMenuBtn"><i class='bx bx-menu'></i></button>
        </nav>

        <div class="mobile-nav-links">
            <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a class="active" href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Jobs</a>
            <a href="manage_applications.php"><i class='bx bx-file'></i> Apps</a>
            <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Edit Job Posting</h1>
                    <p>Modify and update existing job listings</p>
                </div>
            </div>

            <div class="form-container-card">
                <div class="page-header">
                    <a href="manage_jobs.php" class="btn-back"><i class='bx bx-arrow-back'></i> Back</a>
                </div>

                <form action="edit_job.php" method="POST" id="jobForm">
                    <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">

                    <div class="form-section">
                        <h3 class="section-title">Position Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="position">Position Title *</label>
                                <input type="text" id="position" name="position" value="<?php echo $job['position']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="department_name">Department *</label>
                                <input type="text" id="department_name" name="department_name" value="<?php echo $job['department_name']; ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="company_name">Company Name *</label>
                                <input type="text" id="company_name" name="company_name" value="<?php echo $job['company_name']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="location">Location *</label>
                                <input type="text" id="location" name="location" value="<?php echo $job['location']; ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="salary">Salary Range *</label>
                            <input type="text" id="salary" name="salary" value="<?php echo $job['salary']; ?>" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">Job Description</h3>
                        <div class="form-group">
                            <label for="job_description">Overview *</label>
                            <textarea id="job_description" name="job_description" required><?php echo $job['job_description']; ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">Responsibilities & Duties</h3>
                        <div class="form-group">
                            <label for="duties">Key Responsibilities *</label>
                            <textarea id="duties" name="duties" required><?php echo $job['duties']; ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">Qualifications</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="requirements">Required Experience *</label>
                                <textarea id="requirements" name="requirements" required><?php echo $job['requirements']; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="skills">Technical Skills *</label>
                                <textarea id="skills" name="skills" required><?php echo $job['skills']; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">Screening Criteria</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="criteria_skills">Required Skills (comma-separated)</label>
                                <textarea id="criteria_skills" name="criteria_skills" placeholder="e.g., PHP, MySQL, Laravel"><?php echo htmlspecialchars($criteria_skills_val); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="criteria_qualifications">Required Qualifications (comma-separated)</label>
                                <textarea id="criteria_qualifications" name="criteria_qualifications" placeholder="e.g., BSc IT, BSc Computer Science, Diploma in IT"><?php echo htmlspecialchars($criteria_qualifications_val); ?></textarea>

                            </div>
                            <div class="form-group">
                                <label for="criteria_province">Province</label>
                                <input type="text" id="criteria_province" name="criteria_province" placeholder="e.g., Gauteng, Western Cape" value="<?php echo htmlspecialchars($criteria_province_val); ?>">
                            </div>
                            <div class="form-group">
                                <label for="criteria_city">City/Town</label>
                                <input type="text" id="criteria_city" name="criteria_city" placeholder="e.g., Johannesburg, Cape Town" value="<?php echo htmlspecialchars($criteria_city_val); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="criteria_min_experience">Minimum Years Experience</label>
                                <input type="number" id="criteria_min_experience" name="criteria_min_experience" min="0" value="<?php echo (int)$criteria_min_experience_val; ?>">
                            </div>
                            <div class="form-group">
                                <label>Age Range</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="number" id="criteria_min_age" name="criteria_min_age" min="16" max="80" placeholder="Min" value="<?php echo (int)$criteria_min_age_val; ?>" style="flex: 1;">
                                    <span>-</span>
                                    <input type="number" id="criteria_max_age" name="criteria_max_age" min="16" max="80" placeholder="Max" value="<?php echo (int)$criteria_max_age_val; ?>" style="flex: 1;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="submit-container">
                        <input type="submit" value="Update Job Posting" id="submitBtn" class="btn-submit">
                    </div>
                </form>
            </div>
        </main>
    </div>

    <div class="custom-confirm" id="confirmDialog">
        <div class="confirm-box">
            <h3><i class='bx bx-edit' style="color:var(--primary); font-size:32px;"></i><br>Confirm Job Update</h3>
            <p>Are you sure you want to update this job listing? Changes will be live immediately.</p>
            <div class="confirm-buttons">
                <button class="confirm-btn confirm-no" id="confirmNo">Cancel</button>
                <button class="confirm-btn confirm-yes" id="confirmYes">Yes, Update</button>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle
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

        // Mobile Menu
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

        // Tablet View
        function handleTabletView() {
            if (window.innerWidth <= 992 && window.innerWidth > 768) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        }
        window.addEventListener('resize', handleTabletView);
        handleTabletView();

        // Confirmation Dialog
        document.getElementById("submitBtn").addEventListener("click", function(e) {
            const form = document.getElementById("jobForm");
            if (form.checkValidity()) {
                e.preventDefault();
                document.getElementById("confirmDialog").style.display = "flex";
            }
        });

        document.getElementById("confirmNo").addEventListener("click", function() {
            document.getElementById("confirmDialog").style.display = "none";
        });

        document.getElementById("confirmYes").addEventListener("click", function() {
            document.getElementById("confirmDialog").style.display = "none";
            document.getElementById("jobForm").submit();
        });

        // Close dialog on outside click
        document.getElementById("confirmDialog").addEventListener("click", function(e) {
            if (e.target === this) this.style.display = "none";
        });

        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }
    </script>
</body>
</html>