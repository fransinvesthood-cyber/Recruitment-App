<?php
require_once __DIR__ . '/vendor/autoload.php';
include('config.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

// === Set up date range for Monday to Sunday of current week ===
$today = new DateTime();
$startDate = clone $today;
$startDate->modify('Monday this week'); // always get Monday of the current week
$endDate = clone $startDate;
$endDate->modify('+6 days'); // Sunday

$start_date_str = $startDate->format('Y-m-d');
$end_date_str = $endDate->format('Y-m-d');

// === Fetch Task Logs ===
$sql = "SELECT 
            t.work_date, 
            l.client_project, 
            l.summary, 
            l.challenges,
            l.notes,
            l.support
        FROM consultant_timesheets t
        LEFT JOIN consultant_task_logs l 
            ON t.user_id = l.user_id 
            AND t.work_date = l.work_date
        WHERE t.user_id = $user_id
        AND t.work_date BETWEEN '$start_date_str' AND '$end_date_str'";

$result = mysqli_query($conn, $sql);
$logMap = [];
while ($row = mysqli_fetch_assoc($result)) {
    $logMap[$row['work_date']] = $row;
}

// === Fetch Approved Leaves ===
$stmt = $conn->prepare("SELECT leave_type, start_date, end_date FROM consultant_leaves WHERE user_id = ? AND status = 'Approved' AND start_date <= ? AND end_date >= ?");
$stmt->bind_param("iss", $user_id, $end_date_str, $start_date_str);
$stmt->execute();
$stmt->bind_result($leave_type, $leave_start, $leave_end);

$leaveDays = [];
while ($stmt->fetch()) {
    $start = new DateTime($leave_start);
    $end = new DateTime($leave_end);
    while ($start <= $end) {
        $leaveDays[$start->format('Y-m-d')] = $leave_type;
        $start->modify('+1 day');
    }
}
$stmt->close();

// === Fetch Public Holidays ===
$holidaySql = "SELECT holiday_date, holiday_name FROM holidays WHERE holiday_date BETWEEN '$start_date_str' AND '$end_date_str'";
$holidayRes = mysqli_query($conn, $holidaySql);
$holidayDays = [];
while ($row = mysqli_fetch_assoc($holidayRes)) {
    $holidayDays[$row['holiday_date']] = $row['holiday_name'];
}

// === Initialize PDF ===
$pdf = new \TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Task Log System');
$pdf->SetTitle('Task Log Report');
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// User and company info
$employeeName = htmlspecialchars($_SESSION['username'] ?? 'Employee');
$employerName = 'Mr J Dhlamini.';
$companyLogo = 'img/investhoodit-logo.jpeg';

// === Build HTML ===
$html = '<h2 style="text-align:center;">Weekly Task Log Report</h2>';

$html .= '<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="50%" style="text-align: left; font-size: 10pt;">
        <img src="' . $companyLogo . '" width="100" /><br /><br />
        136 2nd St, Randjespark<br />
        Johannesburg, 1685<br />
        Tel: 068 246 0562<br />
        Email: admin@investhoodit.co.za
    </td>
    <td width="50%" style="text-align: right; font-size: 10pt;">
        <strong>Employee:</strong> ' . $employeeName . '<br /><br />
        <strong>Employer:</strong> ' . $employerName . '
    </td>
  </tr>
</table>';

$html .= '<h3 style="text-align:center;">Period: ' . $startDate->format('M d, Y') . ' – ' . $endDate->format('M d, Y') . '</h3>';

$html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
    <thead>
        <tr style="font-weight: bold; background-color: #f2f2f2;">
            <th>Date</th>
            <th>Status</th>
            <th>Project</th>
            <th>Description</th>
            <th>Challenges</th>
            <th>Support Needed</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>';

// === Loop through each day: Monday to Sunday ===
$current = clone $startDate;
while ($current <= $endDate) {
    $dateStr = $current->format('Y-m-d');
    $dayOfWeek = $current->format('l');

    // Determine Status and color
    $status = '';
    $bgColor = '';

    $isWeekend = ($dayOfWeek === 'Saturday' || $dayOfWeek === 'Sunday');
    $isHoliday = isset($holidayDays[$dateStr]);
    $isLeave = isset($leaveDays[$dateStr]);
    $hasLog = isset($logMap[$dateStr]);

    if ($isWeekend && $isHoliday) {
        $status = 'Holiday & Weekend';
        $bgColor = '#fcefe0'; // holiday & weekend color
    } elseif ($isWeekend) {
        $status = 'Weekend';
        $bgColor = '#e6f2ff'; // weekend color
    } elseif ($isHoliday) {
        $status = 'Public Holiday (' . $holidayDays[$dateStr] . ')';
        $bgColor = '#fffae6'; // holiday color
    } elseif ($isLeave) {
        $status = 'On Leave (' . $leaveDays[$dateStr] . ')';
        $bgColor = '#ffe5b4'; // leave color
    } elseif ($hasLog) {
        $status = 'Present';
        $bgColor = '#d4edda'; // present color
    } else {
        // If none of above and weekday => Absent
        $status = 'Absent';
        $bgColor = '#f8d7da'; // absent color
    }

    $log = $logMap[$dateStr] ?? [];

    $html .= '<tr style="background-color:' . $bgColor . ';">
        <td>' . $dateStr . '</td>
        <td>' . htmlspecialchars($status) . '</td>
        <td>' . htmlspecialchars($log['client_project'] ?? '') . '</td>
        <td>' . htmlspecialchars($log['summary'] ?? '') . '</td>
        <td>' . htmlspecialchars($log['challenges'] ?? '') . '</td>
        <td>' . htmlspecialchars($log['support'] ?? '') . '</td>
        <td>' . htmlspecialchars($log['notes'] ?? '') . '</td>
    </tr>';

    $current->modify('+1 day');
}

$html .= '</tbody></table>';

// === Add Legend ===
$html .= '<br><br><table style="width: 100%; border-collapse: collapse; font-size: 12px;">
    <tbody>
        <tr>
            <td style="background-color:#d4edda; padding:6px 12px; text-align:center;">Present</td>
            <td style="background-color:#ffe5b4; padding:6px 12px; text-align:center;">Leave</td>
            <td style="background-color:#f8d7da; padding:6px 12px; text-align:center;">Absent</td>
            <td style="background-color:#fffae6; padding:6px 12px; text-align:center;">Holiday</td>
            <td style="background-color:#e6f2ff; padding:6px 12px; text-align:center;">Weekend</td>
            <td style="background-color:#fcefe0; padding:6px 12px; text-align:center;">Holiday & Weekend</td>
        </tr>
    </tbody>
</table>';

// === Output PDF ===
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('task_log_report.pdf', 'I'); // 'I' = display in browser
?>