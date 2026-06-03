<?php
include('config.php');
session_start();

$target_user_id = (int) $_GET['user_id'];
$application_id = $_GET['application_id'] ?? null;

function parseEducationSnapshot(string $educationBlob): array {
    $educationBlob = trim($educationBlob);
    if ($educationBlob === '') return [];

    $parts = array_map('trim', explode('|', $educationBlob));
    $out = [];

    foreach ($parts as $part) {
        if ($part === '') continue;

        // Expected: Qualification (Institution, Year)
        // Note: Institution may contain commas, so split by last comma before year.
        if (preg_match('/^(.+?)\s*\((.+),\s*([^()]+)\)\s*$/s', $part, $m)) {
            $out[] = [
                'qualification_name' => trim($m[1]),
                'institution' => trim($m[2]),
                'year_completed' => trim($m[3]),
            ];
            continue;
        }

        // Fallback: keep raw part
        $out[] = [
            'qualification_name' => $part,
            'institution' => '',
            'year_completed' => '',
        ];
    }

    return $out;
}

// NOTE: parseWorkExperienceSnapshot is defined later (snapshot-only parser).


if (!isset($target_user_id)) {
    die("target_user_id is not set");
}

// === Fetch main profile data ===
$app_id_var = !empty($application_id) ? (int)$application_id : 0;

$sql = "SELECT
            u.fullname, u.gender, u.dob, u.phone, u.email, u.address,
            a.profile_picture, a.professional_title, a.professional_summary,
            ss.profile_data
        FROM users u
        LEFT JOIN applicant_profile a ON u.user_id = a.user_id
        LEFT JOIN application_snapshots ss ON ss.application_id = ?
        WHERE u.user_id = ?
        ORDER BY ss.created_at DESC
        LIMIT 1";

if ($app_id_var > 0) {
    $sql = "SELECT u.fullname, u.gender, u.dob, u.phone, u.email, u.address, a.profile_picture, a.professional_title, a.professional_summary, ss.profile_data FROM users u LEFT JOIN applicant_profile a ON u.user_id = a.user_id LEFT JOIN application_snapshots ss ON ss.application_id = ? WHERE u.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $app_id_var, $target_user_id);
} else {
    $sql = "SELECT u.fullname, u.gender, u.dob, u.phone, u.email, u.address, a.profile_picture, a.professional_title, a.professional_summary, ss.profile_data FROM users u LEFT JOIN applicant_profile a ON u.user_id = a.user_id LEFT JOIN (SELECT * FROM application_snapshots ss2 JOIN job_applications ja2 ON ss2.application_id = ja2.application_id WHERE ja2.user_id = ? ORDER BY ss2.created_at DESC LIMIT 1) ss ON 1=1 WHERE u.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $target_user_id);
}
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $snapshot_profile = !empty($row['profile_data']) ? json_decode($row['profile_data'], true) : [];
    
    $fullname = htmlspecialchars($snapshot_profile['fullname'] ?? $row['fullname']);
    $fullname_source = !empty($snapshot_profile['fullname']) ? 'Snapshot' : 'Current';
$gender_raw = $snapshot_profile['gender'] ?? $row['gender'];
    $gender = htmlspecialchars($gender_raw ?? '');
    $gender_source = !empty($snapshot_profile['gender']) ? 'Snapshot' : 'Current';
    $dob_raw = $snapshot_profile['dob'] ?? $row['dob'];
    $dob = ($dob_raw ? date("F d, Y", strtotime($dob_raw)) : 'Not provided');
    $dob_source = !empty($snapshot_profile['dob']) ? 'Snapshot' : 'Current';
    $phone = htmlspecialchars($snapshot_profile['phone'] ?? $row['phone'] ?? '');
    $phone_source = !empty($snapshot_profile['phone']) ? 'Snapshot' : 'Current';
    $email = htmlspecialchars($snapshot_profile['email'] ?? $row['email'] ?? '');
    $email_source = !empty($snapshot_profile['email']) ? 'Snapshot' : 'Current';
$address_raw = $snapshot_profile['address'] ?? $row['address'];
    $address = nl2br(htmlspecialchars($address_raw ?? ''));
    $address_source = !empty($snapshot_profile['address']) ? 'Snapshot' : 'Current';
    $orig_title = $snapshot_profile['professional_title'] ?? $row['professional_title'];
    $orig_summary = $snapshot_profile['professional_summary'] ?? $row['professional_summary'];
    $data_source = !empty($row['profile_data']) && !empty($orig_title) ? 'Snapshot' : (!empty($orig_title) ? 'Current' : 'None');

$professional_title = htmlspecialchars($orig_title ?? 'Not specified');
$professional_summary = !empty($orig_summary) ? nl2br(htmlspecialchars($orig_summary)) : 'Profile summary not provided yet.';

$title_source = !empty($row['profile_data']) ? 'Snapshot' : (!empty($row['professional_title']) ? 'Current' : 'None');
$summary_source = !empty($row['profile_data']) ? 'Snapshot' : (!empty($row['professional_summary']) ? 'Current' : 'None');

// Handle profile picture from snapshot (base64) or DB
if (isset($snapshot_profile['profile_picture']) && !empty($snapshot_profile['profile_picture'])) {
    $profile_picture = $snapshot_profile['profile_picture']; // already base64
} elseif ($row['profile_picture']) {
    $profile_picture = 'data:image/jpeg;base64,' . base64_encode($row['profile_picture']);
} else {
    $profile_picture = './img/deco.jpg';
}

$has_snapshot = !empty($row['profile_data']);
$profile_updated = !empty($row['professional_title']) || !empty($row['professional_summary']) || $has_snapshot;
} else {
    die("User not found.");
}

// === Fetch Education (Snapshot Priority) ===
$qualifications = [];
$education_source = 'Snapshot';

if (!empty($snapshot_profile['education'])) {
    $qualifications = parseEducationSnapshot((string) $snapshot_profile['education']);
    if (!empty($qualifications)) {
        $education_source = 'Snapshot';
    }
}

// Snapshot only - no current DB fallback
if (empty($qualifications)) {
    $education_source = 'Snapshot';
}

// === Fetch Technical and Soft Skills (for Skills tab only) ===
$sql_skills = "SELECT technical_skills, soft_skills FROM skills WHERE user_id = ?";
$stmt_skills = $conn->prepare($sql_skills);
$stmt_skills->bind_param("i", $target_user_id);
$stmt_skills->execute();
$result_skills = $stmt_skills->get_result();

$technical_skills = [];
$soft_skills = [];

if ($row = $result_skills->fetch_assoc()) {
    $technical_skills = array_map('trim', explode(',', $row['technical_skills']));
    $soft_skills = array_map('trim', explode(',', $row['soft_skills']));
}

// === Fetch Computer Literacy (for Computer Skills tab) ===
$sql_computer = "SELECT skill_name, proficiency, other_skills FROM computer_literacy WHERE user_id = ?";
$stmt_computer = $conn->prepare($sql_computer);
$stmt_computer->bind_param("i", $target_user_id);
$stmt_computer->execute();
$result_computer = $stmt_computer->get_result();

$computer_literacy_rows = [];
while ($r = $result_computer->fetch_assoc()) {
    $computer_literacy_rows[] = $r;
}

$computer_skills = [];
$computer_other_skills = '';

foreach ($computer_literacy_rows as $row) {
    $skill_name = trim((string)($row['skill_name'] ?? ''));
    $proficiency = $row['proficiency'];
    $other_skills = $row['other_skills'] ?? null;

    if ($skill_name !== '') {
        $computer_skills[] = [
            'skill_name' => $skill_name,
            'proficiency' => $proficiency
        ];
    }

    if ($computer_other_skills === '' && !empty($other_skills)) {
        $computer_other_skills = (string)$other_skills;
    }
}

// Basic dedupe by skill_name (keep first proficiency found)
if (!empty($computer_skills)) {
    $seen = [];
    $deduped = [];
    foreach ($computer_skills as $s) {
        $k = strtolower($s['skill_name']);
        if (isset($seen[$k])) continue;
        $seen[$k] = true;
        $deduped[] = $s;
    }
    $computer_skills = $deduped;
}

// === Fetch Work Experience (Snapshot Priority ONLY) ===
$work_experiences = [];
$work_experience_source = 'Snapshot';

// Parse the snapshot string created by submit_application.php:
// Position @ Company | Country: ... | Employment Status: ... | Work Type: ... | Start: ... | End: ... | Reason for Leaving: ... | Duties: ...
function parseWorkExperienceSnapshot(string $workExpBlob): array {
    $workExpBlob = trim($workExpBlob);
    if ($workExpBlob === '') return [];

    // Snapshot uses ' | ' as delimiter between fields.
    // But each experience itself is separated by ' | ' too, so we need to re-split by the pattern " <pos> @ <company> | Country:".
    // We'll extract blocks using regex that matches from Position @ Company up to the next " | Country:" start.

    // Split by experiences based on ' | Country:' occurrences that follow a new ' @ '.
    // Approach: convert "<pos> @ <company>" marker then capture rest until next marker or end.
    $pattern = '/\s*([^|]+?)\s*@\s*([^|]+?)\s*\|\s*Country:\s*([^|]*?)\s*\|\s*Employment Status:\s*([^|]*?)\s*\|\s*Work Type:\s*([^|]*?)\s*\|\s*Start:\s*([^|]*?)\s*\|\s*End:\s*([^|]*?)\s*\|\s*Reason for Leaving:\s*([^|]*?)\s*\|\s*Duties:\s*([^|]*?)(?=\s*[^|]+?\s*@\s*[^|]+?\s*\||\s*\z)/s';

    if (preg_match_all($pattern, $workExpBlob, $matches, PREG_SET_ORDER)) {
        $out = [];
        foreach ($matches as $m) {
            $out[] = [
                'position' => trim($m[1] ?? ''),
                'company_name' => trim($m[2] ?? ''),
                'duration' => '', // snapshot format doesn't include a single (duration) field
                'duties' => trim($m[9] ?? ''),
                'country' => trim($m[3] ?? ''),
                'employment_status' => trim($m[4] ?? ''),
                'work_type' => trim($m[5] ?? ''),
                'start_date' => trim($m[6] ?? ''),
                'end_date' => trim($m[7] ?? ''),
                'reason_for_leaving' => trim($m[8] ?? ''),
                'employment_type' => '',
            ];
        }
        return $out;
    }

    // Fallback: if regex fails, try older parsing delimiter '(duration): duties' style.
    // We'll reuse simple explode by ' | ' blocks is unreliable; so best effort: return empty.
    return [];
}

if (!empty($snapshot_profile['work_experience'])) {
    $work_experiences = parseWorkExperienceSnapshot((string)$snapshot_profile['work_experience']);
}

// Ensure keys exist for rendering
foreach ($work_experiences as &$we) {
    $we += [
        'position' => '',
        'company_name' => '',
        'duration' => '',
        'duties' => '',
        'employment_status' => '',
        'employment_type' => '',
        'work_type' => '',
        'country' => '',
        'start_date' => '',
        'end_date' => '',
        'reason_for_leaving' => ''
    ];
}
unset($we);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Applicant Profile</title>
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
           SIDEBAR STYLES
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

        /* --- FORCE DARK MODE BACKGROUND --- */
        body.dark-mode .sidebar {
            background-color: var(--dark) !important;
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

        .side-menu li.section-header {
            padding: 8px 16px;
            font-weight: 600;
            color: var(--white);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
            margin-top: 12px;
        }

        .sidebar.collapsed .side-menu li.section-header span {
            display: none;
        }

        .logout {
            margin-top: auto;
            padding: 16px !important;
            background: rgba(0, 0, 0, 0.2);
        }

        @media (min-width: 769px) {
            .logout {
                display: none;
            }
        }

        /* ===========================
           MAIN CONTENT STYLES
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
           NAVBAR STYLES
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

        /* Mobile Menu Button - Ensure Visibility */
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

        /* ===========================
           MAIN CONTENT AREA
        ============================ */
        main {
            padding: 24px;
        }

        /* ===========================
           WELCOME SECTION
        ============================ */
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

        /* ===========================
           HEADER & BREADCRUMB
        ============================ */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .breadcrumb {
            list-style: none;
            display: flex;
            gap: 12px;
        }

        .breadcrumb a {
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
        }

        /* ===========================
           PROFILE STYLES
        ============================ */
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .profile-header {
            position: relative;
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 40px;
            background: var(--white);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 24px;
            align-items: center;
        }

        body.dark-mode .profile-header {
            background: #242526;
        }

        .btn-back {
            position: absolute;
            top: 20px;
            left: 20px;
            background: transparent;
            color: var(--gray);
            border: 1px solid var(--light-gray);
            padding: 11px 20px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            z-index: 10;
        }

        .btn-back:hover {
            background: var(--light-gray);
        }

        body.dark-mode .btn-back {
            color: #adb5bd;
            border-color: #4a4a4a;
        }

        body.dark-mode .btn-back:hover {
            background: #3a3b3c;
        }

        .profile-picture {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .profile-picture img {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 4px solid var(--primary);
            object-fit: cover;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transition: var(--transition);
        }

        .profile-picture img:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.3);
        }

        .profile-info h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            line-height: 1.2;
        }

        body.dark-mode .profile-info h1 {
            color: #a7b7ff;
        }

        .candidate-title {
            font-size: 1.3rem;
            color: var(--gray);
            margin-bottom: 25px;
            font-weight: 500;
        }

        body.dark-mode .candidate-title {
            color: #adb5bd;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 10px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px 12px;
            background: var(--light-gray);
            border-radius: 8px;
            border-top: 3px solid var(--primary);
            transition: var(--transition);
            text-align: center;
        }

        body.dark-mode .info-item {
            background: #3a3b3c;
        }

        .info-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .info-item i {
            color: var(--primary);
            font-size: 1.6rem;
            margin-bottom: 4px;
        }

        .info-item span {
            color: var(--dark);
            font-weight: 500;
            font-size: 0.9rem;
            line-height: 1.3;
        }

        body.dark-mode .info-item span {
            color: #e4e6eb;
        }

        .profile-summary {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 24px;
        }

        body.dark-mode .profile-summary {
            background: #242526;
        }

        .profile-summary h2 {
            color: var(--primary);
            margin-bottom: 16px;
            font-size: 1.5rem;
        }

        body.dark-mode .profile-summary h2 {
            color: #a7b7ff;
        }

        .profile-summary p {
            line-height: 1.6;
            color: var(--dark);
        }

        body.dark-mode .profile-summary p {
            color: #e4e6eb;
        }

        .profile-tabs {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        body.dark-mode .profile-tabs {
            background: #242526;
        }

        .tabs ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            border-bottom: 2px solid var(--light-gray);
            background: var(--light-gray);
        }

        body.dark-mode .tabs ul {
            border-color: #4a4a4a;
            background: #3a3b3c;
        }

        .tabs ul li {
            flex: 1;
            padding: 16px 20px;
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
        }

        .tabs ul li:hover {
            background: var(--light-gray);
            color: #333;
        }

        body.dark-mode .tabs ul li:hover {
            background: #3a3b3c;
            color: #e4e6eb;
        }

        .tabs ul li.active {
            background: var(--white);
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
            font-weight: 600;
        }

        body.dark-mode .tabs ul li.active {
            background: #242526;
            color: #63b3ed;
        }

        .tab-content {
            padding: 30px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-section h3 {
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        body.dark-mode .info-section h3 {
            color: #a7b7ff;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-item label {
            font-weight: 600;
            color: var(--gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            color: var(--dark);
            font-size: 1rem;
        }

        body.dark-mode .info-item span {
            color: #e4e6eb;
        }

        .education-list,
        .experience-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .education-item,
        .experience-item {
            padding: 20px;
            background: var(--light-gray);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        body.dark-mode .education-item,
        body.dark-mode .experience-item {
            background: #3a3b3c;
        }

        .education-header,
        .experience-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .education-header h4,
        .experience-header h4 {
            color: var(--primary);
            margin: 0;
            font-size: 1.1rem;
        }

        body.dark-mode .education-header h4,
        body.dark-mode .experience-header h4 {
            color: #a7b7ff;
        }

        .year,
        .duration {
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .experience-details h5 {
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 1rem;
        }

        body.dark-mode .experience-details h5 {
            color: #a7b7ff;
        }

        .experience-details p {
            line-height: 1.6;
            color: var(--dark);
        }

        body.dark-mode .experience-details p {
            color: #e4e6eb;
        }

        .skills-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .skill-category h3 {
            color: var(--primary);
            margin-bottom: 16px;
            font-size: 1.2rem;
        }

        body.dark-mode .skill-category h3 {
            color: #a7b7ff;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .skill-tag {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .resume-actions {
            text-align: center;
        }

        .resume-actions p {
            margin-bottom: 20px;
            color: var(--dark);
        }

        body.dark-mode .resume-actions p {
            color: #e4e6eb;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            border: 1px solid transparent;
        }

        .btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .no-data {
            text-align: center;
            color: var(--gray);
            font-style: italic;
            padding: 40px;
        }

        body.dark-mode .no-data {
            color: #adb5bd;
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
           RESPONSIVE DESIGN
        ============================ */
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

            .profile-header {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 20px;
            }

            .skills-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-280px);
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

            .mobile-logout-btn {
                display: block;
                font-size: 28px;
                padding: 12px;
            }

            .search-container {
                width: 100%;
                margin-top: 12px;
            }

            .form-input {
                width: 100%;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile-header {
                padding: 20px;
            }

            .profile-picture img {
                width: 120px;
                height: 120px;
            }

            .profile-info h1 {
                font-size: 1.8rem;
            }

            .contact-info {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .mobile-nav-links {
                display: flex;
            }
        }

        @media (max-width: 480px) {
            .welcome-content h1 {
                font-size: 24px;
            }

            .welcome-content p {
                font-size: 16px;
            }

            .profile-header {
                padding: 16px;
            }

            .profile-picture img {
                width: 100px;
                height: 100px;
            }

            .profile-info h1 {
                font-size: 1.6rem;
            }

            .tabs ul {
                flex-direction: column;
            }

            .tabs ul li {
                padding: 12px;
            }

            .tab-content {
                padding: 20px;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f7fb;
            color: #333;
            min-height: 100vh;
        }

        .main_bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            z-index: -1;
            opacity: 0.8;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            animation: fadeInUp 0.8s ease-out;
        }

        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        body.dark-mode .card {
            background: #242526;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            opacity: 0;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .card:hover::before {
            opacity: 1;
        }

        .userProfile {
            grid-column: 1 / 2;
            grid-row: 1 / 2;
            text-align: center;
        }

        .userProfile .profile img {
            width: 100%;
            max-width: 200px;
            height: auto;
            border-radius: 50%;
            border: 4px solid var(--primary);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
            transition: var(--transition);
            margin-bottom: 16px;
        }

        .userProfile .profile img:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
        }

        .userDetails {
            grid-column: 2 / 4;
            grid-row: 1 / 2;
        }

        .work_skills {
            grid-column: 1 / 4;
            grid-row: 2 / 3;
        }

        .timeline_about {
            grid-column: 1 / 4;
            grid-row: 3 / 4;
        }

        .btn-exit {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--btn-exit-bg);
            backdrop-filter: blur(10px);
            color: var(--btn-exit-color);
            border: 1px solid var(--glass-border);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            z-index: 100;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px var(--shadow-color);
        }

        .btn-exit:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px var(--shadow-hover);
        }

        .heading {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 8px;
        }

        .heading::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 2px;
        }

        .work .secondary {
            margin-bottom: 20px;
            padding: 16px;
            background: var(--light-gray);
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
        }
        body.dark-mode .work .secondary {
            background: #3a3b3c;
            border-color: #4a4a4a;
        }

        .work .secondary:hover {
            background: var(--light-gray);
            transform: translateX(4px);
        }
        body.dark-mode .work .secondary:hover {
            background: #3a3b3c;
        }

        .work .secondary h1 {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 8px;
        }
        body.dark-mode .work .secondary h1 {
            color: #e4e6eb;
        }

        .work .secondary p {
            color: var(--gray);
            font-size: 0.9rem;
        }
        body.dark-mode .work .secondary p {
            color: #adb5bd;
        }

        .skills ul {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
        }

        .skills h2 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 20px 0 12px 0;
        }
        body.dark-mode .skills h2 {
            color: #e4e6eb;
        }

        .skills ul li {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
            transition: var(--transition);
            animation: slideInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .skills ul li:nth-child(1) { animation-delay: 0.1s; }
        .skills ul li:nth-child(2) { animation-delay: 0.2s; }
        .skills ul li:nth-child(3) { animation-delay: 0.3s; }
        .skills ul li:nth-child(4) { animation-delay: 0.4s; }
        .skills ul li:nth-child(5) { animation-delay: 0.5s; }

        .skills ul li:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .userName .name {
            font-size: 2.2rem;
            font-weight: 800;
            color: #333;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        body.dark-mode .userName .name {
            color: #e4e6eb;
        }

        .userName .map {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 0;
        }

        .userName .map i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .userName .info {
            color: var(--gray);
            font-size: 0.95rem;
        }
        body.dark-mode .userName .info {
            color: #adb5bd;
        }

        .userName p {
            color: var(--gray);
            font-weight: 500;
            font-size: 1rem;
        }
        body.dark-mode .userName p {
            color: #adb5bd;
        }

        .tabs ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            border-bottom: 2px solid var(--light-gray);
            border-radius: 8px 8px 0 0;
            overflow: hidden;
            background: var(--light-gray);
        }
        body.dark-mode .tabs ul {
            border-color: #4a4a4a;
            background: #3a3b3c;
        }

        .tabs ul li {
            flex: 1;
            padding: 16px 20px;
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
        }
        body.dark-mode .tabs ul li {
            color: #adb5bd;
        }

        .tabs ul li:hover {
            background: var(--light-gray);
            color: #333;
        }
        body.dark-mode .tabs ul li:hover {
            background: #3a3b3c;
            color: #e4e6eb;
        }

        .tabs ul li.active {
            background: var(--white);
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
            font-weight: 600;
        }
        body.dark-mode .tabs ul li.active {
            background: #242526;
            color: #63b3ed;
        }

        .tabs ul li i {
            font-size: 1.1rem;
        }

        .tab-content {
            padding-top: 24px;
            animation: fadeIn 0.4s ease-out;
        }

        .contact_Info ul {
            list-style: none;
            padding: 0;
        }

        .contact_Info li {
            margin-bottom: 16px;
            padding: 12px;
            background: var(--light-gray);
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
        }
        body.dark-mode .contact_Info li {
            background: #3a3b3c;
            border-color: #4a4a4a;
        }

        .contact_Info li:hover {
            background: var(--light-gray);
            transform: translateX(4px);
        }
        body.dark-mode .contact_Info li:hover {
            background: #3a3b3c;
        }

        .contact_Info .label {
            font-size: 1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }
        body.dark-mode .contact_Info .label {
            color: #e4e6eb;
        }

        .contact_Info .info {
            color: var(--gray);
            font-size: 0.95rem;
        }
        body.dark-mode .contact_Info .info {
            color: #adb5bd;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 8px 8px 0 0;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            border: 1px solid transparent;
        }

        .download-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .download-btn i {
            font-size: 1rem;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            .userProfile { grid-column: 1 / 2; grid-row: 1 / 2; }
            .userDetails { grid-column: 2 / 3; grid-row: 1 / 2; }
            .work_skills { grid-column: 1 / 3; grid-row: 2 / 3; }
            .timeline_about { grid-column: 1 / 3; grid-row: 3 / 4; }
        }

        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .userProfile,
            .work_skills,
            .userDetails,
            .timeline_about {
                grid-column: 1 / 2;
            }
            .userProfile .profile img {
                max-width: 150px;
            }
            .userName .name {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }
            .container {
                gap: 16px;
            }
            .card {
                padding: 20px;
            }
            .userProfile .profile img {
                max-width: 120px;
            }
            .userName .name {
                font-size: 1.6rem;
            }
            .heading {
                font-size: 1.3rem;
            }
            .tabs ul li {
                padding: 12px 16px;
                font-size: 0.9rem;
            }
            .skills ul {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .card {
                padding: 16px;
            }
            .userProfile .profile img {
                max-width: 100px;
            }
            .userName .name {
                font-size: 1.4rem;
            }
            .heading {
                font-size: 1.2rem;
            }
            .tabs ul {
                flex-direction: column;
            }
            .tabs ul li {
                padding: 12px;
            }
            .download-btn {
                width: 100%;
                justify-content: center;
                margin: 8px 0;
            }
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

    <div class="mobile-menu-overlay" id="mobileMenuOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; display: none;"></div>

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

    <div class="content">
        <nav>
            <button class="mobile-menu-btn" id="mobileMenuBtn"><i class='bx bx-menu'></i></button>
        </nav>

        <!-- Mobile Nav Links -->
        <div class="mobile-nav-links">
            <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Manage Jobs</a>
            <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
            <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
            <a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a>
            <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="admin_client_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>
            <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
            <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Applicant Profile</h1>
                    <p>View detailed applicant information and qualifications</p>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="profile-container">
                <!-- Profile Header -->
                <div class="profile-header">
                    <a href="#" onclick="window.history.back(); return false;" class="btn-back"><i class='bx bx-arrow-back'></i> Back</a>
                    <div class="profile-picture">
                        <img src="<?php echo $profile_picture; ?>" alt="Profile Picture">
                    </div>
                    <div class="profile-info">
                        <h1 class="candidate-name"><?php echo $fullname; ?></h1>
                        <p class="candidate-title"><?php echo $professional_title; ?></p>
                        <div class="contact-info">
                            <div class="contact-row">
                                <div class="info-item">
                                    <i class='bx bx-phone'></i>
                                    <span><?php echo $phone; ?></span>
                                </div>
                                <div class="info-item">
                                    <i class='bx bx-map-pin'></i>
                                    <span><?php echo $address; ?></span>
                                </div>
                            </div>
                            <div class="info-item email">
                                <i class='bx bx-envelope'></i>
                                <span><?php echo $email; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Summary -->
                <div class="profile-summary">
                    <h2>Professional Summary</h2>
                    <p><?php echo $professional_summary; ?></p>
                </div>

                <!-- Profile Tabs -->
                <div class="profile-tabs">
                    <div class="tabs">
                        <ul>
                            <li class="tab-link active" data-tab="personal">
                                <i class='bx bx-user'></i>
                                <span>Personal Info</span>
                            </li>
                            <li class="tab-link" data-tab="education">
                                <i class='bx bx-graduation'></i>
                                <span>Education</span>
                            </li>
                            <li class="tab-link" data-tab="skills">
                                <i class='bx bx-brain'></i>
                                <span>Skills</span>
                            </li>
                            <li class="tab-link" data-tab="experience">
                                <i class='bx bx-briefcase'></i>
                                <span>Experience</span>
                            </li>
                            <li class="tab-link" data-tab="languages">
                                <i class='bx bx-globe'></i>
                                <span>Languages</span>
                            </li>
                            <li class="tab-link" data-tab="computer_skills">
                                <i class='bx bx-laptop'></i>
                                <span>Computer Skills</span>
                            </li>

                        </ul>
                    </div>

                    <!-- Personal Information Tab -->
                    <div id="personal" class="tab-content active">
                        <div class="info-section">
                            <h3>Personal Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Full Name:</label>
                                    <span><?php echo $fullname; ?></span>
</xai:function_call name="edit_file">
<parameter name="path">
                                </div>
                                <div class="info-item">
                                    <label>Date of Birth:</label>
                                    <span><?php echo $dob; ?></span>
</xai:function_call name="edit_file">
<parameter name="path">
                                </div>
                                <div class="info-item">
                                    <label>Gender:</label>
                                    <span><?php echo $gender; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="info-section">
                            <h3>Contact Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Email:</label>
                                    <span><?php echo $email; ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Phone:</label>
                                    <span><?php echo $phone; ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Address:</label>
                                    <span><?php echo $address; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Education Tab -->
                    <div id="education" class="tab-content">
                        <div class="info-section">
                            <h3>Education</h3>
                            <?php if (!empty($qualifications)): ?>
                                <div class="education-list">
                                    <?php foreach ($qualifications as $qual): ?>
                                        <div class="education-item">
                                            <p>
                                                <?php
                                                $qName = htmlspecialchars((string)($qual['qualification_name'] ?? ''));
                                                $inst = htmlspecialchars((string)($qual['institution'] ?? ''));
                                                $year = htmlspecialchars((string)($qual['year_completed'] ?? ''));

                                                $parts = [];
                                                if ($qName !== '') $parts[] = $qName;
                                                if ($inst !== '') $parts[] = '(' . $inst . ( $year !== '' ? ', ' . $year : '' ) . ')';
                                                elseif ($year !== '') $parts[] = '(' . $year . ')';

                                                echo implode(' ', $parts) !== '' ? implode(' ', $parts) : htmlspecialchars((string)($qual['qualification_name'] ?? ''));
                                                ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-data">No education information available.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Skills Tab -->
                    <div id="skills" class="tab-content">
                        <div class="skills-section">
                            <div class="skill-category">
                                <h3>Technical Skills</h3>
                                <div class="skills-list">
                                    <?php if (!empty($technical_skills)): ?>
                                        <?php foreach ($technical_skills as $skill): ?>
                                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="no-data">No technical skills listed.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="skill-category">
                                <h3>Soft Skills</h3>
                                <div class="skills-list">
                                    <?php if (!empty($soft_skills)): ?>
                                        <?php foreach ($soft_skills as $skill): ?>
                                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="no-data">No soft skills listed.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Experience Tab -->
                    <div id="experience" class="tab-content">
                        <div class="info-section">
                            <h3>Work Experience</h3>
                            <?php if (!empty($work_experiences)): ?>
                                <div class="experience-list">
                                    <?php foreach ($work_experiences as $experience): ?>
                                        <div class="experience-item">
                                            <div class="experience-header">
                                                <h4><?php echo htmlspecialchars($experience['position']); ?> at <?php echo htmlspecialchars($experience['company_name']); ?></h4>
                                                <span class="duration"><?php echo htmlspecialchars($experience['duration']); ?></span>
                                            </div>
                                            <div class="experience-details">
                                                <h5>Duties & Responsibilities:</h5>
                                                <p><?php echo nl2br(htmlspecialchars($experience['duties'])); ?></p>

                                                <div style="margin-top:16px; display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
                                                    <div class="info-item" style="padding:14px; border-radius:8px; border-top:3px solid var(--primary); background: rgba(255,255,255,0.35);">
                                                        <label style="font-weight:700; color:var(--gray);">Country</label>
                                                        <span style="color:var(--dark);">
                                                            <?php echo htmlspecialchars($experience['country']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="info-item" style="padding:14px; border-radius:8px; border-top:3px solid var(--primary); background: rgba(255,255,255,0.35);">
                                                        <label style="font-weight:700; color:var(--gray);">Employment Status</label>
                                                        <span style="color:var(--dark);">
                                                            <?php echo htmlspecialchars($experience['employment_status']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="info-item" style="padding:14px; border-radius:8px; border-top:3px solid var(--primary); background: rgba(255,255,255,0.35);">
                                                        <label style="font-weight:700; color:var(--gray);">Work Type</label>
                                                        <span style="color:var(--dark);">
                                                            <?php echo htmlspecialchars($experience['work_type']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="info-item" style="padding:14px; border-radius:8px; border-top:3px solid var(--primary); background: rgba(255,255,255,0.35);">
                                                        <label style="font-weight:700; color:var(--gray);">Start Date</label>
                                                        <span style="color:var(--dark);">
                                                            <?php echo htmlspecialchars($experience['start_date']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="info-item" style="padding:14px; border-radius:8px; border-top:3px solid var(--primary); background: rgba(255,255,255,0.35);">
                                                        <label style="font-weight:700; color:var(--gray);">End Date</label>
                                                        <span style="color:var(--dark);">
                                                            <?php echo htmlspecialchars($experience['end_date']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="info-item" style="padding:14px; border-radius:8px; border-top:3px solid var(--primary); background: rgba(255,255,255,0.35);">
                                                        <label style="font-weight:700; color:var(--gray);">Reason for Leaving</label>
                                                        <span style="color:var(--dark);">
                                                            <?php echo nl2br(htmlspecialchars($experience['reason_for_leaving'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-data">No work experience information available.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Languages Tab -->
                    <div id="languages" class="tab-content">
                        <div class="info-section">
                            <h3>Language Proficiency</h3>

                            <?php
                            // Snapshot-first languages parsing
                            $languages_source = 'Snapshot';
                            $languages = [];

                            // Try common snapshot keys
                            $candidate_language_keys = ['languages', 'language_proficiency', 'languageSkills', 'language_skills'];
                            $languages_blob = null;
                            foreach ($candidate_language_keys as $k) {
                                if (!empty($snapshot_profile[$k])) {
                                    $languages_blob = $snapshot_profile[$k];
                                    break;
                                }
                            }

                            if (is_string($languages_blob) && trim($languages_blob) !== '') {
                                // Support formats like: "English (Advanced) | French (Intermediate)"
                                $parts = explode('|', $languages_blob);
                                foreach ($parts as $p) {
                                    $p = trim($p);
                                    if ($p === '') continue;

                                    // Try "Name (Level)" then fallback to raw
                                    if (preg_match('/^(.+?)\s*\((.+?)\)\s*$/', $p, $m)) {
                                        $languages[] = ['name' => trim($m[1]), 'level' => trim($m[2])];
                                    } else {
                                        $languages[] = ['name' => $p, 'level' => ''];
                                    }
                                }
                            } elseif (is_array($languages_blob)) {
                                foreach ($languages_blob as $item) {
                                    if (!is_array($item)) continue;

                                    $name = $item['name'] ?? $item['language'] ?? $item['language_name'] ?? '';
                                    $level = $item['level'] ?? $item['proficiency'] ?? $item['proficiency_level'] ?? $item['rating'] ?? '';
                                    if (trim((string)$name) !== '') {
                                        $languages[] = ['name' => trim((string)$name), 'level' => trim((string)$level)];
                                    }
                                }
                            }

                            // Fallback to DB only if snapshot yielded nothing
                            if (empty($languages)) {
                                $languages_source = 'Current';
                                $languages = [];

                                // If your DB table differs, adjust this query.
                                // We try a safe, best-effort approach.
                                try {
                                    // Common possible table names/columns
                                    $stmt = $conn->prepare("SELECT language_name, speaking_level, reading_level, writing_level FROM language_proficiency WHERE user_id = ?");
                                    if ($stmt) {
                                        $stmt->bind_param("i", $target_user_id);
                                        $stmt->execute();
                                        $res = $stmt->get_result();
                                        if ($res) {
                                            while ($r = $res->fetch_assoc()) {
                                                $languages[] = [
                                                    'name' => $r['language_name'] ?? '',
                                                    'speaking' => $r['speaking_level'] ?? '',
                                                    'reading' => $r['reading_level'] ?? '',
                                                    'writing' => $r['writing_level'] ?? ''
                                                ];
                                            }
                                        }
                                    }
                                } catch (Throwable $e) {
                                    // ignore if table doesn't exist
                                }

                                // Another possible schema
                                if (empty($languages)) {
                                    try {
                                        $stmt2 = $conn->prepare("SELECT language, level FROM languages WHERE user_id = ?");
                                        if ($stmt2) {
                                            $stmt2->bind_param("i", $target_user_id);
                                            $stmt2->execute();
                                            $res2 = $stmt2->get_result();
                                            if ($res2) {
                                                while ($r = $res2->fetch_assoc()) {
                                                    $languages[] = [
                                                        'name' => $r['language'] ?? '',
                                                        'level' => $r['level'] ?? ''
                                                    ];
                                                }
                                            }
                                        }
                                    } catch (Throwable $e) {
                                        // ignore
                                    }
                                }
                            }
                            ?>

                            <?php if (!empty($languages)): ?>
                                <div class="experience-list" style="gap: 12px;">
                                    <?php foreach ($languages as $lang): ?>
                                        <div class="experience-item" style="padding: 16px;">
                                            <div class="experience-header" style="margin-bottom: 6px;">
                                                <h4 style="font-size: 1.05rem; margin: 0;"><?php echo htmlspecialchars($lang['name']); ?></h4>
                                                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:6px;">
                                                    <?php if (!empty($lang['speaking'])): ?>
                                                        <span class="duration" style="background: rgba(102,126,234,0.15); padding:4px 8px; border-radius:6px;">Speaking: <?php echo htmlspecialchars($lang['speaking']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($lang['reading'])): ?>
                                                        <span class="duration" style="background: rgba(102,126,234,0.15); padding:4px 8px; border-radius:6px;">Reading: <?php echo htmlspecialchars($lang['reading']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($lang['writing'])): ?>
                                                        <span class="duration" style="background: rgba(102,126,234,0.15); padding:4px 8px; border-radius:6px;">Writing: <?php echo htmlspecialchars($lang['writing']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-data">No language proficiency information available.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Computer Skills Tab -->
                    <div id="computer_skills" class="tab-content">
                        <div class="info-section">
                            <h3>Computer Skills</h3>

                            <div class="skills-section">
                                <div class="skill-category">
                                    <h3>Skills</h3>
                                    <div class="skills-list">
                                        <?php if (!empty($computer_skills)): ?>
                                            <?php foreach ($computer_skills as $skill): ?>
                                                <span class="skill-tag">
                                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                                    <?php if (isset($skill['proficiency']) && $skill['proficiency'] !== null): ?>
                                                        (<?php echo (int)$skill['proficiency']; ?>)
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="no-data">No computer skills listed.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="skill-category">
                                    <h3>Other Skills</h3>
                                    <div class="skills-list">
                                        <?php if (!empty($computer_other_skills)): ?>
                                            <?php
                                            $other = trim((string)$computer_other_skills);
                                            // If comma-separated, show tags; otherwise show as text.
                                            $parts = array_filter(array_map('trim', preg_split('/\s*,\s*/', $other)));
                                            if (count($parts) > 1) {
                                                foreach ($parts as $p) {
                                            ?>
                                                    <span class="skill-tag"><?php echo htmlspecialchars($p); ?></span>
                                            <?php 
                                                }
                                            } else {
                                            ?>
                                                <p class="no-data" style="padding: 0; text-align: left;"><?php echo nl2br(htmlspecialchars($other)); ?></p>
                                            <?php } ?>
                                        <?php else: ?>
                                            <p class="no-data">No other computer skills provided.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
                document.getElementById('mobileMenuOverlay').style.display =
                    document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none';
            });
        }

        document.getElementById('mobileMenuOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            this.style.display = 'none';
        });

        // Close sidebar when clicking menu items on mobile
        document.querySelectorAll('.side-menu a').forEach(link => {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                    document.getElementById('mobileMenuOverlay').style.display = 'none';
                    if (this.href !== window.location.href && !this.onclick) {
                        e.preventDefault();
                        setTimeout(() => {
                            window.location.href = this.href;
                        }, 300);
                    }
                }
            });
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

        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');

            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabLinks.forEach(l => l.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });

        // Theme toggle
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');

            if (themeToggle) {
                const currentTheme = localStorage.getItem('theme');
                if (currentTheme) {
                    themeToggle.checked = (currentTheme === 'dark');
                    if (currentTheme === 'dark') document.body.classList.add('dark-mode');
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
    </script>
</body>

</html>