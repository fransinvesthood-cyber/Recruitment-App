<?php
include('config.php');

// Copy all filter params from manage_applications.php
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$gender_filter = isset($_GET['gender']) ? $conn->real_escape_string($_GET['gender']) : '';
$position_filter = isset($_GET['position']) ? $conn->real_escape_string($_GET['position']) : '';
$qualification_filter = isset($_GET['qualification']) ? $conn->real_escape_string($_GET['qualification']) : '';
$qualification_name_filter = isset($_GET['qualification_name']) ? $conn->real_escape_string($_GET['qualification_name']) : '';
$skills_filter = isset($_GET['skills']) ? $conn->real_escape_string($_GET['skills']) : '';
$experience_range = isset($_GET['experience_range']) ? $conn->real_escape_string($_GET['experience_range']) : '';
$age_range = isset($_GET['age_range']) ? $conn->real_escape_string($_GET['age_range']) : '';
$date_from = isset($_GET['date_from']) ? $conn->real_escape_string($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? $conn->real_escape_string($_GET['date_to']) : '';
$sort_by = isset($_GET['sort_by']) ? $conn->real_escape_string($_GET['sort_by']) : 'fullname Asc';
$qualification_level = isset($_GET['qualification_level']) ? stripslashes(rawurldecode($_GET['qualification_level'])) : '';
$province_filter = isset($_GET['province']) ? $conn->real_escape_string($_GET['province']) : '';
$city_filter = isset($_GET['city']) ? $conn->real_escape_string($_GET['city']) : '';

// Build exact same query as manage_applications.php
$query = "SELECT
            u.fullname, u.email, u.phone, u.gender, 
            DATE_FORMAT(u.dob, '%Y-%m-%d') as dob,
            ap.professional_title,
            ja.position, ja.qualification, ja.application_status,
            ja.submission_date,
            GROUP_CONCAT(DISTINCT s.technical_skills SEPARATOR ', ') AS technical_skills,
            GROUP_CONCAT(DISTINCT s.soft_skills SEPARATOR ', ') AS soft_skills,
            GROUP_CONCAT(DISTINCT q.qualification_name SEPARATOR ', ') AS qualification_names,
            GROUP_CONCAT(DISTINCT q.qualification_level SEPARATOR ', ') AS qualification_levels,
            u.address,
            ap.years_of_experience
          FROM users u
          LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
          JOIN job_applications ja ON u.user_id = ja.user_id
          LEFT JOIN skills s ON u.user_id = s.user_id
          LEFT JOIN qualifications q ON u.user_id = q.user_id
          WHERE u.role = 'applicant'";

// Apply all filters (exact copy from manage_applications.php)
if (!empty($search)) {
    $query .= " AND (u.fullname LIKE '%$search%' OR u.email LIKE '%$search%' OR ja.position LIKE '%$search%' OR ap.professional_title LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $escaped_status = $conn->real_escape_string($status_filter);
    $query .= " AND ja.application_status = '$escaped_status'";
}
if (!empty($gender_filter)) {
    $query .= " AND u.gender = '$gender_filter'";
}
if (!empty($position_filter)) {
    $query .= " AND ja.job_id = '$position_filter'";
}
if (!empty($qualification_filter)) {
    $query .= " AND ja.qualification LIKE '%$qualification_filter%'";
}
if (!empty($qualification_level)) {
    $qual_level_map = [
        'Matric' => 'High School',
        'Certificate' => 'Certificate',
        'Diploma' => 'Diploma',
        "Bachelor's Degree" => "Bachelor's Degree",
        'Postgraduate' => "Master's Degree",
        'Doctorate' => 'Doctorate'
    ];
    $db_qual_level = $qual_level_map[$qualification_level] ?? $qualification_level;
    $escaped_qual_level = $conn->real_escape_string($db_qual_level);
    $query .= " AND q.qualification_level LIKE '%$escaped_qual_level%'";
}
if (!empty($skills_filter)) {
    $skills_array = array_map('trim', explode(',', $skills_filter));
    $skills_conditions = [];
    foreach ($skills_array as $skill) {
        if (!empty($skill)) {
            $escaped_skill = $conn->real_escape_string($skill);
            $skills_conditions[] = "(s.technical_skills LIKE '%$escaped_skill%' OR s.soft_skills LIKE '%$escaped_skill%')";
        }
    }
    if (!empty($skills_conditions)) {
        $query .= " AND (" . implode(' OR ', $skills_conditions) . ")";
    }
}
if (!empty($qualification_name_filter)) {
    $query .= " AND q.qualification_name LIKE '%$qualification_name_filter%'";
}
if (!empty($province_filter)) {
    $query .= " AND u.address LIKE '%$province_filter%'";
}
if (!empty($city_filter)) {
    $query .= " AND u.address LIKE '%$city_filter%'";
}
if (!empty($date_from)) {
    $query .= " AND DATE(ja.submission_date) >= '$date_from'";
}
if (!empty($date_to)) {
    $query .= " AND DATE(ja.submission_date) <= '$date_to'";
}
if (!empty($experience_range)) {
    $exp_field = "COALESCE(ap.years_of_experience, 0)";
    switch ($experience_range) {
        case '0-1': $query .= " AND $exp_field >= 0 AND $exp_field <= 1"; break;
        case '1-2': $query .= " AND $exp_field > 1 AND $exp_field <= 2"; break;
        case '2-3': $query .= " AND $exp_field > 2 AND $exp_field <= 3"; break;
        case '3-5': $query .= " AND $exp_field > 3 AND $exp_field <= 5"; break;
        case '5+': $query .= " AND $exp_field > 5"; break;
    }
}
if (!empty($age_range)) {
    $age_field = "TIMESTAMPDIFF(YEAR, u.dob, CURDATE())";
    switch ($age_range) {
        case '18-25': $query .= " AND u.dob IS NOT NULL AND $age_field >= 18 AND $age_field <= 25"; break;
        case '26-35': $query .= " AND u.dob IS NOT NULL AND $age_field >= 26 AND $age_field <= 35"; break;
        case '36-45': $query .= " AND u.dob IS NOT NULL AND $age_field >= 36 AND $age_field <= 45"; break;
        case '45+': $query .= " AND u.dob IS NOT NULL AND $age_field >= 45"; break;
    }
}

// Sorting
$sort_options = [
    'fullname Asc' => 'u.fullname ASC',
    'fullname_desc' => 'u.fullname DESC',
    'date_newest' => 'ja.submission_date DESC',
    'date_oldest' => 'ja.submission_date Asc',
    'status Asc' => 'ja.application_status Asc',
    'experience Asc' => 'COALESCE(ap.years_of_experience, 0) Asc',
    'experience_desc' => 'COALESCE(ap.years_of_experience, 0) DESC'
];
$sort_clause = $sort_options[$sort_by] ?? 'u.fullname ASC';

$query .= " GROUP BY ja.application_id ORDER BY $sort_clause";

$result = $conn->query($query);

if (!$result) {
    die('Query error: ' . mysqli_error($conn));
}

// CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="filtered_applications_' . date('Y-m-d_H-i-s') . '.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 in Excel
fwrite($output, "\xEF\xBB\xBF");

// CSV header row
fputcsv($output, [
    'Full Name',
    'Email',
    'Phone',
    'Gender',
    'DOB',
    'Professional Title',
    'Position',
    'Qualification',
    'Status',
    'Submission Date',
    'Technical Skills',
    'Soft Skills',
    'Qualifications',
    'Qualification Levels',
    'Address',
    'Experience (Years)'
]);

// Data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['fullname'],
        $row['email'],
        $row['phone'],
        $row['gender'],
        $row['dob'],
        $row['professional_title'],
        $row['position'],
        $row['qualification'],
        $row['application_status'],
        $row['submission_date'],
        $row['technical_skills'],
        $row['soft_skills'],
        $row['qualification_names'],
        $row['qualification_levels'],
        $row['address'],
        $row['years_of_experience']
    ]);
}

fclose($output);
mysqli_close($conn);
exit;
?>

