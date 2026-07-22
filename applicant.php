<?php
include('config.php');
session_start();

// Initialize all user data variables to prevent undefined errors
$fullname = '';
$professional_title = '';
$soft_skills = '';
$technical_skills = '';
$qualification_name = '';
$institution = '';
$position = '';
$company_name = '';
$duties = '';
$email = '';
$gender = '';
$dob = '';
$phone = '';
$address = '';
$year_completed = '';
$duration = '';

$message = '';
$messageClass = '';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to view this page.");
}

$user_id = $_SESSION['user_id'];

// Handle profile update from the applicant form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_entire_profile'])) {
    // Personal details
    $fullname_in = trim($_POST['fullname'] ?? '');
    $email_in = trim($_POST['email'] ?? '');
    $gender_in = trim($_POST['gender'] ?? '');
    $dob_in = trim($_POST['dob'] ?? '');
    $phone_in = trim($_POST['phone'] ?? '');
    $address_in = trim($_POST['address'] ?? '');

    // Education (first entry only)
    $qualification_name_in = trim(is_array($_POST['qualification_name']) ? ($_POST['qualification_name'][0] ?? '') : ($_POST['qualification_name'] ?? ''));
    $institution_in = trim(is_array($_POST['institution']) ? ($_POST['institution'][0] ?? '') : ($_POST['institution'] ?? ''));
    $year_completed_in = intval(is_array($_POST['year_completed']) ? ($_POST['year_completed'][0] ?? 0) : ($_POST['year_completed'] ?? 0));

    // Skills
    $soft_skills_in = trim($_POST['soft_skills'] ?? '');
    $technical_skills_in = trim($_POST['technical_skills'] ?? '');

    // Work experience arrays
    $positions = $_POST['position'] ?? [];
    $company_names = $_POST['company_name'] ?? [];
    $durations = $_POST['duration'] ?? [];
    $duties_arr = $_POST['duties'] ?? [];

    // Update users table
    $uStmt = $conn->prepare("UPDATE users SET fullname=?, email=?, gender=?, dob=?, phone=?, address=? WHERE user_id=?");
    if ($uStmt) {
        $uStmt->bind_param("ssssssi", $fullname_in, $email_in, $gender_in, $dob_in, $phone_in, $address_in, $user_id);
        $uStmt->execute();
        $uStmt->close();
    } else {
        error_log("Update user prepare failed: " . $conn->error);
    }

    // Upsert qualifications (single entry)
    $qCheck = $conn->prepare("SELECT qualification_id FROM qualifications WHERE user_id=? LIMIT 1");
    if ($qCheck) {
        $qCheck->bind_param("i", $user_id);
        $qCheck->execute();
        $qCheck->store_result();
        if ($qCheck->num_rows) {
            $qUpdate = $conn->prepare("UPDATE qualifications SET qualification_name=?, institution=?, year_completed=? WHERE user_id=?");
            if ($qUpdate) {
                $qUpdate->bind_param("ssii", $qualification_name_in, $institution_in, $year_completed_in, $user_id);
                $qUpdate->execute();
                $qUpdate->close();
            }
        } else {
            $qInsert = $conn->prepare("INSERT INTO qualifications (user_id, qualification_name, institution, year_completed) VALUES (?, ?, ?, ?)");
            if ($qInsert) {
                $qInsert->bind_param("issi", $user_id, $qualification_name_in, $institution_in, $year_completed_in);
                $qInsert->execute();
                $qInsert->close();
            }
        }
        $qCheck->close();
    }

    // Upsert skills
    $sCheck = $conn->prepare("SELECT skill_id FROM skills WHERE user_id=? LIMIT 1");
    if ($sCheck) {
        $sCheck->bind_param("i", $user_id);
        $sCheck->execute();
        $sCheck->store_result();
        if ($sCheck->num_rows) {
            $sUpdate = $conn->prepare("UPDATE skills SET technical_skills=?, soft_skills=?, created_at=NOW() WHERE user_id=?");
            if ($sUpdate) {
                $sUpdate->bind_param("ssi", $technical_skills_in, $soft_skills_in, $user_id);
                $sUpdate->execute();
                $sUpdate->close();
            }
        } else {
            $sInsert = $conn->prepare("INSERT INTO skills (user_id, technical_skills, soft_skills) VALUES (?, ?, ?)");
            if ($sInsert) {
                $sInsert->bind_param("iss", $user_id, $technical_skills_in, $soft_skills_in);
                $sInsert->execute();
                $sInsert->close();
            }
        }
        $sCheck->close();
    }

    // Replace work_experience entries: delete existing and re-insert provided entries
    $del = $conn->prepare("DELETE FROM work_experience WHERE user_id=?");
    if ($del) {
        $del->bind_param("i", $user_id);
        $del->execute();
        $del->close();
    }
    $insertWork = $conn->prepare("INSERT INTO work_experience (user_id, position, company_name, duration, duties) VALUES (?, ?, ?, ?, ?)");
    if ($insertWork) {
        for ($i = 0; $i < count($positions); $i++) {
            $pos = trim($positions[$i]);
            $comp = trim($company_names[$i] ?? '');
            $dur = trim($durations[$i] ?? '');
            $duty = trim($duties_arr[$i] ?? '');
            if (!$pos && !$comp && !$dur && !$duty) continue;
            $insertWork->bind_param("issss", $user_id, $pos, $comp, $dur, $duty);
            $insertWork->execute();
        }
        $insertWork->close();
    }

    // Refresh session values for display
    $_SESSION['fullname'] = $fullname_in;
    $_SESSION['email'] = $email_in;
    $_SESSION['dob'] = $dob_in;
    $_SESSION['phone'] = $phone_in;
    $_SESSION['gender'] = $gender_in;
    $_SESSION['address'] = $address_in;
    $_SESSION['qualification_name'] = $qualification_name_in;
    $_SESSION['institution'] = $institution_in;
    $_SESSION['year_completed'] = $year_completed_in;
    $_SESSION['soft_skills'] = $soft_skills_in;
    $_SESSION['technical_skills'] = $technical_skills_in;

    $_SESSION['message'] = 'Profile updated successfully.';
    $_SESSION['messageClass'] = 'success';

    // Redirect to avoid resubmission
    header('Location: applicant.php');
    exit;
}

// Session message handling
if (!empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageClass = $_SESSION['messageClass'];
    unset($_SESSION['message'], $_SESSION['messageClass']);
}

// Fetch full name
$sql = "SELECT fullname FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname);
$stmt->fetch();
$stmt->close();

// Fetch job listings with search filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT job_postings.*, companies.company_name, departments.department_name
          FROM job_postings
          LEFT JOIN companies ON job_postings.company_id = companies.company_id
          LEFT JOIN departments ON job_postings.department_id = departments.department_id
          WHERE 1";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (job_postings.position LIKE '%$search%' 
                OR job_postings.location LIKE '%$search%' 
                OR companies.company_name LIKE '%$search%' 
                OR departments.department_name LIKE '%$search%')";
}
$search_results = $conn->query($query);

// Fetch all job postings
$all_jobs_result = $conn->query("SELECT * FROM job_postings");

// Fetch user's personal details
$sql = "SELECT fullname, email, gender, dob, phone, address FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email, $gender, $dob, $phone, $address);
$stmt->fetch();
$stmt->close();
$_SESSION['fullname'] = $fullname;
$_SESSION['email'] = $email;
$_SESSION['dob'] = $dob;
$_SESSION['phone'] = $phone;
$_SESSION['gender'] = $gender;
$_SESSION['address'] = $address;

// Fetch user's qualifications
$sql = "SELECT qualification_name, institution, year_completed FROM qualifications WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($qualification_name, $institution, $year_completed);
$stmt->fetch();
$stmt->close();
$_SESSION['year_completed'] = $year_completed;
$_SESSION['qualification_name'] = $qualification_name;
$_SESSION['institution'] = $institution;

// Fetch user's skills
$sql = "SELECT soft_skills, technical_skills FROM skills WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($soft_skills, $technical_skills);
$stmt->fetch();
$stmt->close();
$_SESSION['soft_skills'] = $soft_skills;
$_SESSION['technical_skills'] = $technical_skills;

// Fetch user's professional profile
$sql = "SELECT professional_title, professional_summary FROM applicant_profile WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($professional_title, $professional_summary);
$stmt->fetch();
$stmt->close();
$_SESSION['professional_title'] = $professional_title;
$_SESSION['professional_summary'] = $professional_summary;

// Fetch user's work experience
$sql = "SELECT position, company_name, duration, duties FROM work_experience WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($position, $company_name, $duration, $duties);
$stmt->fetch();
$stmt->close();
$_SESSION['position'] = $position;
$_SESSION['company_name'] = $company_name;
$_SESSION['duration'] = $duration;
$_SESSION['duties'] = $duties;

// Fetch upcoming interviews
$sql = "SELECT i.interview_id, j.job_id, i.interview_date, i.interview_status, i.availability_status,
               j.position AS job_title, c.company_name 
        FROM interviews i 
        JOIN job_postings j ON i.job_id = j.job_id 
        JOIN companies c ON j.company_id = c.company_id 
        JOIN users u ON i.user_id = u.user_id 
        WHERE u.user_id = ? AND (i.interview_status = 'Scheduled' OR i.interview_status = 'Rescheduled')
        ORDER BY i.interview_date ASC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $interview_results = $stmt->get_result();
} else {
    die("Error preparing interview statement: " . $conn->error);
}

// Profile Completion Calculation
$personal_info_fields = ['fullname', 'gender', 'dob', 'email', 'phone', 'address'];
$education_fields = ['qualification_name', 'institution', 'year_completed'];
$skills_fields = ['soft_skills', 'technical_skills'];
$work_experience_fields = ['position', 'company_name', 'duration', 'duties'];

function calculate_completion($fields) {
    $total_fields = count($fields);
    $filled_fields = 0;
    foreach ($fields as $field) {
        if (!empty($_SESSION[$field])) {
            $filled_fields++;
        }
    }
    return $total_fields > 0 ? round(($filled_fields / $total_fields) * 100) : 0;
}

$personal_info_completion = calculate_completion($personal_info_fields);
$education_completion = calculate_completion($education_fields);
$skills_completion = calculate_completion($skills_fields);
$work_experience_completion = calculate_completion($work_experience_fields);
$overall_completion = calculate_completion(array_merge($personal_info_fields, $education_fields, $skills_fields, $work_experience_fields));
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Appstyle.css">
    <link rel="stylesheet" href="personalStyle.css">
    <link rel="stylesheet" href="job.css">
    <link rel="stylesheet" href="applicant.css">
    <!-- Multi Select Css -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css"
            integrity="sha384-1ZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9s+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
    <link rel="icon" href="assets/logo1.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Red+Hat+Display:wght@500&display=swap" rel="stylesheet" >
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <title>Applicant Page</title>

    <style>
        .career-quiz-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 30px;
        margin: 20px 0;
        text-align: center;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .quiz-intro h3 {
        font-size: 24px;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .quiz-intro p {
        font-size: 16px;
        margin-bottom: 25px;
        opacity: 0.9;
        line-height: 1.5;
    }

    .take-quiz-btn {
        background: white;
        color: #667eea;
        padding: 15px 35px;
        border: none;
        border-radius: 30px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
    }

    .take-quiz-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(255, 255, 255, 0.4);
        color: #080a60;
    }

    .quiz-stats {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 20px;
        font-size: 14px;
    }

    .quiz-stat-item {
        display: flex;
        align-items: center;
        gap: 8px;
        opacity: 0.9;
    }

    .last-assessment {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .last-assessment h4 {
        margin-bottom: 15px;
        font-size: 18px;
    }

    .assessment-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        text-align: left;
    }

    .assessment-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .career-recommendations {
        margin-top: 15px;
    }

    .career-tag {
        display: inline-block;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        margin: 3px;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    @media (max-width: 768px) {
        .career-quiz-section {
            padding: 20px;
            margin: 10px 0;
        }

        .quiz-stats {
            flex-direction: column;
            gap: 15px;
        }

        .assessment-details {
            grid-template-columns: 1fr;
        }

        .take-quiz-btn {
            padding: 12px 25px;
            font-size: 16px;
        }
    }
        #chatbot-fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #080a60, #1a73e8);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(8, 10, 96, 0.3);
            z-index: 9998;
            font-size: 28px;
            transition: all 0.3s ease;
            border: none;
        }
        
        #chatbot-fab:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 25px rgba(8, 10, 96, 0.4);
        }

        /* =======================
        🧱 Layout Containers
        ======================= */
        .top-container,
        .bottom-container {
        display: flex;
        flex-wrap: wrap;
        padding: 20px;
        gap: 20px;
        }

        .nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        flex-wrap: wrap;
        gap: 15px;
        }

        .status,
        .prog-status,
        .upcoming {
        flex: 1 1 300px;
        background: var(--bg-card);
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .status {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 1px solid rgba(102, 126, 234, 0.15);
        }

        .status:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }



        .status::before,
        .prog-status::before,
        .upcoming::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .status .header h4 {
        color: #080a60;
        font-weight: 700;
        font-size: 24px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        }

        .prog-status .header h4 {
        color: var(--text-main);
        font-weight: 700;
        font-size: 24px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        }

        /* Chatbot Container */
        #chatbot-container {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 380px;
            max-height: 600px;
            min-height: 400px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            background: white;
            z-index: 9999;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #chatbot-header {
            background: linear-gradient(135deg, #080a60, #1a73e8);
            color: white;
            padding: 20px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px 20px 0 0;
        }

        #chatbot-header span:first-child {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #close-btn {
            font-size: 24px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        #close-btn:hover {
            opacity: 1;
        }

        #chatbot-body {
            display: flex;
            flex-direction: column;
            height: 450px;
            padding: 0;
        }

        #chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            font-size: 14px;
            line-height: 1.5;
        }

        #chatbot-messages::-webkit-scrollbar {
            width: 6px;
        }

        #chatbot-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #chatbot-messages::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .user-msg, .bot-msg {
            margin: 15px 0;
            display: flex;
        }

        .user-msg {
            justify-content: flex-end;
        }

        .bot-msg {
            justify-content: flex-start;
        }

        .user-msg span {
            background: linear-gradient(135deg, #080a60, #1a73e8);
            color: white;
            padding: 12px 16px;
            border-radius: 20px 20px 5px 20px;
            max-width: 80%;
            word-wrap: break-word;
            box-shadow: 0 2px 8px rgba(8, 10, 96, 0.2);
        }

        .bot-msg span {
            background: white;
            color: #333;
            padding: 12px 16px;
            border-radius: 20px 20px 20px 5px;
            max-width: 80%;
            word-wrap: break-word;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .typing-indicator {
            display: none;
            justify-content: flex-start;
            margin: 15px 0;
        }

        .typing-indicator span {
            background: white;
            color: #666;
            padding: 12px 16px;
            border-radius: 20px;
            border: 1px solid #e0e0e0;
            font-style: italic;
        }

        #chatbot-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        #chatbot-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        #chatbot-input:focus {
            border-color: #1a73e8;
        }

        #send-btn {
            padding: 12px 16px;
            background: linear-gradient(135deg, #080a60, #1a73e8);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            min-width: 60px;
        }

        #send-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(8, 10, 96, 0.3);
        }

        #send-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .suggestion-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .suggestion-pill {
            background: #f0f8ff;
            color: #1a73e8;
            border: 1px solid #1a73e8;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .suggestion-pill:hover {
            background: #1a73e8;
            color: white;
        }

        /* Your existing styles remain the same */
        
        .backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 999;
            display: none;
        }

        /* Modernized Profile Container */

        .top-container .nav .logo h1{
            color: #ccc;
            font-size: 16px;
        }

        #big {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        #big::before {
            content: '👤';
            font-size: 28px;
        }

        body:not(.dark-mode) #big {
            color: #080a60;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        body:not(.dark-mode) #big::before {
            content: '👤';
            font-size: 28px;
        }

        .right-section i {
            font-size: 20px;
        }

        /* Notification bell visibility */
        body:not(.dark-mode) .notification-container i {
            color: #fff;
            font-size: 24px;
        }

        body.dark-mode .notification-container i {
            color: #fff;
            font-size: 28px;
        }

        /* Notification Styles */
        .notification-container {
            position: relative;
            cursor: pointer;
            margin-right: 15px;
        }

        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .notification-dropdown {
            position: fixed;
            top: 80px;
            right: 20px;
            width: 450px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            max-height: 600px;
            overflow: hidden;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }

        .notification-header h5 {
            margin: 0;
            font-size: 16px;
            color: #080a60;
        }

        .notification-header button {
            background: #667eea;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .notification-header button:hover {
            background: #764ba2;
        }

        .notification-list {
            max-height: 450px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item.unread {
            background: #e3f2fd;
            border-left: 4px solid #667eea;
        }

        .notification-item .message {
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }

        .notification-item .timestamp {
            font-size: 12px;
            color: #666;
        }

        .notification-item .type {
            font-size: 11px;
            color: #667eea;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .pro-img{
            display: flex;
            justify-content: center;
            align-items: center;
            padding-block: 10px;
            padding-inline: 10px;
            height: 250px;
            
        }
        .img-div{
            margin-left: -70px;
            position: relative;
            width: 100%;
            flex: 100%;
            height: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
        }
        .img{
          flex: 100%;  
          border-radius: 10px;
          width: 100%;
          height: 100%;
        }
        .ski-1{
            border: #247E81 5px solid;
            background-color: #007bff; 
            color: white;
            border-radius: 10px;
            padding: 5px;
          
        }
        .ski-2{
            border: #B30D0D 5px solid;
            background-color: #28a745; 
            color: white;
            border-radius: 10px;
            padding: 5px;
          
        }
        .ski-3{
            border: #D46119 5px solid;
            background-color: #143ffd; 
            color: white;
            border-radius: 10px;
            padding: 5px;
          
        }
        .pro-he{
            margin-bottom: 10px ;

        }
        .pro-desc{
            margin-top: 10px;
        }
        .Log-span{
            color: white;
            border: 1px solid white;
            padding-inline: 10px;
            padding-block: 5px;
            border-radius: 25px;
        }
        .pro-btn {
        background-color: #080a60;
        color: #fff;
        padding:  16px 24px;
        font-size: 18px;
        font-family: inherit;
        min-width: 208px;
        border-radius: 40px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        }
        .btn:hover {
        background-color: #080a60;
        }

        .dropdown-content{
            display: none;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: auto;
            width: 450px;
            position: absolute; /* Adjust as necessary */
            z-index: 1; /* Ensure it's above other elements */
        }

        .dropdown {
            display: none;
            position: absolute;
            top: 75px;
            left: 0;
            background-color: white;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 10;
        }
        .dropdown-item {
            padding: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .dropdown-item:hover {
            background-color: #f1f1f1;
        }
        .loc-con, .company-con, .job-title-con, .job-type-con{
            border: #ffffff 1px solid ;
            border-radius: 10;
            width: 210px; 
            height: 73px;
            margin-right: 40px;
        }

        .loc-select {
            border: #0a0a0a 15px solid;

        }

        .pers {
            cursor: pointer; /* Show pointer cursor on hover */
            position: relative; /* Necessary for positioning dropdown */
        }

        /* General Layout */
    .prog-status{
    padding: 15px;
    background-color: #080a60;
    height: 500px;  /* Set the height of the container */
    overflow: auto; /* Enable scrolling if the content overflows */
}
    

    .search-button {
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }

    /* Stats Section */
    .bg-blue,
    .bg-green,
    .bg-orange
    .bg-red {
        display: inline-block; /* Allows the elements to be side by side */
        width: 100px; /* Set square width */
        height: 100px; /* Set square height */
        padding: 0; /* Padding removed for perfect square dimensions */
        border-radius: 10px;
        color: #fff;
        text-align: center; /* Center text horizontally */
        line-height: 100px; /* Center text vertically */
        font-size: 16px; /* Optional: Adjust text size */
    }

    .d-flex {
            display: flex;
        }
    .bd-highlight {
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative; /* For dropdown positioning */
        }

    .bg-blue {
        background-color: #007bff;
        color: white;
        cursor: pointer;
        
    }
    .bg-green {
        background-color: #28a745;
        color: white;
        cursor: pointer;
    }
    .bg-orange {
        background-color: #fd7e14;
        color: white;
        cursor: pointer;
    }
    .bg-red{
        background-color: #B30D0D;
        color: white;
        cursor: pointer;
    }
/*
    .stats-section h3 {
        border: 5px solid rgb(3, 3, 3);
        background-color: rgb(13, 255, 0);
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .stats-section p {
        font-size: 1rem;
        margin: 0;
    }*/

    /* Search Bar  check fixing style*/
    .mb-3 {
        margin-bottom: 4px;
        border-radius: 10px;
        padding: 10px;
        background-color: #e5caca; 
        border: 9;
        border-color: #0a0a0a 20px solid; 
    }
    .sea-type{
        flex: 1;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 10px;
        padding: 10px;
        justify-content: center;
        width: 100%;
        overflow-x: auto;
    }

    .pro-up {
            margin-left: 10px;
        }
        .pro-seach-text {
            font-size: 20px;
            cursor: pointer;
        }
        .pro-seach {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }
        .filters {
            margin: 10px 0;
        }
        .hidden {
            display: none;
        }
        input[type="checkbox"] {
            margin-right: 10px;
        }

			body, h1, h2, h3, h4, p {
				margin: 0;
				padding: 0;
				font-family: 'Arial', sans-serif;
			}

			h1 {
				font-size: 2rem;
				text-align: center;
				color: #2c3e50;
			}

			label {
				font-size: 17px;
				color: #2c3e50;
				margin-bottom: 10px;
			}

			input[type="text"] {
				margin-bottom: 10px;
				margin: 0;
				outline: none;
				transition: border-color 0.3s ease;
                width: 100%;
                padding: 13px 22px;
                border-radius: 5px;
                border: 2px solid #dde3ec;
                background: #ffffff;
                font-weight: 500;
                font-size: 16px;
                color: #536387;
                outline: none;
                resize: none;
			}

            input[type="search"] {
				margin-bottom: 10px;
				margin: 0;
                margin-left: -97px;
				outline: none;
				transition: border-color 0.3s ease;
                width: 97%;
                padding: 13px 22px;
                border-radius: 5px;
                border: 1px solid #dde3ec;
                border: 2px solid #dde3ec;
                background: #ffffff;
                font-weight: 500;
                font-size: 13px;
                color: #536387;
                outline: none;
                resize: none;
			}

			input[type="text"]:focus {
				border-color: #3498db;
			}

			input[type="submit"] {
				background-color: #3498db;
				color: #fff;
				border: none;
				padding: 10px 20px;
				margin-bottom: 10px;
				font-size: 1rem;
				border-radius: 4px;
				cursor: pointer;
				transition: background-color 0.3s ease;
			}

			input[type="submit"]:hover {
				background-color: #2980b9;
			}

			/* Job Listings Container */
			#job-results {
				display: flex;
				flex-direction: column;
				align-items: center;
			}

			.job-listing {
				background-color: #fff;
				border-radius: 8px;
				padding: 20px;
				margin-bottom: 20px;
				width: 152%;
				max-width: 1200px;
				box-shadow: 0 3px 7px rgba(0, 105, 148);
				transition: transform 0.3s ease, box-shadow 0.3s ease;
				line-height: 1.0;
			}

			.job-listing:hover {
				transform: translateY(-5px);
				box-shadow: 0 3px 7px rgba(8, 146, 208);
			}

			.job-listing h4 {
				font-size: 17px;
				color: #2980b9;
				margin-bottom: 10px;
			}

			.job-listing .title {
				font-size: 20px;
				color: #3498db;
			}

			.job-listing p {
				margin-bottom: 10px;
			}

			.job-listing strong {
				color: #34495e;
			}

			.job-listing em {
				font-style: italic;
				color: #7f8c8d;
			}

			/* Buttons */
            .btn-container {
                display: flex;
                justify-content: center;
                gap: 10px; /* Adjusts spacing between buttons */
                margin-top: 5px; /* Optional: Adds space above the buttons */
                text-align: center;
            }

			button {
				background-color: #3498db;
				color: #fff;
				border: none;
				padding: 10px 20px;
				font-size: 1rem;
				border-radius: 4px;
				cursor: pointer;
				margin-right: 10px;
				transition: background-color 0.3s ease;
                justify-content: space-evenly;
                justify-items: stretch;
			}

			button:hover {
				background-color: #2980b9;
			}

			a {
				color: inherit;
				text-decoration: none;
			}

			/* No results message */
			.no-results {
				text-align: center;
				font-size: 1.2rem;
				color: #e74c3c;
				margin-top: 20px;
			}

			.job-cities {
				font-size: 13px;
				line-height: 2.1;
			}

            .right-section a {
                color: #ccc;
            }

            /* Logout Button - Red Styling */
            a[href="logout.php"] {
                display: inline-flex !important;
                align-items: center;
                gap: 8px;
                padding: 10px 20px;
                background-color: #dc3545 !important;
                color: #fff !important;
                border-radius: 5px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s ease;
                cursor: pointer;
            }

            a[href="logout.php"]:hover {
                background-color: #c82333 !important;
                color: #fff !important;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            }

            a[href="logout.php"] i {
                font-size: 18px;
            }

            /* General form container styling */
            .wrapper {
                width: 100%;
                max-width: 500px;
                background: #ffffff;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
                margin: auto;
            }

            /* Form title */
            .title {
                font-size: 20px;
                font-weight: bold;
                text-align: center;
                margin-bottom: 15px;
                color: #333;
            }

            /* Modern Input Fields Styling */
            .inputfield {
                margin-bottom: 20px;
                width: 100%;
                display: flex;
                flex-direction: column;
            }

            label {
                display: block;
                font-weight: 600;
                margin-bottom: 10px;
                color: #080a60;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .input {
                width: 100%;
                padding: 16px 20px;
                border-radius: 12px;
                border: 2px solid transparent;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                font-weight: 500;
                font-size: 16px;
                color: #495057;
                outline: none;
                transition: all 0.3s ease;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .input:focus {
                border-color: #080a60;
                background: #ffffff;
                box-shadow: 0 0 0 4px rgba(8, 10, 96, 0.1), inset 0 2px 4px rgba(0, 0, 0, 0.05);
                transform: scale(1.02);
                outline: none;
            }

            .input:hover {
                border-color: #bdc3c7;
            }

            /* Radio buttons */
            #gender input[type="radio"] {
                margin-right: 5px;
            }

            /* Button styling */
            .btns {
                display: flex;
                justify-content: space-between;
                margin-top: 20px;
            }

            .btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                padding: 14px 30px;
                border-radius: 30px;
                border: none;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                position: relative;
                overflow: hidden;
            }

            .btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }

            .btn:hover::before {
                left: 100%;
            }

            .btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
            }

            .btn:first-child {
                background: #ccc;
                color: #333;
            }

            .btn:last-child {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }

            .pro-ski {
                text-align: center;
                padding: 20px;
                border-radius: 7px;
                max-width: 300px;
                margin: auto;
            }

            .pro-ski img {
                width: 100%;
                max-width: 200px;
                height: 200px;
                border-radius: 50%;
                object-fit: cover;
                transition: transform 0.3s ease-in-out;
            }

            .pro-ski img:hover {
                transform: scale(1.05);
            }

            #upload-btn, #update-btn, #edit-btn, #cancel-btn {
                display: inline-block;
                margin-top: 15px;
                margin-bottom: 5px;
                margin-left: 15px;
                padding: 10px 20px;
                font-size: 12px;
                color: white;
                background: #2980b9;
                border-radius: 5px;
                cursor: pointer;
                transition: background 0.3s ease-in-out;
            }

            #upload-btn:hover {
                background: #0056b3;
            }

            input[type="file"] {
                display: none;
            }

            .textarea {
                width: 100%;
                padding: 16px 20px;
                border-radius: 12px;
                border: 2px solid transparent;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                font-weight: 500;
                font-size: 16px;
                color: #495057;
                outline: none;
                transition: all 0.3s ease;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
                resize: vertical;
                font-family: Arial, sans-serif;
            }

            .textarea:focus {
                border-color: #080a60;
                background: #ffffff;
                box-shadow: 0 0 0 4px rgba(8, 10, 96, 0.1), inset 0 2px 4px rgba(0, 0, 0, 0.05);
                transform: scale(1.02);
                outline: none;
            }

            .textarea::placeholder {
                color: #888;
                font-style: italic;
            }

            .textarea:invalid {
                border-color: #e74c3c;
            }

            .textarea:valid {
                border-color: #080a60;
            }

        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }

        .summary {
            background-color: #FFE5EC;
            padding: 15px;
            font-size: 16px;
            line-height: 1.0;
        }

            .formbold-form-input {
                width: 100%;
                padding: 16px 20px;
                border-radius: 12px;
                border: 2px solid transparent;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                font-weight: 500;
                font-size: 16px;
                color: #495057;
                outline: none;
                resize: none;
                transition: all 0.3s ease;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .formbold-form-input:focus {
                border-color: #080a60;
                background: #ffffff;
                box-shadow: 0 0 0 4px rgba(8, 10, 96, 0.1), inset 0 2px 4px rgba(0, 0, 0, 0.05);
                transform: scale(1.02);
            }

            .modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
            }
            .modal-content {
                background-color: #fff;
                margin: 10% auto;
                padding: 20px;
                border-radius: 8px;
                width: 50%;
                position: relative;
            }
            .close {
                position: absolute;
                top: 5px;
                right: 20px;
                font-size: 24px;
                cursor: pointer;
            }
            .btn {
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }

            /* Individual date items */
            .upcoming .dates .item {
                text-align: center;
                width: 50px;
                padding: 10px;
                border-radius: 5px;
                transition: background 0.3s ease-in-out;
            }

            .upcoming .dates .item.active {
                background: #007bff;
                color: white;
            }  

            .bottom-container .upcoming{
                width: 50%;
                height: 500px;  /* Set the height of the container */
                overflow: auto; /* Enable scrolling if the content overflows */
            }

            .bottom-container .upcoming .dates{
                display: flex;
                justify-content: space-between;
                margin-bottom: 40px;
            }

            .bottom-container .upcoming .dates .item{
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 6px;
            }

            .bottom-container .upcoming .dates .item h5{
                font-weight: 600;
            }

            .bottom-container .upcoming .dates .item a{
                color: #000;
                font-size: 13px;
                padding: 5px 9px;
                border-radius: 50%;
                font-weight: 600;
                transition: all 0.3s ease;
            }

            .bottom-container .upcoming .dates .item.active a,
            .bottom-container .upcoming .dates .item a:hover{
                color: #fff;
                background: #031224;
            }

            .bottom-container .upcoming .events{
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .bottom-container .upcoming .events .item{
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #eff6ff;
                padding: 10px;
                border-radius: 10px;
            }

            .bottom-container .upcoming .events .item > i{
                cursor: pointer;
            }

            .bottom-container .upcoming .events .item > div{
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .bottom-container .upcoming .events .item > div i{
                font-size: 30px;
            }

            .bottom-container .upcoming .events .item .event-info a{
                font-size: 14px;
                color: #000;
                font-weight: 500;
            }

            .bottom-container .upcoming .events .item .event-info p{
                font-size: 13px;
                color: #9b9b9b;
            }

            .completion-status {
                font-weight: bold;
                margin-bottom: 10px;
            }

            .progress-bar {
                width: 100%;
                height: 8px;
                background-color: #ddd;
                border-radius: 5px;
                overflow: hidden;
            }

            .progress {
                height: 100%;
                background-color: #613faf;
                width: 0%;
                transition: width 0.5s;
            }

            .event-info {
                display: flex;
                flex-direction: column;
                gap: 10px;
                padding: 10px;
                background-color: #f8f8f8;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .event-info a {
                font-size: 18px;
                font-weight: bold;
                color: #2c3e50;
                text-decoration: none;
                transition: color 0.3s ease;
            }

            .event-info a:hover {
                color: #3498db; /* Blue color on hover */
            }

            .event-info p {
                font-size: 14px;
                color: #7f8c8d;
                margin: 0;
            }

            .job-details-link {
                font-size: 14px;
                color: #3498db;
                text-decoration: none;
                margin-top: 5px;
            }

            .item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }

            .item i {
                font-size: 24px;
                color: #3498db;
            }

            /* Adjust button sizes */
            .availability-status form button {
                padding: 6px 12px;
                font-size: 12px;
                border-radius: 5px;
                transition: background-color 0.3s, transform 0.2s;
            }


            .availability-status form button {
                padding: 5px 10px;
                font-size: 12px;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                margin: 5px;
                transition: background-color 0.3s ease-in-out, transform 0.2s;
            }

            /* Accept Button (Confirmed) */
            .availability-status form button[name='availability_status'][value='Available'] {
                background-color: #28a745; /* Green */
                color: white;
            }

            .availability-status form button[name='availability_status'][value='Available']:hover {
                background-color: #218838; /* Darker green on hover */
                transform: scale(1.05);
            }

            /* Decline Button (Not Available) */
            .availability-status form button[name='availability_status'][value='Not Available'] {
                background-color: #dc3545; /* Red */
                color: white;
            }

            .availability-status form button[name='availability_status'][value='Not Available']:hover {
                background-color: #c82333; /* Darker red on hover */
                transform: scale(1.05);
            }

            /* Status Badges - Show always */
            .status-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                margin-bottom: 8px;
            }
            .status-accepted {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .status-declined {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .status-pending {
                background-color: #fff3cd;
                color: #856404;
                border: 1px solid #ffeeba;
            }
            /* Buttons always visible */
            .availability-status .btn-accept,
            .availability-status .btn-decline {
                padding: 6px 14px;
                font-size: 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                margin: 3px;
                font-weight: 600;
                transition: all 0.2s ease;
            }
            .availability-status .btn-accept {
                background-color: #28a745;
                color: white;
            }
            .availability-status .btn-accept:hover {
                background-color: #218838;
                transform: scale(1.05);
            }
            .availability-status .btn-decline {
                background-color: #dc3545;
                color: white;
            }
            .availability-status .btn-decline:hover {
                background-color: #c82333;
                transform: scale(1.05);
            }
            .availability-status .btn-accept:disabled,
            .availability-status .btn-decline:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
            }

            /* Responsive adjustments */
            @media (max-width: 600px) {
                .wrapper {
                    width: 90%;
                }
            }

            .alert {
                padding: 15px 20px;
                margin: 15px 0;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 500;
                width: 100%;
                box-sizing: border-box;
                animation: fadeSlideDown 0.9s ease-in-out;
                opacity: 1;
                transition: opacity 0.9s ease-out;
            }

            .alert.success {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .alert.error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            @keyframes fadeSlideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
            }
        /* Header Actions Container - Groups theme toggle, search, and notifications */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: auto;
        }

/* Compact Search in Header */
        .header-search {
            position: relative;
            display: flex;
            align-items: center;
        }

        .header-search input {
            padding: 8px 40px 8px 15px;
            border-radius: 25px;
            border: 1px solid #ddd;
            font-size: 14px;
            width: 200px;
            transition: all 0.3s ease;
            background: #f5f5f5;
        }

        .header-search input:focus {
            width: 250px;
            border-color: #667eea;
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .header-search button {
            position: absolute;
            right: 8px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            transition: color 0.3s ease;
        }

        .header-search button:hover {
            color: #667eea;
        }

        .header-search button i {
            font-size: 18px;
        }

        body.dark-mode .header-search input {
            background: #2a2a2a;
            border-color: #444;
            color: #fff;
        }

        body.dark-mode .header-search input::placeholder {
            color: #888;
        }

        body.dark-mode .header-search .search-icon {
            color: #888;
        }

        /* Theme Toggle in Header */
        .theme-toggle-container {
            display: flex;
            align-items: center;
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background: #f0f0f0;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s ease;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 0;
        }

        .theme-toggle i {
            font-size: 20px;
            position: absolute;
            transition: opacity 0.3s ease;
        }

        .theme-toggle i.bx-sun {
            opacity: 0;
        }

        #theme-toggle:checked + .theme-toggle {
            background: #333;
        }

        #theme-toggle:checked + .theme-toggle i.bx-moon {
            opacity: 0;
        }

        #theme-toggle:checked + .theme-toggle i.bx-sun {
            opacity: 1;
            color: #fff;
        }

        /* =======================
        🌙 Dark Mode Styles
        ======================= */
        body.dark-mode {
            background-color: #0d1117;
            color: #f0f0f0;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Sidebar, navbar, general sections */
        body.dark-mode .top-container,
        body.dark-mode .nav,
        body.dark-mode .status {
            background-color: #161b22 !important;
            color: #ffffff !important;
        }

        /* Jobs for you section */
        body.dark-mode .prog-status {
            background-color: #1f1f1f !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        /* Scheduled interviews section */
        body.dark-mode .upcoming {
            background-color: #1f1f1f !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        /* Titles and headings inside both sections */
        body.dark-mode .prog-status h4,
        body.dark-mode .upcoming h4,
        body.dark-mode .prog-status h6,
        body.dark-mode .upcoming h6,
        body.dark-mode .prog-status p,
        body.dark-mode .upcoming p {
            color: #ffffff !important;
        }

        /* Buttons and pills */
        body.dark-mode .quiz-cta-btn {
            background: linear-gradient(135deg, #3a3aff, #764ba2);
            color: #ffffff;
        }

        body.dark-mode button,
        body.dark-mode .search-button {
            background-color: #2980b9 !important;
            color: #ffffff !important;
            border: none;
        }

        body.dark-mode button:hover,
        body.dark-mode .search-button:hover {
            background-color: #3498db !important;
        }

        /* Search input field */
        body.dark-mode input[type="search"],
        body.dark-mode input[type="text"] {
            background-color: #2a2a2a !important;
            color: #ffffff !important;
            border: 1px solid #444 !important;
        }

        body.dark-mode input[type="search"]::placeholder {
            color: #bbb !important;
        }

        /* Cards inside job listing or events */
        body.dark-mode .job-listing,
        body.dark-mode .event-info,
        body.dark-mode .item {
            background-color: #2a2a2a !important;
            color: #ffffff !important;
            border: 1px solid #444;
        }

        /* Calendar styling */
        body.dark-mode .dates .item a {
            color: #ffffff !important;
            background-color: transparent;
        }

        body.dark-mode .dates .item.active a {
            background-color: #2980b9 !important;
            color: #ffffff !important;
        }

        body.dark-mode .dates .item h5 {
            color: #ffffff !important;
        }

        /* Interview cards & job filters */
        body.dark-mode .bg-blue,
        body.dark-mode .bg-green,
        body.dark-mode .bg-orange,
        body.dark-mode .bg-red {
            color: #ffffff !important;
        }

                    /* Transitions for smooth toggle */
                    body.dark-mode *,
                    body * {
                        transition: background-color 0.3s ease, color 0.3s ease;
                    }

        /* Modern styling for post area (job listings) and upcoming section */
        :root {
            --poppins: 'Poppins', sans-serif;
            --lato: 'Lato', sans-serif;
            --light: #F9F9F9;
            --blue: #3C91E6;
            --light-blue: #CFE8FF;
            --grey: #eee;
            --dark-grey: #AAAAAA;
            --dark: #342E37;
            --red: #DB504A;
            --yellow: #FFCE26;
            --light-yellow: #FFF2C6;
            --orange: #FD7238;
            --light-orange: #FFE0D3;
        }

        .job-listing {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 20px;
            width: 100%;
            max-width: 1200px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .job-listing::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #e74c3c 0%, #3498db 100%);
        }

        .job-listing:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        input[type="search"] {
            width: 100%;
            padding: 13px 22px;
            border-radius: 5px;
            border: 2px solid #dde3ec;
            background: #ffffff;
            font-weight: 500;
            font-size: 16px;
            color: #536387;
            outline: none;
            transition: border-color 0.3s ease;
        }

        input[type="search"]:focus {
            border-color: var(--blue);
        }

        input[type="submit"] {
            background: var(--blue);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 16px;
        }

        input[type="submit"]:hover {
            background: #2980b9;
        }

        #load-more {
            background: var(--blue);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        #load-more:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(60, 145, 230, 0.4);
        }

        .upcoming {
            background: var(--light);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .upcoming .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .upcoming .header h4 {
            color: var(--dark);
            font-size: 20px;
        }

        .upcoming .dates {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .upcoming .dates .item {
            text-align: center;
            width: 50px;
            padding: 10px;
            border-radius: 8px;
            transition: background 0.3s;
            cursor: pointer;
        }

        .upcoming .dates .item.active {
            background: var(--blue);
            color: white;
        }

        .upcoming .dates .item h5 {
            font-weight: 600;
        }

        .upcoming .dates .item a {
            color: var(--dark);
            font-size: 13px;
            padding: 5px 9px;
            border-radius: 50%;
            font-weight: 600;
            transition: all 0.3s;
        }

        .upcoming .dates .item.active a,
        .upcoming .dates .item a:hover {
            color: white;
            background: var(--dark);
        }

        .upcoming .events {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .upcoming .events .item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #eff6ff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .upcoming .events .item:hover {
            transform: translateY(-2px);
        }

        .upcoming .events .item > i {
            cursor: pointer;
            color: var(--red);
        }

        .upcoming .events .item > div {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .upcoming .events .item > div i {
            font-size: 30px;
            color: var(--blue);
        }

        .upcoming .events .item .event-info a {
            font-size: 16px;
            color: var(--dark);
            font-weight: 500;
            text-decoration: none;
        }

        .upcoming .events .item .event-info p {
            font-size: 14px;
            color: var(--dark-grey);
            margin: 0;
        }
        body.dark-mode .con {
            background: #1f1f1f !important; /* Dark background */
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 10px 30px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .profile-close-btn {
            color: #f0f0f0; /* White close button */
        }
                /* Profile Header Section (Professional Summary/Title Area) */
        body.dark-mode .profile-header {
            background: rgba(31, 31, 31, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* General Sections (Personal Info, Education, Skills, Work) */
        body.dark-mode .section,
        body.dark-mode .pro-ski {
            background: #2a2a2a !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 1px 8px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .section:hover,
        body.dark-mode .pro-ski:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        /* Titles and Headings */
        body.dark-mode .pro-tent h4,
        body.dark-mode .pro-tent h6,
        body.dark-mode .section h6 {
            color: #f0f0f0 !important; /* Light text for headings */
        }

        /* Input Fields Container */
        body.dark-mode .inputfield {
            background: #2a2a2a !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .inputfield label {
            color: #ccc !important; /* Light text for labels */
        }
                /* Inputs and Textareas */
        body.dark-mode .input,
        body.dark-mode .textarea,
        body.dark-mode .formbold-form-input {
            border-color: #555 !important;
            background: #333 !important; /* Dark input background */
            color: #f0f0f0 !important; /* Light text color */
        }

        body.dark-mode .input:focus,
        body.dark-mode .textarea:focus,
        body.dark-mode .formbold-form-input:focus {
            border-color: #667eea !important; /* Focus glow remains */
        }

        /* Professional Summary Display */
        body.dark-mode .summary {
            background: #333 !important; /* Dark background for summary */
            color: #f0f0f0 !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .summary::before {
            color: rgba(255, 255, 255, 0.1) !important;
        }
        /* Add/Update this to target the four profile update divs */
        body.dark-mode div[style*="grid-area: personal"],
        body.dark-mode div[style*="grid-area: education"],
        body.dark-mode div[style*="grid-area: skills"],
        body.dark-mode div[style*="grid-area: work"] {
            background: #2a2a2a !important; /* Apply dark background */
            color: #f0f0f0 !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Ensure the headings remain visible (This should already be covered, but good to double-check) */
        body.dark-mode div[style*="grid-area: education"] h6,
        body.dark-mode div[style*="grid-area: skills"] h6,
        body.dark-mode div[style*="grid-area: personal"] h6,
        body.dark-mode div[style*="grid-area: work"] h6 {
            color: #f0f0f0 !important;
        }
            .con {
        text-align: center;
        /* ... (Keep existing layout properties) ... */
        padding: 30px;
    }

    /* New: Structure the main content area */
    .pro-tent {
        display: flex; /* Change from grid to flex for better header control */
        flex-direction: column;
        gap: 30px;
        padding: 20px 0;
    }

    /* Enhanced Profile Header (Title and Summary) */
    .profile-flex {
        display: grid;
        grid-template-columns: 200px 1fr; /* Profile pic width fixed, info takes remaining space */
        gap: 30px;
        align-items: center;
        padding: 20px;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        border-radius: 20px;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }

    .photo-section {
        text-align: center;
    }

    .info-section {
        text-align: left;
    }
    /* Main Profile Update Grid - NEW */
    .profile-update-grid {
        display: grid;
        /* Two columns on large screens */
        grid-template-columns: 1fr 1fr; 
        /* Template areas help with mobile reordering */
        grid-template-areas:
            "personal education"
            "skills work"
            "buttons buttons";
        gap: 30px;
        margin-top: 30px;
    }

    /* Individual Section Styling (Applied to Personal, Education, Skills, Work divs) */
    .profile-section-card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 249, 250, 0.95));
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.4);
        transition: all 0.3s ease;
        position: relative;
    }

    .profile-section-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .profile-section-card h6 {
        color: #080a60;
        font-weight: 700;
        margin-bottom: 25px;
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 2px solid rgba(102, 126, 234, 0.3);
        padding-bottom: 10px;
    }

    .profile-section-card h6 i {
        color: #667eea;
        font-size: 20px;
    }

    /* Input Fields - Added a dark mode fix for labels here */
    .inputfield label {
        font-weight: 700;
        color: #080a60; /* Light Mode Default */
    }

    /* Professional Summary Card */
    .summary {
        background: linear-gradient(135deg, #FFE5EC 0%, #FFC2D1 50%, #FFB3BA 100%);
        color: #333;
    }

    /* Responsive adjustments for the new grid */
    @media (max-width: 992px) {
        .profile-update-grid {
            /* Single column on mobile/small tablets */
            grid-template-columns: 1fr;
            grid-template-areas:
                "personal"
                "education"
                "skills"
                "work"
                "buttons";
        }
    }

    @media (max-width: 768px) {
        .profile-flex {
            grid-template-columns: 1fr;
            text-align: center;
        }
    }

    /* Dark Mode Styling for New/Modified Elements */
    body.dark-mode .profile-section-card {
        background: #2a2a2a !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    body.dark-mode .profile-section-card h6 {
        color: #f0f0f0 !important;
        border-bottom-color: rgba(102, 126, 234, 0.1);
    }

    body.dark-mode .inputfield label {
        color: #ccc !important; /* Dark Mode Label */
    }
    /* --- CSS VARIABLES (THEMING) --- */
    :root {
        /* Light Mode Defaults */
        --bg-card: #ffffff;
        --bg-input: #f9f9f9;
        --border-input: #f1f1f1;
        --text-main: #333333;       /* Dark text for light mode */
        --text-muted: #555555;
        --text-label: #666666;
        --shadow-color: rgba(0, 0, 0, 0.05);
        
        /* Accents */
        --accent-personal: #6c5ce7; 
        --accent-edu: #00b894;     
        --accent-skill: #e17055;   
        --accent-work: #0984e3;    
        --primary-color: #4a90e2;
    }

    /* --- DARK MODE OVERRIDES --- */
    body.dark-mode {
        --bg-card: #1e1e1e;       
        --bg-input: #2d2d2d;      
        --border-input: #404040;  
        --text-main: #ffffff;     /* IMPORTANT: Text turns White in dark mode */
        --text-muted: #cccccc;    /* Muted text turns Light Grey */
        --text-label: #b0b0b0;    
        --shadow-color: rgba(0, 0, 0, 0.5);
    }

    /* --- TYPOGRAPHY FIXES --- */
    /* This specifically fixes the invisible title issue */
    #title-display {
        font-weight: bold;
        color: var(--text-main) !important; /* Forces color change */
        font-size: 1.1rem;
        margin-bottom: 10px;
    }

    #summary-display {
        color: var(--text-muted) !important; /* Forces summary to be readable */
        line-height: 1.6;
    }

    /* --- FORM STYLING --- */
    .formbold-form-input, .textarea {
        width: 100%;
        padding: 12px 15px;
        border-radius: 10px;
        border: 2px solid var(--border-input);
        background: var(--bg-input);
        font-size: 14px;
        color: var(--text-main); /* Input text matches main text color */
        outline: none;
        transition: all 0.3s ease;
        margin-top: 5px;
        box-sizing: border-box;
    }

    .formbold-form-input:focus, .textarea:focus {
        border-color: var(--primary-color);
        box-shadow: 0 5px 15px rgba(74, 144, 226, 0.15);
    }

    .inputfield label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-label);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .inputfield {
        margin-bottom: 18px;
    }

    /* --- CARD STYLING --- */
    .unique-card {
        background: var(--bg-card);
        transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    }
    
    .unique-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px var(--shadow-color) !important;
    }

    /* Fix inner boxes in dark mode */
    body.dark-mode .education-entry, 
    body.dark-mode #work-experience-fields > div {
        background: #252525 !important; 
        border-color: #444 !important;
    }

    /* Button Styling */
    .btn {
        cursor: pointer;
        transition: 0.3s;
    }
    .btn:hover {
        opacity: 0.9;
        transform: scale(1.02);
    }

    /* =======================
   📋 Candidate Information Items (Horizontal Layout)
   ======================= */
.items-list {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  gap: 15px;
  width: 100%;
}

.items-list .item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 12px 10px;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  min-width: 0;
}

.items-list .item:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.items-list .item .info {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  text-align: left;
  width: 100%;
  gap: 10px;
}

.items-list .item .info > div {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.items-list .item h5 {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-main);
  margin-top: 10px;
}

.items-list .item i {
  font-size: 24px;
  color: var(--primary-color);
  align-self: center;
  flex-shrink: 0;
}

.items-list .completion-status {
  font-size: 10px;
  margin-bottom: 4px;
}

.items-list .completion-status p {
  font-size: 10px;
  margin: 0;
}

.items-list .progress-bar {
  height: 4px;
  width: 70%;
}

/* Dark Mode for Candidate Information Section (.items-list) */
body.dark-mode .items-list {
  background: #1f1f1f !important;
  border-radius: 15px;
  padding: 15px;
}

body.dark-mode .items-list .item {
  background: linear-gradient(135deg, #2a2a2a 0%, #333333 100%) !important;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

body.dark-mode .items-list .item:hover {
  background: linear-gradient(135deg, #333333 0%, #3a3a3a 100%) !important;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
}

body.dark-mode .items-list .item h5 {
  color: #ffffff !important;
}

body.dark-mode .items-list .item i {
  color: #667eea !important;
}

body.dark-mode .items-list .completion-status {
  color: #cccccc !important;
}

body.dark-mode .items-list .completion-status p {
  color: #cccccc !important;
}

body.dark-mode .items-list .progress-bar {
  background-color: #444444 !important;
}

body.dark-mode .items-list .progress {
  background: linear-gradient(90deg, #667eea, #764ba2) !important;
}

/* =======================
   🔓 Logout Confirmation Modal
   ======================= */
.logout-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(6px);
    z-index: 99999;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.25s ease-out;
}

.logout-overlay.active {
    display: flex;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes scaleUp {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.logout-modal {
    background: #ffffff;
    border-radius: 20px;
    padding: 40px 35px 30px;
    width: 400px;
    max-width: 90vw;
    text-align: center;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
    animation: scaleUp 0.3s ease-out;
    position: relative;
}

.logout-modal .logout-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    font-size: 34px;
}

.logout-modal h3 {
    color: #1e293b;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 8px;
}

.logout-modal p {
    color: #64748b;
    font-size: 15px;
    line-height: 1.5;
    margin-bottom: 28px;
}

.logout-modal .logout-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.logout-modal .logout-actions button {
    padding: 12px 32px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 120px;
}

.logout-modal .btn-cancel-logout {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.logout-modal .btn-cancel-logout:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.logout-modal .btn-confirm-logout {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: #fff;
    box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35);
}

.logout-modal .btn-confirm-logout:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(220, 38, 38, 0.45);
}

/* Dark Mode for Logout Modal */
body.dark-mode .logout-modal {
    background: #1e293b;
}

body.dark-mode .logout-modal .logout-icon {
    background: linear-gradient(135deg, #450a0a, #7f1d1d);
}

body.dark-mode .logout-modal h3 {
    color: #f1f5f9;
}

body.dark-mode .logout-modal p {
    color: #94a3b8;
}

body.dark-mode .logout-modal .btn-cancel-logout {
    background: #334155;
    color: #e2e8f0;
    border-color: #475569;
}

body.dark-mode .logout-modal .btn-cancel-logout:hover {
    background: #475569;
}
</style>

</head>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById("theme-toggle");

    // Check saved preference
    if (localStorage.getItem("darkMode") === "enabled") {
        document.body.classList.add("dark-mode");
        themeToggle.checked = true;
    }

    themeToggle.addEventListener("change", function () {
        if (this.checked) {
            document.body.classList.add("dark-mode");
            localStorage.setItem("darkMode", "enabled");
        } else {
            document.body.classList.remove("dark-mode");
            localStorage.setItem("darkMode", "disabled");
        }
    });

    // Fetch notifications on page load to update the bell count
    fetchNotifications();
    
    // Toggle notification dropdown when bell is clicked
    const notificationContainer = document.getElementById('notification-container');
    if (notificationContainer) {
        notificationContainer.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('notification-dropdown');
            if (dropdown) {
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('notification-dropdown');
        const container = document.getElementById('notification-container');
        if (dropdown && container && !container.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});

// Function to toggle notification dropdown
function toggleNotificationDropdown(event) {
    if (event) {
        event.stopPropagation();
    }
    const dropdown = document.getElementById('notification-dropdown');
    if (dropdown) {
        // Use a data attribute to track visibility state
        if (!dropdown.dataset.visible) {
            dropdown.dataset.visible = 'true';
            dropdown.style.display = 'block';
        } else {
            dropdown.dataset.visible = dropdown.dataset.visible === 'true' ? 'false' : 'true';
            dropdown.style.display = dropdown.dataset.visible === 'true' ? 'block' : 'none';
        }
    }
}

// Function to fetch notifications from the server
function fetchNotifications() {
    fetch('fetch_notifications.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            updateNotificationUI(data);
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
        });
}

// Function to update the notification UI
function updateNotificationUI(notifications) {
    const notificationList = document.getElementById('notification-list');
    const notificationCount = document.getElementById('notification-count');
    
    if (!notificationList || !notificationCount) return;
    
    // Update count
    const unreadCount = notifications.filter(n => n.is_read == 0).length;
    notificationCount.textContent = unreadCount;
    notificationCount.style.display = unreadCount > 0 ? 'flex' : 'none';
    
    // Clear existing notifications
    notificationList.innerHTML = '';
    
    if (notifications.length === 0) {
        notificationList.innerHTML = '<div class="notification-item"><div class="message">No notifications</div></div>';
        return;
    }
    
    // Add notifications
    notifications.forEach(notification => {
        const item = document.createElement('div');
        item.className = 'notification-item' + (notification.is_read == 0 ? ' unread' : '');
        item.dataset.id = notification.notification_id;
        
        // Format the timestamp
        const date = new Date(notification.created_at);
        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        item.innerHTML = `
            <div class="type">${notification.type || 'Notification'}</div>
            <div class="message">${notification.message}</div>
            <div class="timestamp">${formattedDate}</div>
        `;
        
        // Mark as read when clicked
        item.addEventListener('click', function() {
            markNotificationAsRead(notification.notification_id);
        });
        
        notificationList.appendChild(item);
    });
}

// Function to mark a single notification as read
function markNotificationAsRead(notificationId) {
    fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fetchNotifications(); // Refresh notifications
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

// Function to mark all notifications as read
function markAllAsRead() {
    fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'mark_all=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fetchNotifications(); // Refresh notifications
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}
</script>

<body>

<div class="top-container">
    <div class="nav">

        <div class="profile-update-notice" role="alert" id="profile-update-notice">
            <button type="button" class="profile-notice-exit" onclick="document.getElementById('profile-update-notice').style.display='none'" aria-label="Dismiss">&times;</button>
            <div class="notice-icon">🔔</div>
            <div class="notice-content">
                <div class="notice-title" style="color: #d9e1ec">Important</div>
                <div class="notice-text" style="color: #dbe6f0">Before applying for a job, make sure your profile is updated to match the job requirements of the position you are applying for, as this is important for the auto-evaluation process.</div>
            </div>
        </div>

        <div class="nav-links">
            <a href="my_profile.php">My Profile</a>
            <a href="my_applications.php">Applied Jobs</a>
            <a href="my_interviews.php">My Interviews</a>
            <!--<a href="assessments.php">Assessments</a>-->
            <a href="interview_prep.php">Interview Prep</a>
            <a href="applicant_settings.php">Settings</a>
            <a href="applicant_access_guide.php">Applicant Guide</a>
        </div>
        
        <!-- Header Actions: Theme Toggle, Search, and Notifications grouped together -->
        <div class="header-actions">
            <!-- Search Bar in Header -->
            <div class="header-search">
                <form method="GET" action="global_search.php" style="display: flex; align-items: center;">
                    <input type="text" name="q" placeholder="Search jobs, applications, interviews..." value="<?= isset($_GET['q']) ? $_GET['q'] : '' ?>">
                    <button type="submit" style="background: none; border: none; cursor: pointer; padding: 5px;">
                        <i class='bx bx-search search-icon'></i>
                    </button>
                </form>
            </div>
            
            <!-- Theme Toggle -->
            <div class="theme-toggle-container">
                <input type="checkbox" id="theme-toggle" hidden>
                <label for="theme-toggle" class="theme-toggle" title="Toggle dark mode">
                    <i class='bx bx-moon'></i>
                    <i class='bx bx-sun'></i>
                </label>
            </div>
             <!--   <button onclick="openNotificationsModal()">
                    🔔
                    <span id="notificationBadge"></span>
                </button>
           <!--Added notification to work with modal -->
           <!-- <div id="notificationsModal" class="modal">


                <div class="modal-content">
                    <span class="close" onclick="closeNotificationsModal()">
                        &times;
                    </span>

                    <div id="notificationsModalContent">
                        Loading...
                    </div>
                </div>
            </div>    -->        

            <!-- Notification Bell -->
            <div id="notification-container" class="notification-container">
                <i class="bx bx-bell" style="cursor: pointer;" onclick="toggleNotificationDropdown(event)"></i>
                <span id="notification-count" class="notification-count">0</span>
                <div id="notification-dropdown" class="notification-dropdown">
                    <div class="notification-header">
                        <h5>Notifications</h5>
                        <button onclick="markAllAsRead()">Mark All Read</button>
                    </div>
                    <div id="notification-list" class="notification-list">
                        <!-- Notifications will be loaded here -->
                    </div>
                </div>
            </div> 
        </div>

        <div class="right-section">
            <div class="profile">
                <div class="info">
                    <a href="my_profile.php">
                        <img src="display_profile_pic.php" alt="Profile Picture" width="170" height="170"
                            onerror="this.onerror=null; this.src='img/default_photo.jpg';">
                    </a>
                    <div>
                        <a href="my_profile.php"></a>
                    </div>
                </div>
            </div>
                <a href="logout.php" onclick="showLogoutModal(); return false;">
                    <i class='bx bx-log-out-circle'></i>
                </a>
            </div>
        </div>
        <div class="status">
            <div class="header">
                <h4 id="big"> Candidate Information</h4>
            </div>
            <div class="items-list">
                                <a class="item pers js-personal" href="my_profile.php?section=personal" style="text-decoration:none; color: inherit; display:flex;">

                    <div class="info">
                        <div>
                            <h5>Personal Information</h5>
                        </div>
                        <i class="bx bx-user"></i>
                    </div>
                    <div class="progress">
                        <div class="bar"></div>
                    </div>
                </a>
                <div class="item pers" onclick="window.location.href='my_profile.php?section=education'" role="link" tabindex="0">
                    <div class="info">

                        <div>
                            <h5>Education</h5>
                        </div>
                        <i class="bx bx-book"></i>
                    </div>
                    <div class="progress">
                        <div class="bar"></div>
                    </div>
                </div>
                <div class="item pers" onclick="window.location.href='my_profile.php?section=skills'" role="link" tabindex="0" id="skills">
                    <div class="info">
                        <div>
                            <h5>Skills</h5>
                        </div>
                        <i class="bx bx-brain"></i>
                    </div>
                    <div class="progress">
                        <div class="bar"></div>
                    </div>
                </div>
                <div class="item pers" onclick="window.location.href='my_profile.php?section=work_experience'" role="link" tabindex="0" id="experience">
                    <div class="info">
                        <div>
                            <h5>Work Experience</h5>
                        </div>
                        <i class="bx bx-briefcase"></i>
                    </div>
                    <div class="progress">
                        <div class="bar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <div class="bottom-container">

            <style>
                .profile-update-notice{
                    background: linear-gradient(135deg, rgba(102,126,234,0.15) 0%, rgba(118,75,162,0.12) 100%);
                    border: 1px solid rgba(102,126,234,0.25);
                    border-radius: 16px;
                    padding: 18px 20px;
                    display: flex;
                    align-items: flex-start;
                    gap: 14px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
                    margin-bottom: 18px;
                }
                .profile-update-notice .notice-icon{
                    width: 36px;
                    height: 36px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 12px;
                    background: rgba(102,126,234,0.15);
                    color: #080a60;
                    font-size: 18px;
                    flex-shrink: 0;
                }
                .profile-update-notice .notice-title{
                    font-weight: 800;
                    color: #ffffff;
                    font-size: 14px;
                    letter-spacing: 0.4px;
                    text-transform: uppercase;
                    margin-bottom: 6px;
                }
                .profile-update-notice .notice-text{
                    color: #2c3e50;
                    font-size: 14px;
                    line-height: 1.35;
                    font-weight: 600;
                }
.profile-notice-exit{border: none !important;
                    position: absolute;
                    top: 10px;
                    right: 12px;
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    border: 1px solid rgba(255,255,255,0.4);
                    background: rgba(255,255,255,0.25);
                    color: #ffffff;
                    cursor: pointer;
                    font-size: 20px;
                    line-height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0;
                    transition: transform 0.15s ease, background 0.15s ease;
                }
                .profile-notice-exit:hover{ transform: scale(1.08); background: rgba(255,255,255,0.35); }
                .profile-update-notice{ position: relative; }
                body.dark-mode .profile-update-notice{
                    background: linear-gradient(135deg, rgba(102,126,234,0.18) 0%, rgba(118,75,162,0.15) 100%);
                    border: 1px solid rgba(102,126,234,0.35);
                    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
                }
                body.dark-mode .profile-update-notice .notice-icon{
                    background: rgba(102,126,234,0.18);
                    color: #ffffff;
                }
                body.dark-mode .profile-update-notice .notice-title{
                    color: #ffffff;
                }
                body.dark-mode .profile-update-notice .notice-text{
                    color: #f0f0f0;
                }
            </style>

        <div class="prog-status">
            <div class="header">
                <h4>Jobs for you</h4>
            </div>
            <!-- Career Quiz CTA Button (Smaller)
            <div style="margin: 20px 0; text-align: center;">
                <a href="career_quiz.php" class="quiz-cta-btn">
                    <i class='bx bx-poll'></i> Discover Your Ideal Career
                </a>
            </div> -->

<style>
    .quiz-cta-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 12px 28px;
        border-radius: 50px;
        font-size: 16px;
        font-weight: 600;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
    }
    .quiz-cta-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    .quiz-cta-btn i {
        font-size: 20px;
    }
    @media (max-width: 768px) {
        .quiz-cta-btn {
            font-size: 15px;
            padding: 10px 24px;
            gap: 8px;
        }
    }
</style>
    <!-- Start post Area -->
			<section class="post-area section-gap">
				<div class="container">
					<div class="row justify-content-center d-flex">
						<div id="job-postings-container" class="col-lg-12 post-list">
							<form method="GET" action="">
								<label for="search"></label><br>
								<div class="search-wrapper">
									<i class="fa fa-search search-icon-inside"></i>
									<input type="text" id="search" name="search" placeholder="Search by: Company, Position, Department, Location" value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
								</div>
							</form>

							<div id="job-results">
								
							</div>

						</div>
						
					</div>
					<button id="load-more" onclick="loadMore()"><a>Explore More Jobs</a></button>
				</div>
			</section>
			<!-- End post Area -->
        </div>
        <div class="upcoming">
            <div class="header">
                <h4>Scheduled Interviews</h4>
                <a href="#">Upcoming <i class="bx bx-chevron-down" onclick="toggledown(this)"></i></a>
            </div>

            <div class="dates">
                <?php
                // Get today's date
                $today = date('Y-m-d');

                // Display next 7 days dynamically
                for ($i = 0; $i < 7; $i++) {
                    $date = date('Y-m-d', strtotime("+$i days"));
                    $dayName = date('D', strtotime($date));
                    $dayNumber = date('d', strtotime($date));

                    // Check if the current day is today
                    $isToday = ($date === $today) ? "active" : ""; // Add 'active' class if it's today

                    echo "<div class='item $isToday'>
                            <h5>$dayName</h5>
                            <a href='#'>$dayNumber</a>
                        </div>";
                }
                ?>
            </div>

            <div class="events">
                <?php
                if ($interview_results->num_rows > 0) {
                    while ($row = $interview_results->fetch_assoc()) {
                        $availability_status = $row['availability_status'];
                        $job_id = $row['job_id'];
                        
                        // Format the interview date to exclude seconds (e.g., HH:MM)
                        $formatted_interview_date = date("Y-m-d H:i", strtotime($row['interview_date']));
                        
                        // Determine status display
                        $status_label = '';
                        $status_class = '';
                        if ($availability_status == 'Available') {
                            $status_label = '✓ Accepted';
                            $status_class = 'status-accepted';
                        } elseif ($availability_status == 'Not Available') {
                            $status_label = '✗ Declined';
                            $status_class = 'status-declined';
                        } elseif ($availability_status == 'Pending') {
                            $status_label = '⏳ Pending';
                            $status_class = 'status-pending';
                        }
                        
                        echo "<div class='item'>
                                <div>
                                    <div class='event-info'>
                                        <a href='my_interviews.php'>{$row['job_title']} role interview at {$row['company_name']}</a>
                                        <p>{$formatted_interview_date}</p>
                                        <a href='job_details.php?job_id={$job_id}' class='job-details-link'>View Job Details</a>
                                    </div>
                                </div>
                                <div class='availability-status'>
                                    <span class='status-badge {$status_class}'>{$status_label}</span>
                                    <form class='availability-form' data-interview-id='{$row['interview_id']}'>
                                        <input type='hidden' name='interview_id' value='{$row['interview_id']}'>
                                        <input type='hidden' name='ajax' value='1'>
                                        <button type='button' class='btn-accept' name='availability_status' value='Available' onclick='submitAvailability(this, \"Available\")'>Accept</button>
                                        <button type='button' class='btn-decline' name='availability_status' value='Not Available' onclick='submitAvailability(this, \"Not Available\")'>Decline</button>
                                    </form>
                                </div>
                              </div>";
                    }
                } else {
                    echo "<p>No scheduled interviews at the moment.</p>";
                }
                ?>
            </div>
        </div>

    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share Job Posting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="shareJobTitle"></p>
                    <div class="d-flex justify-content-around">
                        <button class="btn btn-success" onclick="shareToWhatsApp()"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                        <button class="btn btn-primary" onclick="shareToFacebook()"><i class="fab fa-facebook"></i> Facebook</button>
                        <button class="btn btn-info" onclick="shareToTwitter()"><i class="fab fa-twitter"></i> Twitter</button>
                        <button class="btn btn-secondary" onclick="shareToLinkedIn()"><i class="fab fa-linkedin"></i> LinkedIn</button>
                        <button class="btn btn-warning" onclick="shareViaEmail()"><i class="fas fa-envelope"></i> Email</button>
                        <button class="btn btn-dark" onclick="copyLink()"><i class="fas fa-copy"></i> Copy Link</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logout-overlay" class="logout-overlay" onclick="if(event.target===this) closeLogoutModal();">
        <div class="logout-modal">
            <div class="logout-icon">
                <i class='bx bx-log-out-circle'></i>
            </div>
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to log out? You will need to sign in again to access your account.</p>
            <div class="logout-actions">
                <button class="btn-cancel-logout" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-confirm-logout" onclick="proceedLogout()">Yes, Logout</button>
            </div>
        </div>
    </div>

</body>

<script>

let currentModalPage = 1;

function openNotificationsModal() {
    document.getElementById('notificationsModal').style.display = 'block';
    loadModalPage(1);
}

function closeNotificationsModal() {
    document.getElementById('notificationsModal').style.display = 'none';
}

function loadModalPage(page = 1) {

    currentModalPage = page;

    fetch('notifications_modal.php?page=' + page)
        .then(response => response.text())
        .then(data => {
            document.getElementById(
                'notificationsModalContent'
            ).innerHTML = data;
        });
}

    document.getElementById('summary-form').addEventListener('submit', function(e) {
        const confirmed = confirm("Are you sure you want to update your profile?");
        if (!confirmed) {
            e.preventDefault(); // Cancel form submission if user declines
        }
    });

    function loadNotificationCount() {

    fetch('notification_count.php')
        .then(response => response.text())
        .then(count => {

            const badge =
                document.getElementById('notificationBadge');

            if (count > 0) {
                badge.style.display = 'flex';
                badge.innerText = count;
            } else {
                badge.style.display = 'none';
            }
        });
}

loadNotificationCount();

setInterval(loadNotificationCount, 30000);
</script>

<script>
    const alertBox = document.getElementById('alertBox');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 1000);
        }, 10000); // 10 seconds
    }
</script>

<script>
function toggleEdit() {
    // Show input fields for title and summary, hide text display
    document.getElementById('title-display').style.display = 'none';
    document.getElementById('title-input').style.display = 'block';

    document.getElementById('summary-display').style.display = 'none';
    document.getElementById('summary-input').style.display = 'block';

    // Show Cancel & Update buttons, hide Edit button
    document.getElementById('edit-btn').style.display = 'none';
    document.getElementById('update-btn').style.display = 'block';
    document.getElementById('cancel-btn').style.display = 'block';
}

function cancelEdit() {
    // Hide input fields, show text display again
    document.getElementById('title-display').style.display = 'block';
    document.getElementById('title-input').style.display = 'none';

    document.getElementById('summary-display').style.display = 'block';
    document.getElementById('summary-input').style.display = 'none';

    // Show Edit button, hide Cancel & Update buttons
    document.getElementById('edit-btn').style.display = 'block';
    document.getElementById('update-btn').style.display = 'none';
    document.getElementById('cancel-btn').style.display = 'none';
}
</script>

<script>
function uploadProfilePic() {
    document.getElementById('upload-form').submit();
}
</script>

<script>
// Prevent profile from closing when clicking inside it (e.g., on textarea, buttons)
document.querySelector('.con').addEventListener('click', function(event) {
    event.stopPropagation();  // Prevent event from reaching the parent .profile element
});
</script>

<script>
    // Enable editing when the textarea is clicked
    function enableEditing() {
        var textarea = document.getElementById("professional_summary");
        textarea.removeAttribute("readonly");
    }

    // Toggle between displaying the summary and textarea
    function editSummary() {
        var summaryDisplay = document.getElementById("summary-display");
        var textarea = document.getElementById("professional_summary");
        var updateBtn = document.getElementById("update-btn");
        var editBtn = document.getElementById("edit-btn");

        // Toggle visibility of the paragraph and textarea
        if (summaryDisplay.style.display !== "none") {
            summaryDisplay.style.display = "none";
            textarea.style.display = "block";
            updateBtn.style.display = "inline-block"; // Show Update button
            editBtn.innerHTML = "Cancel"; // Change button text to Cancel
        } else {
            summaryDisplay.style.display = "block";
            textarea.style.display = "none";
            updateBtn.style.display = "none"; // Hide Update button
            editBtn.innerHTML = "Edit"; // Reset button text to Edit
        }
    }
</script>

<script>
document.getElementById("upload-btn").addEventListener("click", function() {
    document.getElementById("profile_picture").click();
});

document.getElementById("profile_picture").addEventListener("change", function() {
    document.getElementById("upload-form").submit();
});
</script>

<script>
    function showLogoutModal() {
        document.getElementById('logout-overlay').classList.add('active');
        return false;
    }

    function closeLogoutModal() {
        document.getElementById('logout-overlay').classList.remove('active');
    }

    function proceedLogout() {
        window.location.href = 'logout.php';
    }
</script>
    <script>
                document.getElementById("year").innerHTML = new Date().getFullYear();
    </script>
<!--//////////////////////////////////////////////////////////////////////////////////////////////////////////-->
    <script>
        function toggleDropdown(pers) {
            const dropdownContent = pers.querySelector('.dropdown-content');
            if (dropdownContent.style.display === "none" || dropdownContent.style.display === "") {
                dropdownContent.style.display = "block"; // Show dropdown
            } else {
                dropdownContent.style.display = "none"; // Hide dropdown
            }
        }
            // Function to toggle dropdown location
        function toggleDropdown(loc) {
            const dropdownContent = loc.querySelector('.dropdown-content');
            if (dropdownContent.style.display === "none" || dropdownContent.style.display === "") {
                dropdownContent.style.display = "block"; // Show dropdown
            } else {
                dropdownContent.style.display = "none"; // Hide dropdown
            }
        }
             // Function to toggle dropdown Comapny
        function toggleDropdownCompany(company) {
            const dropdownContent = company.querySelector('.dropdown-content');
            if (dropdownContent.style.display === "none" || dropdownContent.style.display === "") {
                dropdownContent.style.display = "block"; // Show dropdown
            } else {
                dropdownContent.style.display = "none"; // Hide dropdown
            }
        }
             // Function to toggle dropdown JobType
        function toggleDropdownJobTitle(jobtitle) {
            const dropdownContent = jobtitle.querySelector('.dropdown-content');
            if (dropdownContent.style.display === "none" || dropdownContent.style.display === "") {
                dropdownContent.style.display = "block"; // Show dropdown
            } else {
                dropdownContent.style.display = "none"; // Hide dropdown
            }
        }
              // Function to toggle dropdown location
        function toggleDropdownJobType(jobtype) {
            const dropdownContent = jobtype.querySelector('.dropdown-content');
            if (dropdownContent.style.display === "none" || dropdownContent.style.display === "") {
                dropdownContent.style.display = "block"; // Show dropdown
            } else {
                dropdownContent.style.display = "none"; // Hide dropdown
            }
        }
        // Function to close the dropdown on 'Cancel' button click
        function closeDropdown(event) {
            event.stopPropagation(); // Prevents closing due to the parent onclick event
            const dropdownContent = event.target.closest('.dropdown-content');
            dropdownContent.style.display = "none";
        }

        // Function to handle form submission and close dropdown
        function submitForm(event) {
            event.preventDefault(); // Stop form from reloading the page
            const dropdownContent = event.target.closest('.dropdown-content');
            
            // Process form data here if needed

            dropdownContent.style.display = "none"; // Close dropdown after submit
        }

        // Prevent dropdown from closing when interacting with form
        document.querySelectorAll('.dropdown-content').forEach((dropdown) => {
            dropdown.addEventListener('click', (event) => event.stopPropagation());
        });
    </script>
<!--//////////////////////////////////////////////////////////////////////////////////////////////////////////-->
   
   <!--
   <script>
        // JavaScript for Search Functionality
        const searchInput = document.getElementById('searchInput');
        const blocks = document.querySelectorAll('.block');

        searchInput.addEventListener('input', function () {
            const query = searchInput.value.toLowerCase();

            blocks.forEach(block => {
                const text = block.innerText.toLowerCase();
                if (text.includes(query)) {
                    block.style.display = 'block'; // Show matching block
                } else {
                    block.style.display = 'none'; // Hide non-matching block
                }
            });
        });
    </script>
-->
    <!--//////////////////////////////////////////////////////////////////////////////////////////////////////////////-->
    <script>
        // Toggle dropdown visibility
        document.querySelectorAll('.bd-highlight').forEach(block => {
            block.addEventListener('click', function () {
                const dropdown = this.querySelector('.dropdown');
                document.querySelectorAll('.dropdown').forEach(d => {
                    if (d !== dropdown) d.style.display = 'none'; // Hide others
                });
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block'; // Toggle this one
            });
        });
    
        // Hide dropdowns when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.bd-highlight')) {
                document.querySelectorAll('.dropdown').forEach(d => d.style.display = 'none');
            }
        });
    </script>
    <!--//////////////////////////////////////////////////////////////////////////////////////////////////////////-->
<!--
    <script>
        document.getElementById('searchInput').addEventListener('input', filterResults);
        document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', filterResults);
        });
    
        function filterResults() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const selectedFilters = Array.from(document.querySelectorAll('.filter-checkbox:checked')).map(cb => cb.value);
            const blocks = document.querySelectorAll('.bd-highlight');
    
            blocks.forEach(block => {
                const blockText = block.getAttribute('data-search').toLowerCase();
                const blockType = block.getAttribute('data-type');
    
                if (
                    (blockText.includes(searchValue) || !searchValue) &&
                    (selectedFilters.length === 0 || selectedFilters.includes(blockType))
                    /*(selectedFilters.includes(blockType) || selectedFilters.length === 0)*/
                ) {
                    block.classList.remove('hidden');
                } else {
                    block.classList.add('hidden');
                }
            });
        }
    </script>
-->
    <!--//////////////////////////////////////////////////////////////////////////////////////////////////////////-->
    <!--
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownContainer');
            if (dropdown.style.display === 'none') {
                dropdown.style.display = 'block';
            } else {
                dropdown.style.display = 'none';
            }
        }
    </script>-->
    <!--//////////////////////////////////////////////////////////////////////////////////////////////////////////-->
    <script src="assets/plugins/multi-select/js/jquery.multi-select.js"></script> <!-- Multi Select Plugin Js --> 
    <!--//////////////////////////////////////////////////////////////////////////////////////////////////////////-->
    <script src="./personalScript.js" type="text/javascript"></script>
    <!--//////////////////////////////////////////////////////////////////////////////////////////////////////////-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!--//////////////////////////////////////////////////////////////////////////////////////////////////////////-->
    <script src="AppScript.js"></script>

    <script src="js/vendor/jquery-2.2.4.min.js"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
			<script src="js/vendor/bootstrap.min.js"></script>			
			<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBhOdIF3Y9382fqJYt5I_sswSrEw5eihAA"></script>
  			<script src="js/easing.min.js"></script>			
			<script src="js/hoverIntent.js"></script>
			<script src="js/superfish.min.js"></script>	
			<script src="js/jquery.ajaxchimp.min.js"></script>
			<script src="js/jquery.magnific-popup.min.js"></script>	
			<script src="js/owl.carousel.min.js"></script>			
			<script src="js/jquery.sticky.js"></script>
			<script src="js/jquery.nice-select.min.js"></script>			
			<script src="js/parallax.min.js"></script>		
			<script src="js/mail-script.js"></script>	
			<script src="js/main.js"></script>	

			<script>
				var currentPage = 0; //Keep track of the current page
				var searchQuery = ''; //Store the search query

				//Function to load job postings using AJAX
				function loadMore() {
					//Disable the button to prevent multiple clicks
					$('#load-more').prop('disabled', true);

					//Get the search query from the input field
					searchQuery = $('#search').val();

					//AJAX request to fetch job postings
					$.ajax({
						url: 'fetch_jobs.php',
						type: 'POST',
						data: { 
							page: currentPage,
							search: searchQuery //Include search query in the request
						},
						success: function(response) {
							//Append the response to the job results container
							$('#job-results').append(response);

							//Increment the page number
							currentPage++;

							//Enable the button again
							$('#load-more').prop('disabled', false);

							//If no more job postings are found, hide the "Load More" button
							if (response === 'No job listings found.') {
								$('#load-more').hide();
							}
						}
					});
				}

				//Handle the search form submission
				$('#search-form').submit(function(e) {
					e.preventDefault(); //Prevent the form from submitting traditionally
					currentPage = 0; //Reset page number on new search
					$('#job-results').empty(); //Clear the previous results
					loadMore(); //Load the first batch of search results
				});

				//Initially load the first batch of job postings
				$(document).ready(function() {
					loadMore();
				});
			</script>

            <!-- Floating Chatbot Button & Container -->
            <!-- <div class="chatbot-container">
                <button class="chatbot-button" onclick="toggleChatbot()">Chat with us</button>
                <div class="chatbot-content" id="chatbotContent">
                    <iframe src="https://your-chatbot-url.com" frameborder="0"></iframe>
                </div><!-- Floating Chatbot Button -->
                <!--<button id="chatbot-fab" onclick="toggleChatbot()">
                    🤖
                </button>-->
    <div id="chatbot-container">
        <div id="chatbot-header">
            <span>💼 Career Advisor</span>
            <span id="close-btn">&times;</span>
        </div>
        <div id="chatbot-body">
            <div id="chatbot-messages">
                <div class="bot-msg">
                    <span>👋 Hello! I'm your personal career advisor. I can help you find jobs that match your skills and experience. Try asking me:
                        <div class="suggestion-pills">
                            <div class="suggestion-pill" onclick="sendSuggestedMessage('What jobs suit my skills?')">What jobs suit my skills?</div>
                            <div class="suggestion-pill" onclick="sendSuggestedMessage('How can I improve my profile?')">Improve my profile</div>
                            <div class="suggestion-pill" onclick="sendSuggestedMessage('Career advice for my field')">Career advice</div>
                        </div>
                    </span>
                </div>
            </div>
            <div class="typing-indicator">
                <span>Career Advisor is typing...</span>
            </div>
            <div id="chatbot-input-container">
                <input type="text" id="chatbot-input" placeholder="Ask me about career..." />
                <button id="send-btn">Send</button>
            </div>
        </div>
    </div>

<style>
    /* Modernized Jobs for You Section */
    .prog-status {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }
    .prog-status::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }
    .prog-status .header h4 {
        color: #080a60;
        font-weight: 700;
        font-size: 24px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .prog-status .header h4::before {
        content: '💼';
        font-size: 28px;
    }
    /* Modernized Filter Buttons */
    .bd-highlight {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 15px;
        padding: 20px;
        margin: 10px;
        transition: all 0.3s ease;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    .bd-highlight::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.5s;
    }
    .bd-highlight:hover::before {
        left: 100%;
    }
    .bd-highlight:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .bg-blue {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }
    .bg-green {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
    }
    .bg-orange {
        background: linear-gradient(135deg, #fd7e14, #e8680d);
        color: white;
    }
    .bg-red {
        background: linear-gradient(135deg, #dc3545, #bd2130);
        color: white;
    }
    .bd-highlight h6 {
        font-weight: 600;
        font-size: 16px;
        margin: 0;
    }
    /* Modernized Search Bar */
    .search-wrapper {
        position: relative;
        margin: 20px 0;
    }
    .search-wrapper input {
        width: 100%;
        padding: 15px 20px 15px 45px;
        border: 2px solid #ddd;
        border-radius: 25px;
        font-size: 16px;
        outline: none;
        transition: all 0.3s ease;
        background: #ffffff;
    }
    .search-wrapper input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }
    .search-icon-inside {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
        font-size: 18px;
        pointer-events: none;
    }
    .search-wrapper input:focus + .search-icon-inside {
        color: #667eea;
    }
    /* Dark mode support for search */
    body.dark-mode .search-wrapper input {
        background: #2a2a2a;
        border-color: #444;
        color: #fff;
    }
    body.dark-mode .search-wrapper input::placeholder {
        color: #888;
    }
    body.dark-mode .search-icon-inside {
        color: #888;
    }
    body.dark-mode .search-wrapper input:focus + .search-icon-inside {
        color: #667eea;
    }
    /* Modernized Load More Button */
    #load-more {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 25px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        margin-top: 20px;
    }
    #load-more:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
    }
    /* Modernized Bottom Container for Equal Heights and Spacing */
    .bottom-container {
        display: flex;
        gap: 20px;
        padding-top: 20px;
        align-items: stretch;
    }

    /* Modernized Jobs for You Section */
    .prog-status {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-top: 0;
        margin-bottom: 0;
        position: relative;
        overflow-y: auto;
        flex: 1;
        height: 500px;
        max-height: 500px;
    }

    /* Modernized Scheduled Interviews Section */
    .upcoming {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-top: 0;
        margin-bottom: 0;
        position: relative;
        overflow: hidden;
        flex: 1;
        height: 500px;
    }
    .upcoming::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }
    .upcoming .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .upcoming .header h4 {
        color: #080a60;
        font-weight: 700;
        font-size: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .upcoming .header h4::before {
        content: '📅';
        font-size: 28px;
    }
    .upcoming .header a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    .upcoming .header a:hover {
        color: #764ba2;
    }
    /* Modernized Dates */
    .upcoming .dates {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        gap: 10px;
    }
    .upcoming .dates .item {
        flex: 1;
        text-align: center;
        padding: 15px;
        border-radius: 15px;
        background: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .upcoming .dates .item.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        transform: scale(1.05);
    }
    .upcoming .dates .item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .upcoming .dates .item h5 {
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 14px;
    }
    .upcoming .dates .item a {
        font-size: 18px;
        font-weight: 700;
        color: inherit;
        text-decoration: none;
    }
    /* Modernized Events */
    .upcoming .events .item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }
    .upcoming .events .item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .upcoming .events .item .event-info a {
        color: #080a60;
        font-weight: 600;
        text-decoration: none;
        font-size: 16px;
        margin-bottom: 5px;
        display: block;
    }
    .upcoming .events .item .event-info p {
        color: #666;
        font-size: 14px;
        margin: 0;
    }
    .upcoming .events .item .job-details-link {
        color: #667eea;
        font-size: 14px;
        text-decoration: none;
        font-weight: 500;
        margin-top: 5px;
        display: inline-block;
    }
    .upcoming .events .item .job-details-link:hover {
        color: #764ba2;
    }
    /* Modernized Buttons */
    .availability-status button {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin: 5px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    .availability-status button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }
    .availability-status button:last-child {
        background: linear-gradient(135deg, #dc3545, #fd7e14);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }
    .availability-status button:last-child:hover {
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
    }
</style>
<style>
    #chatbot-container {
        position: fixed; bottom: 20px; right: 20px; width: 350px; max-height: 500px;
        border: 1px solid #ddd; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        background: white; z-index: 9999; font-family: Arial, sans-serif; display: none;
        flex-direction: column;
    }
    #chatbot-header {
        background: #080a60; color: white; padding: 12px; font-weight: bold;
        display: flex; justify-content: space-between; align-items: center; cursor: pointer;
    }
    #chatbot-body { display: flex; flex-direction: column; height: 300px; padding: 10px; }
    .user-msg { text-align: right; margin: 5px 0; }
    .bot-msg { text-align: left; margin: 5px 0; }
    .user-msg span, .bot-msg span {
        display: inline-block; padding: 8px 12px; border-radius: 15px; max-width: 80%;
    }
    .user-msg span { background-color: #007bff; color: white; }
    .bot-msg span { background-color: #e9ecef; color: #333; }
    #close-btn { font-size: 20px; }
</style>

<script>
        // Get user data from PHP session (you'll need to output this from your PHP)
        const userData = {
    fullname: "<?php echo addslashes(htmlspecialchars($fullname ?? 'Not specified')); ?>",
    professional_title: "<?php echo addslashes(htmlspecialchars($professional_title ?? 'Not specified')); ?>",
    soft_skills: "<?php echo addslashes(htmlspecialchars($soft_skills ?? 'Not specified')); ?>",
    technical_skills: "<?php echo addslashes(htmlspecialchars($technical_skills ?? 'Not specified')); ?>",
    qualification_name: "<?php echo addslashes(htmlspecialchars($qualification_name ?? 'Not specified')); ?>",
    institution: "<?php echo addslashes(htmlspecialchars($institution ?? 'Not specified')); ?>",
    position: "<?php echo addslashes(htmlspecialchars($position ?? 'Not specified')); ?>",
    company_name: "<?php echo addslashes(htmlspecialchars($company_name ?? 'Not specified')); ?>",
    duties: "<?php echo addslashes(htmlspecialchars($duties ?? 'Not specified')); ?>"
};

function toggleChatbot() {
    const container = document.getElementById('chatbot-container');
    const fab = document.getElementById('chatbot-fab');
    
    if (container.style.display === 'none' || container.style.display === '') {
        container.style.display = 'flex';
        fab.style.transform = 'scale(0.9)';
    } else {
        container.style.display = 'none';
        fab.style.transform = 'scale(1)';
    }
}

function sendSuggestedMessage(message) {
    document.getElementById('chatbot-input').value = message;
    sendMessage();
}

function showTypingIndicator() {
    document.querySelector('.typing-indicator').style.display = 'flex';
    const messagesDiv = document.getElementById('chatbot-messages');
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function hideTypingIndicator() {
    document.querySelector('.typing-indicator').style.display = 'none';
}

function addMessage(content, isUser = false) {
    const messagesDiv = document.getElementById('chatbot-messages');
    const messageClass = isUser ? 'user-msg' : 'bot-msg';
    messagesDiv.innerHTML += `<div class="${messageClass}"><span>${content}</span></div>`;
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

async function sendMessage() {
    const input = document.getElementById('chatbot-input');
    const sendBtn = document.getElementById('send-btn');
    const userMessage = input.value.trim();
    
    if (!userMessage) return;

    // Disable input and show user message
    input.value = '';
    sendBtn.disabled = true;
    addMessage(userMessage, true);
    showTypingIndicator();

    // Create enhanced prompt for career advice
    const prompt = `You are an expert career advisor helping job seekers. Based on this candidate profile, provide personalized advice:

CANDIDATE PROFILE:
• Name: ${userData.fullname}
• Current Title: ${userData.professional_title}
• Soft Skills: ${userData.soft_skills}
• Technical Skills: ${userData.technical_skills}
• Education: ${userData.qualification_name} from ${userData.institution}
• Work Experience: ${userData.position} at ${userData.company_name}
• Key Responsibilities: ${userData.duties}

USER QUESTION: ${userMessage}

Provide practical career advice including:
- Specific job titles that match their skills
- Industries to consider
- Skills to develop
- Career next steps

Keep response conversational and under 200 words.`;

    try {
        console.log('Sending request to gemini_proxy.php...');
        
        const response = await fetch('gemini_proxy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: prompt })
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        const responseText = await response.text();
        console.log('Raw response:', responseText);

        hideTypingIndicator();

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            addMessage(`❌ Server returned invalid response. Please check the console for details.`);
            sendBtn.disabled = false;
            return;
        }

        let botReply;

        if (data.error) {
            console.error('API Error Details:', data);
            
            // Provide specific error messages
            if (data.code === 400) {
                botReply = `🔧 API Configuration Error: ${data.message || 'Bad request - please check API setup'}`;
            } else if (data.code === 403) {
                botReply = `🔑 API Key Error: ${data.message || 'Invalid or restricted API key'}`;
            } else if (data.code === 429) {
                botReply = `⏰ Rate Limited: Please wait a moment before asking another question.`;
            } else {
                botReply = `❌ Error ${data.code}: ${data.message || 'Unknown error occurred'}`;
            }
        } else if (data.candidates && data.candidates[0]?.content?.parts[0]?.text) {
            botReply = data.candidates[0].content.parts[0].text;
        } else {
            console.warn('Unexpected response structure:', data);
            botReply = "I received an unexpected response format. Please try asking your question again.";
        }

        addMessage(botReply);

    } catch (error) {
        console.error('Network/Fetch error:', error);
        hideTypingIndicator();
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            addMessage(`🌐 Connection Error: Cannot reach the server. Please check if 'gemini_proxy.php' exists and is accessible.`);
        } else {
            addMessage(`❌ Network Error: ${error.message}. Please check your connection and try again.`);
        }
    }

    sendBtn.disabled = false;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Close button
    document.getElementById('close-btn').onclick = () => {
        document.getElementById('chatbot-container').style.display = 'none';
    };

    // Enter key to send
    document.getElementById('chatbot-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Send button
    document.getElementById('send-btn').onclick = sendMessage;

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        const container = document.getElementById('chatbot-container');
        const fab = document.getElementById('chatbot-fab');
        
        if (!container.contains(e.target) && !fab.contains(e.target)) {
            if (container.style.display === 'flex') {
                container.style.display = 'none';
            }
        }
    });

    console.log('Chatbot initialized. User data:', userData);
});
const toggle = document.getElementById('darkModeToggle');

    toggle.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode');
      if (document.body.classList.contains('dark-mode')) {
        localStorage.setItem('theme', 'dark');
        toggle.textContent = '☀️';
      } else {
        localStorage.setItem('theme', 'light');
        toggle.textContent = '🌙';
      }
    });

    // Load saved theme on page load
    window.onload = () => {
      const theme = localStorage.getItem('theme');
      if (theme === 'dark') {
        document.body.classList.add('dark-mode');
        toggle.textContent = '☀️';
      }
    };
    </script>

<script>
document.getElementById('add-education-btn').addEventListener('click', function() {
    const container = document.getElementById('education-fields');
    const newFieldSet = document.createElement('div');
    newFieldSet.className = 'education-entry';
    
    // 1. Add inline styles to match the PHP loop (Green theme)
    newFieldSet.style.cssText = "background: #f8fffd; padding: 15px; border-radius: 10px; border: 1px dashed var(--accent-edu); margin-top: 15px; position: relative;";

    // 2. Insert HTML that mimics the grid layout (Qualification and Year side-by-side)
    newFieldSet.innerHTML = `
        <div class="inputfield">
            <label>Institution Name</label>
            <input type="text" name="institution[]" class="formbold-form-input" placeholder="Enter institution name">
        </div>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
            <div class="inputfield">
                <label>Qualification Name</label>
                <input type="text" name="qualification_name[]" class="formbold-form-input" placeholder="Enter qualification">
            </div>
            <div class="inputfield">
                <label>Year Graduated</label>
                <input type="number" name="year_completed[]" class="formbold-form-input" placeholder="Year">
            </div>
        </div>
        <button type="button" class="remove-education-btn btn" style="background: #ff7675; border: none; padding: 8px 15px; color: white; border-radius: 5px; font-size: 12px; position: absolute; top: 10px; right: 10px; cursor: pointer;">
            <i class='bx bx-trash'></i> Remove
        </button>
    `;
    
    container.appendChild(newFieldSet);

    // 3. Add functionality to the Remove button
    newFieldSet.querySelector('.remove-education-btn').addEventListener('click', function() {
        newFieldSet.remove();
    });
});

document.getElementById('add-experience-btn').addEventListener('click', function() {
    const container = document.getElementById('work-experience-fields');
    const newFieldSet = document.createElement('div');
    
    // 1. Add class for dark mode compatibility
    newFieldSet.className = 'work-experience-entry';

    // 2. Add inline styles to match the original PHP-generated design
    newFieldSet.style.cssText = "background: #f4f9ff; padding: 15px; border-radius: 10px; border: 1px dashed var(--accent-work); margin-top: 15px; position: relative;";

    // 3. Insert HTML that matches the grid layout of your original form
    newFieldSet.innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="inputfield">
                <label>Position</label>
                <input type="text" name="position[]" class="formbold-form-input" placeholder="Enter position">
            </div>
            <div class="inputfield">
                <label>Company</label>
                <input type="text" name="company_name[]" class="formbold-form-input" placeholder="Enter company name">
            </div>
        </div>
        <div class="inputfield">
            <label>Duration</label>
            <input type="text" name="duration[]" class="formbold-form-input" placeholder="e.g., 2019-2021">
        </div>
        <div class="inputfield">
            <label>Duties and Responsibilities</label>
            <textarea class="textarea" name="duties[]" cols="35" rows="5" maxlength="1000" placeholder="Describe your duties"></textarea>
        </div>
        <button type="button" class="remove-experience-btn btn" style="background: #ff7675; border: none; padding: 8px 15px; color: white; border-radius: 5px; font-size: 12px; position: absolute; top: 10px; right: 10px; cursor: pointer;">
            <i class='bx bx-trash'></i> Remove
        </button>
    `;
    
    container.appendChild(newFieldSet);

    // 4. Add functionality to the Remove button
    newFieldSet.querySelector('.remove-experience-btn').addEventListener('click', function() {
        newFieldSet.remove();
    });
});

// Share Modal Functions
let currentShareUrl = '';
let currentShareTitle = '';

function openShareModal(url, title) {
    currentShareUrl = decodeURIComponent(url);
    currentShareTitle = title;
    document.getElementById('shareJobTitle').textContent = title;
    const modal = new bootstrap.Modal(document.getElementById('shareModal'));
    modal.show();
}

function shareToWhatsApp() {
    const text = 'Check out this job: ' + currentShareTitle;
    const url = 'https://wa.me/?text=' + encodeURIComponent(text + ' ' + currentShareUrl);
    window.open(url, '_blank');
}

function shareToFacebook() {
    const url = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(currentShareUrl);
    window.open(url, '_blank');
}

function shareToTwitter() {
    const text = 'Check out this job: ' + currentShareTitle;
    const url = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent(currentShareUrl);
    window.open(url, '_blank');
}

function shareToLinkedIn() {
    const url = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(currentShareUrl);
    window.open(url, '_blank');
}

function shareViaEmail() {
    const subject = 'Job Opportunity: ' + currentShareTitle;
    const body = 'Check out this job posting: ' + currentShareUrl;
    const url = 'mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
    window.location.href = url;
}

function copyLink() {
        navigator.clipboard.writeText(currentShareUrl).then(() => {
            alert('Link copied to clipboard!');
        });
    }
    function closeProfile(event) {
        // Prevent the click from bubbling up to parent elements
        event.stopPropagation();
        // Hide the profile container
        const profileContainer = document.querySelector('.con');
        if (profileContainer) {
            profileContainer.style.display = 'none';
        }
    }
</script>

<script>
// AJAX function to submit availability without page reload
function submitAvailability(button, status) {
    const form = button.closest('form');
    const formData = new FormData(form);
    formData.set('availability_status', status);
    formData.append('ajax', '1');
    
    // Disable buttons while processing
    const buttons = form.querySelectorAll('button');
    buttons.forEach(btn => btn.disabled = true);
    
    fetch('confirm_availability.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the status badge
            const statusBadge = form.closest('.availability-status').querySelector('.status-badge');
            if (status === 'Available') {
                statusBadge.className = 'status-badge status-accepted';
                statusBadge.textContent = '✓ Accepted';
            } else {
                statusBadge.className = 'status-badge status-declined';
                statusBadge.textContent = '✗ Declined';
            }
        } else {
            alert('Error: ' + (data.error || 'Failed to update availability'));
            buttons.forEach(btn => btn.disabled = false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please try again.');
        buttons.forEach(btn => btn.disabled = false);
    });
}
</script>
</body>
</html>
