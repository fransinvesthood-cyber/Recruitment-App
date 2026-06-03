<?php
include 'config.php';
header('Content-Type: application/json');

session_start();

// Check if match_percentage column exists
$hasMatchCol = false;
$colCheck = $conn->query("SHOW COLUMNS FROM job_applications LIKE 'match_percentage'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasMatchCol = true;
}

$matchCol = $hasMatchCol ? 'ja.match_percentage' : '0 AS match_percentage';

// Fetch all shortlisted applicants with job details
$sql = "
    SELECT 
        u.user_id,
        u.fullname,
        u.email,
        ja.application_id,
        ja.job_id,
        jp.position AS job_position,
        jp.minimum_criteria,
        ja.application_status,
        ja.comments,
        $matchCol,
        ap.years_experience,
        ap.professional_title
    FROM users u
    JOIN job_applications ja ON u.user_id = ja.user_id
    JOIN job_postings jp ON ja.job_id = jp.job_id
    LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
    WHERE u.role = 'Applicant' 
      AND ja.application_status = 'Shortlisted'
    ORDER BY ja.application_id DESC, u.fullname ASC
";

$result = $conn->query($sql);
$applicants = [];

// Fallback query if no results
if (!$result || $result->num_rows === 0) {
    $sql2 = "
        SELECT DISTINCT u.user_id, u.fullname, u.email, ja.job_id
        FROM users u
        JOIN job_applications ja ON u.user_id = ja.user_id
        WHERE u.role = 'Applicant' AND ja.application_status = 'Shortlisted'
        ORDER BY u.fullname ASC
    ";
    $result2 = $conn->query($sql2);
    if ($result2 && $result2->num_rows > 0) {
        while ($row = $result2->fetch_assoc()) {
            $applicants[] = [
                'user_id' => (int)$row['user_id'],
                'application_id' => 0,
                'fullname' => $row['fullname'],
                'email' => $row['email'],
                'job_id' => (int)$row['job_id'],
                'job_position' => 'Unknown Position',
                'match_percentage' => 0,
                'years_experience' => 0,
                'professional_title' => 'Applicant'
            ];
        }
    }
} else {
    while ($row = $result->fetch_assoc()) {
        // Compute screening match %
        $screening = [];
        if (!empty($row['minimum_criteria'])) {
            $screening = json_decode($row['minimum_criteria'], true) ?: [];
        }
        $screening_skills = $screening['required_skills'] ?? [];
        $screening_skills = array_filter(array_map('trim', (array)$screening_skills));
        $total_screen = count($screening_skills);
        
        $match_pct = 0;
        if ($total_screen > 0) {
            $app_skills_q = $conn->query("SELECT technical_skills, soft_skills FROM skills WHERE user_id = " . (int)$row['user_id']);
            $app_skills = [];
            if ($app_skills_q) {
                while ($s = $app_skills_q->fetch_assoc()) {
                    if ($s['technical_skills']) $app_skills = array_merge($app_skills, array_map('trim', explode(',', $s['technical_skills'])));
                    if ($s['soft_skills']) $app_skills = array_merge($app_skills, array_map('trim', explode(',', $s['soft_skills'])));
                }
            }
            $app_skills = array_unique(array_filter($app_skills));
            $app_norm = array_map(function($s) { return strtolower(trim($s)); }, $app_skills);
            
            $matched_count = 0;
            foreach ($screening_skills as $req) {
                $req_norm = strtolower(trim($req));
                $found = false;
                foreach ($app_norm as $app) {
                    if (strpos($app, $req_norm) !== false || strpos($req_norm, $app) !== false) {
                        $found = true;
                        break;
                    }
                }
                if ($found) $matched_count++;
            }
            $match_pct = round(($matched_count / $total_screen) * 100, 1);
        }

        $applicants[] = [
            'user_id' => (int)$row['user_id'],
            'application_id' => (int)$row['application_id'],
            'fullname' => $row['fullname'],
            'email' => $row['email'],
            'job_id' => (int)$row['job_id'],
            'job_position' => $row['job_position'],
            'match_percentage' => $match_pct,
            'years_experience' => $row['years_experience'] ?? 0,
            'professional_title' => $row['professional_title'] ?? 'Applicant'
        ];
    }
}

echo json_encode($applicants);
?>

