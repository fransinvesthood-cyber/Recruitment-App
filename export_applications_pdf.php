<?php
include('config.php');
require_once 'vendor/autoload.php'; // Dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

// Copy all filter params (exact same as CSV)
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

// Build exact same query
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

// Apply ALL filters (exact copy)
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
        'Matric' => 'High School', 'Certificate' => 'Certificate', 'Diploma' => 'Diploma',
        "Bachelor's Degree" => "Bachelor's Degree", 'Postgraduate' => "Master's Degree", 'Doctorate' => 'Doctorate'
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
    'fullname Asc' => 'u.fullname ASC', 'fullname_desc' => 'u.fullname DESC',
    'date_newest' => 'ja.submission_date DESC', 'date_oldest' => 'ja.submission_date Asc',
    'status Asc' => 'ja.application_status Asc',
    'experience Asc' => 'COALESCE(ap.years_of_experience, 0) Asc', 'experience_desc' => 'COALESCE(ap.years_of_experience, 0) DESC'
];
$sort_clause = $sort_options[$sort_by] ?? 'u.fullname ASC';

$query .= " GROUP BY ja.application_id ORDER BY $sort_clause";

$result = $conn->query($query);

if (!$result) {
    die('Query error: ' . mysqli_error($conn));
}

// Dompdf setup
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVuSans');
$dompdf = new Dompdf($options);

// Generate HTML
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Filtered Applications Report</title>
    <style>
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.1; margin: 10px; }
        h1 { text-align: center; color: #333; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .filters { font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ddd; padding: 4px 2px; text-align: left; font-size: 9px; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-submitted { background: #e3f2fd; }
        .status-shortlisted { background: #e8f5e9; }
        .status-hired { background: #e8f5f2; }
        .status-rejected { background: #ffebee; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <h1>Job Applications Report</h1>
    <div class="header">
        <div>
            <strong>Total Records:</strong> <?= $result->num_rows ?>
        </div>
        <?php if (!empty(array_filter($_GET))): ?>
        <div class="filters">
            <strong>Filters Applied:</strong><br>
            <?php foreach ($_GET as $key => $value): if (empty($value)) continue; ?>
                <?= ucfirst(str_replace('_', ' ', $key)) ?>: <?= htmlspecialchars($value) ?><br>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Gender</th>
                <th>DOB</th>
                <th>Professional Title</th>
                <th>Position</th>
                <th>Qualification</th>
                <th>Status</th>
                <th>Submission Date</th>
            <th>Technical Skills</th>
            <th>Soft Skills</th>
            <th>Qualifications</th>
            <th>Address</th>
            <th>Experience</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="status-<?= strtolower(str_replace(' ', '-', $row['application_status'])) ?>">
                <td><?= htmlspecialchars($row['fullname']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
                <td><?= htmlspecialchars($row['dob']) ?></td>
                <td><?= htmlspecialchars($row['professional_title']) ?></td>
                <td><?= htmlspecialchars($row['position']) ?></td>
                <td><?= htmlspecialchars($row['qualification']) ?></td>
                <td><?= htmlspecialchars($row['application_status']) ?></td>
                <td><?= $row['submission_date'] ?></td>
                <td><?= htmlspecialchars($row['technical_skills']) ?></td>
                <td><?= htmlspecialchars($row['soft_skills']) ?></td>
                <td><?= htmlspecialchars($row['qualification_names']) ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td><?= $row['years_of_experience'] ?? 'N/A' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div class="footer">
        Generated on <?= date('Y-m-d H:i:s') ?> | Total: <?= $result->num_rows ?> records
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Load HTML to Dompdf
$dompdf->loadHtml($html);
$dompdf->setPaper(array(0,0,794.0,1122.0), 'portrait'); // A4 with slight margin adjustment
$dompdf->render();

// Output PDF
$filename = 'applications_report_' . date('Y-m-d_H-i-s') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
?>

