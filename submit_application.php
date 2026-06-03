<?php
// Prevent PHP from emitting HTML error messages; buffer output so we can return JSON on fatal errors
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ob_start();

include('config.php');
session_start();

// Always return JSON
header("Content-Type: application/json");

// Ensure fatal errors are returned as JSON
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err !== NULL) {
        http_response_code(500);
        if (ob_get_length()) ob_clean();
        echo json_encode(["status" => "error", "message" => "Fatal error: " . ($err['message'] ?? 'unknown') . " in " . ($err['file'] ?? '') . " on line " . ($err['line'] ?? '')]);
        exit;
    }
});

// ---------------- Check Login ----------------
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "You must be logged in to apply."
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = $_POST['job_id'] ?? null;

if (!$job_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid job ID."
    ]);
    exit();
}

// ---------------- Check if job exists ----------------
$sql = "SELECT job_id FROM job_postings WHERE job_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "The job posting does not exist."
    ]);
    $stmt->close();
    exit();
}
$stmt->close();

// ---------------- Check for duplicate application ----------------
$check_sql = "SELECT application_id FROM job_applications WHERE user_id = ? AND job_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $user_id, $job_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode([
        "status" => "warning",
        "message" => "You have already applied for this job."
    ]);
    $stmt->close();
    exit();
}
$stmt->close();

// ---------------- Collect form data ----------------
$gender        = $_POST['gender'] ?? '';
$phone         = $_POST['phone'] ?? '';
$dob           = $_POST['dob'] ?? '';
$qualification = $_POST['qualification'] ?? '';
$position      = $_POST['position'] ?? '';
$availability  = $_POST['availability'] ?? '';
$address       = $_POST['address'] ?? '';

$cover_letter  = $_POST['cover_letter'] ?? '';
// Snapshot fields (optional)
$soft_skills = trim($_POST['soft_skills'] ?? '');
$technical_skills = trim($_POST['technical_skills'] ?? '');
$years_experience = isset($_POST['years_experience']) && $_POST['years_experience'] !== '' ? intval($_POST['years_experience']) : null; 

// ---------------- Resume upload ----------------
if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "status" => "error",
        "message" => "Resume upload failed. Please try again."
    ]);
    exit();
}

$resume_type = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
$allowed_types = ['pdf', 'doc', 'docx'];

if (!in_array($resume_type, $allowed_types)) {
    echo json_encode([
        "status" => "error",
        "message" => "Only PDF, DOC, and DOCX files are allowed."
    ]);
    exit();
}

$resume_file_content = file_get_contents($_FILES['resume']['tmp_name']);
$resume_filename     = $_FILES['resume']['name'];

// ---------------- Fetch fullname & email ----------------
$fullname = $_SESSION['fullname'] ?? '';
$email    = $_SESSION['email'] ?? '';

// ---------------- Insert into DB ----------------
// Check if job_applications has snapshot columns
$has_soft = $conn->query("SHOW COLUMNS FROM job_applications LIKE 'soft_skills'")->num_rows > 0;
$has_technical = $conn->query("SHOW COLUMNS FROM job_applications LIKE 'technical_skills'")->num_rows > 0;
$has_years = $conn->query("SHOW COLUMNS FROM job_applications LIKE 'years_experience'")->num_rows > 0;

$extraCols = '';
$extraPlaceholders = '';
$extraTypes = '';
$extraValues = [];

if ($has_soft) { $extraCols .= ', soft_skills'; $extraPlaceholders .= ', ?'; $extraTypes .= 's'; $extraValues[] = $soft_skills; }
if ($has_technical) { $extraCols .= ', technical_skills'; $extraPlaceholders .= ', ?'; $extraTypes .= 's'; $extraValues[] = $technical_skills; }
if ($has_years) { $extraCols .= ', years_experience'; $extraPlaceholders .= ', ?'; $extraTypes .= 'i'; $extraValues[] = $years_experience; }

// If DB doesn't support snapshot columns, append snapshot data to cover_letter as fallback
if (!$has_soft && !$has_technical && !$has_years) {
    $snapshot = "\n\n[Snapshot] Technical Skills: " . ($technical_skills ?: 'N/A') . "; Soft Skills: " . ($soft_skills ?: 'N/A') . "; Years: " . ($years_experience !== null ? $years_experience : 'N/A');
    $cover_letter .= $snapshot;
}

$sql = "INSERT INTO job_applications 
        (user_id, job_id, fullname, email, gender, phone, dob, qualification, position, availability, address, cover_letter, resume, resume_filename" . $extraCols . ") 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?" . $extraPlaceholders . ")";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "DB prepare failed: " . $conn->error]);
    exit();
}

$baseTypes = "iissssssssssss"; // 14 params before extras
$bindTypes = $baseTypes . $extraTypes;
$bindParams = [
    $bindTypes,
    $user_id, $job_id, $fullname, $email, $gender, $phone, $dob,
    $qualification, $position, $availability, $address, $cover_letter,
    $resume_file_content, $resume_filename
];
foreach ($extraValues as $v) $bindParams[] = $v;

// Bind params dynamically (needs references)
$refs = [];
foreach ($bindParams as $key => $value) $refs[$key] = &$bindParams[$key];
call_user_func_array([$stmt, 'bind_param'], $refs);

// Send resume as long data (to avoid truncation)
$stmt->send_long_data(12, $resume_file_content); // resume remains at index 12

try {
    if ($stmt->execute()) {
        $application_id = $stmt->insert_id;

        // Insert notification for admin (assuming admin user_id is 3)
        $admin_user_id = 3;
        $notification_message = "New application submitted for " . $position . " by " . $fullname;
        $notification_sql = "INSERT INTO notifications (message, user_id, type, reference_id, created_at) VALUES (?, ?, 'application', ?, NOW())";
        $notification_stmt = $conn->prepare($notification_sql);
        $notification_stmt->bind_param("sii", $notification_message, $admin_user_id, $application_id);
        $notification_stmt->execute();
        $notification_stmt->close();

        // Insert notification for the applicant
        $applicant_notification_message = "Your application for " . $position . " has been submitted successfully.";
        $applicant_notification_sql = "INSERT INTO notifications (message, user_id, type, reference_id, created_at) VALUES (?, ?, 'application', ?, NOW())";
        $applicant_notification_stmt = $conn->prepare($applicant_notification_sql);
        $applicant_notification_stmt->bind_param("sii", $applicant_notification_message, $user_id, $application_id);
        $applicant_notification_stmt->execute();
        $applicant_notification_stmt->close();

// ========== NEW: CREATE PROFILE SNAPSHOT ==========
        // Fetch complete profile snapshot at submission time
$snapshot_sql = "
            SELECT 
                u.fullname, u.email, u.phone, u.gender, u.dob, u.address,
                ap.professional_title, ap.professional_summary, ap.years_experience as years_of_experience,
                GROUP_CONCAT(DISTINCT s.technical_skills SEPARATOR ',') as tech_skills,
                GROUP_CONCAT(DISTINCT s.soft_skills SEPARATOR ',') as soft_skills,
                GROUP_CONCAT(DISTINCT CONCAT(q.qualification_name, ' (', COALESCE(q.institution,'N/A'), ', ', COALESCE(q.year_completed,'N/A'), ')') SEPARATOR ' | ') as education,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        we.position,
                        ' @ ', we.company_name,
                        ' | Country: ', COALESCE(we.country,'N/A'),
                        ' | Employment Status: ', COALESCE(we.employment_status,'N/A'),
                        ' | Work Type: ', COALESCE(we.work_type,'N/A'),
                        ' | Start: ', COALESCE(we.start_date,'N/A'),
                        ' | End: ', COALESCE(we.end_date,'N/A'),
                        ' | Reason for Leaving: ', COALESCE(we.reason_for_leaving,'N/A'),
                        ' | Duties: ', LEFT(COALESCE(we.duties,''),1000)

                    )
                    SEPARATOR ' | '
                ) as work_experience
            FROM users u 
            LEFT JOIN applicant_profile ap ON u.user_id = ?
            LEFT JOIN skills s ON u.user_id = s.user_id  
            LEFT JOIN qualifications q ON u.user_id = q.user_id
            LEFT JOIN work_experience we ON u.user_id = we.user_id
            WHERE u.user_id = ? 
            GROUP BY u.user_id
        ";
        
        $snapshot_stmt = $conn->prepare($snapshot_sql);
        $snapshot_stmt->bind_param("ii", $user_id, $user_id);
        $snapshot_stmt->execute();
        $snapshot_result = $snapshot_stmt->get_result();
        
        if ($snapshot_result->num_rows > 0) {
            $profile_data = $snapshot_result->fetch_assoc();
            $profile_data['work_experience'] = $profile_data['work_experience'] ?? null;
            
            // DEBUG: Log snapshot data (REMOVE AFTER TESTING)
            error_log("SNAPSHOT DEBUG for app_id $application_id, user $user_id: " . print_r($profile_data, true));
            
            // Also return in JSON response for immediate testing (REMOVE AFTER TESTING)
            $debug_snapshot = $profile_data;
            
            $profile_json = json_encode($profile_data);
            
            // Insert snapshot
            $snapshot_insert_sql = "INSERT INTO application_snapshots (application_id, profile_data, created_at) VALUES (?, ?, NOW())";
            $snapshot_insert_stmt = $conn->prepare($snapshot_insert_sql);
            $snapshot_insert_stmt->bind_param("is", $application_id, $profile_json);
            if ($snapshot_insert_stmt->execute()) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Application submitted successfully!",
// "debug_snapshot" => $debug_snapshot,  // Disabled for production
                    "snapshot_inserted" => true
                ]);
            } else {
                echo json_encode([
                    "status" => "success",
                    "message" => "Application submitted, snapshot failed: " . $snapshot_insert_stmt->error,
                    "debug_snapshot" => $debug_snapshot
                ]);
            }
            $snapshot_insert_stmt->close();
        } else {
            echo json_encode([
                "status" => "success", 
                "message" => "Application submitted, no profile data found for snapshot.",
                "debug_snapshot" => null
            ]);
        }
        $snapshot_stmt->close();

        // ========== END SNAPSHOT ==========

    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Error submitting application: " . $stmt->error
        ]);
    }
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
exit();
?>
