<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode(['error' => 'Invalid user_id']);
    exit;
}

$user_id = (int)$_GET['user_id'];

// Check if match_percentage column exists
$hasMatchCol = false;
$colCheck = $conn->query("SHOW COLUMNS FROM job_applications LIKE 'match_percentage'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasMatchCol = true;
}
$matchCol = $hasMatchCol ? 'ja.match_percentage' : 'NULL AS match_percentage';

// Fetch applicant profile + skills + evaluation summary
$sql = "
    SELECT 
        u.user_id,
        u.fullname,
        u.email,
        u.phone,
        u.address,
        ja.application_id,
        ja.job_id,
        ja.comments AS evaluation_comments,
        $matchCol,
        jp.position AS job_position,
        jp.skills,
        jp.minimum_criteria,
        jp.requirements,
        ap.professional_summary,
        ap.years_experience,
        ap.professional_title,
        ap.hourly_rate
    FROM users u
    JOIN job_applications ja ON u.user_id = ja.user_id
    JOIN job_postings jp ON ja.job_id = jp.job_id
    LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
    WHERE u.user_id = ? AND ja.application_status = 'Shortlisted'
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Parse applicant skills
    $applicant_skills = [];
    $skill_sql = "SELECT technical_skills, soft_skills FROM skills WHERE user_id = ?";
    $skill_stmt = $conn->prepare($skill_sql);
    if ($skill_stmt) {
        $skill_stmt->bind_param("i", $user_id);
        $skill_stmt->execute();
        $skill_res = $skill_stmt->get_result();
        while ($s = $skill_res->fetch_assoc()) {
            if ($s['technical_skills']) {
                $applicant_skills = array_merge($applicant_skills, array_map('trim', explode(',', $s['technical_skills'])));
            }
            if ($s['soft_skills']) {
                $applicant_skills = array_merge($applicant_skills, array_map('trim', explode(',', $s['soft_skills'])));
            }
        }
        $skill_stmt->close();
    }
    $applicant_skills = array_unique(array_filter($applicant_skills));

    // Parse screening criteria for required skills (PRIORITY)
    $screening = [];
    if (!empty($row['minimum_criteria'])) {
        $screening = json_decode($row['minimum_criteria'], true) ?: [];
    }
    $required_skills = $screening['required_skills'] ?? [];
    if (empty($required_skills) && !empty($row['skills'])) {
        $required_skills = array_map('trim', explode(',', $row['skills']));
    }
    $required_skills = array_filter(array_map('trim', (array)$required_skills));

    // Normalize skills
    $app_skills_norm = array_map(function($s) { return strtolower(trim($s)); }, $applicant_skills);
    $req_skills_norm = array_map(function($s) { return strtolower(trim($s)); }, $required_skills);

    // Calculate matched/missing skills
    $matched_skills = [];
    $missing_skills = [];
    foreach ($required_skills as $req) {
        $req_norm = strtolower(trim($req));
        $found = false;
        foreach ($app_skills_norm as $app_norm) {
            if (strpos($app_norm, $req_norm) !== false || strpos($req_norm, $app_norm) !== false) {
                $found = true;
                $matched_skills[] = $req;
                break;
            }
        }
        if (!$found) {
            $missing_skills[] = $req;
        }
    }

    // Compute accurate match percentage from screening skills match
    $total_req = count($required_skills);
    $match_pct = $total_req > 0 ? round((count($matched_skills) / $total_req) * 100, 1) : 0;

    echo json_encode([
        'user_id' => (int)$row['user_id'],
        'fullname' => $row['fullname'],
        'email' => $row['email'],
        'phone' => $row['phone'] ?? '',
        'address' => $row['address'] ?? '',
        'job_id' => (int)$row['job_id'],
        'job_position' => $row['job_position'] ?? 'N/A',
        'match_percentage' => $match_pct,
        'years_experience' => (int)($row['years_experience'] ?? 0),
        'professional_title' => $row['professional_title'] ?? 'Applicant',
        'professional_summary' => $row['professional_summary'] ?? 'No summary available.',
        'hourly_rate' => $row['hourly_rate'] ?? null,
        'skills_match' => [
            'matched' => $matched_skills,
            'missing' => $missing_skills,
            'total_required' => $total_req,
            'applicant_skills' => array_slice($applicant_skills, 0, 10),
            'screening_based' => !empty($screening)
        ],
        'requirements' => $row['requirements'] ?? ''
    ]);
} else {
    echo json_encode(['error' => 'Applicant not found or not shortlisted']);
}
?>
