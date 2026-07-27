<?php
/**
 * API endpoint to fetch applicant details for the Applicant Pool page.
 * GET parameters:
 *   - user_id (required): The applicant's user ID
 *   - type (optional): 'qualifications', 'skills', 'work_experience', or empty for full profile
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include('config.php');
session_start();

// RBAC check
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authenticated.']);
    exit;
}

$admin_id = intval($_SESSION['user_id']);
$role_check = $conn->query("SELECT role FROM users WHERE user_id = $admin_id");
$role_row = $role_check->fetch_assoc();
if (!$role_row || $role_row['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required.']);
    exit;
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid user_id.']);
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

try {
    if ($type === 'qualifications') {
        $q = $conn->prepare("SELECT qualification_level, qualification_name, institution, year_completed FROM qualifications WHERE user_id = ? ORDER BY year_completed DESC");
        $q->bind_param("i", $user_id);
        $q->execute();
        $result = $q->get_result();
        $qualifications = [];
        while ($row = $result->fetch_assoc()) {
            $qualifications[] = $row;
        }
        $q->close();
        echo json_encode(['qualifications' => $qualifications]);
    } 
    elseif ($type === 'skills') {
        $q = $conn->prepare("SELECT technical_skills, soft_skills FROM skills WHERE user_id = ?");
        $q->bind_param("i", $user_id);
        $q->execute();
        $result = $q->get_result();
        $skills = $result->fetch_assoc() ?: ['technical_skills' => '', 'soft_skills' => ''];
        $q->close();
        
        // Count qualifications and work experience for completeness
        $countQual = $conn->prepare("SELECT COUNT(*) AS cnt FROM qualifications WHERE user_id = ?");
        $countQual->bind_param("i", $user_id);
        $countQual->execute();
        $qualCount = $countQual->get_result()->fetch_assoc()['cnt'] ?? 0;
        $countQual->close();
        
        $countWork = $conn->prepare("SELECT COUNT(*) AS cnt FROM work_experience WHERE user_id = ?");
        $countWork->bind_param("i", $user_id);
        $countWork->execute();
        $workCount = $countWork->get_result()->fetch_assoc()['cnt'] ?? 0;
        $countWork->close();
        
        $skills['has_qualifications'] = intval($qualCount);
        $skills['has_work_experience'] = intval($workCount);
        echo json_encode($skills);
    } 
    elseif ($type === 'work_experience') {
        $q = $conn->prepare("SELECT position, company_name, duration, duties, start_date, end_date FROM work_experience WHERE user_id = ? ORDER BY start_date DESC");
        $q->bind_param("i", $user_id);
        $q->execute();
        $result = $q->get_result();
        $work = [];
        while ($row = $result->fetch_assoc()) {
            $work[] = $row;
        }
        $q->close();
        echo json_encode(['work_experience' => $work]);
    } 
    else {
        // Full profile - dynamically build select columns to avoid missing column errors
        $profile_pic_col_exists = $conn->query("SHOW COLUMNS FROM applicant_profile LIKE 'profile_picture'")->num_rows > 0;
        $cv_col_exists = $conn->query("SHOW COLUMNS FROM applicant_profile LIKE 'cv'")->num_rows > 0;
        $user_pic_col_exists = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'")->num_rows > 0;

        $profileFields = "u.user_id, u.fullname, u.username, u.email, u.gender, u.dob, u.phone, u.address, u.created_at,
            ap.professional_title, ap.professional_summary";
        if ($profile_pic_col_exists) {
            $profileFields .= ", ap.profile_picture";
        } elseif ($user_pic_col_exists) {
            $profileFields .= ", u.profile_picture as profile_picture";
        }
        if ($cv_col_exists) {
            $profileFields .= ", ap.cv";
        }
        $profileFields .= ", s.technical_skills, s.soft_skills";

        $q = $conn->prepare("SELECT $profileFields
            FROM users u
            LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
            LEFT JOIN skills s ON u.user_id = s.user_id
            WHERE u.user_id = ?");
        $q->bind_param("i", $user_id);
        $q->execute();
        $result = $q->get_result();
        $profile = $result->fetch_assoc();
        $q->close();

        if (!$profile) {
            echo json_encode(['error' => 'User not found.']);
            exit;
        }

        // Count qualifications and work experience
        $countQual = $conn->prepare("SELECT COUNT(*) AS cnt FROM qualifications WHERE user_id = ?");
        $countQual->bind_param("i", $user_id);
        $countQual->execute();
        $profile['has_qualifications'] = intval($countQual->get_result()->fetch_assoc()['cnt'] ?? 0);
        $countQual->close();

        $countWork = $conn->prepare("SELECT COUNT(*) AS cnt FROM work_experience WHERE user_id = ?");
        $countWork->bind_param("i", $user_id);
        $countWork->execute();
        $profile['has_work_experience'] = intval($countWork->get_result()->fetch_assoc()['cnt'] ?? 0);
        $countWork->close();

        echo json_encode($profile);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

