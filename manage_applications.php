<?php
include('config.php');

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login_signup.php');
    exit;
}

// --- FILTER LOGIC (Enhanced) ---
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$status_filter = isset($_GET['status']) ? $_GET['status'] : ''; // Can be comma-separated for multi-select
$gender_filter = isset($_GET['gender']) ? $conn->real_escape_string($_GET['gender']) : '';
$position_filter = isset($_GET['position']) ? $conn->real_escape_string($_GET['position']) : '';
$qualification_filter = isset($_GET['qualification']) ? trim($_GET['qualification']) : '';
$qualification_name_filter = isset($_GET['qualification_name']) ? trim($_GET['qualification_name']) : '';
$skills_filter = isset($_GET['skills']) ? trim($_GET['skills']) : '';

// Experience filter (range: 0-1, 1-2, 2-3, 3-5, 5+)
$experience_range = isset($_GET['experience_range']) ? $conn->real_escape_string($_GET['experience_range']) : '';

// Age range filter (18-25, 26-35, 36-45, 45+)
$age_range = isset($_GET['age_range']) ? $conn->real_escape_string($_GET['age_range']) : '';

// Date range filters
$date_from = isset($_GET['date_from']) ? $conn->real_escape_string($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? $conn->real_escape_string($_GET['date_to']) : '';
$sort_by = isset($_GET['sort_by']) ? $conn->real_escape_string($_GET['sort_by']) : 'fullname_asc';
$qualification_level = isset($_GET['qualification_level']) ? stripslashes(rawurldecode($_GET['qualification_level'])) : '';

$province_filter = isset($_GET['province']) ? $conn->real_escape_string($_GET['province']) : '';
$city_filter = isset($_GET['city']) ? $conn->real_escape_string($_GET['city']) : '';

function normalize_search($text) {
    $text = strtolower($text);
    $text = str_replace(["'", '"', '-', '(', ')'], ' ', $text);
    $text = preg_replace('/\\s+(in|of|the|a|an)\\s+/i', ' ', $text);
    $text = trim(preg_replace('/\\s+/', ' ', $text));
    $word_map = [
        'degree' => 'degree',
        'diploma' => 'diploma'
    ];
    $text = str_ireplace(array_keys($word_map), array_values($word_map), $text);
    $abbr_map = [
        'it' => 'information technology',
        'bsit' => 'bachelor information technology',
        'bs' => 'bachelor',
        'bsc' => 'bachelor science',
        'ba' => 'bachelor arts',
        'ms' => 'master',
        'phd' => 'doctorate'
    ];
    foreach ($abbr_map as $abbr => $full) {
        $text = str_replace($abbr, $full, $text);
    }
    return $text;
}





// Base query
$query = "SELECT
            u.user_id, u.fullname, u.email, u.phone, u.gender, u.dob,
            ap.professional_title, ap.profile_picture,
            ja.application_id, ja.job_id, ja.position, ja.qualification, ja.application_status,
            ja.cover_letter, ja.resume AS cv, ja.submission_date,
            ss.profile_data,
            GROUP_CONCAT(DISTINCT s.technical_skills SEPARATOR ', ') AS technical_skills,
            GROUP_CONCAT(DISTINCT s.soft_skills SEPARATOR ', ') AS soft_skills,
            GROUP_CONCAT(DISTINCT q.qualification_name SEPARATOR ', ') AS qualification_names,
            GROUP_CONCAT(DISTINCT q.qualification_level SEPARATOR ', ') AS qualification_levels,
            u.address
          FROM users u
          LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
          JOIN job_applications ja ON u.user_id = ja.user_id
          LEFT JOIN application_snapshots ss ON ja.application_id = ss.application_id
          LEFT JOIN skills s ON u.user_id = s.user_id
          LEFT JOIN qualifications q ON u.user_id = q.user_id
          WHERE u.role = 'applicant'";

// Apply filters
if (!empty($search)) {
    $query .= " AND (u.fullname LIKE '%$search%' OR u.email LIKE '%$search%' OR ja.position LIKE '%$search%' OR ap.professional_title LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $status_array = array_map(function($s) use ($conn) {
        $trimmed = trim($s);
        return $trimmed ? "'" . $conn->real_escape_string($trimmed) . "'" : null;
    }, explode(',', $status_filter));
    $status_array = array_filter($status_array); // Remove empty/null
    if (!empty($status_array)) {
        $query .= " AND ja.application_status IN (" . implode(',', $status_array) . ")";
    }
}

if (!empty($gender_filter)) {
    $query .= " AND u.gender = '$gender_filter'";
}
if (!empty($position_filter)) {
    $query .= " AND ja.job_id = '$position_filter'";
}
if (!empty($qualification_filter)) {
    $norm_qual = normalize_search($qualification_filter);
    $escaped_norm = $conn->real_escape_string($norm_qual);
    $query .= " AND (
        LOWER(ja.qualification) LIKE '%degree%' OR 
        LOWER(ja.qualification) LIKE '%diploma%' OR
        LOWER(ja.qualification) LIKE '% $escaped_norm %' OR 
        LOWER(ja.qualification) LIKE '%$escaped_norm%'
    )";
}

// Qualification level filter - filter by qualification_level from qualifications table
    if (!empty($qualification_level)) {
        // Enhanced mapping with normalization and synonyms
        $qual_level_map = [
            'matric' => ['high school', 'matric', 'grade 12'],
            'certificate' => ['certificate', 'cert', 'nqf level 5'],
            'diploma' => ['diploma', 'nqf level 6'],
            "bachelor's degree" => ["bachelor's degree", 'bsc', 'ba', 'btech', 'beng', 'nqf level 7'],
            'postgraduate' => ["master's degree", 'honours', 'msc', 'ma', 'nqf level 8', 'postgraduate'],
            'doctorate' => ['doctorate', 'phd', 'dphil', 'nqf level 10']
        ];
        $norm_level = strtolower(trim($qualification_level));
        $found_levels = [];
        foreach ($qual_level_map as $key => $synonyms) {
            if (in_array($norm_level, $synonyms) || $norm_level === $key) {
                $found_levels[] = $key;
            }
        }
        if (!empty($found_levels)) {
            $escaped_levels = array_map(function($level) use ($conn) {
                return "'" . $conn->real_escape_string($level) . "'";
            }, $found_levels);
            $query .= " AND LOWER(q.qualification_level) IN (" . implode(',', $escaped_levels) . ")";
        } else {
            // Fallback fuzzy match
            $escaped_level = $conn->real_escape_string($norm_level);
            $query .= " AND LOWER(q.qualification_level) LIKE '%$escaped_level%'";
        }
    }

    // Skills synonyms mapping
$skills_synonyms = [
        'communication' => ['good communication', 'effective communication', 'excellent communication', 'communication skills', 'strong communication', 'verbal communication', 'written communication', 'interpersonal skills', 'presentation skills', 'articulation', 'public speaking', 'communication'],
        'teamwork' => ['teamwork', 'team work', 'team player', 'collaboration', 'collaborative', 'works well in a team', 'cross-functional teamwork', 'partnership'],
        'problem solving' => ['problem solving', 'problem-solving', 'analytical thinking', 'critical thinking', 'troubleshooting', 'debugging', 'issue resolution', 'solution-oriented', 'decision making'],
        'leadership' => ['leadership', 'team leadership', 'leading teams', 'management', 'people management', 'supervision', 'mentoring', 'coaching'],
        'adaptability' => ['flexibility', 'versatile', 'open to change', 'resilient', 'adjustable', 'dynamic'],
        'willingness_to_learn' => ['willingness to learn', 'eager to learn', 'quick learner', 'fast learner', 'self-motivated learner', 'growth mindset', 'continuous learning'],
        'time_management' => ['time management', 'deadline driven', 'ability to meet deadlines', 'prioritization', 'task management', 'organizational skills', 'multitasking'],
        'attention_to_detail' => ['attention to detail', 'detail-oriented', 'accuracy', 'precision', 'thoroughness'],
        'work_ethic' => ['hardworking', 'strong work ethic', 'dedicated', 'committed', 'reliable', 'responsible']
    ];

    if (!empty($skills_filter)) {
        $raw_skills = array_map('trim', explode(',', $skills_filter));
        $skills_conditions = [];
        $combined_skills_field = "CONCAT(COALESCE(technical_skills, ''), ', ', COALESCE(soft_skills, ''))";
        $norm_field = "LOWER(TRIM(REGEXP_REPLACE(REPLACE(REPLACE($combined_skills_field, '-', ' '), \"'\", ' '), '[^a-zA-Z0-9\\s]', ' ')))";
        
        foreach ($raw_skills as $raw_skill) {
            if (!empty($raw_skill)) {
                $norm_skill = strtolower(preg_replace('/[^a-zA-Z0-9\\s-]/', ' ', $raw_skill));
                $norm_skill = str_replace('-', ' ', $norm_skill);
                $norm_skill = trim(preg_replace('/\\s+/', ' ', $norm_skill));
                
                // Check for synonyms
                $synonym_conditions = ["$norm_field LIKE '%$norm_skill%'"];
                foreach ($skills_synonyms as $key => $synonyms) {
                    $key_norm = strtolower(trim(preg_replace('/\\s+/', ' ', $key)));
                    if (strpos($norm_skill, $key_norm) !== false || $norm_skill === $key_norm) {
                        foreach ($synonyms as $synonym) {
                            $syn_norm = strtolower(trim(preg_replace('/\\s+/', ' ', $synonym)));
                            $escaped_syn = $conn->real_escape_string($syn_norm);
                            $synonym_conditions[] = "$norm_field LIKE '%$escaped_syn%'";
                        }
                        break;
                    }
                }
                
                $skills_conditions[] = "(" . implode(' OR ', $synonym_conditions) . ")";
            }
        }
        if (!empty($skills_conditions)) {
            $query .= " AND (" . implode(' AND ', $skills_conditions) . ")";
        }
    }

// Qualification name filter - uses ONLY SNAPSHOT data (match if has snapshot AND contains term)
if (!empty($qualification_name_filter)) {
    $norm_qual_name = normalize_search($qualification_name_filter);
    $escaped_norm_name = $conn->real_escape_string($norm_qual_name);
    
    // Snapshot must exist AND contain the term (broad search in JSON text)
    $query .= " AND ss.profile_data IS NOT NULL AND LOWER(ss.profile_data) LIKE '%" . $escaped_norm_name . "%'";
    
    // Word-by-word AND matching - only if snapshot matches all words
    $filter_words = array_filter(array_map('trim', explode(' ', strtolower($norm_qual_name))), function($w) { return strlen($w) > 2; });
    if (count($filter_words) > 1) {
        $word_conditions = [];
        foreach ($filter_words as $word) {
            $escaped_word = $conn->real_escape_string($word);
            $word_conditions[] = "LOWER(ss.profile_data) LIKE '%" . $escaped_word . "%'";
        }
        $query .= " AND ss.profile_data IS NOT NULL AND (" . implode(' AND ', $word_conditions) . ")";
    }
}

// Location filters - exact word match using REGEXP (word boundaries)
if (!empty($province_filter)) {
    $province_escaped = $conn->real_escape_string(strtolower($province_filter));
    $profile_regexp = "(LOWER(ss.profile_data) REGEXP '(^|[^a-zA-Z0-9])" . $province_escaped . "($|[^a-zA-Z0-9])')";
    $address_regexp = "(LOWER(u.address) REGEXP '(^|[^a-zA-Z0-9])" . $province_escaped . "($|[^a-zA-Z0-9])')";
    $query .= " AND ($profile_regexp OR $address_regexp)";
}
if (!empty($city_filter)) {
    $city_escaped = $conn->real_escape_string(strtolower($city_filter));
    $profile_regexp = "(LOWER(ss.profile_data) REGEXP '(^|[^a-zA-Z0-9])" . $city_escaped . "($|[^a-zA-Z0-9])')";
    $address_regexp = "(LOWER(u.address) REGEXP '(^|[^a-zA-Z0-9])" . $city_escaped . "($|[^a-zA-Z0-9])')";
    $query .= " AND ($profile_regexp OR $address_regexp)";
}


// Date range filter
if (!empty($date_from)) {
    $query .= " AND DATE(ja.submission_date) >= '$date_from'";
}
if (!empty($date_to)) {
    $query .= " AND DATE(ja.submission_date) <= '$date_to'";
}

// Experience range filter
if (!empty($experience_range)) {
    $exp_field = "COALESCE(ap.years_of_experience, 0)";
    switch ($experience_range) {
        case '0-1':
            $query .= " AND $exp_field >= 0 AND $exp_field <= 1";
            break;
        case '1-2':
            $query .= " AND $exp_field > 1 AND $exp_field <= 2";
            break;
        case '2-3':
            $query .= " AND $exp_field > 2 AND $exp_field <= 3";
            break;
        case '3-5':
            $query .= " AND $exp_field > 3 AND $exp_field <= 5";
            break;
        case '5+':
            $query .= " AND $exp_field > 5";
            break;
    }
}

// Age range filter
if (!empty($age_range)) {
    $age_field = "TIMESTAMPDIFF(YEAR, u.dob, CURDATE())";
    switch ($age_range) {
        case '18-25':
            $query .= " AND u.dob IS NOT NULL AND $age_field >= 18 AND $age_field <= 25";
            break;
        case '26-35':
            $query .= " AND u.dob IS NOT NULL AND $age_field >= 26 AND $age_field <= 35";
            break;
        case '36-45':
            $query .= " AND u.dob IS NOT NULL AND $age_field >= 36 AND $age_field <= 45";
            break;
        case '45+':
            $query .= " AND u.dob IS NOT NULL AND $age_field >= 45";
            break;
    }
}


// Sorting
$sort_options = [
    'fullname_asc' => 'u.fullname ASC',
    'fullname_desc' => 'u.fullname DESC',
    'date_newest' => 'ja.submission_date DESC',
    'date_oldest' => 'ja.submission_date ASC',
    'status_asc' => 'ja.application_status ASC',
    'experience_asc' => 'COALESCE(ap.years_of_experience, 0) ASC',
    'experience_desc' => 'COALESCE(ap.years_of_experience, 0) DESC'
];
$sort_clause = $sort_options[$sort_by] ?? 'u.fullname ASC';

$query .= " GROUP BY ja.application_id ORDER BY $sort_clause";
$result = $conn->query($query);

// Fetch stats
$total_candidates = $conn->query("SELECT COUNT(DISTINCT u.user_id) AS c FROM users u JOIN job_applications ja ON u.user_id = ja.user_id WHERE u.role = 'applicant'")->fetch_assoc()['c'];
$gender_stats = $conn->query("
    SELECT
        COUNT(DISTINCT CASE WHEN u.gender = 'Male' THEN u.user_id END) AS male,
        COUNT(DISTINCT CASE WHEN u.gender = 'Female' THEN u.user_id END) AS female,
        COUNT(DISTINCT CASE WHEN u.gender NOT IN ('Male','Female') OR u.gender IS NULL THEN u.user_id END) AS other
    FROM users u JOIN job_applications ja ON u.user_id = ja.user_id WHERE u.role = 'applicant'
")->fetch_assoc();

$status_stats = $conn->query("
    SELECT 
        application_status, COUNT(*) AS count
    FROM job_applications 
    GROUP BY application_status
")->fetch_all(MYSQLI_ASSOC);

$position_stats = $conn->query("
    SELECT jp.position, COUNT(ja.application_id) as app_count 
    FROM job_applications ja 
    JOIN job_postings jp ON ja.job_id = jp.job_id 
    GROUP BY ja.job_id, jp.position 
    ORDER BY app_count DESC 
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);

// For dropdowns
$positions = $conn->query("SELECT DISTINCT job_id, position FROM job_postings ORDER BY position")->fetch_all(MYSQLI_ASSOC);
$genders = ['Male', 'Female', 'Other', 'M', 'F'];

$available_statuses = ['Submitted', 'Under Review', 'Shortlisted', 'Rejected', 'Hired'];

// Count total leave requests by status
$leave_count_sql = "SELECT
    COUNT(*) AS total_leave_requests,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_leave_count,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_leave_count,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_leave_count
    FROM consultant_leaves";
$leave_count_result = $conn->query($leave_count_sql);
$leave_counts = $leave_count_result->fetch_assoc();
$total_leave_requests = $leave_counts['total_leave_requests'] ?? 0;
$pending_leave_count = $leave_counts['pending_leave_count'] ?? 0;

// Fetch leave requests
$leave_sql = "SELECT cl.*, u.fullname, u.email
              FROM consultant_leaves cl
              JOIN users u ON cl.user_id = u.user_id
              ORDER BY
                CASE
                    WHEN cl.status = 'Pending' THEN 1
                    WHEN cl.status = 'Approved' THEN 2
                    WHEN cl.status = 'Rejected' THEN 3
                END,
                cl.start_date DESC";
$leave_result = $conn->query($leave_sql);

// Collect filtered application IDs for auto-evaluation
$filtered_app_ids = [];
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        $filtered_app_ids[] = (int)$row['application_id'];
    }
}
?>
<script>
window.filteredAppIds = <?= json_encode($filtered_app_ids) ?>;
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ========== GLOBAL VARS & RESET (from dashboard) ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
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
        }
        body.dark-mode {
            background-color: var(--dark);
            color: #e4e6eb;
        }

        /* ========== SIDEBAR ========== */
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
        .sidebar.collapsed { width: 80px; }
        .logo { display: flex; align-items: center; gap: 12px; padding: 24px 20px; color: var(--white); font-size: 22px; font-weight: 700; text-decoration: none; }
        .logo i { font-size: 32px; }
        .logo-name span { white-space: nowrap; transition: var(--transition); }
        .sidebar.collapsed .logo-name span { display: none; }
        .side-menu { list-style: none; padding: 0 15px; flex: 1; overflow-y: auto; }
        .side-menu li { margin: 8px 0; }
        .side-menu li a { display: flex; align-items: center; gap: 14px; padding: 14px 16px; color: var(--white); text-decoration: none; border-radius: 8px; transition: var(--transition); font-size: 16px; }
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

        .sidebar.collapsed .side-menu li.section-title {
            display: none;
        }

        .logout { margin-top: auto; padding: 16px !important; background: rgba(0,0,0,0.2); }

        /* ========== CONTENT & NAV ========== */
        .content { flex: 1; margin-left: 280px; transition: var(--transition); }
        .sidebar.collapsed ~ .content { margin-left: 80px; }
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
        body.dark-mode nav { background: #242526; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 28px; color: var(--gray); cursor: pointer; }
        .form-input {
            display: flex;
            align-items: center;
            background: var(--light-gray);
            border-radius: 30px;
            padding: 8px 16px;
            width: 300px;
        }
        body.dark-mode .form-input { background: #3a3b3c; }
        .form-input input { background: transparent; border: none; outline: none; padding: 8px; width: 100%; font-size: 16px; color: inherit; }
        .search-btn { background: transparent; border: none; cursor: pointer; color: var(--gray); }
        .theme-toggle {
            width: 50px; height: 24px;
            background: var(--light-gray);
            border-radius: 50px;
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 2px;
        }
        body.dark-mode .theme-toggle { background: #3a3b3c; }
        .theme-toggle::before {
            content: ''; width: 20px; height: 20px;
            background: var(--white); border-radius: 50%;
            transition: var(--transition);
        }
        #theme-toggle:checked + .theme-toggle::before {
            transform: translateX(26px); background: var(--primary);
        }

        /* ========== MAIN ========== */
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
        .welcome-section h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .welcome-section p {
            opacity: 0.9;
            font-size: 18px;
        }

        /* ========== INSIGHTS ========== */
        .insights {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .insights li {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            gap: 16px;
            transition: var(--transition);
        }
        body.dark-mode .insights li { background: #242526; }
        .insights li:hover { transform: translateY(-5px); }
        .insights li i { font-size: 28px; color: var(--primary); background: rgba(102,126,234,0.1); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .info h3 { font-size: 24px; margin-bottom: 6px; }
        .info p { color: var(--gray); font-size: 16px; }
        body.dark-mode .info p { color: #adb5bd; }

        /* ========== FILTERS ========== */
        .filter-section {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            box-shadow: var(--box-shadow);
        }
        body.dark-mode .filter-section { background: #242526; }
        .filter-title { display: flex; align-items: center; font-weight: 600; font-size: 18px; color: var(--primary); margin-bottom: 16px; gap: 10px; }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .filter-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            background: var(--white);
            font-size: 15px;
            color: inherit;
            transition: border-color 0.3s;
        }
        body.dark-mode .filter-group input,
        body.dark-mode .filter-group select {
            background: #3a3b3c;
            color: #e4e6eb;
            border-color: #4a4a4c;
        }
        .filter-buttons { display: flex; gap: 12px; }
        .btn { 
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            text-decoration: none;
            transition: var(--transition);
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: transparent; border: 2px solid var(--light-gray); color: var(--gray); }
        body.dark-mode .btn-outline { border-color: #4a4a4c; color: #adb5bd; }
        .btn-outline:hover { background: var(--light-gray); }
        body.dark-mode .btn-outline:hover { background: #3a3b3c; }
        .filter-stats {
            font-size: 0.95rem;
            padding: 12px;
            background: var(--light-gray);
            border-radius: 8px;
            color: var(--gray);
        }
        body.dark-mode .filter-stats { background: #3a3b3c; }

        /* Active Filters */
        .active-filters { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
        .active-filter-tag {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px;
            background: var(--primary);
            color: white;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .active-filter-tag i { cursor: pointer; }
        .active-filter-tag i:hover { opacity: 0.7; }

        /* Advanced Options */
        .advanced-options {
            margin-top: 24px;
            background: var(--light-gray);
            border-radius: 8px;
            padding: 20px;
        }
        body.dark-mode .advanced-options { background: #33373b; }
        .advanced-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 17px;
            cursor: pointer;
            color: var(--gray);
        }
        body.dark-mode .advanced-title { color: #e4e6eb; }
        .advanced-content { display: none; margin-top: 20px; }
        .advanced-content.active { display: block; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: var(--white);
            padding: 16px;
            border-radius: 10px;
            text-align: center;
            box-shadow: var(--box-shadow);
        }
        body.dark-mode .stat-card { background: #2a2b2c; }
        .stat-value { font-size: 26px; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 0.9rem; color: var(--gray); margin-top: 6px; }
        body.dark-mode .stat-label { color: #adb5bd; }

        /* Export */
        .export-section {
            background: var(--white);
            padding: 16px;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
        }
        body.dark-mode .export-section { background: #2a2b2c; }
        .export-title { font-weight: 600; margin-bottom: 12px; }
        .export-buttons { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-export {
            padding: 8px 16px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-export:hover { background: #229954; }

        /* Bulk Actions */
        .bulk-actions {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 16px;
            margin-top: 16px;
            display: none;
        }
        body.dark-mode .bulk-actions { background: #3a372c; border-color: #d4af37; }
        .bulk-actions.active { display: block; }
        .bulk-status-form { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }

        /* ========== CANDIDATE VIEWS ========== */
        .view-toggle {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }
        .view-btn {
            padding: 8px 16px;
            background: transparent;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            color: var(--gray);
            cursor: pointer;
            font-weight: 600;
        }
        .view-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        body.dark-mode .view-btn { border-color: #4a4a4c; color: #adb5bd; }
        body.dark-mode .view-btn.active { background: var(--primary); }

        /* Card View */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        .candidate-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
        }
        body.dark-mode .candidate-card { background: #242526; }
        .candidate-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .card-header {
            padding: 20px;
            display: flex;
            gap: 16px;
            border-bottom: 1px solid var(--light-gray);
        }
        body.dark-mode .card-header { border-color: #3a3b3c; }
        .profile-pic {
            width: 70px; height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-gray);
        }
        body.dark-mode .profile-pic { border-color: #3a3b3c; }
        .candidate-info h3 { margin: 0 0 6px 0; font-size: 19px; }
        body.dark-mode .candidate-info h3 { color: #e4e6eb; }
        .candidate-info p { margin: 4px 0; font-size: 14px; color: var(--gray); display: flex; gap: 6px; }
        body.dark-mode .candidate-info p { color: #adb5bd; }
        .card-body { padding: 20px; }
        .professional-title { font-weight: 600; margin-bottom: 12px; color: var(--primary); }
        .meta-row { display: flex; justify-content: space-between; margin-top: 12px; }
        .action-btn {
            padding: 8px 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 400;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            margin: 0;
        }
        .action-btn:hover { background: var(--primary-dark); }
        a.action-btn {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 400;
            gap: 6px;
        }

        /* Table View */
        .candidates-table-container { display: none; margin-top: 24px; }
        .candidates-table-container.active { display: block; }
.table-wrapper {
            background: var(--white);
            margin: 0 -12px;
            padding: 24px 24px 24px 12px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow-x: auto;
            max-width: calc(100vw - 48px);
        }
        body.dark-mode .table-wrapper {
            background: #242526;
        }
        .candidates-table-container.active {
            max-width: 1400px;
            margin: 0 auto;
        }
        body.dark-mode .table-wrapper { background: #242526; }
table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0; 
            min-width: 1100px; 
            font-size: 0.875rem; 
        }
        thead { 
            position: sticky; 
            top: 0; 
            z-index: 10; 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(10px); 
        }
        body.dark-mode thead { 
            background: rgba(36,37,38,0.95); 
        }
        tbody tr:nth-child(even) { 
            background: rgba(0,0,0,0.02); 
        }
        body.dark-mode tbody tr:nth-child(even) { 
            background: rgba(255,255,255,0.03); 
        }
th { 
            padding: 16px 12px 16px 16px; 
            font-weight: 600; 
            font-size: 0.875rem; 
            color: var(--primary); 
            white-space: nowrap; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            border-bottom: 2px solid rgba(102,126,234,0.1); 
        }
        td { 
            padding: 16px 12px; 
            border-bottom: 1px solid rgba(0,0,0,0.05); 
            vertical-align: middle; 
        }
        body.dark-mode td { 
            border-bottom-color: rgba(255,255,255,0.05); 
        }
        .name-cell, .position-cell, .skills-cell, .qual-cell { 
            max-width: 160px; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            white-space: nowrap; 
        }
        .name-cell:hover, .position-cell:hover, .skills-cell:hover, .qual-cell:hover { 
            overflow: visible !important; 
            white-space: normal; 
            background: rgba(102,126,234,0.06); 
            border-radius: 6px; 
            z-index: 5; 
            position: relative; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }
        body.dark-mode th, body.dark-mode td { border-color: #3a3b3c; }
        th { background: rgba(102, 126, 234, 0.08); font-weight: 600; color: var(--primary); }
        body.dark-mode th { background: rgba(102, 126, 234, 0.15); }
tr { 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
            cursor: pointer; 
        }
        tr:hover { 
            background: linear-gradient(90deg, rgba(102,126,234,0.08), rgba(102,126,234,0.04)); 
            transform: scale(1.005); 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
        }
        body.dark-mode tr:hover { 
            background: linear-gradient(90deg, rgba(102,126,234,0.15), rgba(102,126,234,0.08)); 
        }
        .profile-pic {
            border-radius: 50%;
            border: 2px solid var(--light-gray);
            transition: border-color 0.2s ease;
        }
        .profile-pic:hover { border-color: var(--primary); }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            transition: all 0.2s ease;
        }
        .status-badge:hover { transform: scale(1.05); }
        .btn-sm {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-sm::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .btn-sm:hover::before {
            left: 100%;
        }
        .btn-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .btn-sm:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .btn-sm i { font-size: 1rem; }
        .btn-sm.btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        .btn-sm.btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        }
        .btn-sm.btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        .btn-sm.btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        .btn-sm.btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        .btn-sm.btn-success:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }
        .btn-sm.btn-info {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        .btn-sm.btn-info:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }
        .btn-sm.btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        .btn-sm.btn-warning:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
        }

        /* Status Badges */
.status-badge { 
            padding: 4px 10px; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: 600; 
            min-width: 70px; 
            text-align: center; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
            transition: all 0.2s ease; 
        }
        .status-badge:hover { 
            transform: scale(1.05); 
        }
        .btn-group { 
            display: flex; 
            gap: 4px; 
            flex-wrap: wrap; 
        }
        .btn-sm { 
            padding: 6px 10px; 
            font-size: 0.8rem; 
            border-radius: 6px; 
            margin: 0; 
            min-width: 44px; 
            justify-content: center; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.08) !important; 
            transition: all 0.15s ease; 
        }
        .btn-sm:hover { 
            transform: translateY(-1px); 
            box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important; 
        }
        .profile-pic { 
            width: 36px !important; 
            height: 36px !important; 
            cursor: pointer; 
            transition: transform 0.2s ease; 
        }
        .profile-pic:hover { 
            transform: scale(1.1); 
        }
        .status-submitted { background: #e3f2fd; color: #1565c0; }
        .status-under-review { background: #fff8e1; color: #f57f17; }
        .status-shortlisted { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }
        .status-hired { background: #e8f5f2; color: #00695c; }
        /* Dark mode badges */
        body.dark-mode .status-submitted { background: #1a237e; color: #bbdefb; }
        body.dark-mode .status-under-review { background: #f57c00; color: #fff; }
        body.dark-mode .status-shortlisted { background: #1b5e20; color: #c8e6c9; }
        body.dark-mode .status-rejected { background: #b71c1c; color: #ffcdd2; }
        body.dark-mode .status-hired { background: #004d40; color: #b2dfdb; }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background: var(--white);
            margin: 10% auto;
            padding: 24px;
            border-radius: 12px;
            width: 600px;
            max-width: 90%;
            max-height: 70vh;
            overflow-y: auto;
            position: relative;
        }
        body.dark-mode .modal-content { background: #2a2b2c; }
        .close {
            position: absolute; top: 16px; right: 16px;
            font-size: 28px; cursor: pointer;
            color: var(--gray);
        }
        body.dark-mode .close { color: #adb5bd; }
        .modal h2 { margin-bottom: 16px; }
        .modal p { line-height: 1.6; }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: rgba(0,0,0,0.85);
            color: #fff;
            padding: 12px 18px;
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
            z-index: 2000;
            max-width: 320px;
            font-weight: 600;
        }
        .toast.success { background: linear-gradient(90deg,#10b981,#059669); }
        .toast.error { background: linear-gradient(90deg,#ef4444,#b91c1c); }

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

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .logo-name span, .side-menu li a span { display: none; }
            .side-menu li a { justify-content: center; padding: 16px; }
            .content { margin-left: 80px; }
            .insights { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .header { flex-direction: column; align-items: flex-start; }
            .filters-grid { grid-template-columns: 1fr; }
            .filter-buttons, .export-buttons, .bulk-status-form { flex-direction: column; }
            .btn, .btn-export { width: 100%; justify-content: center; }
            .candidates-grid { grid-template-columns: 1fr; }
            .insights { grid-template-columns: 1fr; }
            nav { padding: 16px; }
        }
@media (max-width: 1200px) {
            table { min-width: 1000px; font-size: 0.8rem; }
            th, td { padding-left: 8px; padding-right: 8px; }
            .btn-sm { font-size: 0.75rem; padding: 5px 8px; }
        }
        @media (max-width: 768px) {
            .table-wrapper { padding: 16px 12px; margin: 0 -8px; }
            table { min-width: 700px; font-size: 0.75rem; }
            th, td { padding: 12px 6px !important; }
            .btn-group { flex-direction: column; gap: 2px; }
            .btn-sm { min-width: 36px; padding: 4px 6px; font-size: 0.7rem; }
            .status-badge { font-size: 0.7rem; padding: 3px 8px; min-width: 60px; }
        }
        @media (max-width: 480px) {
            .header h2 { font-size: 24px; }
            .card-header { flex-direction: column; text-align: center; }
            .profile-pic { width: 80px; height: 80px; }
            .candidate-info h3 { font-size: 20px; }
            .action-btn { width: 100%; justify-content: center; }
            .modal-content { width: 95%; margin: 20% auto; }
            .table-wrapper { padding: 12px 8px; }
            table { min-width: 600px; }
        }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            
        }

        
        body.dark-mode .sidebar {
            background: var(--dark);
        }

        /* ========== NEW FILTER STYLES ========== */
        .filter-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--danger);
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            min-width: 22px;
            height: 22px;
            border-radius: 11px;
            padding: 0 6px;
            margin-left: 8px;
        }
        .filter-with-clear {
            position: relative;
        }
        .clear-filter {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
            font-size: 18px;
            display: none;
            padding: 4px;
            line-height: 1;
        }
        .clear-filter:hover {
            color: var(--danger);
        }
        .filter-with-clear input:not(:placeholder-shown) + .clear-filter {
            display: block;
        }
        .secondary-filters {
            margin-top: 8px;
            padding-top: 16px;
            border-top: 1px dashed var(--light-gray);
        }
        .filter-presets {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 16px;
            padding: 12px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 8px;
        }
        .preset-label {
            font-weight: 600;
            color: var(--gray);
            font-size: 0.9rem;
        }
        .preset-btn {
            padding: 6px 12px;
            background: var(--white);
            border: 1px solid var(--primary);
            color: var(--primary);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        .preset-btn:hover {
            background: var(--primary);
            color: white;
        }
        body.dark-mode .preset-btn {
            background: #3a3b3c;
            border-color: var(--primary);
            color: var(--primary);
        }
        body.dark-mode .preset-btn:hover {
            background: var(--primary);
            color: white;
        }
        select[multiple] {
            height: auto !important;
            min-height: 42px;
        }
    </style>
</head>
<body>
    <script>
        (function() {
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark') document.body.classList.add('dark-mode');

            // Show stored evaluation summary on page load
            const storedSummary = localStorage.getItem('evaluationSummary');
            if (storedSummary) {
                const summaryDiv = document.getElementById('evaluationSummary');
                const summaryText = document.getElementById('summaryText');
                summaryText.textContent = storedSummary;
                summaryDiv.style.display = 'block';
            }
        })();
    </script>

    <!-- Mobile Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;"></div>

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
            <li class="active"><a href="manage_applications.php"><i class='bx bx-file'></i><span>Applications</span></a></li>
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

    <!-- Main Content -->
    <div class="content">
        <!-- Mobile Nav Links -->
            <div class="mobile-nav-links">
                <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
                <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Manage Jobs</a>
                <a class="active" href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
                <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
                <a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a>
                <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
                <a href="admin_client_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>
                <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
                <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
            </div>

        <main>
            <div class="welcome-section">
                <h1> Manage Applications</h1>
                <p>Access submitted applications, review candidate profiles and resumes, update statuses, and coordinate next steps in the recruitment process.</p>
            </div>

            <!-- Insights -->
            <ul class="insights">
                <li>
                    <i class='bx bx-group'></i>
                    <span class="info">
                        <h3><?= $total_candidates ?></h3>
                        <p>Total Candidates</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-male-female'></i>
                    <span class="info">
                        <h3><?= $gender_stats['male'] ?></h3>
                        <p>Male</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-female'></i>
                    <span class="info">
                        <h3><?= $gender_stats['female'] ?></h3>
                        <p>Female</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-user'></i>
                    <span class="info">
                        <h3><?= $gender_stats['other'] ?></h3>
                        <p>Other / N/A</p>
                    </span>
                </li>
                <?php foreach (array_slice($position_stats, 0, 3) as $index => $stat): ?>
                <li>
                    <i class='bx bx-briefcase'></i>
                    <span class="info">
                        <h3><?= $stat['app_count'] ?></h3>
                        <p><?= htmlspecialchars($stat['position']) ?> (<?= $index + 1 ?>st)</p>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Filters -->
            <div class="filter-section">
                <div class="filter-title">
                    <i class='bx bx-filter-alt'></i> Advanced Filters
                    <span class="filter-count-badge" id="filterCountBadge">0</span>
                </div>
                <form method="GET" id="filterForm">
                    <!-- Primary Filters Row -->
                    <div class="filters-grid">
                        <div class="filter-group filter-with-clear">
                            <label>Search (Name, Email, Position)</label>
                            <input type="text" name="search" id="searchInput" placeholder="e.g. John, john@email.com, Developer" value="<?= htmlspecialchars($search) ?>">
                            <span class="clear-filter" onclick="clearFilter('search')" title="Clear">&times;</span>
                        </div>
                        <div class="filter-group">
                            <label>Position</label>
                            <select name="position">
                                <option value="">All Positions</option>
                                <?php foreach ($positions as $p): ?>
                                    <option value="<?= $p['job_id'] ?>" <?= $position_filter == $p['job_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['position']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

<div class="filter-group">
                            <label>Status</label>
                            <select name="status" id="statusSelect">
                                <option value="">All Statuses</option>
                                <?php foreach ($available_statuses as $s): ?>
                                    <option value="<?= $s ?>" <?= $status_filter == $s ? 'selected' : '' ?>>
                                        <?= $s ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="">All Genders</option>
                                <?php foreach ($genders as $g): ?>
                                    <option value="<?= $g ?>" <?= $gender_filter == $g ? 'selected' : '' ?>>
                                        <?= $g ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Qualification Level</label>
                            <select name="qualification_level">
                                <option value="">All Levels</option>
                                <option value="Matric" <?= $qualification_level == 'Matric' ? 'selected' : '' ?>>Matric / High School</option>
                                <option value="Certificate" <?= $qualification_level == 'Certificate' ? 'selected' : '' ?>>Certificate</option>
                                <option value="Diploma" <?= $qualification_level == 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                                <option value="Bachelor's Degree" <?= $qualification_level == "Bachelor's Degree" ? 'selected' : '' ?>>Bachelor's Degree</option>
                                <option value="Postgraduate" <?= $qualification_level == 'Postgraduate' ? 'selected' : '' ?>>Postgraduate (Honours / Masters)</option>
                                <option value="Doctorate" <?= $qualification_level == 'Doctorate' ? 'selected' : '' ?>>Doctorate / PhD</option>
                            </select>
                        </div>
                        <div class="filter-group filter-with-clear">
                            <label>Qualification Name</label>
                            <input type="text" name="qualification_name" placeholder="e.g. Information Technology" value="<?= htmlspecialchars($qualification_name_filter) ?>">
                            <span class="clear-filter" onclick="clearFilter('qualification_name')" title="Clear">&times;</span>
                        </div>
                        <div class="filter-group filter-with-clear">
                            <label>Skills (Tech/Soft)</label>
                            <input type="text" name="skills" placeholder="e.g. Python, Leadership" value="<?= htmlspecialchars($skills_filter) ?>">
                            <span class="clear-filter" onclick="clearFilter('skills')" title="Clear">&times;</span>
                        </div>
                    </div>
                    
                    <!-- Secondary Filters Row - Date Range, Sort, Experience -->
                    <div class="filters-grid secondary-filters">
                        <div class="filter-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="filter-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select name="sort_by">
                                <option value="fullname_asc" <?= $sort_by == 'fullname_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                                <option value="fullname_desc" <?= $sort_by == 'fullname_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                                <option value="date_newest" <?= $sort_by == 'date_newest' ? 'selected' : '' ?>>Date (Newest)</option>
                                <option value="date_oldest" <?= $sort_by == 'date_oldest' ? 'selected' : '' ?>>Date (Oldest)</option>
                                <option value="status_asc" <?= $sort_by == 'status_asc' ? 'selected' : '' ?>>Status</option>
                                <option value="experience_desc" <?= $sort_by == 'experience_desc' ? 'selected' : '' ?>>Experience (High to Low)</option>
                                <option value="experience_asc" <?= $sort_by == 'experience_asc' ? 'selected' : '' ?>>Experience (Low to High)</option>
                            </select>
                        </div>
                        <div class="filter-group">
<label>Experience Level</label>
                            <select name="experience_range">
                                <option value="">Any</option>
                                <option value="0-1" <?= $experience_range == '0-1' ? 'selected' : '' ?>>0-1 Years</option>
                                <option value="1-2" <?= $experience_range == '1-2' ? 'selected' : '' ?>>1-2 Years</option>
                                <option value="2-3" <?= $experience_range == '2-3' ? 'selected' : '' ?>>2-3 Years</option>
                                <option value="3-5" <?= $experience_range == '3-5' ? 'selected' : '' ?>>3-5 Years</option>
                                <option value="5+" <?= $experience_range == '5+' ? 'selected' : '' ?>>5+ Years</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Province</label>
                            <select name="province">
                                <option value="">All Provinces</option>
                                <option value="Gauteng" <?= $province_filter == 'Gauteng' ? 'selected' : '' ?>>Gauteng</option>
                                <option value="Western Cape" <?= $province_filter == 'Western Cape' ? 'selected' : '' ?>>Western Cape</option>
                                <option value="KwaZulu-Natal" <?= $province_filter == 'KwaZulu-Natal' ? 'selected' : '' ?>>KwaZulu-Natal</option>
                                <option value="Eastern Cape" <?= $province_filter == 'Eastern Cape' ? 'selected' : '' ?>>Eastern Cape</option>
                                <option value="Free State" <?= $province_filter == 'Free State' ? 'selected' : '' ?>>Free State</option>
                                <option value="Limpopo" <?= $province_filter == 'Limpopo' ? 'selected' : '' ?>>Limpopo</option>
                                <option value="Mpumalanga" <?= $province_filter == 'Mpumalanga' ? 'selected' : '' ?>>Mpumalanga</option>
                                <option value="Northern Cape" <?= $province_filter == 'Northern Cape' ? 'selected' : '' ?>>Northern Cape</option>
                                <option value="North West" <?= $province_filter == 'North West' ? 'selected' : '' ?>>North West</option>
                            </select>
                        </div>
                        <div class="filter-group filter-with-clear">
                            <label>City/Town</label>
                            <input type="text" name="city" placeholder="e.g. Johannesburg, Cape Town, Durban" value="<?= htmlspecialchars($city_filter) ?>">
                            <span class="clear-filter" onclick="clearFilter('city')" title="Clear">&times;</span>
                        </div>
                        <div class="filter-group">
                            <label>Age Range</label>
                            <select name="age_range">
                                <option value="">Any</option>
                                <option value="18-25" <?= $age_range == '18-25' ? 'selected' : '' ?>>18-25</option>
                                <option value="26-35" <?= $age_range == '26-35' ? 'selected' : '' ?>>26-35</option>
                                <option value="36-45" <?= $age_range == '36-45' ? 'selected' : '' ?>>36-45</option>
                                <option value="45+" <?= $age_range == '45+' ? 'selected' : '' ?>>45+</option>
                            </select>
                        </div>
                    </div>

                    <!-- Quick Filter Presets -->
                    <div class="filter-presets">
                        <span class="preset-label"><i class='bx bx-bookmark'></i> Quick Filters:</span>
                        <button type="button" class="preset-btn" onclick="applyPreset('submitted')">Submitted Today</button>
                        <button type="button" class="preset-btn" onclick="applyPreset('shortlisted')">Shortlisted Only</button>
                        <button type="button" class="preset-btn" onclick="applyPreset('rejected')">Rejected Only</button>
                        <button type="button" class="preset-btn" onclick="applyPreset('under_review')">Under Review</button>
                        <button type="button" class="preset-btn" onclick="applyPreset('hired')">Hired</button>
                    </div>

                    <div class="filter-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-check-circle'></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-outline" onclick="resetFilters()">
                            <i class='bx bx-reset'></i> Reset All
                        </button>
                        <button type="button" class="btn btn-primary" onclick="autoEvaluateAll()">
                            <i class='bx bx-rocket'></i> Auto-Evaluate All Applications
                        </button>
                    </div>
                    <div class="filter-stats">
                        <i class='bx bx-info-circle'></i> <strong><?= $result->num_rows ?></strong> application(s) found
                    </div>

                    <!-- Active Filters -->
                    <?php
                    $active = [];
                    // Existing filters
                    foreach (['search', 'status', 'gender', 'position', 'qualification', 'skills'] as $f) {
                        if (!empty(${$f.'_filter'} ?? $search)) {
                            $val = ${$f.'_filter'} ?? $search;
                            $label = ucfirst(str_replace('_', ' ', $f));
                            if ($f === 'position') {
                                $p = $conn->query("SELECT position FROM job_postings WHERE job_id = '$val'")->fetch_assoc();
                                $val = $p['position'] ?? $val;
                            }
                            $active[] = [$label, $val, $f];
                        }
                    }
                    // New filters: age, province, city, qualification_name, qualification_level, experience
                    if (!empty($age_range)) {
                        $active[] = ['Age', $age_range, 'age_range'];
                    }
                    if (!empty($province_filter)) {
                        $active[] = ['Province', $province_filter, 'province'];
                    }
                    if (!empty($city_filter)) {
                        $active[] = ['City', $city_filter, 'city'];
                    }
                    if (!empty($qualification_name_filter)) {
                        $active[] = ['Qualification Name', $qualification_name_filter, 'qualification_name'];
                    }
                    if (!empty($qualification_level)) {
                        $active[] = ['Qualification Level', $qualification_level, 'qualification_level'];
                    }
                    if (!empty($experience_range)) {
                        $active[] = ['Experience', $experience_range, 'experience_range'];
                    }

                    // Date range (combine from/to)
                    if (!empty($date_from) || !empty($date_to)) {
                        $date_val = '';
                        if (!empty($date_from)) $date_val .= 'From: ' . date('M j, Y', strtotime($date_from));
                        if (!empty($date_to)) $date_val .= (!empty($date_from) ? ' To: ' : 'To: ') . date('M j, Y', strtotime($date_to));
                        $active[] = ['Date Range', $date_val, 'date_from'];  // Use date_from as key for clearing both
                    }

                    ?>
                    <?php if (!empty($active)): ?>
                    <div class="active-filters">
                        <?php foreach ($active as [$label, $val, $key]): ?>
                            <span class="active-filter-tag">
                                <?= $label ?>: <strong><?= htmlspecialchars($val) ?></strong>
                                <?php 
                                $clear_params = array_diff_key($_GET, [$key => '']);
                                if ($key === 'date_from') {
                                    $clear_params['date_to'] = '';  // Clear both date fields
                                }
                                ?>
                                <a href="?<?= http_build_query($clear_params) ?>" style="color:white;text-decoration:none;">
                                    <i class='bx bx-x'></i>
                                </a>

                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </form>

                <!-- Advanced -->
                <div class="advanced-options">
                    <div class="advanced-title" onclick="toggleAdvanced(this)">
                        <i class='bx bx-chevron-right'></i> Advanced Options & Analytics
                    </div>
                    <div class="advanced-content" id="advancedContent">
                        <!-- Stats -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?= $result->num_rows ?></div>
                                <div class="stat-label">Filtered</div>
                            </div>
                            <?php
$statMap = [
    'Submitted' => 'submitted-count',
    'Hired' => 'hired-count',
    'Shortlisted' => 'shortlisted-count',
    'Under Review' => 'review-count',
    'Rejected' => 'rejected-count'
];
                            foreach ($statMap as $label => $id): 
                                $count = 0;
                                foreach ($status_stats as $s) {
                                    if ($s['application_status'] === $label) $count = $s['count'];
                                }
                            ?>
                            <div class="stat-card">
                                <div class="stat-value"><?= $count ?></div>
                                <div class="stat-label"><?= $label ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Export -->
                        <div class="export-section">
                            <div class="export-title">Export Applications</div>
                            <div class="export-buttons">
                                <a href="export_applications_csv.php?<?= http_build_query($_GET) ?>" class="btn-export">
                                    <i class='bx bx-file'></i> Export CSV
                                </a>
                                <a href="export_applications_pdf.php?<?= http_build_query($_GET) ?>" class="btn-export">
                                    <i class='bx bx-file-pdf'></i> Export PDF
                                </a>
                            </div>
                        </div>

                        <!-- Bulk -->
                        <div class="bulk-actions" id="bulkActions">
                            <div style="margin-bottom:12px;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"> 
                                <label for="selectAll">Select All Candidates</label>
                            </div>
                            <div class="bulk-status-form">
                                <label>Update Status:</label>
                                <select id="bulkStatus">
                                    <option value="">Choose...</option>
                                    <?php foreach ($available_statuses as $s): ?>
                                        <option><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary" onclick="applyBulkStatus()">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View Toggle -->
            <div class="view-toggle">
                <button class="view-btn active" data-view="cards">Card View</button>
                <button class="view-btn" data-view="table">Table View</button>
            </div>

            <!-- Card View -->
            <div class="candidates-grid" id="cardView">
                <?php 
                $result->data_seek(0); 
                if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="candidate-card" data-application-id="<?= $row['application_id'] ?>">
                            <div class="card-header">
                                <img src="<?= !empty($row['profile_picture']) 
                                    ? 'data:image/jpeg;base64,'.base64_encode($row['profile_picture']) 
                                    : 'img/default_photo.jpg' ?>" 
                                    alt="Profile" class="profile-pic"
                                    onerror="this.src='img/default_photo.jpg'">
                                <div class="candidate-info">
                                <?php 
                                $snapshot_profile = $row['profile_data'] ? json_decode($row['profile_data'], true) : [];
$snapshot_fullname = $snapshot_profile['fullname'] ?? 'N/A';
                                ?>
                                <h3><?= htmlspecialchars($snapshot_fullname) ?></h3>
<p><i class='bx bx-envelope'></i> <?= htmlspecialchars($snapshot_profile['email'] ?? $row['email'] ?? '—') ?></p>
<p><i class='bx bx-phone'></i> <?= htmlspecialchars($snapshot_profile['phone'] ?? $row['phone'] ?? '—') ?></p>
                                <p><i class='bx bx-calendar'></i> <?= ($snapshot_profile['dob'] ?? $row['dob']) ? date('M d, Y', strtotime($snapshot_profile['dob'] ?? $row['dob'])) : '—' ?></p>
                                <?php if ($row['profile_data']): ?>
                                <p style="font-size: 0.85rem; color: var(--gray); font-style: italic;">
                                    <i class='bx bx-camera'></i> Snapshot from <?= date('M j, Y', strtotime($row['submission_date'])) ?>
                                </p>
                                <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($row['professional_title'])): ?>
                                    <div class="professional-title">
                                        <i class='bx bx-briefcase'></i> <?= htmlspecialchars($snapshot_profile['professional_title'] ?? $row['professional_title'] ?? '') ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($row['position'])): ?>
                                    <p><strong>Applied for:</strong> <?= htmlspecialchars($row['position']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($row['application_status'])): ?>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $row['application_status'])) ?>">
                                        <?= htmlspecialchars($row['application_status']) ?>
                                    </span>
                                <?php endif; ?>
                                <div class="meta-row">
                                <button class="action-btn" 
onclick="viewSkills(<?= htmlspecialchars(json_encode([
                                            'snapshot_tech' => $snapshot_profile['technical_skills'] ?? $snapshot_profile['tech_skills'] ?? null,
                                            'snapshot_soft' => $snapshot_profile['soft_skills'] ?? $snapshot_profile['soft_skills_list'] ?? null,
                                            'tech' => $row['technical_skills'],
                                            'soft' => $row['soft_skills']
                                        ])) ?>)"
                                        <i class='bx bx-show'></i> Skills
                                    </button>
                                    <button class="action-btn" onclick="evaluateApplication(<?= $row['application_id'] ?>, this)">
                                        <i class='bx bx-rocket'></i> Evaluate
                                    </button>
                                    <a href="view_applicant_profile.php?user_id=<?= $row['user_id'] ?>&application_id=<?= $row['application_id'] ?>" class="action-btn">
                                        <i class='bx bx-user'></i> Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--gray);">
                        <i class='bx bx-user-x' style="font-size:64px;margin-bottom:16px;"></i>
                        <h3>No applications found</h3>
                        <p>Try adjusting your filters.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table View -->
            <div class="candidates-table-container" id="tableView">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Picture</th>
                            <th class="name-col">Name</th>
                                <th class="email-col">Email</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Qualification</th>
                                <th>CV</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $result->data_seek(0); 
                            while ($row = $result->fetch_assoc()): 
                                $snapshot_profile = $row['profile_data'] ? json_decode($row['profile_data'], true) : [];
                                // Enhanced snapshot qualification detection (level first)
                                $snapshot_qual_level = '';
                                $snapshot_qual_name = '';
                                $snapshot_display = '';
                                
                                if ($snapshot_profile) {
                                    // Possible snapshot level keys
                                    $level_keys = ['qualification_level', 'level', 'degree_level', 'qual_level'];
                                    foreach ($level_keys as $key) {
                                        if (isset($snapshot_profile[$key]) && !empty($snapshot_profile[$key])) {
                                            $snapshot_qual_level = $snapshot_profile[$key];
                                            break;
                                        }
                                    }
                                    
                                    // Possible snapshot name keys (existing + more)
                                    $name_keys = ['qualification_name', 'qualifications', 'highest_qualification', 'qualification', 'degree', 'education', 'qual_name'];
                                    foreach ($name_keys as $key) {
                                        if (isset($snapshot_profile[$key]) && !empty($snapshot_profile[$key])) {
                                            $snapshot_qual_name = $snapshot_profile[$key];
                                            break;
                                        }
                                    }
                                }
                                
                                // Format snapshot display: Level - Name (if both) or just name/level
                                if (!empty($snapshot_qual_level) && !empty($snapshot_qual_name)) {
                                    $snapshot_display = $snapshot_qual_level . ' - ' . $snapshot_qual_name;
                                } elseif (!empty($snapshot_qual_level)) {
                                    $snapshot_display = $snapshot_qual_level;
                                } elseif (!empty($snapshot_qual_name)) {
                                    $snapshot_display = $snapshot_qual_name;
                                }
                                
                                // DB fallback: levels first, then names
                                $db_levels = $row['qualification_levels'] ?? '';
                                $db_names = $row['qualification_names'] ?? '';
                                $db_display = '';
                                if (!empty($db_levels) && !empty($db_names)) {
                                    $db_display = $db_levels . ' - ' . $db_names;
                                } elseif (!empty($db_levels)) {
                                    $db_display = $db_levels;
                                } elseif (!empty($db_names)) {
                                    $db_display = $db_names;
                                } else {
                                    $db_display = '—';
                                }
                                
                                $display_qual = $snapshot_display ?: $db_display;
                            ?>
                                <tr data-application-id="<?= $row['application_id'] ?>">  
                                    <td><input type="checkbox" class="bulk-checkbox"></td>
                                    <td>
                                        <img src="<?= !empty($row['profile_picture']) 
                                            ? 'data:image/jpeg;base64,'.base64_encode($row['profile_picture']) 
                                            : 'img/default_photo.jpg' ?>" 
                                            alt="Profile" width="40" height="40" class="profile-pic"
                                            style="border-radius:50%;">
                                    </td>
                                    <td class="name-cell"><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td class="email-cell" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($row['email']) ?></td>
                                    <td class="position-cell"><?= htmlspecialchars($row['position'] ?? '—') ?></td>
                                    <td>
                                        <?php if (!empty($row['application_status'])): ?>
                                            <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $row['application_status'])) ?>">
                                                <?= htmlspecialchars($row['application_status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="qual-cell"><?= htmlspecialchars($display_qual) ?></td>
                                    <td style="white-space: nowrap;">
                                        <?php if (!empty($row['cv'])): ?>
                                            <div class="btn-group">
                                                <a href="download_cv.php?user_id=<?= $row['user_id'] ?>" class="btn btn-sm btn-success">
                                                    <i class='bx bx-download' style='font-size: 1.2rem; margin-right: 4px;'></i>Download
                                                </a>
                                                <a href="view_cv.php?user_id=<?= $row['user_id'] ?>" class="btn btn-sm btn-info">
                                                    <i class='bx bx-eye' style='font-size: 1.2rem; margin-right: 4px;'></i>View
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--gray); font-style: italic;">No CV</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-primary" onclick="evaluateApplication(<?= $row['application_id'] ?>, this);" title="Auto-Evaluate">
                                                <i class='bx bx-rocket'></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="updateStatus(<?= $row['application_id'] ?>, '<?= htmlspecialchars($row['application_status']) ?>', this);" title="Status">
                                                <i class='bx bx-edit-alt'></i>
                                            </button>
                                            <a href="view_applicant_profile.php?user_id=<?= $row['user_id'] ?>&application_id=<?= $row['application_id'] ?>" class="btn btn-sm btn-outline" title="View Profile">
                                                <i class='bx bx-user'></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Leave Requests Management Section -->
        <div class="jobs-wrapper" id="leaveRequestsSection" style="margin-top: 40px; display: none;">
            <div class="page-header">
                <i class='bx bx-calendar-minus'></i>
                <h3>Leave Requests Management</h3>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <span class="status-filter">
                        <select id="statusFilter" class="btn btn-sm">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </span>
                    <a href="#" id="hideLeaveRequests" class="btn btn-sm btn-danger">
                        <i class='bx bx-x'></i> Close
                    </a>
                </div>
            </div>

            <div class="leave-stats">
                <div class="stat-item" style="background: #fef3c7; border-left: 4px solid #f59e0b;">
                    <strong><?php echo $pending_leave_count; ?></strong> Pending
                </div>
                <div class="stat-item" style="background: #d1fae5; border-left: 4px solid #10b981;">
                    <strong><?php echo $leave_counts['approved_leave_count']; ?></strong> Approved
                </div>
                <div class="stat-item" style="background: #fee2e2; border-left: 4px solid #ef4444;">
                    <strong><?php echo $leave_counts['rejected_leave_count']; ?></strong> Rejected
                </div>
            </div>

            <div class="table-responsive">
                <?php
                if ($leave_result && $leave_result->num_rows > 0) {
                    echo "<table id='leaveRequestsTable' class='responsive-table'>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>";
                    while ($row = $leave_result->fetch_assoc()) {
                        $start_date = new DateTime($row['start_date']);
                        $end_date = new DateTime($row['end_date']);
                        $duration = $start_date->diff($end_date)->days + 1;

                        echo "<tr data-status='" . strtolower($row['status']) . "'>
                                <td>
                                    <div style='display: flex; align-items: center; gap: 8px;'>
                                        <i class='bx bx-user-circle' style='font-size: 24px; color: #667eea;'></i>
                                        <div>
                                            <strong>" . htmlspecialchars($row['fullname']) . "</strong><br>
                                            <small style='color: #666;'>" . htmlspecialchars($row['email']) . "</small>
                                        </div>
                                    </div>
                                </td>
                                <td>" . htmlspecialchars($row['leave_type']) . "</td>
                                <td>" . $start_date->format('M d, Y') . "</td>
                                <td>" . $end_date->format('M d, Y') . "</td>
                                <td><strong>" . $duration . "</strong> day" . ($duration > 1 ? 's' : '') . "</td>
                                <td>
                                    <span class='status-badge status-" . strtolower($row['status']) . "'>" . htmlspecialchars($row['status']) . "</span>
                                </td>
                                <td>
                                    <div style='display: flex; gap: 5px; flex-wrap: wrap;'>
                                        <a href='view_leave_request.php?id=" . $row['consult_leave_id'] . "' class='btn btn-sm'>
                                            <i class='bx bx-show'></i> View
                                        </a>";

                        if ($row['status'] == 'Pending') {
                            echo "      <a href='approve_leave.php?id=" . $row['consult_leave_id'] . "' class='btn btn-sm' style='background-color: #10b981;'>
                                            <i class='bx bx-check'></i> Approve
                                        </a>
                                        <a href='reject_leave.php?id=" . $row['consult_leave_id'] . "' class='btn btn-sm btn-danger'>
                                            <i class='bx bx-x'></i> Reject
                                        </a>";
                        }

                        echo "      </div>
                                </td>
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<div style='text-align: center; padding: 40px; color: #666;'>
                            <i class='bx bx-calendar-x' style='font-size: 48px; margin-bottom: 15px;'></i>
                            <p>No leave requests found.</p>
                          </div>";
                }
                ?>
            </div>
        </div>

        <!-- Evaluation Summary -->
        <div id="evaluationSummary" style="display:none;margin-top:24px;padding:20px;background:#e8f5e9;border:1px solid #4caf50;border-radius:8px;text-align:center;">
            <h3 style="color:#2e7d32;margin-bottom:10px;">Evaluation Summary</h3>
            <p id="summaryText" style="font-size:16px;color:#333;"></p>
        </div>
    </div>

    <!-- Skills Modal -->
    <div class="modal" id="skillsModal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('skillsModal').style.display='none'">&times;</span>
            <h2>Candidate Skills</h2>
            <div id="skillsContent"></div>
        </div>
    </div>

    <!-- Evaluation Modal -->
    <div class="modal" id="evalModal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('evalModal').style.display='none'">&times;</span>
            <div id="evalContent"></div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal" id="statusModal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('statusModal').style.display='none'">&times;</span>
            <h2>Update Application Status</h2>
            <div id="statusContent">
                <form id="statusForm">
                    <div style="margin-bottom: 20px;">
                        <label for="newStatus" style="display: block; margin-bottom: 8px; font-weight: 600;">New Status:</label>
                        <select id="newStatus" name="newStatus" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px;">
                            <option value="">Select Status</option>
                            <?php foreach ($available_statuses as $s): ?>
                                <option value="<?= $s ?>"><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="text-align: right;">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('statusModal').style.display='none';">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="margin-left: 10px;">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast" style="display:none;"></div>

    <script>
        // Mobile Menu
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            const sb = document.getElementById('sidebar');
            sb.classList.toggle('active');
            document.getElementById('mobileMenuOverlay').style.display = sb.classList.contains('active') ? 'block' : 'none';
        });
        document.getElementById('mobileMenuOverlay')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('active');
            this.style.display = 'none';
        });

        // Tablet Collapse
        window.addEventListener('resize', () => {
            const sb = document.getElementById('sidebar');
            if (window.innerWidth <= 992 && window.innerWidth > 768) {
                sb.classList.add('collapsed');
            } else {
                sb.classList.remove('collapsed');
            }
        });
        window.dispatchEvent(new Event('resize'));

        // Modal close on overlay click and ESC key
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => modal.style.display = 'none');
            }
        });

        // Toast helper
        function showToast(msg, type = 'success', timeout = 4000) {
            const t = document.getElementById('toast');
            t.className = 'toast ' + (type === 'error' ? 'error' : 'success');
            t.textContent = msg;
            t.style.display = 'block';
            setTimeout(() => { t.style.display = 'none'; }, timeout);
        }

        // View Toggle
        let currentView = localStorage.getItem('currentView') || 'cards';
        
        // Set initial view and active button
        function setView(view) {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            const activeBtn = document.querySelector(`[data-view="${view}"]`);
            if (activeBtn) activeBtn.classList.add('active');
            
            document.getElementById('cardView').style.display = view === 'cards' ? 'grid' : 'none';
            document.getElementById('tableView').style.display = view === 'table' ? 'block' : 'none';
            
            localStorage.setItem('currentView', view);
        }
        
        // Apply initial view
        setView(currentView);
        
        // View toggle buttons
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.view;
                setView(view);
            });
        });
        
        // Restore view after form submission (page reload)
        window.addEventListener('load', () => {
            const restoredView = localStorage.getItem('currentView') || 'cards';
            setView(restoredView);
        });

        function autoEvaluateAll() {
            const appIds = window.filteredAppIds || [];
            if (appIds.length === 0) {
                alert('No applications match your current filters.');
                return;
            }
            if (!confirm(`⚠️ Auto-evaluate ${appIds.length} filtered application(s)?`)) return;
            
            const formData = new FormData();
            formData.append('app_ids', JSON.stringify(appIds));
            
            fetch('auto_evaluate.php', { 
                method: 'POST', 
                body: formData 
            })
                .then(res => res.text().then(text => ({ ok: res.ok, status: res.status, text })))
                .then(obj => {
                    let data;
                    try {
                        data = JSON.parse(obj.text);
                    } catch (e) {
                        // Show raw response to help debugging
                        alert('❌ Invalid JSON response from server:\n' + obj.text);
                        return;
                    }
                    if (!obj.ok) {
                        const msg = data.error || data.message || JSON.stringify(data);
                        alert('❌ Server error: ' + msg);
                        return;
                    }
                    // Update stats dynamically
                    const statCards = document.querySelectorAll('.stat-card');
                    statCards.forEach(card => {
                        const label = card.querySelector('.stat-label').textContent;
                        if (label === 'Shortlisted') {
                            card.querySelector('.stat-value').textContent = data.shortlisted || 0;
                        } else if (label === 'Rejected') {
                            card.querySelector('.stat-value').textContent = data.rejected || 0;
                        }
                    });

                    // Update individual application statuses
                    if (data.updates && Array.isArray(data.updates)) {
                        data.updates.forEach(update => {
                            const appId = update.application_id;
                            const newStatus = update.status;
                            // Update in card view
                            const card = document.querySelector(`.candidate-card[data-application-id="${appId}"]`);
                            if (card) {
                                const badge = card.querySelector('.status-badge');
                                if (badge) {
                                    badge.textContent = newStatus;
                                    badge.className = 'status-badge status-' + newStatus.toLowerCase().replace(/\s+/g, '-');
                                }
                            }
                            // Update in table view
                            const row = document.querySelector(`tr[data-application-id="${appId}"]`);
                            if (row) {
                                const badge = row.querySelector('.status-badge');
                                if (badge) {
                                    badge.textContent = newStatus;
                                    badge.className = 'status-badge status-' + newStatus.toLowerCase().replace(/\s+/g, '-');
                                }
                            }
                        });
                    }

                    // Show summary at bottom
                    const summaryDiv = document.getElementById('evaluationSummary');
                    const summaryText = document.getElementById('summaryText');
                    const summaryContent = `${data.shortlisted || 0} applications shortlisted, ${data.rejected || 0} applications rejected${data.skipped ? ', ' + data.skipped + ' skipped' : ''}${data.errors && data.errors.length ? ', Errors: ' + data.errors.join('; ') : ''}`;
                    summaryText.textContent = summaryContent;
                    summaryDiv.style.display = 'block';

                    // Store in localStorage to persist across page loads
                    localStorage.setItem('evaluationSummary', summaryContent);

                    // Show toast with summary including email status
                    const parts = [];
                    parts.push(`${data.shortlisted || 0} shortlisted`);
                    parts.push(`${data.rejected || 0} rejected`);
                    if (data.skipped) parts.push(`${data.skipped} skipped`);
                    if (data.errors && data.errors.length) parts.push('Errors: ' + data.errors.join('; '));
                    showToast('Evaluation complete: ' + parts.join(', ') + '. Email notifications sent.', 'success');
                })
                .catch(err => {
                    console.error('Auto-evaluate fetch failed', err);
                    if (!navigator.onLine) {
                        alert('❌ Failed: Network offline. Check your connection.');
                        return;
                    }
                    // Try pinging endpoint to check server reachability
                    fetch('auto_evaluate.php?ping=1')
                        .then(r => r.json())
                        .then(p => {
                            if (p && p.ok === true) {
                                alert('❌ Failed: Request blocked or server returned an error. Check server logs (see console) for details.');
                            } else {
                                alert('❌ Failed: Could not reach auto_evaluate.php; server may be down or inaccessible.');
                            }
                        })
                        .catch(pingErr => {
                            console.error('Ping failed', pingErr);
                            alert('❌ Failed: ' + pingErr + '\nOpen devtools console for details.');
                        });
                });
        }
        // Advanced Toggle
        function toggleAdvanced(el) {
            const content = el.nextElementSibling;
            const icon = el.querySelector('i');
            content.classList.toggle('active');
            icon.style.transform = content.classList.contains('active') ? 'rotate(90deg)' : 'rotate(0deg)';
        }

        // Skills Modal
        function viewSkills(skillsObj) {
            let html = '<div style="font-weight: 600; color: var(--primary); margin-bottom: 8px;">Affinda Snapshot Skills (CV Extracted)</div>';
            if (skillsObj.snapshot_tech || skillsObj.snapshot_soft) {
                html += '<p style="font-weight: 600; color: #059669; margin-bottom: 12px; font-size: 14px;"><i class="bx bx-shield-alt"></i> Technical Skills First</p>';
                // Technical first (green highlight)
                if (skillsObj.snapshot_tech) {
                    html += `<h4 style="color: #10b981;">🔧 Technical Skills</h4><p style="background: rgba(16,185,129,0.1); padding: 12px; border-radius: 8px; border-left: 4px solid #10b981; margin-bottom: 8px;">${skillsObj.snapshot_tech.replace(/,/g, ', ')}</p>`;
                }
                // Soft second (blue highlight)
                if (skillsObj.snapshot_soft) {
                    html += `<h4 style="color: #3b82f6;">✨ Soft Skills</h4><p style="background: rgba(59,130,246,0.1); padding: 12px; border-radius: 8px; border-left: 4px solid #3b82f6;">${skillsObj.snapshot_soft.replace(/,/g, ', ')}</p>`;
                }
            } else {
                html += '<p style="color: var(--gray); font-style: italic;">No Affinda snapshot skills extracted</p>';
            }
            document.getElementById('skillsContent').innerHTML = html;
            document.getElementById('skillsModal').style.display = 'block';

        }

        // Bulk
        function toggleSelectAll() {
            const checked = document.getElementById('selectAll').checked;
            document.querySelectorAll('.bulk-checkbox').forEach(cb => cb.checked = checked);
            document.getElementById('bulkActions').classList.toggle('active', checked);
        }

        // Single-application evaluation
function formatEvaluationResult(result) {
            const statusEmoji = result.pass ? '✅' : '❌';
            const statusColor = result.pass ? '#10b981' : '#ef4444';
            
            let html = `
                <div style="font-size: 24px; font-weight: bold; margin-bottom: 20px; text-align: center;">
                    🔍 Auto-Evaluation Summary
                </div>
                <div style="background: linear-gradient(135deg, ${result.pass ? '#d4edda' : '#f8d7da'}, ${result.pass ? '#c3e6cb' : '#f5c6cb'}); padding: 20px; border-radius: 12px; border-left: 6px solid ${statusColor}; margin-bottom: 24px;">
                    <div style="font-size: 20px; font-weight: bold; color: ${statusColor}; margin-bottom: 8px;">
                        Status: ${statusEmoji} ${result.status}
                    </div>
                </div>
            `;

                // Parse reasons array into structured sections
                const reasons = result.reasons || [];
                let skillsSection = '';
                let skillsScore = '';
                let skillMatches = 0;
                let totalSkills = 0;
                let qualSection = '';
                let otherSection = '';

                // Analyze reasons for skills
                reasons.forEach(reason => {
                    if (reason.includes('✅ Found:')) {
                        skillMatches++;
                        totalSkills++;
                    } else if (reason.includes('⚠️ Missing:')) {
                        totalSkills++;
                    } else if (reason.includes('📊 Skill match:')) {
                        skillsScore = reason;
                    } else if (reason.includes('✅ Qual match:') || reason.includes('❌ No qualification match')) {
                        qualSection += reason + '<br>';
                    } else if (reason.includes('Experience') || reason.includes('Province') || reason.includes('City') || reason.includes('Age')) {
                        otherSection += reason + '<br>';
                    }
                });

// Skills breakdown - Found skills first, then missing
            if (totalSkills > 0) {
                const skillPercentage = Math.round((skillMatches / totalSkills) * 100);
                
                // Categorize skills from reasons
                const foundSkills = reasons.filter(r => r.includes('✅ Found:')).map(r => r.replace('✅ Found: ', '')).join(', ');
                const missingSkills = reasons.filter(r => r.includes('⚠️ Missing:')).map(r => r.replace('⚠️ Missing: ', '')).join(', ');
                
                skillsSection = `
                    <div style="margin-bottom: 24px;">
                        <h3 style="font-size: 18px; color: #3b82f6; margin-bottom: 12px;">🧠 Skills Analysis</h3>
                        
                        ${foundSkills ? `
                        <div style="background: #d1fae5; padding: 12px; border-radius: 8px; border-left: 4px solid #10b981; margin-bottom: 12px;">
                            <div style="font-weight: 700; color: #065f46; margin-bottom: 6px;">✅ FOUND SKILLS:</div>
                            <div style="font-size: 14px; color: #047857;">${foundSkills}</div>
                        </div>
                        ` : ''}
                        
                        <div style="background: #fee2e2; padding: 16px; border-radius: 8px; border-left: 6px solid #dc2626; margin-bottom: 12px;">
                            <div style="font-weight: 700; color: #dc2626; margin-bottom: 8px; font-size: 15px;">❌ MISSING SKILLS:</div>
                            <div style="font-size: 14px; line-height: 1.5; color: #991b1b;">${missingSkills || 'None - all required skills found!'}</div>
                        </div>
                        
                        <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border-left: 4px solid #3b82f6; font-size: 14px;">
                            📊 Overall Match: <strong style="color: ${skillPercentage >= 70 ? '#10b981' : '#dc2626'}">${skillPercentage}%</strong> (${skillMatches}/${totalSkills})
                        </div>
                    </div>
                `;
            }


            // Enhanced Qualification with required list on fail\n            if (qualSection) {\n                let qualHtml = '';\n                const isFail = qualSection.includes('❌ No qualification match');\n                \n                if (isFail) {\n                    // Extract required quals from reasons - look for 'Required:' line\n                    const requiredMatch = qualSection.match(/Required: ([^<]+)/i);\n                    const requiredList = requiredMatch ? requiredMatch[1].trim() : 'Multiple qualifications';\n                    \n                    qualHtml = `\n                        <div style="background: #f8d7da; padding: 16px; border-radius: 8px; border-left: 4px solid #ef4444; margin-bottom: 20px;">\n                            <h3 style="font-size: 16px; margin-bottom: 8px; color: #c53030;">❌ No Qualification Match</h3>\n                            <div style="font-weight: 600; margin-bottom: 8px;">Candidate Qualifications:</div>\n                            <div style="color: #666; font-size: 14px; margin-bottom: 12px;">${result.debug ? result.debug.candidate_quals || 'None listed' : 'See candidate profile'}</div>\n                            <div style="font-weight: 700; color: #c53030; background: #fed7d7; padding: 8px; border-radius: 6px; font-size: 14px;">📋 <strong>Required:</strong> ${requiredList}</div>\n                        </div>\n                    `;\n                } else {\n                    // Pass case\n                    qualHtml = `\n                        <div style="background: #d4edda; padding: 16px; border-radius: 8px; border-left: 4px solid #10b981; margin-bottom: 20px;">\n                            <h3 style="font-size: 16px; margin-bottom: 8px;">✅ Qualification Match</h3>\n                            <div style="font-weight: 600;">${qualSection}</div>\n                        </div>\n                    `;\n                }\n                qualSection = qualHtml;\n            }

            // Other checks
            if (otherSection) {
                html += `
                    <div style="background: #f0f9ff; padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6; margin-top: 20px;">
                        <h3 style="font-size: 16px; margin-bottom: 12px;">📍 Other Criteria</h3>
                        <div style="white-space: pre-line; font-weight: 500;">${otherSection}</div>
                    </div>
                `;
            }

            html += skillsSection + qualSection;

            if (result.comments) {
                html += `
                    <div style="background: #fff3cd; padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b; margin-top: 20px;">
                        <strong>💭 Comments:</strong> ${result.comments}
                    </div>
                `;
            }

            return html;
        }

        function evaluateApplication(appId, btn) {
            if (!confirm('Run auto-evaluation for this application?')) return;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Evaluating...';

            const fd = new FormData();
            fd.append('application_id', appId);

            fetch('evaluate_single.php', { method: 'POST', body: fd })
                .then(res => res.text())
                .then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        alert('Invalid server response:\n' + text);
                        console.error('Invalid JSON from evaluate_single:', text);
                        return;
                    }

                    if (data.status !== 'ok') {
                        alert('Evaluation failed: ' + (data.message || JSON.stringify(data)));
                        return;
                    }

                    const r = data.result;
                    // Update status badge in card or table
                    let container = btn.closest('.candidate-card') || btn.closest('tr');
                    if (container) {
                        const badge = container.querySelector('.status-badge');
                        if (badge) {
                            badge.textContent = r.status;
                            // update classes
                            badge.className = 'status-badge status-' + r.status.toLowerCase().replace(/\s+/g, '-');
                        }
                    }

                    // Show toast briefly
                    showToast('Evaluation: ' + r.status, (r.status === 'Shortlisted' ? 'success' : (r.status === 'Rejected' ? 'error' : 'success')));

                    // Show details in modal
                    let html = formatEvaluationResult(r);
                    document.getElementById('evalContent').innerHTML = html;
                    document.getElementById('evalModal').style.display = 'block';
                })
                .catch(err => {
                    console.error('Evaluate single failed', err);
                    alert('Request failed. Open devtools console for details.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        }

        function applyBulkStatus() {
            const status = document.getElementById('bulkStatus').value;
            if (!status) { alert('Select a status'); return; }
            const count = document.querySelectorAll('.bulk-checkbox:checked').length;
            if (count === 0) { alert('Select at least one candidate'); return; }
            if (confirm(`Update ${count} candidate(s) to "${status}"?`)) {
                // In real app: AJAX or form submit
                alert('Bulk update would be processed here.');
            }
        }

        // Manual status update
        let currentAppId = null;
        function updateStatus(appId, currentStatus, btn) {
            currentAppId = appId;
            const select = document.getElementById('newStatus');
            select.value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }

        // Handle status form submission
        document.getElementById('statusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const newStatus = document.getElementById('newStatus').value;
            if (!newStatus) {
                alert('Please select a status');
                return;
            }

            const fd = new FormData();
            fd.append('application_id', currentAppId);
            fd.append('new_status', newStatus);

            fetch('update_application_status.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update status badge in both views
                        const newStatusClass = 'status-' + newStatus.toLowerCase().replace(/\s+/g, '-');

                        // Update in card view
                        const card = document.querySelector(`.candidate-card[data-application-id="${currentAppId}"]`);
                        if (card) {
                            const badge = card.querySelector('.status-badge');
                            if (badge) {
                                badge.textContent = newStatus;
                                badge.className = 'status-badge ' + newStatusClass;
                            }
                        }

                        // Update in table view
                        const row = document.querySelector(`tr[data-application-id="${currentAppId}"]`);
                        if (row) {
                            const badge = row.querySelector('.status-badge');
                            if (badge) {
                                badge.textContent = newStatus;
                                badge.className = 'status-badge ' + newStatusClass;
                            }
                        }

                        // Update stats if needed
                        updateStats();

                        showToast('Status updated successfully', 'success');
                        document.getElementById('statusModal').style.display = 'none';
                    } else {
                        alert('Failed to update status: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Status update failed', err);
                    alert('Request failed. Open devtools console for details.');
                });
        });

        function updateStats() {
            // Update the status stats cards
            fetch('get_status_stats.php')
                .then(res => res.json())
                .then(data => {
                    const statCards = document.querySelectorAll('.stat-card');
                    statCards.forEach(card => {
                        const label = card.querySelector('.stat-label').textContent;
                        if (data[label] !== undefined) {
                            card.querySelector('.stat-value').textContent = data[label];
                        }
                    });
                })
                .catch(err => console.error('Failed to update stats', err));
        }

        // Export
        function printResults() {
            const printWin = window.open('', '_blank');
            printWin.document.write(`
                <html><head><title>Candidates</title>
                <style>
                    body { font-family: Arial; }
                    table { width:100%; border-collapse:collapse; }
                    th, td { border:1px solid #ccc; padding:8px; text-align:left; }
                    th { background:#667eea; color:white; }
                </style></head>
                <body><h2>Candidate List</h2><p>Generated: ${new Date().toLocaleString()}</p>
                ${document.querySelector('.candidates-table-container table').outerHTML}
                </body></html>
            `);
            printWin.document.close();
            setTimeout(() => printWin.print(), 500);
        }

        // Theme
        document.getElementById('theme-toggle')?.addEventListener('change', function() {
            const isDark = this.checked;
            document.body.classList.toggle('dark-mode', isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
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
            
            // Initialize filter count on page load
            updateFilterCount();
        });

        // ========== NEW FILTER JAVASCRIPT FUNCTIONS ==========
        
        // Update the filter count badge
        function updateFilterCount() {
            let count = 0;
            const form = document.getElementById('filterForm');
            if (!form) return;
            
// Count non-empty filter inputs
            const inputs = form.querySelectorAll('input[name], select[name]');
            inputs.forEach(input => {
                if (input.name === 'search' && input.value.trim() !== '') count++;
                else if (input.name === 'position' && input.value !== '') count++;
                else if (input.name === 'status' && input.value !== '') count++;
                else if (input.name === 'gender' && input.value !== '') count++;
                else if (input.name === 'province' && input.value !== '') count++;
                else if (input.name === 'city' && input.value.trim() !== '') count++;
                else if (input.name === 'qualification' && input.value.trim() !== '') count++;
                else if (input.name === 'qualification_name' && input.value.trim() !== '') count++;
                else if (input.name === 'qualification_level' && input.value !== '') count++;
                else if (input.name === 'skills' && input.value.trim() !== '') count++;
                else if (input.name === 'date_from' && input.value !== '') count++;
                else if (input.name === 'date_to' && input.value !== '') count++;
                else if (input.name === 'sort_by' && input.value !== 'fullname_asc') count++;
                else if (input.name === 'experience_range' && input.value !== '') count++;
                else if (input.name === 'age_range' && input.value !== '') count++;
            });
            
            const badge = document.getElementById('filterCountBadge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            }
        }

        // Clear individual filter
function clearFilter(fieldName) {
            const form = document.getElementById('filterForm');
            if (!form) return;
            
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (input) {
                if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                } else {
                    input.value = '';
                }
                // Trigger form submission
                form.submit();
            }
        }

        // Reset all filters
        function resetFilters() {
            window.location.href = window.location.pathname;
        }

        // Apply quick filter preset
        function applyPreset(preset) {
            const form = document.getElementById('filterForm');
            if (!form) return;
            
            const today = new Date().toISOString().split('T')[0];
            
            switch(preset) {
                case 'submitted':
                    // Set date to today
                    form.querySelector('[name="date_from"]').value = today;
                    form.querySelector('[name="date_to"]').value = today;
                    // Clear other status filters
                    form.querySelector('[name="status"]').value = '';
                    break;
                case 'shortlisted':
                    form.querySelector('[name="status"]').value = 'Shortlisted';
                    form.querySelector('[name="date_from"]').value = '';
                    form.querySelector('[name="date_to"]').value = '';
                    break;
                case 'rejected':
                    form.querySelector('[name="status"]').value = 'Rejected';
                    form.querySelector('[name="date_from"]').value = '';
                    form.querySelector('[name="date_to"]').value = '';
                    break;
                case 'under_review':
                    form.querySelector('[name="status"]').value = 'Under Review';
                    form.querySelector('[name="date_from"]').value = '';
                    form.querySelector('[name="date_to"]').value = '';
                    break;
                case 'hired':
                    form.querySelector('[name="status"]').value = 'Hired';
                    form.querySelector('[name="date_from"]').value = '';
                    form.querySelector('[name="date_to"]').value = '';
                    break;
            }
            
            // Submit the form
            form.submit();
        }

        // Listen for input changes to update filter count
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filterForm');
            if (form) {
                form.addEventListener('input', updateFilterCount);
                form.addEventListener('change', updateFilterCount);
            }
        });
    </script>
</body>
</html>