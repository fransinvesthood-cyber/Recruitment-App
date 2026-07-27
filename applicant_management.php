<?php
include('config.php');
session_start();

// ============================================================
// RBAC: Only Admin can access this page
// ============================================================
if (!isset($_SESSION['user_id'])) {
    die("Access denied: You must be logged in.");
}

$user_id = $_SESSION['user_id'];
$role_check = $conn->query("SELECT role FROM users WHERE user_id = $user_id");
$role_row = $role_check->fetch_assoc();
if (!$role_row || $role_row['role'] !== 'Admin') {
    die("Access denied: Admin privileges required.");
}

// ============================================================
// Helper: Check if a column exists (for CV / profile_picture)
// ============================================================
function columnExists($conn, $table, $column) {
    $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $r && $r->num_rows > 0;
}

$hasProfilePicCol = columnExists($conn, 'applicant_profile', 'profile_picture');
$hasCVCol = columnExists($conn, 'applicant_profile', 'cv');
$hasUserProfilePicCol = columnExists($conn, 'users', 'profile_picture');

// ============================================================
// DASHBOARD ANALYTICS QUERIES
// ============================================================

// 1. Total Registered Applicants
$total_applicants_q = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='Applicant'");
$total_applicants = $total_applicants_q->fetch_assoc()['cnt'] ?? 0;

// 2. Applicants with Completed Profiles (has applicant_profile with professional_title)
$completed_profiles_q = $conn->query("
    SELECT COUNT(DISTINCT u.user_id) AS cnt
    FROM users u
    INNER JOIN applicant_profile ap ON u.user_id = ap.user_id
    WHERE u.role='Applicant' AND ap.professional_title IS NOT NULL AND ap.professional_title != ''
");
$completed_profiles = $completed_profiles_q->fetch_assoc()['cnt'] ?? 0;

// 3. Applicants with Incomplete Profiles
$incomplete_profiles = $total_applicants - $completed_profiles;

// 4. CV Uploaded
$cv_uploaded = 0;
$cv_not_uploaded = 0;
if ($hasCVCol) {
    $cv_q = $conn->query("
        SELECT COUNT(DISTINCT u.user_id) AS cnt
        FROM users u
        INNER JOIN applicant_profile ap ON u.user_id = ap.user_id
        WHERE u.role='Applicant' AND ap.cv IS NOT NULL AND ap.cv != ''
    ");
    $cv_uploaded = $cv_q->fetch_assoc()['cnt'] ?? 0;
    $cv_not_uploaded = $total_applicants - $cv_uploaded;
}

// 5. Work Experience
$with_work_exp_q = $conn->query("
    SELECT COUNT(DISTINCT u.user_id) AS cnt
    FROM users u
    INNER JOIN work_experience we ON u.user_id = we.user_id
    WHERE u.role='Applicant'
");
$with_work_exp = $with_work_exp_q->fetch_assoc()['cnt'] ?? 0;
$without_work_exp = $total_applicants - $with_work_exp;

// 6. Qualifications
$with_qual_q = $conn->query("
    SELECT COUNT(DISTINCT u.user_id) AS cnt
    FROM users u
    INNER JOIN qualifications q ON u.user_id = q.user_id
    WHERE u.role='Applicant'
");
$with_qual = $with_qual_q->fetch_assoc()['cnt'] ?? 0;
$without_qual = $total_applicants - $with_qual;

// 7. Profile Pictures
$with_pic = 0;
$without_pic = 0;
if ($hasProfilePicCol) {
    $pic_q = $conn->query("
        SELECT COUNT(DISTINCT u.user_id) AS cnt
        FROM users u
        INNER JOIN applicant_profile ap ON u.user_id = ap.user_id
        WHERE u.role='Applicant' AND ap.profile_picture IS NOT NULL AND ap.profile_picture != ''
    ");
    $with_pic = $pic_q->fetch_assoc()['cnt'] ?? 0;
} elseif ($hasUserProfilePicCol) {
    $pic_q = $conn->query("
        SELECT COUNT(*) AS cnt FROM users WHERE role='Applicant' AND profile_picture IS NOT NULL AND profile_picture != ''
    ");
    $with_pic = $pic_q->fetch_assoc()['cnt'] ?? 0;
}
$without_pic = $total_applicants - $with_pic;

// ============================================================
// PROFESSIONAL TITLE ANALYTICS
// ============================================================
$prof_titles_q = $conn->query("
    SELECT ap.professional_title, COUNT(DISTINCT u.user_id) AS cnt
    FROM users u
    INNER JOIN applicant_profile ap ON u.user_id = ap.user_id
    WHERE u.role='Applicant' AND ap.professional_title IS NOT NULL AND ap.professional_title != ''
    GROUP BY ap.professional_title
    ORDER BY cnt DESC
    LIMIT 20
");
$prof_titles = [];
while ($row = $prof_titles_q->fetch_assoc()) {
    $prof_titles[] = $row;
}

// ============================================================
// QUALIFICATION ANALYTICS
// ============================================================
$qual_levels = ['Higher Certificate', 'Diploma', "Bachelor's Degree", "Honours Degree", "Master's Degree", 'Doctorate', 'Certificate', 'High School', 'Other'];
$qual_analytics = [];
foreach ($qual_levels as $ql) {
    $r = $conn->query("
        SELECT COUNT(DISTINCT u.user_id) AS cnt
        FROM users u
        INNER JOIN qualifications q ON u.user_id = q.user_id
        WHERE u.role='Applicant' AND q.qualification_level = '" . $conn->real_escape_string($ql) . "'
    ");
    $qual_analytics[$ql] = $r->fetch_assoc()['cnt'] ?? 0;
}
$qual_without = $without_qual;

// ============================================================
// SKILLS ANALYTICS - Parse technical_skills
// ============================================================
$skill_examples = ['PHP', 'JavaScript', 'SQL', 'MySQL', 'Python', 'Java', 'C++', 'HTML/CSS', 'Git', 'Linux', 'Networking', 'Microsoft Office'];
$skill_counts = [];
$skill_all_applicants = [];

// Get all applicants' technical skills
$skills_q = $conn->query("
    SELECT u.user_id, s.technical_skills
    FROM users u
    INNER JOIN skills s ON u.user_id = s.user_id
    WHERE u.role='Applicant' AND s.technical_skills IS NOT NULL AND s.technical_skills != ''
");
while ($row = $skills_q->fetch_assoc()) {
    $uid = $row['user_id'];
    $skills = preg_split('/[,;\/\n\r]+/', $row['technical_skills']);
    foreach ($skills as $sk) {
        $sk = trim($sk);
        if ($sk === '') continue;
        if (!isset($skill_all_applicants[$sk])) $skill_all_applicants[$sk] = [];
        if (!in_array($uid, $skill_all_applicants[$sk])) $skill_all_applicants[$sk][] = $uid;
    }
}

// Count for example skills
foreach ($skill_examples as $ex) {
    $found = false;
    foreach ($skill_all_applicants as $sk => $uids) {
        if (stripos($sk, $ex) !== false || stripos($ex, $sk) !== false) {
            $skill_counts[$ex] = count($uids);
            $found = true;
            break;
        }
    }
    if (!$found) $skill_counts[$ex] = 0;
}

// "Other" skills count
$other_count = 0;
foreach ($skill_all_applicants as $sk => $uids) {
    $is_example = false;
    foreach ($skill_examples as $ex) {
        if (stripos($sk, $ex) !== false || stripos($ex, $sk) !== false) { $is_example = true; break; }
    }
    if (!$is_example) $other_count += count($uids);
}

// ============================================================
// BUILD FILTERING QUERY
// ============================================================
$where_conditions = ["u.role = 'Applicant'"];
$params = [];
$param_types = '';

function addCond(&$where, $cond) {
    $where[] = $cond;
}

// Name
if (!empty($_GET['filter_name'])) {
    $name = $conn->real_escape_string($_GET['filter_name']);
    addCond($where_conditions, "(u.fullname LIKE '%$name%' OR u.username LIKE '%$name%')");
}

// Email
if (!empty($_GET['filter_email'])) {
    $email = $conn->real_escape_string($_GET['filter_email']);
    addCond($where_conditions, "u.email LIKE '%$email%'");
}

// Username
if (!empty($_GET['filter_username'])) {
    $uname = $conn->real_escape_string($_GET['filter_username']);
    addCond($where_conditions, "u.username LIKE '%$uname%'");
}

// Professional Title
if (!empty($_GET['filter_title'])) {
    $title = $conn->real_escape_string($_GET['filter_title']);
    addCond($where_conditions, "ap.professional_title LIKE '%$title%'");
}

// Gender
if (!empty($_GET['filter_gender'])) {
    $gender = $conn->real_escape_string($_GET['filter_gender']);
    addCond($where_conditions, "u.gender = '$gender'");
}

// Qualification
if (!empty($_GET['filter_qualification'])) {
    $qual = $conn->real_escape_string($_GET['filter_qualification']);
    addCond($where_conditions, "q.qualification_name LIKE '%$qual%'");
}

// Qualification Type
if (!empty($_GET['filter_qual_level'])) {
    $qlf = $conn->real_escape_string($_GET['filter_qual_level']);
    addCond($where_conditions, "q.qualification_level = '$qlf'");
}

// Graduation Year
if (!empty($_GET['filter_grad_year'])) {
    $gy = intval($_GET['filter_grad_year']);
    addCond($where_conditions, "q.year_completed = $gy");
}

// Technical Skills
if (!empty($_GET['filter_tech_skills'])) {
    $ts = $conn->real_escape_string($_GET['filter_tech_skills']);
    addCond($where_conditions, "s.technical_skills LIKE '%$ts%'");
}

// Soft Skills
if (!empty($_GET['filter_soft_skills'])) {
    $ss = $conn->real_escape_string($_GET['filter_soft_skills']);
    addCond($where_conditions, "s.soft_skills LIKE '%$ss%'");
}

// Province / Address
if (!empty($_GET['filter_address'])) {
    $addr = $conn->real_escape_string($_GET['filter_address']);
    addCond($where_conditions, "u.address LIKE '%$addr%'");
}

// Years of Work Experience (minimum)
if (!empty($_GET['filter_exp_years'])) {
    $expy = intval($_GET['filter_exp_years']);
    // We check by counting years in start_date/end_date or duration field. For simplicity, use duration.
    addCond($where_conditions, "(SELECT COALESCE(SUM(
        CASE 
            WHEN we.duration REGEXP '^[0-9]+$' THEN CAST(we.duration AS UNSIGNED)
            ELSE 0
        END
    ), 0) FROM work_experience we WHERE we.user_id = u.user_id) >= $expy");
}

// Registration Date
if (!empty($_GET['filter_reg_date'])) {
    $rd = $conn->real_escape_string($_GET['filter_reg_date']);
    addCond($where_conditions, "DATE(u.created_at) = '$rd'");
}

// CV Uploaded
if (isset($_GET['filter_cv']) && $_GET['filter_cv'] !== '') {
    $cvv = intval($_GET['filter_cv']);
    if ($hasCVCol) {
        if ($cvv) addCond($where_conditions, "ap.cv IS NOT NULL AND ap.cv != ''");
        else addCond($where_conditions, "(ap.cv IS NULL OR ap.cv = '')");
    }
}

// Profile Picture Uploaded
if (isset($_GET['filter_pic']) && $_GET['filter_pic'] !== '') {
    $picv = intval($_GET['filter_pic']);
    if ($hasProfilePicCol) {
        if ($picv) addCond($where_conditions, "ap.profile_picture IS NOT NULL AND ap.profile_picture != ''");
        else addCond($where_conditions, "(ap.profile_picture IS NULL OR ap.profile_picture = '')");
    } elseif ($hasUserProfilePicCol) {
        if ($picv) addCond($where_conditions, "u.profile_picture IS NOT NULL AND u.profile_picture != ''");
        else addCond($where_conditions, "(u.profile_picture IS NULL OR u.profile_picture = '')");
    }
}

// Work Experience Added
if (isset($_GET['filter_work_exp']) && $_GET['filter_work_exp'] !== '') {
    $wev = intval($_GET['filter_work_exp']);
    if ($wev) addCond($where_conditions, "EXISTS (SELECT 1 FROM work_experience we2 WHERE we2.user_id = u.user_id)");
    else addCond($where_conditions, "NOT EXISTS (SELECT 1 FROM work_experience we2 WHERE we2.user_id = u.user_id)");
}

// Profile Completion
if (isset($_GET['filter_completion']) && $_GET['filter_completion'] !== '') {
    $comp = intval($_GET['filter_completion']);
    if ($comp) {
        // "Complete" = has professional_title + qualification + skills + work_exp
        addCond($where_conditions, "ap.professional_title IS NOT NULL AND ap.professional_title != ''");
        addCond($where_conditions, "EXISTS (SELECT 1 FROM qualifications q2 WHERE q2.user_id = u.user_id)");
        addCond($where_conditions, "EXISTS (SELECT 1 FROM skills s2 WHERE s2.user_id = u.user_id)");
        addCond($where_conditions, "EXISTS (SELECT 1 FROM work_experience we2 WHERE we2.user_id = u.user_id)");
    } else {
        addCond($where_conditions, "(ap.professional_title IS NULL OR ap.professional_title = '' OR NOT EXISTS (SELECT 1 FROM qualifications q2 WHERE q2.user_id = u.user_id) OR NOT EXISTS (SELECT 1 FROM skills s2 WHERE s2.user_id = u.user_id) OR NOT EXISTS (SELECT 1 FROM work_experience we2 WHERE we2.user_id = u.user_id))");
    }
}

// Search query
$search_query = '';
if (!empty($_GET['search'])) {
    $sq = $conn->real_escape_string($_GET['search']);
    $search_query = $sq;
    addCond($where_conditions, "(u.fullname LIKE '%$sq%' OR u.email LIKE '%$sq%' OR ap.professional_title LIKE '%$sq%' OR q.qualification_name LIKE '%$sq%' OR s.technical_skills LIKE '%$sq%' OR s.soft_skills LIKE '%$sq%')");
}

$where_clause = implode(' AND ', $where_conditions);

// ============================================================
// PAGINATION
// ============================================================
$limit = 10;
$page = isset($_GET['p']) && intval($_GET['p']) > 0 ? intval($_GET['p']) : 1;
$offset = ($page - 1) * $limit;

// Count total for pagination
$count_sql = "SELECT COUNT(DISTINCT u.user_id) AS total
    FROM users u
    LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
    LEFT JOIN qualifications q ON u.user_id = q.user_id
    LEFT JOIN skills s ON u.user_id = s.user_id
    LEFT JOIN work_experience we ON u.user_id = we.user_id
    WHERE $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_records / $limit);

// Main query with pagination - dynamically build SELECT based on existing columns
$select_cols = "u.user_id, u.fullname, u.username, u.email, u.gender, u.dob, u.phone, u.address, u.created_at,
    ap.professional_title, ap.professional_summary";
if ($hasProfilePicCol) {
    $select_cols .= ", ap.profile_picture";
} elseif ($hasUserProfilePicCol) {
    $select_cols .= ", u.profile_picture";
}
if ($hasCVCol) {
    $select_cols .= ", ap.cv";
}
$main_sql = "SELECT DISTINCT $select_cols
    FROM users u
    LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
    LEFT JOIN qualifications q ON u.user_id = q.user_id
    LEFT JOIN skills s ON u.user_id = s.user_id
    LEFT JOIN work_experience we ON u.user_id = we.user_id
    WHERE $where_clause
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset";
$main_result = $conn->query($main_sql);
$applicants = [];
while ($row = $main_result->fetch_assoc()) {
    $applicants[] = $row;
}

// ============================================================
// FETCH QUALIFICATIONS, SKILLS, WORK EXP FOR DISPLAYED APPLICANTS
// ============================================================
$app_ids = array_map(function($a) { return $a['user_id']; }, $applicants);
$app_ids_str = implode(',', $app_ids);

$qual_map = [];
$skill_map = [];
$work_map = [];

if (!empty($app_ids_str)) {
    $q_q = $conn->query("SELECT * FROM qualifications WHERE user_id IN ($app_ids_str) ORDER BY user_id");
    while ($row = $q_q->fetch_assoc()) {
        $uid = $row['user_id'];
        if (!isset($qual_map[$uid])) $qual_map[$uid] = [];
        $qual_map[$uid][] = $row;
    }

    $s_q = $conn->query("SELECT * FROM skills WHERE user_id IN ($app_ids_str)");
    while ($row = $s_q->fetch_assoc()) {
        $skill_map[$row['user_id']] = $row;
    }

    $w_q = $conn->query("SELECT * FROM work_experience WHERE user_id IN ($app_ids_str) ORDER BY user_id, start_date DESC");
    while ($row = $w_q->fetch_assoc()) {
        $uid = $row['user_id'];
        if (!isset($work_map[$uid])) $work_map[$uid] = [];
        $work_map[$uid][] = $row;
    }
}

// ============================================================
// PROFILE COMPLETION CALCULATION
// ============================================================
function calcProfileCompletion($user_id, $has_profile, $has_title, $has_quals, $has_skills, $has_work, $has_pic, $has_cv) {
    $sections = 0;
    $completed = 0;

    // 1. Personal Information (fullname, email, gender, etc - always true if user exists)
    $sections++;
    $completed++;

    // 2. Professional Summary
    $sections++;
    if ($has_profile) $completed++;

    // 3. Professional Title
    $sections++;
    if ($has_title) $completed++;

    // 4. Qualifications
    $sections++;
    if ($has_quals) $completed++;

    // 5. Technical Skills
    $sections++;
    if ($has_skills) $completed++;

    // 6. Work Experience
    $sections++;
    if ($has_work) $completed++;

    // 7. Profile Picture
    $sections++;
    if ($has_pic) $completed++;

    // 8. CV Upload
    $sections++;
    if ($has_cv) $completed++;

    return $sections > 0 ? round(($completed / $sections) * 100) : 0;
}

// Pre-calculate completion for displayed applicants
$completion_map = [];
foreach ($applicants as $app) {
    $uid = $app['user_id'];
    $has_profile = !empty($app['professional_summary']);
    $has_title = !empty($app['professional_title']);
    $has_quals = isset($qual_map[$uid]) && count($qual_map[$uid]) > 0;
    $has_skills = isset($skill_map[$uid]) && (!empty($skill_map[$uid]['technical_skills']) || !empty($skill_map[$uid]['soft_skills']));
    $has_work = isset($work_map[$uid]) && count($work_map[$uid]) > 0;
    $has_pic = !empty($app['profile_picture']);
    $has_cv = !empty($app['cv']);

    $completion_map[$uid] = calcProfileCompletion($uid, $has_profile, $has_title, $has_quals, $has_skills, $has_work, $has_pic, $has_cv);
}

// Calculate work experience years
$exp_years_map = [];
foreach ($work_map as $uid => $works) {
    $total_years = 0;
    foreach ($works as $w) {
        if (!empty($w['duration'])) {
            $d = trim($w['duration']);
            if (is_numeric($d)) $total_years += intval($d);
            elseif (preg_match('/(\d+)/', $d, $m)) $total_years += intval($m[1]);
        } elseif (!empty($w['start_date']) && !empty($w['end_date'])) {
            $s = new DateTime($w['start_date']);
            $e = new DateTime($w['end_date']);
            $diff = $s->diff($e);
            $total_years += $diff->y + ($diff->m / 12);
        }
    }
    $exp_years_map[$uid] = round($total_years, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Pool - Talent Management</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        :root {
            --primary:#667eea; --primary-dark:#5a67d8; --secondary:#c9a9eaff;
            --dark:#18191a; --darker:#121314; --light:#f8f9fa;
            --gray:#6c757d; --light-gray:#e9ecef; --success:#28a745;
            --danger:#dc3545; --warning:#ffc107; --info:#17a2b8;
            --white:#ffffff; --black:#000000;
            --border-radius:12px; --box-shadow:0 4px 12px rgba(0,0,0,0.1);
            --transition:all 0.3s ease;
        }
        body { background:#f5f7fb; color:#333; display:flex; min-height:100vh; overflow-x:hidden; }
        body.dark-mode { background:var(--dark); color:#e4e6eb; }

        /* SIDEBAR */
        .sidebar {
            position:fixed; top:0; left:0; width:280px; height:100vh;
            display:flex; flex-direction:column;
            background:linear-gradient(180deg,var(--primary),var(--secondary));
            overflow:hidden; z-index:100; transition:.3s;
        }
        body.dark-mode .sidebar { background:var(--dark) !important; background-image:none !important; }
        .sidebar.collapsed { width:80px; }
        .logo { display:flex; align-items:center; gap:12px; padding:24px 20px; text-decoration:none; color:var(--white); font-size:22px; font-weight:700; }
        .logo i { font-size:32px; }
        .logo-name span { white-space:nowrap; transition:var(--transition); }
        .sidebar.collapsed .logo-name span { display:none; }
        .side-menu { list-style:none; margin:0; padding:0 15px; }
        .main-menu { flex:1; overflow-y:auto; overflow-x:hidden; min-height:0; }
        .bottom-menu { margin-top:auto; flex:0; padding:15px; border-top:1px solid rgba(255,255,255,.15); }
        .side-menu li { margin:8px 0; }
        .side-menu li a { display:flex; align-items:center; gap:14px; padding:14px 16px; color:var(--white); text-decoration:none; border-radius:8px; transition:var(--transition); font-size:16px; }
        .side-menu li a:hover, .side-menu li.active a { background:rgba(255,255,255,0.15); }
        .side-menu li a i { font-size:22px; min-width:24px; text-align:center; }
        .side-menu li.section-header { padding:8px 16px; font-weight:600; color:var(--white); font-size:14px; text-transform:uppercase; letter-spacing:.5px; opacity:.8; margin-top:12px; }
        .sidebar.collapsed .side-menu li.section-header span { display:none; }
        .logout { display:flex; align-items:center; gap:14px; padding:16px !important; background:rgba(0,0,0,.18); border-radius:10px; transition:.3s; }
        .logout:hover { background:#d32f2f !important; color:#fff; }
        .main-menu::-webkit-scrollbar { width:7px; }
        .main-menu::-webkit-scrollbar-thumb { background:rgba(255,255,255,.35); border-radius:20px; }
        .main-menu::-webkit-scrollbar-thumb:hover { background:rgba(255,255,255,.55); }
        .main-menu::-webkit-scrollbar-track { background:transparent; }

        .content { flex:1; margin-left:280px; transition:var(--transition); }
        .sidebar.collapsed ~ .content { margin-left:80px; }

        nav { display:flex; justify-content:space-between; align-items:center; padding:20px 30px; background:rgba(255,255,255,0.95); backdrop-filter:blur(20px); box-shadow:0 8px 32px rgba(0,0,0,0.1); position:sticky; top:0; z-index:99; }
        body.dark-mode nav { background:#242526; box-shadow:0 2px 10px rgba(0,0,0,0.3); }
        nav .bx-menu { font-size:28px; cursor:pointer; color:var(--gray); transition:var(--transition); }
        nav .bx-menu:hover { color:var(--primary); transform:scale(1.1); }

        main { padding:24px; }

        .welcome-section { background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%); color:var(--white); padding:25px; border-radius:var(--border-radius); margin-bottom:24px; box-shadow:var(--box-shadow); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; }
        body.dark-mode .welcome-section { background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%); border:1px solid rgba(102,126,234,0.3); }
        .welcome-content { flex:1; min-width:250px; }
        .welcome-content h1 { font-size:28px; margin-bottom:8px; }
        .welcome-content p { opacity:.9; font-size:18px; }

        .card { background:var(--white); padding:20px; border-radius:var(--border-radius); box-shadow:var(--box-shadow); }
        body.dark-mode .card { background:#242526; }

        /* KPI Grid */
        .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:12px; margin-bottom:24px; }
        .kpi-card { background:var(--white); border-radius:var(--border-radius); padding:18px; box-shadow:var(--box-shadow); border-left:4px solid var(--primary); transition:var(--transition); }
        body.dark-mode .kpi-card { background:#242526; }
        .kpi-card:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.15); }
        .kpi-card .kpi-label { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--gray); margin-bottom:6px; display:flex; align-items:center; gap:6px; }
        .kpi-card .kpi-value { font-size:28px; font-weight:900; }
        .kpi-card.kpi-green { border-left-color:var(--success); }
        .kpi-card.kpi-green .kpi-value { color:var(--success); }
        .kpi-card.kpi-red { border-left-color:var(--danger); }
        .kpi-card.kpi-red .kpi-value { color:var(--danger); }
        .kpi-card.kpi-purple { border-left-color:var(--secondary); }
        .kpi-card.kpi-purple .kpi-value { color:#9b59b6; }
        .kpi-card.kpi-blue { border-left-color:var(--primary); }
        .kpi-card.kpi-blue .kpi-value { color:var(--primary); }
        .kpi-card.kpi-orange { border-left-color:var(--warning); }
        .kpi-card.kpi-orange .kpi-value { color:#e67e22; }

        /* Sections grid */
        .analytics-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
        @media (max-width:992px) { .analytics-grid { grid-template-columns:1fr; } }
        .analytics-card { background:var(--white); border-radius:var(--border-radius); box-shadow:var(--box-shadow); padding:20px; }
        body.dark-mode .analytics-card { background:#242526; }
        .analytics-card h3 { font-size:16px; font-weight:800; margin-bottom:14px; display:flex; align-items:center; gap:8px; color:var(--primary); text-transform:uppercase; letter-spacing:.5px; }

        /* Title/Qual/Skill chips */
        .chip-grid { display:flex; flex-wrap:wrap; gap:8px; }
        .chip { display:inline-flex; align-items:center; gap:6px; padding:10px 16px; background:rgba(102,126,234,0.08); border:1px solid rgba(102,126,234,0.2); border-radius:10px; cursor:pointer; font-weight:600; font-size:13px; color:var(--gray); transition:var(--transition); }
        .chip:hover { background:rgba(102,126,234,0.15); transform:translateY(-2px); box-shadow:0 4px 12px rgba(102,126,234,0.15); }
        .chip .chip-count { display:inline-flex; align-items:center; justify-content:center; background:var(--primary); color:#fff; border-radius:50%; min-width:24px; height:24px; font-size:11px; font-weight:800; }
        body.dark-mode .chip { background:rgba(102,126,234,0.12); border-color:rgba(102,126,234,0.3); color:#e4e6eb; }
        .chip.active { background:var(--primary); color:#fff; border-color:var(--primary); }
        .chip.active .chip-count { background:#fff; color:var(--primary); }

        .empty-chip { color:var(--gray); font-size:13px; font-weight:600; padding:20px 0; }

        /* Filter Section */
        .filter-section { background:var(--white); border-radius:var(--border-radius); box-shadow:var(--box-shadow); padding:20px; margin-bottom:20px; }
        body.dark-mode .filter-section { background:#242526; }
        .filter-section .filter-title { font-size:14px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--primary); margin-bottom:14px; display:flex; align-items:center; gap:8px; cursor:pointer; }
        .filter-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:12px; }
        .filter-grid .form-control, .filter-grid .form-select { border-radius:8px; border:1px solid var(--light-gray); padding:10px 14px; font-size:14px; font-weight:600; }
        body.dark-mode .filter-grid .form-control, body.dark-mode .filter-grid .form-select { background:#2f3133; border-color:#3f4244; color:#e4e6eb; }
        .filter-actions { display:flex; gap:10px; margin-top:14px; flex-wrap:wrap; }
        .btn { padding:10px 18px; border:none; border-radius:10px; font-weight:700; cursor:pointer; transition:var(--transition); display:inline-flex; align-items:center; gap:8px; font-size:14px; }
        .btn-primary { background:var(--primary); color:#fff; }
        .btn-primary:hover { background:var(--primary-dark); transform:translateY(-2px); box-shadow:0 4px 12px rgba(102,126,234,0.3); }
        .btn-danger { background:var(--danger); color:#fff; }
        .btn-danger:hover { background:#c82333; transform:translateY(-2px); }
        .btn-outline { background:transparent; border:1px solid var(--light-gray); color:var(--gray); }
        .btn-outline:hover { border-color:var(--primary); color:var(--primary); }
        .btn-sm { padding:6px 12px; font-size:12px; }

        /* Table */
        .table-container { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:14px 16px; text-align:left; border-bottom:1px solid var(--light-gray); vertical-align:middle; }
        body.dark-mode th, body.dark-mode td { border-color:#3a3b3c; }
        th { background:#f8f9fa; font-weight:700; color:var(--primary); font-size:12px; text-transform:uppercase; letter-spacing:.5px; }
        body.dark-mode th { background:#2d2e2f; color:#a7b7ff; }
        tr:hover td { background:rgba(102,126,234,0.04); }
        .app-pic { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--light-gray); }
        body.dark-mode .app-pic { border-color:#3a3b3c; }

        .completion-bar { display:flex; align-items:center; gap:8px; }
        .completion-bar .bar { flex:1; height:6px; background:var(--light-gray); border-radius:99px; overflow:hidden; }
        .completion-bar .bar-fill { height:100%; border-radius:99px; transition:width .5s ease; }
        .completion-bar .bar-fill.high { background:var(--success); }
        .completion-bar .bar-fill.medium { background:var(--warning); }
        .completion-bar .bar-fill.low { background:var(--danger); }
        .completion-bar .pct { font-size:12px; font-weight:800; min-width:35px; }

        /* Skill tags in table */
        .skill-tag { display:inline-block; padding:2px 8px; background:rgba(102,126,234,0.1); color:var(--primary); border-radius:6px; font-size:11px; font-weight:700; margin:2px; }

        .actions-dropdown { position:relative; display:inline-block; }
        .actions-dropdown .dropdown-menu { display:none; position:absolute; right:0; top:100%; min-width:200px; background:var(--white); border-radius:10px; box-shadow:0 8px 25px rgba(0,0,0,0.15); z-index:50; padding:8px 0; }
        body.dark-mode .actions-dropdown .dropdown-menu { background:#242526; border:1px solid #3a3b3c; }
        .actions-dropdown .dropdown-menu a { display:flex; align-items:center; gap:10px; padding:10px 16px; text-decoration:none; color:var(--gray); font-size:13px; font-weight:600; transition:var(--transition); }
        .actions-dropdown .dropdown-menu a:hover { background:rgba(102,126,234,0.08); color:var(--primary); }
.actions-dropdown.open .dropdown-menu { display:block; }

        /* Pagination */
        .pagination { display:flex; justify-content:center; gap:6px; margin-top:20px; flex-wrap:wrap; }
        .pagination a { display:inline-flex; align-items:center; justify-content:center; min-width:38px; height:38px; padding:0 12px; border-radius:8px; text-decoration:none; font-weight:700; font-size:14px; color:var(--gray); background:var(--white); border:1px solid var(--light-gray); transition:var(--transition); }
        body.dark-mode .pagination a { background:#2f3133; border-color:#3f4244; color:#e4e6eb; }
        .pagination a:hover, .pagination a.active { background:var(--primary); color:#fff; border-color:var(--primary); }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:999; align-items:center; justify-content:center; }
        .modal-overlay.show { display:flex; }
        .modal-box { width:min(800px, calc(100vw - 24px)); background:var(--white); border-radius:var(--border-radius); box-shadow:0 20px 60px rgba(0,0,0,0.25); overflow:hidden; max-height:90vh; display:flex; flex-direction:column; }
        body.dark-mode .modal-box { background:#242526; }
        .modal-header { padding:16px 20px; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg,rgba(102,126,234,0.15),rgba(201,169,234,0.1)); border-bottom:1px solid rgba(102,126,234,0.25); }
        .modal-header h5 { font-weight:900; font-size:18px; }
        .modal-close { border:none; background:transparent; font-size:24px; cursor:pointer; color:var(--gray); }
        .modal-body { padding:18px 20px; overflow-y:auto; flex:1 1 auto; max-height:70vh; }
        .modal-body .detail-grid { display:grid; grid-template-columns:120px 1fr; gap:8px 12px; }
        .detail-grid .k { color:var(--gray); font-weight:700; font-size:13px; }
        .detail-grid .v { word-break:break-word; font-weight:500; }

        /* Tabs in modal */
        .modal-tabs { display:flex; gap:6px; margin-bottom:16px; border-bottom:2px solid var(--light-gray); padding-bottom:10px; }
        body.dark-mode .modal-tabs { border-color:#3a3b3c; }
        .modal-tab { padding:8px 16px; border:none; background:transparent; font-weight:700; color:var(--gray); cursor:pointer; border-radius:8px; transition:var(--transition); }
        .modal-tab:hover, .modal-tab.active { background:rgba(102,126,234,0.1); color:var(--primary); }
        .modal-tab-content { display:none; }
        .modal-tab-content.active { display:block; }

        .mobile-menu-btn { display:none; background:none; border:none; font-size:28px; color:var(--gray); cursor:pointer; padding:8px; border-radius:8px; transition:var(--transition); }
        .mobile-menu-btn:hover { background:rgba(102,126,234,0.1); color:var(--primary); }

        .no-data { text-align:center; padding:40px 20px; color:var(--gray); }
        .no-data i { font-size:48px; margin-bottom:12px; opacity:.5; }

        .badge-success { background:#d4edda; color:#155724; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-danger { background:#f8d7da; color:#721c24; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-warning { background:#fff3cd; color:#856404; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }

        .active-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
        .active-filter { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(102,126,234,0.1); border:1px solid rgba(102,126,234,0.2); border-radius:8px; font-size:12px; font-weight:600; color:var(--primary); }
        .active-filter .remove { cursor:pointer; font-size:16px; line-height:1; opacity:.7; }
        .active-filter .remove:hover { opacity:1; }

        /* Mobile */
        @media(max-width:992px) {
            .sidebar { width:80px; }
            .sidebar.collapsed { width:80px; }
            .logo-name span, .side-menu li a span { display:none; }
            .side-menu li a { justify-content:center; padding:16px; }
            .content { margin-left:80px; }
        }
        @media(max-width:768px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.active { transform:translateX(0); }
            .content { margin-left:0; }
            nav { padding:12px 16px; }
            .mobile-menu-btn { display:block; }
            main { padding:12px; }
            .kpi-grid { grid-template-columns:repeat(auto-fit, minmax(140px,1fr)); }
            .filter-grid { grid-template-columns:1fr; }
            .kpi-card { padding:14px; }
            .kpi-card .kpi-value { font-size:22px; }
        }
    </style>
</head>
<body>
<script>
(function(){ const t=localStorage.getItem('theme'); if(t==='dark') document.body.classList.add('dark-mode'); })();
</script>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="#" class="logo"><i class='bx bx-user-circle'></i><div class="logo-name"><span>Admin</span></div></a>
    <ul class="side-menu main-menu">
        <li><a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
        <li class="section-header"><span>Talent Pool</span></li>
        <li class="active"><a href="applicant_management.php"><i class='bx bx-user-pin'></i><span>Applicant Pool</span></a></li>
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
        <li><a href="admin_settings.php"><i class='bx bx-cog'></i><span>Settings</span></a></li>
        <li><a href="admin_contact_messages.php"><i class='bx bx-message-dots'></i><span>Contact Messages</span></a></li>
    </ul>
    <ul class="side-menu bottom-menu">
        <li><a href="logout.php" class="logout" onclick="return confirm('Are you sure you want to log out?');"><i class='bx bx-log-out-circle'></i><span>Logout</span></a></li>
    </ul>
</div>

<!-- CONTENT -->
<div class="content">
    <nav>
        <button class="mobile-menu-btn" id="mobileMenuBtn"><i class='bx bx-menu'></i></button>
        <div></div>
        <div style="display:flex;align-items:center;gap:12px;">
            <span class="muted" style="font-size:14px;"><?= $total_records ?> applicant(s)</span>
        </div>
    </nav>
    <main>
        <div class="welcome-section">
            <div class="welcome-content">
                <h1>Applicant Pool Management</h1>
                <p>Centralized overview of all registered applicants - analytics, filtering, and talent discovery.</p>
            </div>
            <div style="min-width:200px;font-size:14px;text-align:right;">
                <strong>Independent Talent Pool</strong><br>
                <span style="opacity:.8;">Profile-based only</span>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- KPI SUMMARY CARDS -->
        <!-- ============================================================ -->
        <div class="kpi-grid">
            <div class="kpi-card kpi-blue">
                <div class="kpi-label"><i class='bx bx-user'></i> Total Applicants</div>
                <div class="kpi-value"><?= $total_applicants ?></div>
            </div>
            <div class="kpi-card kpi-green">
                <div class="kpi-label"><i class='bx bx-check-circle'></i> Completed Profiles</div>
                <div class="kpi-value"><?= $completed_profiles ?></div>
            </div>
            <div class="kpi-card kpi-red">
                <div class="kpi-label"><i class='bx bx-x-circle'></i> Incomplete Profiles</div>
                <div class="kpi-value"><?= $incomplete_profiles ?></div>
            </div>
            <div class="kpi-card kpi-blue">
                <div class="kpi-label"><i class='bx bx-upload'></i> CV Uploaded</div>
                <div class="kpi-value"><?= $cv_uploaded ?></div>
            </div>
            <div class="kpi-card kpi-orange">
                <div class="kpi-label"><i class='bx bx-block'></i> Without CV</div>
                <div class="kpi-value"><?= $cv_not_uploaded ?></div>
            </div>
            <div class="kpi-card kpi-green">
                <div class="kpi-label"><i class='bx bx-briefcase'></i> With Work Exp</div>
                <div class="kpi-value"><?= $with_work_exp ?></div>
            </div>
            <div class="kpi-card kpi-red">
                <div class="kpi-label"><i class='bx bx-briefcase-alt-2'></i> No Work Exp</div>
                <div class="kpi-value"><?= $without_work_exp ?></div>
            </div>
            <div class="kpi-card kpi-green">
                <div class="kpi-label"><i class='bx bx-book'></i> With Qualifications</div>
                <div class="kpi-value"><?= $with_qual ?></div>
            </div>
            <div class="kpi-card kpi-red">
                <div class="kpi-label"><i class='bx bx-book-x'></i> No Qualifications</div>
                <div class="kpi-value"><?= $without_qual ?></div>
            </div>

        </div>

        <!-- ============================================================ -->
        <!-- ANALYTICS SECTION: Titles, Qualifications, Skills -->
        <!-- ============================================================ -->
        <div class="analytics-grid">
            <!-- Professional Titles -->
            <div class="analytics-card">
                <h3><i class='bx bx-badge'></i> Professional Titles</h3>
                <div class="chip-grid" id="titleChips">
                    <?php foreach ($prof_titles as $pt): ?>
                    <div class="chip" data-filter="filter_title" data-value="<?= htmlspecialchars($pt['professional_title']) ?>" onclick="applyChipFilter(this)">
                        <?= htmlspecialchars($pt['professional_title']) ?>
                        <span class="chip-count"><?= $pt['cnt'] ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($prof_titles)): ?>
                    <div class="empty-chip">No professional titles found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Qualification Analytics -->
            <div class="analytics-card">
                <h3><i class='bx bx-book'></i> Qualification Types</h3>
                <div class="chip-grid" id="qualChips">
                    <?php foreach ($qual_analytics as $ql => $cnt): ?>
                    <div class="chip" data-filter="filter_qual_level" data-value="<?= htmlspecialchars($ql) ?>" onclick="applyChipFilter(this)">
                        <?= htmlspecialchars($ql) ?>
                        <span class="chip-count"><?= $cnt ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="chip" data-filter="filter_qual_level" data-value="" onclick="applyChipFilter(this)">
                        Without Qualifications
                        <span class="chip-count"><?= $qual_without ?></span>
                    </div>
                </div>
            </div>

            <!-- Skills Analytics -->
            <div class="analytics-card" style="grid-column: 1 / -1;">
                <h3><i class='bx bx-brain'></i> Top Technical Skills</h3>
                <div class="chip-grid" id="skillChips">
                    <?php foreach ($skill_counts as $sk => $cnt): ?>
                    <div class="chip" data-filter="filter_tech_skills" data-value="<?= htmlspecialchars($sk) ?>" onclick="applyChipFilter(this)">
                        <?= htmlspecialchars($sk) ?>
                        <span class="chip-count"><?= $cnt ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="chip" data-filter="filter_tech_skills" data-value="other" onclick="applyChipFilter(this)">
                        Other Skills
                        <span class="chip-count"><?= $other_count ?></span>
                    </div>
                </div>
            </div>
        </div>

<!-- ============================================================ -->
        <!-- FILTER SECTION -->
        <!-- ============================================================ -->
        <div class="filter-section">
            <div class="filter-title" onclick="toggleFilters()">
                <i class='bx bx-filter-alt'></i> Advanced Filters
                <i class='bx bx-chevron-down' id="filterToggleIcon" style="margin-left:auto;font-size:18px;"></i>
            </div>
            <form method="GET" action="" id="filterForm">
                <div class="filter-grid" id="filterGrid" style="display: none;">
                    <input type="text" name="filter_name" class="form-control" placeholder="Full Name" value="<?= htmlspecialchars($_GET['filter_name'] ?? '') ?>">
                    <input type="email" name="filter_email" class="form-control" placeholder="Email" value="<?= htmlspecialchars($_GET['filter_email'] ?? '') ?>">
                    <input type="text" name="filter_username" class="form-control" placeholder="Username" value="<?= htmlspecialchars($_GET['filter_username'] ?? '') ?>">
                    <input type="text" name="filter_title" class="form-control" placeholder="Professional Title" value="<?= htmlspecialchars($_GET['filter_title'] ?? '') ?>">
                    <select name="filter_gender" class="form-select">
                        <option value="">All Genders</option>
                        <option value="Male" <?= ($_GET['filter_gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($_GET['filter_gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                    <select name="filter_qual_level" class="form-select">
                        <option value="">All Qualification Levels</option>
                        <option value="Higher Certificate" <?= ($_GET['filter_qual_level'] ?? '') === 'Higher Certificate' ? 'selected' : '' ?>>Higher Certificate</option>
                        <option value="Diploma" <?= ($_GET['filter_qual_level'] ?? '') === 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                        <option value="Bachelor's Degree" <?= ($_GET['filter_qual_level'] ?? '') === "Bachelor's Degree" ? 'selected' : '' ?>>Bachelor's Degree</option>
                        <option value="Honours Degree" <?= ($_GET['filter_qual_level'] ?? '') === 'Honours Degree' ? 'selected' : '' ?>>Honours Degree</option>
                        <option value="Master's Degree" <?= ($_GET['filter_qual_level'] ?? '') === "Master's Degree" ? 'selected' : '' ?>>Master's Degree</option>
                        <option value="Doctorate" <?= ($_GET['filter_qual_level'] ?? '') === 'Doctorate' ? 'selected' : '' ?>>Doctorate</option>
                        <option value="Certificate" <?= ($_GET['filter_qual_level'] ?? '') === 'Certificate' ? 'selected' : '' ?>>Certificate</option>
                        <option value="High School" <?= ($_GET['filter_qual_level'] ?? '') === 'High School' ? 'selected' : '' ?>>High School</option>
                        <option value="Other" <?= ($_GET['filter_qual_level'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                    <input type="number" name="filter_grad_year" class="form-control" placeholder="Graduation Year" value="<?= htmlspecialchars($_GET['filter_grad_year'] ?? '') ?>">
                    <input type="text" name="filter_tech_skills" class="form-control" placeholder="Technical Skills" value="<?= htmlspecialchars($_GET['filter_tech_skills'] ?? '') ?>">
                    <input type="text" name="filter_soft_skills" class="form-control" placeholder="Soft Skills" value="<?= htmlspecialchars($_GET['filter_soft_skills'] ?? '') ?>">
                    <input type="text" name="filter_address" class="form-control" placeholder="Province / Address" value="<?= htmlspecialchars($_GET['filter_address'] ?? '') ?>">
                    <input type="number" name="filter_exp_years" class="form-control" placeholder="Min Years Experience" value="<?= htmlspecialchars($_GET['filter_exp_years'] ?? '') ?>" min="0" step="1">
                    <input type="date" name="filter_reg_date" class="form-control" value="<?= htmlspecialchars($_GET['filter_reg_date'] ?? '') ?>">
                    <select name="filter_cv" class="form-select">
                        <option value="">CV Uploaded (All)</option>
                        <option value="1" <?= (isset($_GET['filter_cv']) && $_GET['filter_cv'] === '1') ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= (isset($_GET['filter_cv']) && $_GET['filter_cv'] === '0') ? 'selected' : '' ?>>No</option>
                    </select>
                    <select name="filter_pic" class="form-select">
                        <option value="">Profile Pic (All)</option>
                        <option value="1" <?= (isset($_GET['filter_pic']) && $_GET['filter_pic'] === '1') ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= (isset($_GET['filter_pic']) && $_GET['filter_pic'] === '0') ? 'selected' : '' ?>>No</option>
                    </select>
                    <select name="filter_work_exp" class="form-select">
                        <option value="">Work Experience (All)</option>
                        <option value="1" <?= (isset($_GET['filter_work_exp']) && $_GET['filter_work_exp'] === '1') ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= (isset($_GET['filter_work_exp']) && $_GET['filter_work_exp'] === '0') ? 'selected' : '' ?>>No</option>
                    </select>
                    <select name="filter_completion" class="form-select">
                        <option value="">Profile Completion (All)</option>
                        <option value="1" <?= (isset($_GET['filter_completion']) && $_GET['filter_completion'] === '1') ? 'selected' : '' ?>>Complete</option>
                        <option value="0" <?= (isset($_GET['filter_completion']) && $_GET['filter_completion'] === '0') ? 'selected' : '' ?>>Incomplete</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><i class='bx bx-search'></i> Apply Filters</button>
                    <a href="applicant_management.php" class="btn btn-outline"><i class='bx bx-reset'></i> Clear All</a>
                </div>
                <input type="hidden" name="p" value="1">
                <input type="hidden" name="search" id="searchHidden" value="<?= htmlspecialchars($search_query) ?>">
            </form>
        </div>

        <!-- Active Filters Display -->
        <?php
        $active_filters = [];
        $filter_fields = ['filter_name'=>'Name','filter_email'=>'Email','filter_username'=>'Username','filter_title'=>'Title','filter_gender'=>'Gender','filter_qualification'=>'Qualification','filter_qual_level'=>'Qual Level','filter_grad_year'=>'Grad Year','filter_tech_skills'=>'Tech Skills','filter_soft_skills'=>'Soft Skills','filter_address'=>'Address','filter_exp_years'=>'Min Exp','filter_reg_date'=>'Reg Date','filter_cv'=>'CV','filter_pic'=>'Profile Pic','filter_work_exp'=>'Work Exp','filter_completion'=>'Completion'];
        foreach ($filter_fields as $k => $label) {
            if (!empty($_GET[$k])) {
                $active_filters[] = ['key'=>$k, 'label'=>$label, 'value'=>htmlspecialchars($_GET[$k])];
            }
        }
        ?>
        <?php if (!empty($active_filters)): ?>
        <div class="active-filters">
            <span style="font-size:13px;font-weight:700;color:var(--gray);">Active Filters:</span>
            <?php foreach ($active_filters as $af): ?>
            <span class="active-filter">
                <?= $af['label'] ?>: <?= $af['value'] ?>
                <span class="remove" onclick="removeFilter('<?= $af['key'] ?>')">&times;</span>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ============================================================ -->
        <!-- SEARCH BAR -->
        <!-- ============================================================ -->
        <div class="card" style="margin-bottom:20px;">
            <form method="GET" action="" id="searchForm" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <!-- Preserve existing filter parameters -->
                <?php foreach ($_GET as $key => $val): ?>
                    <?php if ($key !== 'search' && $key !== 'p'): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <input type="hidden" name="p" value="1">
                <div style="position:relative;flex:1;min-width:200px;">
                    <i class='bx bx-search' style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--gray);font-size:18px;"></i>
                    <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search by name, email, title, qualification, skills..." value="<?= htmlspecialchars($search_query) ?>" style="padding-left:40px;border-radius:10px;">
                </div>
                <button type="submit" class="btn btn-primary"><i class='bx bx-search'></i> Search</button>
                <?php if (!empty($search_query)): ?>
                <a href="?<?= http_build_query(array_diff_key($_GET, ['search' => '', 'p' => ''])) ?>" class="btn btn-danger"><i class='bx bx-x'></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ============================================================ -->
        <!-- APPLICANT TABLE -->
        <!-- ============================================================ -->
        <div class="card">
            <div class="table-container">
                <?php if (empty($applicants)): ?>
                <div class="no-data">
                    <i class='bx bx-user-x'></i>
                    <h5>No Applicants Found</h5>
                    <p>Try adjusting your filters or search criteria to find applicants in the talent pool.</p>
                    <a href="applicant_management.php" class="btn btn-primary"><i class='bx bx-reset'></i> Reset Filters</a>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;">Pic</th>
                            <th>Name</th>
                            <th>Professional Title</th>
                            <th>Qualification</th>
                            <th>Tech Skills</th>
                            <th style="width:110px;">Completion</th>
                            <th>Registered</th>
                            <th style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applicants as $app): 
                            $uid = $app['user_id'];
                            $qual_list = $qual_map[$uid] ?? [];
                            $qual_names = array_map(function($q) { return $q['qualification_name']; }, $qual_list);
                            $qual_str = !empty($qual_names) ? implode(', ', array_slice($qual_names, 0, 2)) : '-';
                            $skills_row = $skill_map[$uid] ?? null;
                            $tech_skills_str = $skills_row ? $skills_row['technical_skills'] : '';
                            $tech_skills_arr = !empty($tech_skills_str) ? array_slice(preg_split('/[,;\/\n\r]+/', $tech_skills_str), 0, 4) : [];
                            $completion = $completion_map[$uid] ?? 0;
                            $exp_years = $exp_years_map[$uid] ?? 0;
                            $reg_date = date('M d, Y', strtotime($app['created_at']));
                            $pic_src = !empty($app['profile_picture']) ? 'fetch_profile_pic.php?user_id='.$uid : 'img/default_photo.jpg';
                        ?>
                        <tr>
                            <td><img src="<?= $pic_src ?>" alt="" class="app-pic" onerror="this.src='img/default_photo.jpg'"></td>
                            <td><strong><?= htmlspecialchars($app['fullname'] ?? $app['username'] ?? 'N/A') ?></strong><br><span style="font-size:11px;color:var(--gray);"><?= htmlspecialchars($app['email'] ?? '') ?></span></td>
                            <td><?= htmlspecialchars($app['professional_title'] ?? '-') ?></td>
                            <td><span style="font-size:12px;"><?= htmlspecialchars($qual_str) ?></span></td>
                            <td>
                                <?php foreach ($tech_skills_arr as $ts): ?>
                                <span class="skill-tag"><?= htmlspecialchars(trim($ts)) ?></span>
                                <?php endforeach; ?>
                                <?php if (empty($tech_skills_arr)): ?><span style="color:var(--gray);font-size:12px;">-</span><?php endif; ?>
                            </td>
                            <td>
                                <div class="completion-bar">
                                    <div class="bar">
                                        <div class="bar-fill <?= $completion >= 70 ? 'high' : ($completion >= 40 ? 'medium' : 'low') ?>" style="width:<?= $completion ?>%;"></div>
                                    </div>
                                    <span class="pct"><?= $completion ?>%</span>
                                </div>
                            </td>
                            <td style="font-size:12px;color:var(--gray);"><?= $reg_date ?></td>
                            <td>
                                <div class="actions-dropdown">
                                    <button class="btn btn-primary btn-sm"><i class='bx bx-dots-horizontal-rounded'></i></button>
                                    <div class="dropdown-menu">
                                        <?php if (!empty($app['cv'])): ?>
                                        <a href="#" onclick="previewCV(<?= $uid ?>); return false;"><i class='bx bx-show'></i> Preview CV</a>
                                        <a href="download_cv.php?user_id=<?= $uid ?>" target="_blank"><i class='bx bx-download'></i> Download CV</a>
                                        <?php endif; ?>
                                        <a href="#" onclick="viewQualifications(<?= $uid ?>); return false;"><i class='bx bx-book'></i> Qualifications</a>
                                        <a href="#" onclick="viewSkills(<?= $uid ?>); return false;"><i class='bx bx-brain'></i> Skills</a>
                                        <a href="#" onclick="viewWorkExperience(<?= $uid ?>); return false;"><i class='bx bx-briefcase'></i> Work Experience</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['p'=>$page-1])) ?>">&laquo;</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['p'=>$i])) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['p'=>$page+1])) ?>">&raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- ============================================================ -->
<!-- PROFILE MODAL -->
<!-- ============================================================ -->
<div class="modal-overlay" id="profileModal">
    <div class="modal-box">
        <div class="modal-header">
            <h5><i class='bx bx-user'></i> Applicant Profile</h5>
            <button class="modal-close" onclick="closeModal('profileModal')">&times;</button>
        </div>
        <div class="modal-body" id="profileModalBody">
            Loading...
        </div>
    </div>
</div>

<!-- ============================================================ -->
<script>
function toggleFilters() {
    const grid = document.getElementById('filterGrid');
    const icon = document.getElementById('filterToggleIcon');
    if (grid.style.display === 'none' || grid.style.display === '') {
        grid.style.display = 'grid';
        icon.classList.replace('bx-chevron-down', 'bx-chevron-up');
    } else {
        grid.style.display = 'none';
        icon.classList.replace('bx-chevron-up', 'bx-chevron-down');
    }
}

function removeFilter(key) {
    const url = new URL(window.location.href);
    url.searchParams.delete(key);
    url.searchParams.set('p', '1');
    window.location.href = url.toString();
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.set('p', '1');
    window.location.href = url.toString();
}

// Search is handled via form submission (click Search button or press Enter)

function applyChipFilter(el) {
    const filter = el.getAttribute('data-filter');
    const value = el.getAttribute('data-value');
    const url = new URL(window.location.href);
    if (value) url.searchParams.set(filter, value);
    else url.searchParams.delete(filter);
    url.searchParams.set('p', '1');
    window.location.href = url.toString();
}

function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
}

// Close modals on backdrop click
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
});

// View Profile
function viewProfile(uid) {
    const modal = document.getElementById('profileModal');
    const body = document.getElementById('profileModalBody');
    body.innerHTML = 'Loading profile...';
    openModal('profileModal');

    fetch('get_applicant_details.php?user_id=' + uid)
        .then(r => r.json())
        .then(data => {
            if (!data || data.error) {
                body.innerHTML = '<div class="no-data"><i class="bx bx-error-circle"></i><p>' + (data?.error || 'Failed to load profile') + '</p></div>';
                return;
            }
            let html = '<div class="detail-grid">';
            html += '<div class="k">Name</div><div class="v">' + escapeHtml(data.fullname || '') + '</div>';
            html += '<div class="k">Email</div><div class="v">' + escapeHtml(data.email || '') + '</div>';
            html += '<div class="k">Username</div><div class="v">' + escapeHtml(data.username || '') + '</div>';
            html += '<div class="k">Gender</div><div class="v">' + escapeHtml(data.gender || '-') + '</div>';
            html += '<div class="k">Phone</div><div class="v">' + escapeHtml(data.phone || '-') + '</div>';
            html += '<div class="k">Address</div><div class="v">' + escapeHtml(data.address || '-') + '</div>';
            html += '<div class="k">Title</div><div class="v">' + escapeHtml(data.professional_title || '-') + '</div>';
            html += '<div class="k">Summary</div><div class="v">' + escapeHtml(data.professional_summary || '-') + '</div>';
            html += '<div class="k">Registered</div><div class="v">' + (data.created_at ? new Date(data.created_at).toLocaleDateString() : '-') + '</div>';
            html += '</div>';
            body.innerHTML = html;
        })
        .catch(err => {
            body.innerHTML = '<div class="no-data"><i class="bx bx-error-circle"></i><p>Failed to load profile.</p></div>';
        });
}

function previewCV(uid) {
    window.open('preview_cv.php?user_id=' + uid, '_blank', 'width=900,height=700');
}

function viewQualifications(uid) {
    const modal = document.getElementById('profileModal');
    const body = document.getElementById('profileModalBody');
    body.innerHTML = 'Loading qualifications...';
    openModal('profileModal');

    fetch('get_applicant_details.php?user_id=' + uid + '&type=qualifications')
        .then(r => r.json())
        .then(data => {
            if (!data || data.error || !data.qualifications || data.qualifications.length === 0) {
                body.innerHTML = '<div class="no-data"><i class="bx bx-book-x"></i><p>No qualifications found.</p></div>';
                return;
            }
            let html = '<div style="display:grid;gap:12px;">';
            data.qualifications.forEach(q => {
                html += '<div style="background:rgba(102,126,234,0.05);border-radius:10px;padding:14px;border:1px solid rgba(102,126,234,0.1);">';
                html += '<strong>' + escapeHtml(q.qualification_name || '') + '</strong>';
                if (q.qualification_level) html += ' <span class="badge-success">' + escapeHtml(q.qualification_level) + '</span>';
                html += '<br><span style="font-size:13px;color:var(--gray);">' + escapeHtml(q.institution || '') + (q.year_completed ? ' (' + q.year_completed + ')' : '') + '</span>';
                html += '</div>';
            });
            html += '</div>';
            body.innerHTML = html;
        })
        .catch(err => {
            body.innerHTML = '<div class="no-data"><i class="bx bx-error-circle"></i><p>Failed to load qualifications.</p></div>';
        });
}

function viewSkills(uid) {
    const modal = document.getElementById('profileModal');
    const body = document.getElementById('profileModalBody');
    body.innerHTML = 'Loading skills...';
    openModal('profileModal');

    fetch('get_applicant_details.php?user_id=' + uid + '&type=skills')
        .then(r => r.json())
        .then(data => {
            if (!data || data.error) {
                body.innerHTML = '<div class="no-data"><i class="bx bx-error-circle"></i><p>' + (data?.error || 'Failed to load skills') + '</p></div>';
                return;
            }
            let html = '';
            if (data.technical_skills) {
                html += '<h6 style="font-weight:800;margin-bottom:8px;">Technical Skills</h6>';
                const skills = data.technical_skills.split(',').map(s => s.trim()).filter(s => s);
                html += '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;">';
                skills.forEach(s => { html += '<span class="skill-tag" style="font-size:13px;padding:6px 12px;">' + escapeHtml(s) + '</span>'; });
                html += '</div>';
            }
            if (data.soft_skills) {
                html += '<h6 style="font-weight:800;margin-bottom:8px;">Soft Skills</h6>';
                const soft = data.soft_skills.split(',').map(s => s.trim()).filter(s => s);
                html += '<div style="display:flex;flex-wrap:wrap;gap:6px;">';
                soft.forEach(s => { html += '<span class="skill-tag" style="font-size:13px;padding:6px 12px;background:rgba(201,169,234,0.15);color:#9b59b6;">' + escapeHtml(s) + '</span>'; });
                html += '</div>';
            }
            if (!html) html = '<div class="no-data"><i class="bx bx-brain"></i><p>No skills recorded.</p></div>';
            body.innerHTML = html;
        })
        .catch(err => {
            body.innerHTML = '<div class="no-data"><i class="bx bx-error-circle"></i><p>Failed to load skills.</p></div>';
        });
}

function viewWorkExperience(uid) {
    const modal = document.getElementById('profileModal');
    const body = document.getElementById('profileModalBody');
    body.innerHTML = 'Loading work experience...';
    openModal('profileModal');

    fetch('get_applicant_details.php?user_id=' + uid + '&type=work_experience')
        .then(r => r.json())
        .then(data => {
            if (!data || data.error || !data.work_experience || data.work_experience.length === 0) {
                body.innerHTML = '<div class="no-data"><i class="bx bx-briefcase-alt-2"></i><p>No work experience recorded.</p></div>';
                return;
            }
            let html = '<div style="display:grid;gap:12px;">';
            data.work_experience.forEach(w => {
                html += '<div style="background:rgba(102,126,234,0.05);border-radius:10px;padding:14px;border:1px solid rgba(102,126,234,0.1);">';
                html += '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">';
                html += '<strong>' + escapeHtml(w.position || '') + '</strong>';
                if (w.duration) html += '<span style="font-size:12px;font-weight:700;color:var(--primary);">' + escapeHtml(w.duration) + ' yrs</span>';
                html += '</div>';
                html += '<div style="font-size:13px;color:var(--gray);">' + escapeHtml(w.company_name || '') + '</div>';
                if (w.duties) html += '<div style="margin-top:6px;font-size:13px;">' + escapeHtml(w.duties) + '</div>';
                if (w.start_date) html += '<div style="margin-top:4px;font-size:11px;color:var(--gray);">' + w.start_date + (w.end_date ? ' to ' + w.end_date : '') + '</div>';
                html += '</div>';
            });
            html += '</div>';
            body.innerHTML = html;
        })
        .catch(err => {
            body.innerHTML = '<div class="no-data"><i class="bx bx-error-circle"></i><p>Failed to load work experience.</p></div>';
        });
}

function viewCompletionDetails(uid) {
    const modal = document.getElementById('profileModal');
    const body = document.getElementById('profileModalBody');
    body.innerHTML = 'Loading completion details...';
    openModal('profileModal');

    fetch('get_applicant_details.php?user_id=' + uid)
        .then(r => r.json())
        .then(data => {
            if (!data || data.error) {
                body.innerHTML = '<div class="no-data"><i class="bx bx-error-circle"></i><p>Failed to load details.</p></div>';
                return;
            }
            const checks = {
                'Personal Information': !!(data.fullname && data.email),
                'Professional Summary': !!data.professional_summary,
                'Professional Title': !!data.professional_title,
                'Qualifications': data.has_qualifications > 0,
                'Technical Skills': !!(data.technical_skills),
                'Work Experience': data.has_work_experience > 0,
                'Profile Picture': !!data.profile_picture,
                'CV Upload': !!data.cv
            };
            const total = Object.keys(checks).length;
            const completed = Object.values(checks).filter(v => v).length;
            const pct = Math.round((completed / total) * 100);

            let html = '<div style="text-align:center;margin-bottom:20px;">';
            html += '<div style="font-size:36px;font-weight:900;color:' + (pct >= 70 ? 'var(--success)' : (pct >= 40 ? 'var(--warning)' : 'var(--danger)')) + ';">' + pct + '%</div>';
            html += '<div style="font-weight:700;color:var(--gray);">Profile Complete</div>';
            html += '</div>';
            html += '<div style="display:grid;gap:8px;">';
            for (const [section, done] of Object.entries(checks)) {
                html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:' + (done ? 'rgba(40,167,69,0.08)' : 'rgba(220,53,69,0.08)') + ';border-radius:8px;">';
                html += '<span style="font-weight:600;font-size:14px;">' + section + '</span>';
                html += '<span style="font-weight:800;font-size:14px;color:' + (done ? 'var(--success)' : 'var(--danger)') + ';">' + (done ? '✓ Complete' : '✗ Incomplete') + '</span>';
                html += '</div>';
            }
            html += '</div>';
            body.innerHTML = html;
        })
        .catch(err => {
            body.innerHTML = '<div class="no-data"><i class="bx bx-error-circle"></i><p>Failed to load completion details.</p></div>';
        });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'<').replace(/>/g,'>').replace(/"/g,'"').replace(/'/g,'&#039;');
}

// Actions dropdown - click to toggle (not hover)
document.querySelectorAll('.actions-dropdown > .btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const dd = this.closest('.actions-dropdown');
        // Close all other dropdowns
        document.querySelectorAll('.actions-dropdown.open').forEach(d => {
            if (d !== dd) d.classList.remove('open');
        });
        dd.classList.toggle('open');
    });
});
// Close dropdowns when clicking outside
document.addEventListener('click', function() {
    document.querySelectorAll('.actions-dropdown.open').forEach(d => d.classList.remove('open'));
});

// Mobile sidebar toggle
document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('active');
});
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</body>
</html>

