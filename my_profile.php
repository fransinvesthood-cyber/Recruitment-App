<?php
include('config.php');
session_start();

// Initialize all user data variables to prevent undefined errors
$fullname = '';
$professional_title = '';
$professional_summary = '';
$soft_skills = '';
$technical_skills = '';
$qualifications = []; // Array for multiple qualifications
$work_experiences = []; // Array for multiple work experiences
$email = '';
$gender = '';
$dob = '';
$phone = '';
$address = '';

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
    $duties_arr = $_POST['duties'] ?? [];


    // Language proficiency arrays
    $language_names_in = $_POST['language_name'] ?? [];
    $speaking_levels_in = $_POST['speaking_level'] ?? [];
    $reading_levels_in = $_POST['reading_level'] ?? [];
    $writing_levels_in = $_POST['writing_level'] ?? [];


    // Update users table
    $uStmt = $conn->prepare("UPDATE users SET fullname=?, email=?, gender=?, dob=?, phone=?, address=? WHERE user_id=?");
    if ($uStmt) {
        $uStmt->bind_param("ssssssi", $fullname_in, $email_in, $gender_in, $dob_in, $phone_in, $address_in, $user_id);
        $uStmt->execute();
        $uStmt->close();
    } else {
        error_log("Update user prepare failed: " . $conn->error);
    }

    // Get all qualification arrays from POST and save multiple qualifications
    $qualification_levels = $_POST['qualification_level'] ?? [];
    $qualification_names = $_POST['qualification_name'] ?? [];
    $institutions = $_POST['institution'] ?? [];
    $years_completed = $_POST['year_completed'] ?? [];

    // Delete existing qualifications and re-insert all provided entries
    $delQual = $conn->prepare("DELETE FROM qualifications WHERE user_id=?");
    if ($delQual) {
        $delQual->bind_param("i", $user_id);
        $delQual->execute();
        $delQual->close();
    }
    $insertQual = $conn->prepare("INSERT INTO qualifications (user_id, qualification_level, qualification_name, institution, year_completed) VALUES (?, ?, ?, ?, ?)");
    if ($insertQual) {
        for ($i = 0; $i < count($qualification_names); $i++) {
            $qualLevel = trim($qualification_levels[$i] ?? '');
            $qualName = trim($qualification_names[$i]);
            $inst = trim($institutions[$i] ?? '');
            $year = intval($years_completed[$i] ?? 0);
            if (!$qualName && !$inst && !$year && !$qualLevel) continue;
            $insertQual->bind_param("isssi", $user_id, $qualLevel, $qualName, $inst, $year);
            $insertQual->execute();
        }
        $insertQual->close();
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

// Upsert applicant_profile for professional title and summary - FIXED
    $prof_title_post = trim($_POST['professional_title'] ?? '');
    $prof_summary_post = trim($_POST['professional_summary'] ?? '');

    // Fetch current values
    $current_prof_title = '';
    $current_prof_summary = '';
    $app_fetch = $conn->prepare("SELECT professional_title, professional_summary FROM applicant_profile WHERE user_id=?");
    $app_fetch->bind_param("i", $user_id);
    $app_fetch->execute();
    $app_fetch_result = $app_fetch->get_result();
    if ($row = $app_fetch_result->fetch_assoc()) {
        $current_prof_title = $row['professional_title'] ?? '';
        $current_prof_summary = $row['professional_summary'] ?? '';
    }
    $app_fetch->close();

    // Use POST if provided, else current
    $final_prof_title = $prof_title_post !== '' ? $prof_title_post : $current_prof_title;
    $final_prof_summary = $prof_summary_post !== '' ? $prof_summary_post : $current_prof_summary;

    // Always upsert both fields
    $app_check = $conn->prepare("SELECT app_profile_id FROM applicant_profile WHERE user_id=?");
    $app_check->bind_param("i", $user_id);
    $app_check->execute();
    $app_check->store_result();
    if ($app_check->num_rows > 0) {
        $app_update = $conn->prepare("UPDATE applicant_profile SET professional_title=?, professional_summary=?, updated_at=NOW() WHERE user_id=?");
        $app_update->bind_param("ssi", $final_prof_title, $final_prof_summary, $user_id);
        $app_update->execute();
        $app_update->close();
    } else {
        $app_insert = $conn->prepare("INSERT INTO applicant_profile (user_id, professional_title, professional_summary, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $app_insert->bind_param("iss", $user_id, $final_prof_title, $final_prof_summary);
        $app_insert->execute();
        $app_insert->close();
    }
    $app_check->close();

    // Replace work_experience entries: delete existing and re-insert provided entries
    $del = $conn->prepare("DELETE FROM work_experience WHERE user_id=?");

    // Work experience extra arrays (nullable)
    $countries_in = $_POST['country'] ?? [];
    $employment_status_in = $_POST['employment_status'] ?? [];
    $work_type_in = $_POST['work_type'] ?? [];
    $start_dates_in = $_POST['start_date'] ?? [];
    $end_dates_in = $_POST['end_date'] ?? [];
    $reasons_in = $_POST['reason_for_leaving'] ?? [];

    // DB compatibility: some installs used `reason` in work_experience.
    // Map form field `reason_for_leaving` to that expected value.
    if (!isset($_POST['reason']) && !empty($reasons_in)) {
        $_POST['reason'] = $reasons_in;
    }



    // Replace languages: delete existing and re-insert provided entries
    $delLang = $conn->prepare("DELETE FROM language_proficiency WHERE user_id=?");
    if ($delLang) {
        $delLang->bind_param("i", $user_id);
        $delLang->execute();
        $delLang->close();
    }

    $insertLang = $conn->prepare("INSERT INTO language_proficiency (user_id, language_name, speaking_level, reading_level, writing_level) VALUES (?, ?, ?, ?, ?)");
    if ($insertLang) {
        for ($i = 0; $i < count($language_names_in); $i++) {
            $langName = trim($language_names_in[$i] ?? '');
            $sLevel = trim($speaking_levels_in[$i] ?? '');
            $rLevel = trim($reading_levels_in[$i] ?? '');
            $wLevel = trim($writing_levels_in[$i] ?? '');

            if (!$langName && !$sLevel && !$rLevel && !$wLevel) continue;
            if ($langName === '') continue;

            $insertLang->bind_param("issss", $user_id, $langName, $sLevel, $rLevel, $wLevel);
            $insertLang->execute();
        }
        $insertLang->close();
    }

    if ($del) {
        $del->bind_param("i", $user_id);
        $del->execute();
        $del->close();
    }
    // Insert work experience.
    // Your current DB schema for `work_experience` may not include columns like `reason`.
    // To prevent runtime crashes (e.g., Unknown column 'reason'), we insert only the columns that are guaranteed.
    // Update this list once you confirm the exact DB columns.
    $insertWork = $conn->prepare(
        "INSERT INTO work_experience (user_id, position, company_name, duration, duties, country, employment_status, work_type, start_date, end_date, reason_for_leaving) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );


    if ($insertWork) {
        for ($i = 0; $i < count($positions); $i++) {
            $pos = trim($positions[$i] ?? '');
            $comp = trim($company_names[$i] ?? '');
            // UI currently does not collect `duration`. Keep it safe/nullable.
            $dur = null;
            $duty = trim($duties_arr[$i] ?? '');

            $country = trim($countries_in[$i] ?? '');
            $employmentStatus = trim($employment_status_in[$i] ?? '');
            $workType = trim($work_type_in[$i] ?? '');
            $startDate = trim($start_dates_in[$i] ?? '');
            $endDate = trim($end_dates_in[$i] ?? '');
            $reason = trim($reasons_in[$i] ?? '');

            // Skip completely empty rows
            if (!$pos && !$comp && !$dur && !$duty && !$country && !$employmentStatus && !$workType && !$startDate && !$endDate && !$reason) {
                continue;
            }

            // These values exist on the form, but may not exist in every deployed DB schema.
            // We keep them computed for future upgrades, but we only persist the columns used in the current INSERT.
            $countryVal = $country !== '' ? $country : null;
            $employmentStatusVal = $employmentStatus !== '' ? $employmentStatus : null;
            $workTypeVal = $workType !== '' ? $workType : null;
            $startDateVal = $startDate !== '' ? $startDate : null;
            $endDateVal = $endDate !== '' ? $endDate : null;
            $reasonVal = $reason !== '' ? $reason : null;

            // Bind parameters matching the full INSERT column list (11 placeholders)
            $insertWork->bind_param(
                "issssssssss",
                $user_id,
                $pos,
                $comp,
                $dur,
                $duty,
                $countryVal,
                $employmentStatusVal,
                $workTypeVal,
                $startDateVal,
                $endDateVal,
                $reasonVal
            );
            $insertWork->execute();
        }
        $insertWork->close();
    }


    // Save Computer Literacy / Computer Skills (per-skill proficiency)
    $computer_skills_in = $_POST['computer_skills'] ?? [];
    if (!is_array($computer_skills_in)) {
        $computer_skills_in = [$computer_skills_in];
    }
    $computer_skills_other_in = trim($_POST['computer_skills_other'] ?? '');

    // Support rating “other skills” too (comma-separated)
    $computer_other_skills = [];
    if ($computer_skills_other_in !== '') {
        // Split by comma, trim, drop empties
        $parts = preg_split('/\s*,\s*/', $computer_skills_other_in);
        if (is_array($parts)) {
            foreach ($parts as $p) {
                $p = trim((string)$p);
                if ($p !== '') $computer_other_skills[] = $p;
            }
        }
    }

    $computer_other_skills_proficiency_map = $_POST['computer_other_skills_proficiency'] ?? [];
    if (!is_array($computer_other_skills_proficiency_map)) {
        $computer_other_skills_proficiency_map = [];
    }


    // Map: skill_name => proficiency
    $computer_skills_proficiency_map = $_POST['computer_skills_proficiency'] ?? [];
    if (!is_array($computer_skills_proficiency_map)) {
        $computer_skills_proficiency_map = [];
    }

    // Normalize selected skills
    $computer_skills_clean = [];
    foreach ($computer_skills_in as $s) {
        $s = trim((string)$s);
        if ($s !== '') $computer_skills_clean[] = $s;
    }

    $delComputer = $conn->prepare("DELETE FROM computer_literacy WHERE user_id=?");
    if ($delComputer) {
        $delComputer->bind_param("i", $user_id);
        $delComputer->execute();
        $delComputer->close();
    }

    $insertComputer = $conn->prepare(
        "INSERT INTO computer_literacy (user_id, skill_name, proficiency, other_skills) VALUES (?, ?, ?, ?)"
    );

    if ($insertComputer) {
        // Save predefined (checkbox) skills
        foreach ($computer_skills_clean as $skillName) {
            $prof = intval($computer_skills_proficiency_map[$skillName] ?? 0);
            if ($prof < 0) $prof = 0;
            if ($prof > 100) $prof = 100;

            $otherVal = $computer_skills_other_in;
            $insertComputer->bind_param("isii", $user_id, $skillName, $prof, $otherVal);
            $insertComputer->execute();
        }

        // Save “other” skills (comma-separated) as separate computer_literacy rows too
        foreach ($computer_other_skills as $otherSkillName) {
            $prof = intval($computer_other_skills_proficiency_map[$otherSkillName] ?? 0);
            if ($prof < 0) $prof = 0;
            if ($prof > 100) $prof = 100;

            $otherVal = $computer_skills_other_in;
            $insertComputer->bind_param("isii", $user_id, $otherSkillName, $prof, $otherVal);
            $insertComputer->execute();
        }

        $insertComputer->close();
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
    $_SESSION['computer_skills_selected'] = $computer_skills_clean;
    $_SESSION['computer_skills_other'] = $computer_skills_other_in;
    $_SESSION['computer_skills_proficiency'] = $computer_skills_proficiency_in;

    $_SESSION['message'] = 'Profile updated successfully.';
    $_SESSION['messageClass'] = 'success';

    // Redirect to avoid resubmission
    header('Location: my_profile.php');
    exit;
}

// Session message handling
if (!empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageClass = $_SESSION['messageClass'];
    unset($_SESSION['message'], $_SESSION['messageClass']);
}

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

// Fetch user's qualifications (all entries)
$qualifications = [];
$sql = "SELECT qualification_level, qualification_name, institution, year_completed FROM qualifications WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $qualifications[] = $row;
}
$stmt->close();
// Store first qualification in session for backward compatibility
if (!empty($qualifications)) {
    $_SESSION['qualification_level'] = $qualifications[0]['qualification_level'] ?? '';
    $_SESSION['qualification_name'] = $qualifications[0]['qualification_name'] ?? '';
    $_SESSION['institution'] = $qualifications[0]['institution'] ?? '';
    $_SESSION['year_completed'] = $qualifications[0]['year_completed'] ?? '';
} else {
    $_SESSION['qualification_level'] = '';
    $_SESSION['qualification_name'] = '';
    $_SESSION['institution'] = '';
    $_SESSION['year_completed'] = '';
}

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

// Fetch user's work experience (all entries)
$work_experiences = [];
$sql = "SELECT position, company_name, duration, duties, country, employment_status, work_type, start_date, end_date, reason_for_leaving FROM work_experience WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $work_experiences[] = $row;
}
$stmt->close();
// Store first work experience in session for backward compatibility
if (!empty($work_experiences)) {
    $_SESSION['position'] = $work_experiences[0]['position'] ?? '';
    $_SESSION['company_name'] = $work_experiences[0]['company_name'] ?? '';
    $_SESSION['duration'] = $work_experiences[0]['duration'] ?? '';
    $_SESSION['duties'] = $work_experiences[0]['duties'] ?? '';
} else {
    $_SESSION['position'] = '';
    $_SESSION['company_name'] = '';
    $_SESSION['duration'] = '';
    $_SESSION['duties'] = '';
}
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css"
            integrity="sha384-1ZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9s+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
    <link rel="icon" href="assets/logo1.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Red+Hat+Display:wght@500&display=swap" rel="stylesheet" >
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <title>My Profile</title>

    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .container {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 1200px;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideIn 0.5s ease-out;
            transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
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

        /* Page-specific styles */
        .profile-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .profile-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
        }

        .profile-page-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }

        .back-to-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .back-to-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            color: #764ba2;
        }

        /* Alert styles */
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

        .btn-exit {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f3f4f6;
            color: #4f46e5;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            backdrop-filter: blur(10px);
        }
        .btn-exit:hover {
            background: #e0e7ff;
            color: #3730a3;
            transform: scale(1.05);
        }

        /* Profile Header Section - Modernized */
        .profile-flex {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 0;
            align-items: stretch;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
            border-radius: 24px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            margin-bottom: 30px;
            overflow: hidden;
            position: relative;
        }

        /* Left Side - Photo Card with gradient accent */
        .profile-flex::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #667eea, #764ba2, #f093fb);
        }

        .photo-section {
            text-align: center;
            padding: 40px 30px;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-right: 1px solid rgba(102, 126, 234, 0.1);
        }

        .photo-section h4 {
            margin-bottom: 20px;
            color: #080a60;
            font-weight: 700;
            font-size: 20px;
            letter-spacing: 0.5px;
            padding: 10px 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 30px;
        }

        .pro-ski {
            text-align: center;
            padding: 10px;
        }

        .pro-ski img {
            width: 100%;
            max-width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 5px solid transparent;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, #667eea, #764ba2, #f093fb) border-box;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
        }

        .pro-ski img:hover {
            transform: scale(1.08) rotate(3deg);
            box-shadow: 0 20px 45px rgba(102, 126, 234, 0.4);
        }

        /* Right Side - Info Section */
        .info-section {
            text-align: left;
            padding: 35px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .info-section h6 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-section h6::before {
            content: '';
            width: 20px;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        #title-display {
            font-weight: 700;
            color: #080a60;
            font-size: 20px;
            margin-bottom: 20px;
            line-height: 1.3;
            background: linear-gradient(135deg, #080a60, #667eea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .summary {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.9));
            border-radius: 16px;
            padding: 25px;
            font-size: 15px;
            line-height: 1.8;
            color: #4a5568;
            border: 1px solid rgba(102, 126, 234, 0.1);
            position: relative;
            overflow: hidden;
        }

        .summary::before {
            content: '"';
            position: absolute;
            top: -10px;
            left: 15px;
            font-size: 80px;
            color: rgba(102, 126, 234, 0.1);
            font-family: Georgia, serif;
            line-height: 1;
        }

        /* Modern Button Container */
        .btn-container {
            margin-top: 25px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-container .btn {
            padding: 12px 28px;
            font-size: 14px;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .btn-container .btn:hover {
            transform: translateY(-3px);
        }

        #edit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
        }

        #edit-btn:hover {
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.45);
        }

        #cancel-btn {
            background: linear-gradient(135deg, #ff7675, #d63031);
            box-shadow: 0 6px 20px rgba(255, 118, 117, 0.35);
        }

        #cancel-btn:hover {
            box-shadow: 0 10px 30px rgba(255, 118, 117, 0.45);
        }

        #update-btn {
            background: linear-gradient(135deg, #00b894, #00cec9);
            box-shadow: 0 6px 20px rgba(0, 184, 148, 0.35);
            color: #fff;
        }

        #update-btn:hover {
            box-shadow: 0 10px 30px rgba(0, 184, 148, 0.45);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .profile-flex {
                grid-template-columns: 1fr;
            }

            .photo-section {
                border-right: none;
                border-bottom: 1px solid rgba(102, 126, 234, 0.1);
                padding: 30px;
            }

            .profile-flex::before {
                width: 100%;
                height: 4px;
                top: 0;
                left: 0;
            }

            .info-section {
                padding: 30px;
            }
        }

        @media (max-width: 576px) {
            .profile-flex {
                border-radius: 16px;
            }

            .photo-section {
                padding: 25px 20px;
            }

            .pro-ski img {
                width: 140px;
                height: 140px;
            }

            .photo-section h4 {
                font-size: 18px;
            }

            .info-section {
                padding: 25px 20px;
            }

            #title-display {
                font-size: 22px;
            }

            .summary {
                padding: 20px;
                font-size: 14px;
            }

            .btn-container {
                flex-direction: column;
            }

            .btn-container .btn {
                width: 100%;
                justify-content: center;
            }

            .inputgrid-2,
            .edu-row,
            .work-row {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .acc-header {
                padding: 14px 14px;
            }

            .acc-panel {
                padding: 14px 14px 16px;
            }
        }


        /* Dark Mode Support */
        body.dark-mode .profile-flex {
            background: linear-gradient(135deg, rgba(30, 30, 60, 0.6), rgba(40, 20, 60, 0.6));
            border-color: rgba(102, 126, 234, 0.2);
        }

        body.dark-mode .photo-section {
            background: rgba(30, 30, 60, 0.5);
            border-right-color: rgba(102, 126, 234, 0.15);
        }

        body.dark-mode .photo-section h4 {
            color: #f0f0f0;
            background: rgba(102, 126, 234, 0.15);
        }

        body.dark-mode .info-section h6 {
            color: #a5b4fc;
        }

        body.dark-mode #title-display {
            background: linear-gradient(135deg, #a5b4fc, #818cf8);
            -webkit-background-clip: text;
            background-clip: text;
        }

        body.dark-mode .summary {
            background: rgba(30, 30, 60, 0.8);
            color: #d1d5db;
            border-color: rgba(102, 126, 234, 0.15);
        }

        body.dark-mode .summary::before {
            color: rgba(102, 126, 234, 0.15);
        }

        body.dark-mode .acc-item {
            background: rgba(31, 31, 31, 0.9);
            border-color: rgba(102, 126, 234, 0.25);
            box-shadow: 0 14px 34px rgba(0,0,0,0.35);
        }

        body.dark-mode .acc-header {
            background: rgba(31, 31, 31, 0.75);
        }

        body.dark-mode .acc-panel {
            border-top-color: rgba(102, 126, 234, 0.18);
        }


        /* Accordion (Profile categories) */
        .profile-accordion {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 100%;
            margin: 0 auto;
        }

        .acc-item {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(102, 126, 234, 0.18);
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            overflow: hidden;
        }

        .acc-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.12);
            border-color: rgba(102, 126, 234, 0.28);
        }

        .acc-header {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border: none;
            background: rgba(255, 255, 255, 0.75);
            cursor: pointer;
        }

        .acc-header:focus-visible {
            outline: 3px solid rgba(102, 126, 234, 0.25);
            outline-offset: 2px;
        }

        .acc-title {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            color: #0f172a;
            font-size: 16px;
        }

        .acc-icon {
            font-size: 18px;
            color: #667eea;
            transition: transform 0.25s ease;
        }

        .acc-item[data-open="true"] .acc-icon {
            transform: rotate(0deg);
        }


        .acc-panel {
            padding: 16px 18px 18px;
            border-top: 1px solid rgba(102, 126, 234, 0.12);
        }

        .inputgrid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 12px;
        }

        .edu-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .work-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 12px;
        }

        .acc-action {
            margin-top: 10px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 700;
            color: #667eea;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 6px;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .acc-action:hover {
            color: #4f46e5;
            transform: translateY(-1px);
        }

        .empty-state {
            padding: 12px 0;
            color: #4b5563;
        }

        .empty-state .small {
            margin-top: 6px;
            font-size: 13px;
            color: #6b7280;
        }

        /* Backward-compat for older layout (not used after accordion refactor) */
        .profile-update-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-areas:
                "personal education"
                "skills work"
                "buttons buttons";
            gap: 30px;
        }


        .unique-card {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .unique-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .unique-card h6 {
            font-weight: 800;
            margin-bottom: 25px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .unique-card[style*="grid-area: personal"] {
            grid-area: personal;
            border-top: 5px solid var(--accent-personal);
        }

        .unique-card[style*="grid-area: personal"] h6 {
            color: var(--accent-personal);
        }

        .unique-card[style*="grid-area: education"] {
            grid-area: education;
            border-top: 5px solid var(--accent-edu);
        }

        .unique-card[style*="grid-area: education"] h6 {
            color: var(--accent-edu);
        }

        .unique-card[style*="grid-area: skills"] {
            grid-area: skills;
            border-top: 5px solid var(--accent-skill);
        }

        .unique-card[style*="grid-area: skills"] h6 {
            color: var(--accent-skill);
        }

        .unique-card[style*="grid-area: work"] {
            grid-area: work;
            border-top: 5px solid var(--accent-work);
        }

        .unique-card[style*="grid-area: work"] h6 {
            color: var(--accent-work);
        }

        :root {
            --accent-personal: #6c5ce7;
            --accent-edu: #00b894;
            --accent-skill: #e17055;
            --accent-work: #0984e3;
        }

        .inputfield {
            margin-bottom: 18px;
        }

        .inputfield label {
            display: block;
            font-weight: 600;
            color: #080a60;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .formbold-form-input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            background: #f9f9f9;
            font-size: 14px;
            color: #333;
            outline: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .formbold-form-input:focus {
            border-color: #667eea;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }

        .textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            background: #f9f9f9;
            font-size: 14px;
            color: #333;
            outline: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
            resize: vertical;
            font-family: Arial, sans-serif;
        }

        .textarea:focus {
            border-color: #667eea;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }

        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            padding: 14px 30px;
            border-radius: 30px;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
        }

        .buttons-section {
            grid-column: span 1;
            text-align: center;
            margin-top: 30px;
            grid-area: buttons;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .profile-update-grid {
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

            .profile-page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
        }

        /* Dark Mode Support */
        body.dark-mode {
            background-color: #0d1117;
            color: #f0f0f0;
        }

        body.dark-mode .unique-card {
            background: #1f1f1f;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        body.dark-mode .unique-card h6 {
            color: #f0f0f0;
        }

body.dark-mode .inputfield label {
            color: #e5e7eb;
        }

        /* Ensure accordion panel text (labels/hints) remains readable in dark mode */
        body.dark-mode .acc-panel,
        body.dark-mode .acc-item,
        body.dark-mode .empty-state,
        body.dark-mode .hint,
        body.dark-mode .small {
            color: #e5e7eb;
        }

        body.dark-mode .empty-state .small,
        body.dark-mode .hint {
            color: #cbd5e1;
        }

        body.dark-mode .acc-title {
            color: #f1f5f9;
        }

        body.dark-mode .acc-icon {
            color: #a5b4fc;
        }

        body.dark-mode .formbold-form-input,
        body.dark-mode .textarea {
            background: #2d2d2d;
            color: #f0f0f0;
            border-color: #444;
        }

        body.dark-mode .formbold-form-input:focus,
        body.dark-mode .textarea:focus {
            border-color: #667eea;
            background: #333;
        }

        body.dark-mode #title-display {
            color: #f0f0f0;
        }

        body.dark-mode .summary {
            background: #333;
            color: #fff;
        }

        body.dark-mode .photo-section h4 {
            color: #f0f0f0;
        }

        body.dark-mode .profile-flex {
            background: rgba(31, 31, 31, 0.95);
        }

        body.dark-mode .education-entry {
            background: #2d2d2d !important;
            border: 1px dashed #666 !important;
        }

        body.dark-mode #work-experience-fields > div,
        body.dark-mode [style*="background: #f4f9ff"] {
            background: #2d2d2d !important;
            border: 1px dashed #666 !important;
        }

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: #e0e0e0;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }

        /* Shared remove button styling (Education/Work/Language) */
        .btn-remove{
            background: #ff6b6b;
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            margin-top: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 8px 18px rgba(255, 107, 107, 0.25);
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }
        .btn-remove:hover{
            transform: translateY(-1px);
            background: #ff5252;
            box-shadow: 0 12px 26px rgba(255, 107, 107, 0.35);
        }
        .btn-remove:active{
            transform: translateY(0);
            box-shadow: 0 8px 18px rgba(255, 107, 107, 0.25);
        }
        .btn-remove:focus-visible{
            outline: 3px solid rgba(255, 107, 107, 0.35);
            outline-offset: 2px;
        }

        body.dark-mode .btn-remove{
            background: #ff5252;
            box-shadow: 0 10px 24px rgba(255, 82, 82, 0.22);
        }
        body.dark-mode .btn-remove:hover{
            background: #ff3b3b;
            box-shadow: 0 14px 30px rgba(255, 82, 82, 0.30);
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
});
</script>

<body>
<div class="container">
    <button class="btn-exit" id="exitPage"><i class='bx bx-x'></i></button>
    <div class="profile-page">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h1>My Profile</h1>
                <p>Manage your personal information, skills, education, and work experience</p>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if (!empty($message) || !empty($_SESSION['message'])): ?>
            <?php 
            if (!empty($_SESSION['message'])) {
                $message = $_SESSION['message'];
                $messageClass = $_SESSION['messageClass'];
                unset($_SESSION['message'], $_SESSION['messageClass']);
            }
            ?>
            <div class="alert <?= $messageClass ?>" id="alertBox">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Profile Header Section -->
        <div class="profile-flex">
            <div class="photo-section">
                <h4><?php echo htmlspecialchars($fullname); ?></h4>
                <div class="pro-ski">
                    <form action="upload_profile_pic.php" method="post" enctype="multipart/form-data" id="upload-form">
                        <label for="profile_picture">
                            <img src="display_profile_pic.php" alt="Profile Picture" id="profile-img"
                                onerror="this.onerror=null; this.src='img/default_photo.jpg';"
                                style="border-radius: 50%; box-shadow: 0 10px 25px rgba(0,0,0,0.1); cursor: pointer;">
                        </label>
                        <input type="file" name="profile_picture" id="profile_picture" style="display: none;" accept="image/*" onchange="uploadProfilePic()">
                    </form>
                </div>
            </div>

            <div class="info-section">
                <form action="update_profile.php" method="POST" id="summary-form">
                    <p id="title-display" style="font-weight: bold; color: #333;"><?php echo htmlspecialchars($professional_title); ?></p>
                    <input type="text" name="professional_title" id="title-input" class="formbold-form-input" value="<?php echo htmlspecialchars($professional_title); ?>" style="display: none;"><br>

                    <p class="summary" id="summary-display" style="color: #fff; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($professional_summary)); ?>
                    </p>
                    <textarea class="textarea" name="professional_summary" id="summary-input" cols="35" rows="7" style="display: none;"><?php echo htmlspecialchars($professional_summary); ?></textarea>
                </form>
            </div>
        </div>

<!-- Profile Update Form -->
        <form action="my_profile.php" method="POST">

            <div class="profile-accordion">
                <!-- 1. Personal Information -->
                <section class="acc-item" data-open="false">

                    <button type="button" class="acc-header" aria-expanded="true">
                        <span class="acc-title"><i class='bx bx-user'></i> Personal Information</span>
                        <span class="acc-icon" aria-hidden="true">▾</span>
                    </button>

                    <div class="acc-panel" style="display:none;">

                        <div class="inputgrid-2">
                            <div class="inputfield">
                                <label>Full Name</label>
                                <input type="text" id="fullname" name="fullname" class="formbold-form-input" value="<?php echo $_SESSION['fullname']; ?>">
                            </div>
                            <div class="inputfield">
                                <label>Gender</label>
                                <input type="text" id="gender" name="gender" class="formbold-form-input" value="<?php echo $_SESSION['gender']; ?>">
                            </div>
                            <div class="inputfield">
                                <label>Date of Birth</label>
                                <input type="date" id="dob" name="dob" class="formbold-form-input" value="<?php echo $_SESSION['dob']; ?>">
                            </div>
                            <div class="inputfield">
                                <label>Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="formbold-form-input" value="<?php echo $_SESSION['phone']; ?>">
                            </div>
                        </div>
                        <div class="inputfield">
                            <label>Email Address</label>
                            <input type="email" id="email" name="email" class="formbold-form-input" value="<?php echo $_SESSION['email']; ?>">
                        </div>
                        <div class="inputfield">
                            <label>Residential Address</label>
                            <input type="text" id="address" name="address" class="formbold-form-input" value="<?php echo $_SESSION['address']; ?>">
                        </div>
                    </div>
                </section>

                <!-- 2. Professional Summary -->
                <section class="acc-item">
                    <button type="button" class="acc-header" aria-expanded="false">
                        <span class="acc-title"><i class='bx bx-edit-alt'></i> Professional Summary</span>
                        <span class="acc-icon" aria-hidden="true">▾</span>
                    </button>
                    <div class="acc-panel" style="display:none;">
                        <div class="inputfield">
                            <label>Professional Title</label>
                            <input type="text" name="professional_title" class="formbold-form-input" value="<?php echo htmlspecialchars($professional_title); ?>" />
                        </div>
                        <div class="inputfield">
                            <label>Summary</label>
                            <textarea name="professional_summary" class="textarea" rows="6" placeholder="Write a concise summary..."><?php echo htmlspecialchars($professional_summary); ?></textarea>
                        </div>
                    </div>
                </section>

                <!-- 3. Education -->
                <section class="acc-item">
                    <button type="button" class="acc-header" aria-expanded="false">
                        <span class="acc-title"><i class='bx bx-book-reader'></i> Education</span>
                        <span class="acc-icon" aria-hidden="true">▾</span>
                    </button>
                    <div class="acc-panel" style="display:none;">
                        <div id="education-fields">
                            <?php if (!empty($qualifications)): ?>
                                <?php foreach ($qualifications as $qual): ?>
                                    <div class="education-entry education-entry--panel">
                                    <div class="inputfield">
                                        <label>Institution Name</label>
                                        <input type="text" name="institution[]" class="formbold-form-input" value="<?php echo htmlspecialchars($qual['institution'] ?? ''); ?>">
                                    </div>
                                    <div class="edu-row">
                                        <div class="inputfield">
                                            <label>Qualification Level</label>
                                            <select name="qualification_level[]" class="formbold-form-input">
                                                <option value="">Select Level</option>
                                                <option value="High School" <?php echo (isset($qual['qualification_level']) && $qual['qualification_level'] === 'High School') ? 'selected' : ''; ?>>High School</option>
                                                <option value="Certificate" <?php echo (isset($qual['qualification_level']) && $qual['qualification_level'] === 'Certificate') ? 'selected' : ''; ?>>Certificate</option>
                                                <option value="Diploma" <?php echo (isset($qual['qualification_level']) && $qual['qualification_level'] === 'Diploma') ? 'selected' : ''; ?>>Diploma</option>
                                                <option value="Bachelor's Degree" <?php echo (isset($qual['qualification_level']) && $qual['qualification_level'] === "Bachelor's Degree") ? 'selected' : ''; ?>>Bachelor's Degree</option>
                                                <option value="Master's Degree" <?php echo (isset($qual['qualification_level']) && $qual['qualification_level'] === "Master's Degree") ? 'selected' : ''; ?>>Master's Degree</option>
                                                <option value="Doctorate" <?php echo (isset($qual['qualification_level']) && $qual['qualification_level'] === 'Doctorate') ? 'selected' : ''; ?>>Doctorate</option>
                                                <option value="Professional Qualification" <?php echo (isset($qual['qualification_level']) && $qual['qualification_level'] === 'Professional Qualification') ? 'selected' : ''; ?>>Professional Qualification</option>
                                            </select>
                                        </div>
                                        <div class="inputfield">
                                            <label>Qualification Name</label>
                                            <input type="text" name="qualification_name[]" class="formbold-form-input" value="<?php echo htmlspecialchars($qual['qualification_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="inputfield">
                                        <label>Year Graduated</label>
                                        <input type="number" name="year_completed[]" class="formbold-form-input" value="<?php echo htmlspecialchars($qual['year_completed'] ?? ''); ?>">
                                    </div>
                                    <button type="button" class="btn-remove" onclick="removeEducation(this)">Remove</button>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="education-entry education-entry--panel">
                                    <div class="inputfield">
                                        <label>Institution Name</label>
                                        <input type="text" name="institution[]" class="formbold-form-input" value="<?php echo $_SESSION['institution']; ?>">
                                    </div>
                                    <div class="edu-row">
                                        <div class="inputfield">
                                            <label>Qualification Level</label>
                                            <select name="qualification_level[]" class="formbold-form-input">
                                                <option value="">Select Level</option>
                                                <option value="High School" <?php echo (isset($_SESSION['qualification_level']) && $_SESSION['qualification_level'] === 'High School') ? 'selected' : ''; ?>>High School</option>
                                                <option value="Certificate" <?php echo (isset($_SESSION['qualification_level']) && $_SESSION['qualification_level'] === 'Certificate') ? 'selected' : ''; ?>>Certificate</option>
                                                <option value="Diploma" <?php echo (isset($_SESSION['qualification_level']) && $_SESSION['qualification_level'] === 'Diploma') ? 'selected' : ''; ?>>Diploma</option>
                                                <option value="Bachelor's Degree" <?php echo (isset($_SESSION['qualification_level']) && $_SESSION['qualification_level'] === "Bachelor's Degree") ? 'selected' : ''; ?>>Bachelor's Degree</option>
                                                <option value="Master's Degree" <?php echo (isset($_SESSION['qualification_level']) && $_SESSION['qualification_level'] === "Master's Degree") ? 'selected' : ''; ?>>Master's Degree</option>
                                                <option value="Doctorate" <?php echo (isset($_SESSION['qualification_level']) && $_SESSION['qualification_level'] === 'Doctorate') ? 'selected' : ''; ?>>Doctorate</option>
                                                <option value="Professional Qualification" <?php echo (isset($_SESSION['qualification_level']) && $_SESSION['qualification_level'] === 'Professional Qualification') ? 'selected' : ''; ?>>Professional Qualification</option>
                                            </select>
                                        </div>
                                        <div class="inputfield">
                                            <label>Qualification Name</label>
                                            <input type="text" name="qualification_name[]" class="formbold-form-input" value="<?php echo $_SESSION['qualification_name']; ?>">
                                        </div>
                                    </div>
                                    <div class="inputfield">
                                        <label>Year Graduated</label>
                                        <input type="number" name="year_completed[]" class="formbold-form-input" value="<?php echo $_SESSION['year_completed']; ?>">
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="acc-action" onclick="addEducation()"><i class='bx bx-plus'></i> Add More Education</button>
                    </div>
                </section>

                <!-- 4. Work Experience -->
                <section class="acc-item">
                    <button type="button" class="acc-header" aria-expanded="false">
                        <span class="acc-title"><i class='bx bx-briefcase'></i> Work Experience</span>
                        <span class="acc-icon" aria-hidden="true">▾</span>
                    </button>
                    <div class="acc-panel" style="display:none;">
                        <div id="work-experience-fields">
                               <?php if (!empty($work_experiences)): ?>
                                <?php foreach ($work_experiences as $work): ?>
                                <div class="work-entry work-entry--panel">
                                    <div class="work-row">
                                        <div class="inputfield">
                                            <label>Position</label>
                                            <input type="text" name="position[]" class="formbold-form-input" value="<?php echo htmlspecialchars($work['position'] ?? ''); ?>">
                                        </div>
                                        <div class="inputfield">
                                            <label>Company</label>
                                            <input type="text" name="company_name[]" class="formbold-form-input" value="<?php echo htmlspecialchars($work['company_name'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="work-row">
                                        <div class="inputfield">
                                            <label>Country</label>
                                            <input type="text" name="country[]" class="formbold-form-input" value="<?php echo htmlspecialchars($work['country'] ?? ''); ?>">
                                        </div>
                                        <div class="inputfield">
                                            <label>Employment Status</label>
                                            <select name="employment_status[]" class="formbold-form-input">
                                                <option value="" <?php echo empty($work['employment_status'] ?? '') ? 'selected' : ''; ?>>Select</option>
                                                <option value="Current" <?php echo (($work['employment_status'] ?? '') === 'Current') ? 'selected' : ''; ?>>Current</option>
                                                <option value="Previous" <?php echo (($work['employment_status'] ?? '') === 'Previous') ? 'selected' : ''; ?>>Previous</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="work-row">
                                        <div class="inputfield">
                                            <label>Work Type</label>
                                            <select name="work_type[]" class="formbold-form-input">
                                                <option value="" <?php echo empty($work['work_type'] ?? '') ? 'selected' : ''; ?>>Select</option>
                                                <option value="Contract" <?php echo (($work['work_type'] ?? '') === 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                                <option value="Learnership" <?php echo (($work['work_type'] ?? '') === 'Learnership') ? 'selected' : ''; ?>>Learnership</option>
                                                <option value="Internship" <?php echo (($work['work_type'] ?? '') === 'Internship') ? 'selected' : ''; ?>>Internship</option>
                                                <option value="Permanent" <?php echo (($work['work_type'] ?? '') === 'Permanent') ? 'selected' : ''; ?>>Permanent</option>
                                                <option value="Training Programme" <?php echo (($work['work_type'] ?? '') === 'Training Programme') ? 'selected' : ''; ?>>Training Programme</option>
                                            </select>
                                        </div>
                                        <div class="inputfield">
                                            <label>Reason for Leaving</label>
                                            <input type="text" name="reason_for_leaving[]" class="formbold-form-input" maxlength="2000" value="<?php echo htmlspecialchars($work['reason_for_leaving'] ?? ''); ?>" />
                                        </div>
                                    </div>

                                    <div class="work-row">
                                        <div class="inputfield">
                                            <label>Start Date</label>
                                            <input type="date" name="start_date[]" class="formbold-form-input" value="<?php echo htmlspecialchars($work['start_date'] ?? ''); ?>">
                                        </div>
                                        <div class="inputfield">
                                            <label>End Date</label>
                                            <input type="date" name="end_date[]" class="formbold-form-input" value="<?php echo htmlspecialchars($work['end_date'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="inputfield">
                                        <label>Duties and Responsibilities</label>
                                        <textarea class="textarea" name="duties[]" cols="35" rows="5" maxlength="1000"><?php echo htmlspecialchars($work['duties'] ?? ''); ?></textarea>
                                    </div>

                                    <button type="button" class="btn-remove" onclick="removeWorkExperience(this)">Remove</button>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="work-entry work-entry--panel">
                                    <div class="work-row">
                                        <div class="inputfield">
                                            <label>Position</label>
                                            <input type="text" name="position[]" class="formbold-form-input" value="<?php echo $_SESSION['position']; ?>">
                                        </div>
                                        <div class="inputfield">
                                            <label>Company</label>
                                            <input type="text" name="company_name[]" class="formbold-form-input" value="<?php echo $_SESSION['company_name']; ?>">
                                        </div>
                                    </div>

                                    <div class="work-row">
                                        <div class="inputfield">
                                            <label>Country</label>
                                            <input type="text" name="country[]" class="formbold-form-input" value="<?php echo $_SESSION['country'] ?? ''; ?>">
                                        </div>
                                        <div class="inputfield">
                                            <label>Employment Status</label>
                                            <select name="employment_status[]" class="formbold-form-input">
                                                <option value="" <?php echo empty($_SESSION['employment_status'] ?? '') ? 'selected' : ''; ?>>Select</option>
                                                <option value="Current" <?php echo (($_SESSION['employment_status'] ?? '') === 'Current') ? 'selected' : ''; ?>>Current</option>
                                                <option value="Previous" <?php echo (($_SESSION['employment_status'] ?? '') === 'Previous') ? 'selected' : ''; ?>>Previous</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="work-row">
                                        <div class="inputfield">
                                            <label>Work Type</label>
                                            <select name="work_type[]" class="formbold-form-input">
                                                <option value="" <?php echo empty($_SESSION['work_type'] ?? '') ? 'selected' : ''; ?>>Select</option>
                                                <option value="Contract" <?php echo (($_SESSION['work_type'] ?? '') === 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                                <option value="Learnership" <?php echo (($_SESSION['work_type'] ?? '') === 'Learnership') ? 'selected' : ''; ?>>Learnership</option>
                                                <option value="Internship" <?php echo (($_SESSION['work_type'] ?? '') === 'Internship') ? 'selected' : ''; ?>>Internship</option>
                                                <option value="Permanent" <?php echo (($_SESSION['work_type'] ?? '') === 'Permanent') ? 'selected' : ''; ?>>Permanent</option>
                                                <option value="Training Programme" <?php echo (($_SESSION['work_type'] ?? '') === 'Training Programme') ? 'selected' : ''; ?>>Training Programme</option>
                                            </select>
                                        </div>
                                        <div class="inputfield">
                                            <label>Reason for Leaving</label>
                                            <input type="text" name="reason_for_leaving[]" class="formbold-form-input" maxlength="2000" value="<?php echo htmlspecialchars($_SESSION['reason_for_leaving'] ?? ''); ?>" />
                                        </div>
                                    </div>

                                    <div class="work-row">
                                        <div class="inputfield">
                                            <label>Start Date</label>
                                            <input type="date" name="start_date[]" class="formbold-form-input" value="<?php echo $_SESSION['start_date'] ?? ''; ?>">
                                        </div>
                                        <div class="inputfield">
                                            <label>End Date</label>
                                            <input type="date" name="end_date[]" class="formbold-form-input" value="<?php echo $_SESSION['end_date'] ?? ''; ?>">
                                        </div>
                                    </div>

                                    <div class="inputfield">
                                        <label>Duties and Responsibilities</label>
                                        <textarea class="textarea" name="duties[]" cols="35" rows="5" maxlength="1000"><?php echo htmlspecialchars($_SESSION['duties'] ?? ''); ?></textarea>
                                    </div>

                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="acc-action" onclick="addWorkExperience()"><i class='bx bx-plus'></i> Add More Work Experience</button>
                    </div>
                </section>

                <!-- 5. Skills -->
                <section class="acc-item">

                    <button type="button" class="acc-header" aria-expanded="false">
                        <span class="acc-title"><i class='bx bx-bulb'></i> Skills</span>
                        <span class="acc-icon" aria-hidden="true">▾</span>
                    </button>
                    <div class="acc-panel" style="display:none;">
                        <div class="skill-grid">
                            <div class="inputfield">
                                <label style="color: var(--accent-skill);">Non-Technical Skills</label>
                                <textarea class="textarea" name="soft_skills" id="soft_skills" cols="35" rows="7" placeholder="Communication, Leadership..."><?php echo htmlspecialchars($soft_skills); ?></textarea>
                            </div>
                            <div class="inputfield">
                                <label style="color: var(--accent-skill);">Technical Skills</label>
                                <textarea class="textarea" name="technical_skills" id="technical_skills" cols="35" rows="7" placeholder="HTML, PHP, SQL..."><?php echo htmlspecialchars($technical_skills); ?></textarea>
                            </div>
                            <div class="hint">
                                These skills and your education/work entries are used for job criteria comparison and auto-evaluation.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 6. Language Proficiency -->
                <section class="acc-item">
                    <button type="button" class="acc-header" aria-expanded="false">
                        <span class="acc-title"><i class='bx bx-globe'></i> Language Proficiency</span>
                        <span class="acc-icon" aria-hidden="true">▾</span>
                    </button>
                    <div class="acc-panel" style="display:none;">
                        <div id="language-proficiency-fields">
                            <?php
                            $language_rows = [];
                            $langSql = "SELECT language_name, speaking_level, reading_level, writing_level FROM language_proficiency WHERE user_id = ?";
                            $langStmt = $conn->prepare($langSql);
                            $langStmt->bind_param("i", $user_id);
                            $langStmt->execute();
                            $langResult = $langStmt->get_result();
                            while ($langRow = $langResult->fetch_assoc()) {
                                $language_rows[] = $langRow;
                            }
                            $langStmt->close();
                            ?>

                            <?php if (!empty($language_rows)): ?>
                                <?php foreach ($language_rows as $idx => $lang): ?>
                                    <div class="language-entry" style="background:#f4f9ff; padding:15px; border-radius:10px; border:1px dashed #0984e3; margin-bottom:15px;">
                                        <div class="inputgrid-2" style="margin-bottom:0;">
                                            <div class="inputfield">
                                                <label>Language</label>
                                                <input type="text" name="language_name[]" class="formbold-form-input" value="<?php echo htmlspecialchars($lang['language_name'] ?? ''); ?>" />
                                            </div>
                                            <div class="inputfield">
                                                <label>Speaking Level</label>
                                                <select name="speaking_level[]" class="formbold-form-input">
                                                    <option value="" <?php echo empty($lang['speaking_level']) ? 'selected' : ''; ?>>Select</option>
                                                    <?php foreach (['Beginner','Fair','Good','Fluent'] as $opt): ?>
                                                        <option value="<?php echo $opt; ?>" <?php echo (($lang['speaking_level'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="inputgrid-2" style="margin-bottom:0; margin-top:12px;">
                                            <div class="inputfield">
                                                <label>Reading Level</label>
                                                <select name="reading_level[]" class="formbold-form-input">
                                                    <option value="" <?php echo empty($lang['reading_level']) ? 'selected' : ''; ?>>Select</option>
                                                    <?php foreach (['Beginner','Fair','Good','Fluent'] as $opt): ?>
                                                        <option value="<?php echo $opt; ?>" <?php echo (($lang['reading_level'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="inputfield">
                                                <label>Writing Level</label>
                                                <select name="writing_level[]" class="formbold-form-input">
                                                    <option value="" <?php echo empty($lang['writing_level']) ? 'selected' : ''; ?>>Select</option>
                                                    <?php foreach (['Beginner','Fair','Good','Fluent'] as $opt): ?>
                                                        <option value="<?php echo $opt; ?>" <?php echo (($lang['writing_level'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <button type="button" class="btn-remove" onclick="removeLanguageProficiency(this)" style="background:#ff6b6b; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer; font-size:12px; margin-top:12px;">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="language-entry" style="background:#f4f9ff; padding:15px; border-radius:10px; border:1px dashed #0984e3; margin-bottom:15px;">
                                    <div class="inputgrid-2" style="margin-bottom:0;">
                                        <div class="inputfield">
                                            <label>Language</label>
                                            <input type="text" name="language_name[]" class="formbold-form-input" value="" />
                                        </div>
                                        <div class="inputfield">
                                            <label>Speaking Level</label>
                                            <select name="speaking_level[]" class="formbold-form-input">
                                                <option value="">Select</option>
                                                <?php foreach (['Beginner','Fair','Good','Fluent'] as $opt): ?>
                                                    <option value="<?php echo $opt; ?>"><?php echo $opt; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="inputgrid-2" style="margin-bottom:0; margin-top:12px;">
                                        <div class="inputfield">
                                            <label>Reading Level</label>
                                            <select name="reading_level[]" class="formbold-form-input">
                                                <option value="">Select</option>
                                                <?php foreach (['Beginner','Fair','Good','Fluent'] as $opt): ?>
                                                    <option value="<?php echo $opt; ?>"><?php echo $opt; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="inputfield">
                                            <label>Writing Level</label>
                                            <select name="writing_level[]" class="formbold-form-input">
                                                <option value="">Select</option>
                                                <?php foreach (['Beginner','Fair','Good','Fluent'] as $opt): ?>
                                                    <option value="<?php echo $opt; ?>"><?php echo $opt; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="button" class="acc-action" onclick="addLanguageProficiency()"><i class='bx bx-plus'></i> Add Language</button>
                    </div>
                </section>

                <!-- 7. Computer Literacy / Computer Skills -->
                <section class="acc-item">
                    <button type="button" class="acc-header" aria-expanded="false">
                        <span class="acc-title"><i class='bx bx-laptop'></i> Computer Literacy / Computer Skills</span>
                        <span class="acc-icon" aria-hidden="true">▾</span>
                    </button>
                    <div class="acc-panel" style="display:none;">

                        <?php
                        // Load computer literacy rows to render knowledge graph
                        $computerGraph = [];
                        $computerGraphSql = "SELECT skill_name, proficiency FROM computer_literacy WHERE user_id = ?";
                        $computerGraphStmt = $conn->prepare($computerGraphSql);
                        if ($computerGraphStmt) {
                            $computerGraphStmt->bind_param("i", $user_id);
                            $computerGraphStmt->execute();
                            $computerGraphResult = $computerGraphStmt->get_result();
                            while ($computerGraphRow = $computerGraphResult->fetch_assoc()) {
                                $computerGraph[] = [
                                    'skill_name' => $computerGraphRow['skill_name'] ?? '',
                                    'proficiency' => intval($computerGraphRow['proficiency'] ?? 0)
                                ];
                            }
                            $computerGraphStmt->close();
                        }

                        $maxProficiency = 0;
                        foreach ($computerGraph as $row) {
                            if (($row['proficiency'] ?? 0) > $maxProficiency) $maxProficiency = $row['proficiency'];
                        }
                        ?>

                        <div class="empty-state" style="margin-bottom:12px;">
                            <strong>Skill Knowledge</strong>
                            <div class="small">Bar graph based on your selected computer skills & proficiency.</div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr; gap:10px; margin-bottom:14px;">
                            <?php if (!empty($computerGraph)): ?>
                                <?php
                                // Show up to 9 bars, but keep the full dataset for per-skill proficiency rendering.
                                $computerGraphBar = array_slice($computerGraph, 0, 9);
                                foreach ($computerGraphBar as $row):

                                    $skill = (string)($row['skill_name'] ?? '');
                                    $prof = intval($row['proficiency'] ?? 0);
                                    $pct = max(0, min(100, $prof));
                                    // If all selected skills share the same proficiency (current DB design in this page),
                                    // they will render with the same bar height. That's expected.
                                    // (Proper per-skill proficiency would require changing how `computer_literacy` is stored.)
                                ?>
                                    <div style="background:#f4f9ff; border:1px solid rgba(9,132,227,0.15); border-radius:12px; padding:10px 12px;">
                                        <div style="display:flex; justify-content:space-between; align-items:baseline; gap:10px; margin-bottom:6px;">
                                            <div style="font-weight:800; color:#0f172a; font-size:13px;"><?php echo htmlspecialchars($skill); ?></div>
                                            <div style="font-weight:900; color:#0984e3; font-size:13px;"><?php echo htmlspecialchars((string)$prof); ?>/100</div>
                                        </div>
                                        <div style="height:10px; background:rgba(102,126,234,0.12); border-radius:999px; overflow:hidden;">
                                            <div style="height:100%; width:<?php echo $pct; ?>%; background:linear-gradient(90deg,#6c5ce7,#0984e3); border-radius:999px;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="small" style="color:#6b7280; font-weight:700;">No computer skills saved yet.</div>
                            <?php endif; ?>
                        </div>
                        <div class="inputfield">
                            <label>Computer Skills</label>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                <?php
                                $computerSkillOptions = [
                                    'Microsoft Word' => 'Microsoft Word',
                                    'Excel' => 'Excel',
                                    'PowerPoint' => 'PowerPoint',
                                    'Outlook' => 'Outlook',
                                    'Access' => 'Access',
                                    'Publisher' => 'Publisher',
                                    'Visio' => 'Visio',
                                    'Web Browser' => 'Web Browser',
                                    'Project' => 'Project',
                                ];
                                $selectedComputerSkills = [];
                                if (isset($_SESSION['computer_skills_selected']) && is_array($_SESSION['computer_skills_selected'])) {
                                    $selectedComputerSkills = $_SESSION['computer_skills_selected'];
                                }
                                $computerSkillOther = isset($_SESSION['computer_skills_other']) ? (string)$_SESSION['computer_skills_other'] : '';
                                ?>

                                <?php foreach ($computerSkillOptions as $value => $label):
                                    $isChecked = in_array($value, $selectedComputerSkills, true);
                                    $skillId = 'skill_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$value);
                                ?>
                                    <label style="display:flex; align-items:center; gap:10px; font-weight:600; color:#334155; text-transform:none; letter-spacing:0; font-size:14px;">
                                        <input
                                            type="checkbox"
                                            class="computer-skill-checkbox"
                                            data-skill="<?php echo htmlspecialchars($value); ?>"
                                            id="<?php echo htmlspecialchars($skillId); ?>"
                                            name="computer_skills[]"
                                            value="<?php echo htmlspecialchars($value); ?>"
                                            <?php echo $isChecked ? 'checked' : ''; ?>
                                        >
                                        <?php echo htmlspecialchars($label); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="inputfield">
                            <label>Rate Each Computer Skill (0-100)</label>
                            <?php
                            // Build per-skill proficiency map from DB rows
                            $computerProficiencyMap = [];
                            if (!empty($computerGraph)) {
                                foreach ($computerGraph as $row) {
                                    $skillKey = (string)($row['skill_name'] ?? '');
                                    if ($skillKey !== '') {
                                        $computerProficiencyMap[$skillKey] = intval($row['proficiency'] ?? 0);
                                    }
                                }
                            }
                            ?>

                            <div style="display:grid; grid-template-columns:1fr; gap:10px;">
                                <?php
                                // Render proficiency sliders ONLY for selected items (stored in DB)
                                // If your user selects new skills that are not in DB yet, they will default to 0.
                                foreach ($computerSkillOptions as $value => $label):
                                    $isChecked = in_array($value, $selectedComputerSkills, true);
                                    if (!$isChecked) continue;
                                    $profVal = intval($computerProficiencyMap[$value] ?? 0);
                                    ?>
                                    <div style="background:#f4f9ff; border:1px solid rgba(9,132,227,0.15); border-radius:12px; padding:10px 12px;">
                                        <div style="display:flex; justify-content:space-between; align-items:baseline; gap:10px; margin-bottom:6px;">
                                            <div style="font-weight:800; color:#0f172a; font-size:13px;"> <?php echo htmlspecialchars($label); ?> </div>
                                            <div style="font-weight:900; color:#0984e3; font-size:13px;">
                                                <?php echo htmlspecialchars((string)$profVal); ?>/100
                                            </div>
                                        </div>
                                        <input type="range"
                                               name="computer_skills_proficiency[<?php echo htmlspecialchars($value); ?>]"
                                               min="0" max="100" step="1"
                                               value="<?php echo htmlspecialchars((string)$profVal); ?>"
                                               style="width:100%;">
                                    </div>
                                <?php endforeach; ?>

                                <?php if (empty($selectedComputerSkills)): ?>
                                    <div class="small" style="color:#6b7280; font-weight:700;">Select at least one computer skill above.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="inputfield" style="margin-top:10px;">
                            <label>Other Skills</label>
                            <input type="text" name="computer_skills_other" class="formbold-form-input" value="<?php echo htmlspecialchars($computerSkillOther); ?>" placeholder="Type additional skills... (comma-separated)" id="computer_skills_other_input">
                            <div class="hint" style="margin-top:6px;">Example: Photoshop, Canva, AutoCAD</div>
                        </div>

                        <?php if (!empty($computer_other_skills)): ?>
                            <div class="inputfield" style="margin-top:-6px;">
                                <label>Rate Each Other Skill (0-100)</label>
                                <div style="display:grid; grid-template-columns:1fr; gap:10px;">
                                    <?php foreach ($computer_other_skills as $otherSkillName):
                                        $otherSkillKey = $otherSkillName;
                                        $otherProf = intval($computerProficiencyMap[$otherSkillKey] ?? 0);
                                    ?>
                                        <div style="background:#f4f9ff; border:1px solid rgba(9,132,227,0.15); border-radius:12px; padding:10px 12px;">
                                            <div style="display:flex; justify-content:space-between; align-items:baseline; gap:10px; margin-bottom:6px;">
                                                <div style="font-weight:800; color:#0f172a; font-size:13px;"> <?php echo htmlspecialchars($otherSkillName); ?> </div>
                                                <div style="font-weight:900; color:#0984e3; font-size:13px;"> <?php echo htmlspecialchars((string)$otherProf); ?>/100 </div>
                                            </div>
                                            <input type="range"
                                                   name="computer_other_skills_proficiency[<?php echo htmlspecialchars($otherSkillName); ?>]"
                                                   min="0" max="100" step="1"
                                                   value="<?php echo htmlspecialchars((string)$otherProf); ?>"
                                                   style="width:100%;">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="small" style="color:#6b7280; font-weight:700; margin-top:-6px;">
                                Add “Other Skills” above to rate them.
                            </div>
                        <?php endif; ?>



                        <button type="submit" class="btn" name="update_entire_profile" style="margin-top:16px; width:100%; background: linear-gradient(135deg, #6c5ce7, #0984e3);">
                            <i class="fas fa-save"></i> Save Computer Skills
                        </button>
                    </div>
                </section>

                <!-- 8. Certifications -->
                <section class="acc-item">
                    <button type="button" class="acc-header" aria-expanded="false">
                        <span class="acc-title"><i class='bx bx-badge-check'></i> Certifications</span>
                        <span class="acc-icon" aria-hidden="true">▾</span>
                    </button>

                    <div class="acc-panel" style="display:none;">
                        <div class="small" style="margin-bottom:12px;color:#6b7280;font-weight:700;">
                            Add your certifications.
                        </div>

                        <div id="certifications-fields">
                            <?php
                            // Fetch certifications
                            $certifications = [];
                            try {
                                $certSql = "SELECT certification_name, issuing_organization, year_completed FROM certifications WHERE user_id = ? ORDER BY certification_id ASC";
                                $certStmt = $conn->prepare($certSql);
                                $certStmt->bind_param("i", $user_id);
                                $certStmt->execute();
                                $certResult = $certStmt->get_result();
                                while ($certRow = $certResult->fetch_assoc()) {
                                    $certifications[] = $certRow;
                                }
                                $certStmt->close();
                            } catch (Throwable $e) {
                                // If table/columns don't exist in some installs, render empty UI.
                                $certifications = [];
                            }
                            ?>

                            <?php if (!empty($certifications)): ?>
                                <?php foreach ($certifications as $cert): ?>
                                    <div class="cert-entry education-entry--panel" style="background:#f8fffd; padding:15px; border-radius:10px; border: 1px dashed var(--accent-skill); margin-bottom:15px;">
                                        <div class="edu-row">
                                            <div class="inputfield">
                                                <label>Certification Name</label>
                                                <input type="text" name="certification_name[]" class="formbold-form-input" value="<?php echo htmlspecialchars($cert['certification_name'] ?? ''); ?>" />
                                            </div>
                                            <div class="inputfield">
                                                <label>Issuing Organization</label>
                                                <input type="text" name="issuing_organization[]" class="formbold-form-input" value="<?php echo htmlspecialchars($cert['issuing_organization'] ?? ''); ?>" />
                                            </div>
                                        </div>
                                        <div class="inputfield" style="margin-top:12px;">
                                            <label>Year Completed</label>
                                            <input type="number" name="cert_year_completed[]" class="formbold-form-input" value="<?php echo htmlspecialchars((string)($cert['year_completed'] ?? '')); ?>" />
                                        </div>
                                        <button type="button" class="btn-remove" onclick="removeCertification(this)" style="background: #ff6b6b; color: white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer; font-size:12px; margin-top:12px;">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="cert-entry education-entry--panel" style="background:#f8fffd; padding:15px; border-radius:10px; border: 1px dashed var(--accent-skill); margin-bottom:15px;">
                                    <div class="edu-row">
                                        <div class="inputfield">
                                            <label>Certification Name</label>
                                            <input type="text" name="certification_name[]" class="formbold-form-input" value="" />
                                        </div>
                                        <div class="inputfield">
                                            <label>Issuing Organization</label>
                                            <input type="text" name="issuing_organization[]" class="formbold-form-input" value="" />
                                        </div>
                                    </div>
                                    <div class="inputfield" style="margin-top:12px;">
                                        <label>Year Completed</label>
                                        <input type="number" name="cert_year_completed[]" class="formbold-form-input" value="" />
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="button" class="acc-action" onclick="addCertification()" style="margin-top:8px;">
                            <i class='bx bx-plus'></i> Add More Certifications
                        </button>

                        <div class="hint" style="margin-top:12px;">
                            Saving happens when you click <b>Update Profile</b>.
                        </div>
                    </div>
                </section>


                <!-- Submit Button -->
                <div class="buttons-section">
                    <button type="submit" name="update_entire_profile" class="btn"
                            style="background: linear-gradient(45deg, #6c5ce7, #0984e3); border: none; padding: 15px 40px; color: white; border-radius: 30px; font-size: 16px; font-weight: bold; letter-spacing: 1px; box-shadow: 0 10px 20px rgba(108, 92, 231, 0.3);">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

<script>
// Exit button functionality - redirect to applicant page
    document.getElementById('exitPage').addEventListener('click', function() {
        window.location.href = 'applicant.php';
    });

    // Alert auto-dismiss
    const alertBox = document.getElementById('alertBox');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 1000);
        }, 10000);
    }

    // Toggle edit mode for professional title and summary
    function toggleEdit() {
        document.getElementById('title-display').style.display = 'none';
        document.getElementById('title-input').style.display = 'block';
        document.getElementById('summary-display').style.display = 'none';
        document.getElementById('summary-input').style.display = 'block';
        document.getElementById('edit-btn').style.display = 'none';
        document.getElementById('update-btn').style.display = 'block';
        document.getElementById('cancel-btn').style.display = 'block';
    }

    function cancelEdit() {
        document.getElementById('title-display').style.display = 'block';
        document.getElementById('title-input').style.display = 'none';
        document.getElementById('summary-display').style.display = 'block';
        document.getElementById('summary-input').style.display = 'none';
        document.getElementById('edit-btn').style.display = 'block';
        document.getElementById('update-btn').style.display = 'none';
        document.getElementById('cancel-btn').style.display = 'none';
    }

    // Profile picture upload
    function uploadProfilePic() {
        document.getElementById('upload-form').submit();
    }

    // Summary form confirmation
    document.getElementById('summary-form').addEventListener('submit', function(e) {
        const confirmed = confirm("Are you sure you want to update your professional title and summary?");
        if (!confirmed) {
            e.preventDefault();
        }
    });

    // Add Education function
    function addEducation() {
        const container = document.getElementById('education-fields');
        const newEntry = document.createElement('div');
        newEntry.className = 'education-entry';
        newEntry.style.cssText = 'background: #f8fffd; padding: 15px; border-radius: 10px; border: 1px dashed var(--accent-edu); margin-bottom: 15px;';
        newEntry.innerHTML = `
            <div class="inputfield">
                <label>Institution Name</label>
                <input type="text" name="institution[]" class="formbold-form-input" value="">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
                <div class="inputfield">
                    <label>Qualification Level</label>
                    <select name="qualification_level[]" class="formbold-form-input">
                        <option value="">Select Level</option>
                        <option value="High School">High School</option>
                        <option value="Certificate">Certificate</option>
                        <option value="Diploma">Diploma</option>
                        <option value="Bachelor's Degree">Bachelor's Degree</option>
                        <option value="Master's Degree">Master's Degree</option>
                        <option value="Doctorate">Doctorate</option>
                        <option value="Professional Qualification">Professional Qualification</option>
                    </select>
                </div>
                <div class="inputfield">
                    <label>Qualification Name</label>
                    <input type="text" name="qualification_name[]" class="formbold-form-input" value="">
                </div>
            </div>
            <div class="inputfield">
                <label>Year Graduated</label>
                <input type="number" name="year_completed[]" class="formbold-form-input" value="">
            </div>
            <button type="button" class="btn-remove" onclick="removeEducation(this)" style="background: #ff6b6b; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px; margin-top: 10px;">Remove</button>
        `;
        container.appendChild(newEntry);
    }

    // Remove Education function
    function removeEducation(button) {
        const entry = button.parentElement;
        entry.remove();
    }

    // Add Work Experience function
    function addWorkExperience() {
        const container = document.getElementById('work-experience-fields');
        const newEntry = document.createElement('div');
        newEntry.className = 'work-entry work-entry--panel';
        newEntry.style.cssText = 'background: #f4f9ff; padding: 15px; border-radius: 10px; border: 1px dashed var(--accent-work); margin-bottom: 15px;';
        newEntry.innerHTML = `
            <div class="work-row">
                <div class="inputfield">
                    <label>Position</label>
                    <input type="text" name="position[]" class="formbold-form-input" value="">
                </div>
                <div class="inputfield">
                    <label>Company</label>
                    <input type="text" name="company_name[]" class="formbold-form-input" value="">
                </div>
            </div>

            <div class="work-row">
                <div class="inputfield">
                    <label>Country</label>
                    <input type="text" name="country[]" class="formbold-form-input" value="">
                </div>
                <div class="inputfield">
                    <label>Employment Status</label>
                    <select name="employment_status[]" class="formbold-form-input">
                        <option value="">Select</option>
                        <option value="Current">Current</option>
                        <option value="Previous">Previous</option>
                    </select>
                </div>
            </div>

            <div class="work-row">
                <div class="inputfield">
                    <label>Work Type</label>
                    <select name="work_type[]" class="formbold-form-input">
                        <option value="">Select</option>
                        <option value="Contract">Contract</option>
                        <option value="Learnership">Learnership</option>
                        <option value="Internship">Internship</option>
                        <option value="Permanent">Permanent</option>
                        <option value="Training Programme">Training Programme</option>
                    </select>
                </div>
                <div class="inputfield">
                    <label>Reason for Leaving</label>
                    <input type="text" name="reason_for_leaving[]" class="formbold-form-input" maxlength="2000" value="" />
                </div>
            </div>

            <div class="work-row">
                <div class="inputfield">
                    <label>Start Date</label>
                    <input type="date" name="start_date[]" class="formbold-form-input" value="">
                </div>
                <div class="inputfield">
                    <label>End Date</label>
                    <input type="date" name="end_date[]" class="formbold-form-input" value="">
                </div>
            </div>

            <div class="inputfield">
                <label>Duties and Responsibilities</label>
                <textarea class="textarea" name="duties[]" cols="35" rows="5" maxlength="1000"></textarea>
            </div>

            <button type="button" onclick="removeWorkExperience(this)" style="background: #ff6b6b; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px;">Remove</button>
        `;
        container.appendChild(newEntry);
    }

    // Remove Work Experience function
    function removeWorkExperience(button) {
        const entry = button.parentElement;
        entry.remove();
    }

    // Add Language Proficiency function
    function addLanguageProficiency() {
        const container = document.getElementById('language-proficiency-fields');
        if (!container) return;

        const newEntry = document.createElement('div');
        newEntry.className = 'language-entry';
        newEntry.style.cssText = 'background:#f4f9ff; padding:15px; border-radius:10px; border:1px dashed #0984e3; margin-bottom:15px;';
        newEntry.innerHTML = `
            <div class="inputgrid-2" style="margin-bottom:0;">
                <div class="inputfield">
                    <label>Language</label>
                    <input type="text" name="language_name[]" class="formbold-form-input" value="" />
                </div>
                <div class="inputfield">
                    <label>Speaking Level</label>
                    <select name="speaking_level[]" class="formbold-form-input">
                        <option value="">Select</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Fair">Fair</option>
                        <option value="Good">Good</option>
                        <option value="Fluent">Fluent</option>
                    </select>
                </div>
            </div>

            <div class="inputgrid-2" style="margin-bottom:0; margin-top:12px;">
                <div class="inputfield">
                    <label>Reading Level</label>
                    <select name="reading_level[]" class="formbold-form-input">
                        <option value="">Select</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Fair">Fair</option>
                        <option value="Good">Good</option>
                        <option value="Fluent">Fluent</option>
                    </select>
                </div>
                <div class="inputfield">
                    <label>Writing Level</label>
                    <select name="writing_level[]" class="formbold-form-input">
                        <option value="">Select</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Fair">Fair</option>
                        <option value="Good">Good</option>
                        <option value="Fluent">Fluent</option>
                    </select>
                </div>
            </div>

            <button type="button" class="btn-remove" onclick="removeLanguageProficiency(this)" style="background:#ff6b6b; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer; font-size:12px; margin-top:12px;">Remove</button>
        `;

        container.appendChild(newEntry);
    }

    function removeLanguageProficiency(button) {
        const entry = button.parentElement;
        entry.remove();
    }

    // Add Certification function
    function addCertification() {
        const container = document.getElementById('certifications-fields');
        if (!container) return;

        const newEntry = document.createElement('div');
        newEntry.className = 'cert-entry education-entry--panel';
        newEntry.style.cssText = 'background:#f8fffd; padding:15px; border-radius:10px; border: 1px dashed var(--accent-skill); margin-bottom:15px;';
        newEntry.innerHTML = `
            <div class="edu-row">
                <div class="inputfield">
                    <label>Certification Name</label>
                    <input type="text" name="certification_name[]" class="formbold-form-input" value="" />
                </div>
                <div class="inputfield">
                    <label>Issuing Organization</label>
                    <input type="text" name="issuing_organization[]" class="formbold-form-input" value="" />
                </div>
            </div>
            <div class="inputfield" style="margin-top:12px;">
                <label>Year Completed</label>
                <input type="number" name="cert_year_completed[]" class="formbold-form-input" value="" />
            </div>
            <button type="button" class="btn-remove" onclick="removeCertification(this)" style="background: #ff6b6b; color: white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer; font-size:12px; margin-top:12px;">Remove</button>
        `;

        container.appendChild(newEntry);
    }

    // Remove Certification function
    function removeCertification(button) {
        const entry = button.parentElement;
        entry.remove();
    }


async function loadSupportingDocsBlock() {
        const block = document.getElementById('supporting-docs-block');
        const form = document.getElementById('supporting-docs-form');
        if (!block || !form) return;

        // Ensure form is actually submittable even if it was previously hidden
        form.style.display = 'block';

        try {
            block.innerHTML = '<div class="small">Loading your documents status...</div>';
            const res = await fetch('get_applicant_supporting_docs_status.php', { cache: 'no-store' });
            if (!res.ok) throw new Error('Request failed');
            const data = await res.json();

            if (!data.ok) {
                block.innerHTML = '<strong>Supporting documents</strong><div class="small">Unable to load documents status.</div>';
                form.style.display = 'none';
                return;
            }

            const count = Number(data.documentsCount ?? 0);
            const max = 5;
            const remaining = Math.max(0, max - count);

            if (count >= max) {
                block.innerHTML = '<strong>Supporting documents</strong><div class="small">Uploaded: <b>' + count + '</b> / ' + max + '. You cannot upload more.</div>';
                form.style.display = 'none';
            } else {
                block.innerHTML = '<strong>Supporting documents</strong><div class="small">Uploaded: <b>' + count + '</b> / ' + max + '. Remaining: <b>' + remaining + '</b>.</div>';
                form.style.display = 'block';
            }
        } catch (e) {
            block.innerHTML = '<strong>Supporting documents</strong><div class="small">Failed to load documents status.</div>';
            form.style.display = 'none';
        }
    }

    // Load resume tools content inside accordion panel
    async function loadResumeDocumentsBlock() {
        const block = document.getElementById('resume-documents-block');
        if (!block) return;
        block.innerHTML = '<div class="small">Loading your resume status...</div>';

        try {
            const res = await fetch('get_resume_status.php', { cache: 'no-store' });
            if (!res.ok) throw new Error('Request failed');
            const data = await res.json();

            if (!data.ok) {
                block.innerHTML = '<strong>Resume</strong><div class="small">Unable to load resume status.</div>';
                return;
            }

            const resumeExists = !!data.resumeExists;
            const filename = data.filename ? String(data.filename) : '';

            block.innerHTML = `
                <div style="font-weight:800; display:flex; align-items:center; gap:10px;">
                    <i class='bx bx-file' style="color:#667eea;"></i>
                    Resume
                </div>
                <div class="small" style="margin-top:8px;">
                    ${resumeExists ? 'Uploaded: <b>' + filename + '</b>' : 'Not uploaded yet.'}
                </div>
                <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
                    <a class="btn" style="background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; text-decoration:none; padding:10px 16px; border-radius:12px; font-size:14px;" href="resume.php">
                        <i class='bx bx-upload'></i> ${resumeExists ? 'Update Resume' : 'Upload Resume'}
                    </a>
                    <a class="btn" style="background:linear-gradient(135deg,#10b981,#059669); color:#fff; text-decoration:none; padding:10px 16px; border-radius:12px; font-size:14px; ${resumeExists ? '' : 'opacity:.6; pointer-events:none;'}" href="resume_download.php" download>
                        <i class='bx bx-download'></i> Download
                    </a>
                    <a class="btn" style="background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; text-decoration:none; padding:10px 16px; border-radius:12px; font-size:14px; ${resumeExists ? '' : 'opacity:.6; pointer-events:none;'}" href="preview_resume.php" target="_blank">
                        <i class='bx bx-show'></i> Preview
                    </a>
                </div>
            `;
        } catch (e) {
            block.innerHTML = '<strong>Resume</strong><div class="small">Failed to load resume status.</div>';
        }
    }

    loadResumeDocumentsBlock();
loadSupportingDocsBlock();

    // Show/hide proficiency sliders automatically when skills are checked/unchecked
    (function syncComputerSkillProficiencyUI() {
        const checkboxEls = document.querySelectorAll('.computer-skill-checkbox');

        function setVisibility() {
            // Toggle the related slider block (wrapper div) visibility
            const allSliderInputs = document.querySelectorAll('input[name^="computer_skills_proficiency["]');
            checkboxEls.forEach((cb) => {
                const skillName = cb.getAttribute('data-skill');
                if (!skillName) return;

                allSliderInputs.forEach((si) => {
                    const name = si.getAttribute('name') || '';
                    const m = name.match(/^computer_skills_proficiency\[(.*)\]$/);
                    const key = m && m[1] ? m[1] : null;
                    if (key === skillName) {
                        si.closest('div').style.display = cb.checked ? 'block' : 'none';
                    }
                });
            });

            // If none checked, show the default message (already rendered by PHP)
            const anyChecked = Array.from(checkboxEls).some((c) => c.checked);
            const hintEl = document.querySelector('#computerSkillRatingHint');
            if (hintEl) hintEl.style.display = anyChecked ? 'none' : 'block';
        }

        checkboxEls.forEach((cb) => cb.addEventListener('change', setVisibility));
        setVisibility();
    })();



    function submitSupportingDocsForm() {
        const form = document.getElementById('supporting-docs-form');
        if (!form) return;
        form.style.display = 'block';
        form.submit();
    }



    // Accordion logic for profile categories
    (function initProfileAccordion() {

        const items = document.querySelectorAll('.acc-item');
        items.forEach((item) => {
            const header = item.querySelector('.acc-header');
            if (!header) return;

            header.addEventListener('click', () => {
                // Toggle open/closed
                const open = item.getAttribute('data-open') === 'true';

                item.setAttribute('data-open', open ? 'false' : 'true');

                const panel = item.querySelector('.acc-panel');
                if (!panel) return;
                panel.style.display = open ? 'none' : 'block';

                const isExpanded = !open;
                header.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');

            });
        });

        // Default-open state sync
        items.forEach((item) => {
            const open = item.getAttribute('data-open') === 'true';

            const panel = item.querySelector('.acc-panel');
            const header = item.querySelector('.acc-header');
            if (!panel || !header) return;
            panel.style.display = open ? 'block' : 'none';
            header.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        // Auto-open section from query string (e.g., my_profile.php?section=education)
        try {
            const params = new URLSearchParams(window.location.search);
            const section = (params.get('section') || '').toLowerCase();

            // Map section name -> accordion item index (based on page markup order)
            // 1) Personal Information, 2) Professional Summary, 3) Education, 4) Work Experience, 5) Skills
            const indexMap = {
                personal: 0,
                education: 2,
                skills: 4,
                work: 3,
                work_experience: 3
            };

            // Fallback mapping: locate accordion items by their button text (more reliable)
            function openByTitle(targetTitle) {
                const match = Array.from(items).find((it) => {
                    const t = (it.querySelector('.acc-title')?.textContent || '').toLowerCase();
                    return t.includes(targetTitle);
                });
                if (!match) return false;

                const panel = match.querySelector('.acc-panel');
                const header = match.querySelector('.acc-header');
                if (!panel || !header) return false;

                match.setAttribute('data-open', 'true');
                panel.style.display = 'block';
                header.setAttribute('aria-expanded', 'true');
                match.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return true;
            }

            // Special-case mappings for full section keys used by the access guide
            if (section === 'computer_skills' || section === 'computer_skill' || section === 'computer literacy') {
                openByTitle('computer literacy'); // matches header text
            } else if (section === 'languages' || section === 'language_proficiency') {
                openByTitle('language proficiency');
            } else if (section === 'work_experience') {
                openByTitle('work experience');
            } else if (section === 'skills') {
                openByTitle('skills');
            }


            const idx = indexMap[section];
            if (typeof idx === 'number' && idx >= 0 && idx < items.length) {
                const targetItem = items[idx];
                if (targetItem) {
                    const panel = targetItem.querySelector('.acc-panel');
                    const header = targetItem.querySelector('.acc-header');
                    if (panel && header) {
                        targetItem.setAttribute('data-open', 'true');
                        panel.style.display = 'block';
                        header.setAttribute('aria-expanded', 'true');
                        // Optional: scroll into view for better UX
                        targetItem.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            }
        } catch (e) {
            // no-op
        }
    })();
</script>


</body>
</html>
