<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Consultant') {
    die("Unauthorized access.");
}

$signature = trim($_POST['signature'] ?? '');
if (empty($signature)) {
    die("Signature is required to export the timesheet.");
}

include('config.php');
$user_id = $_SESSION['user_id'];

$startDate = new DateTime();
$startDate->modify('this week Monday');
$endDate = clone $startDate;
$endDate->modify('+6 days');

$start_date_str = $startDate->format('Y-m-d');
$end_date_str   = $endDate->format('Y-m-d');

// === Fetch Timesheets ===
$stmt = $conn->prepare("SELECT work_date, client_project, hours_worked, billable FROM consultant_timesheets WHERE user_id = ? AND work_date BETWEEN ? AND ?");
$stmt->bind_param("iss", $user_id, $start_date_str, $end_date_str);
$stmt->execute();
$stmt->bind_result($work_date, $client_project, $hours_worked, $billable);

$timesheets = [];
while ($stmt->fetch()) {
    $timesheets[$work_date] = [
        'client_project' => $client_project,
        'hours_worked' => $hours_worked,
        'billable' => $billable
    ];
}
$stmt->close();

// === Fetch Approved Leaves ===
$stmt = $conn->prepare("SELECT leave_type, start_date, end_date, reason FROM consultant_leaves WHERE user_id = ? AND status = 'Approved' AND start_date <= ? AND end_date >= ?");
$stmt->bind_param("iss", $user_id, $end_date_str, $start_date_str);
$stmt->execute();
$stmt->bind_result($leave_type, $start_date, $end_date, $reason);

$leaves = [];
while ($stmt->fetch()) {
    $leaves[] = compact('leave_type', 'start_date', 'end_date', 'reason');
}
$stmt->close();

// === Fetch Holidays ===
$stmt = $conn->prepare("SELECT holiday_name, holiday_date FROM public_holidays WHERE holiday_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date_str, $end_date_str);
$stmt->execute();
$stmt->bind_result($holiday_name, $holiday_date);

$holidays = [];
while ($stmt->fetch()) {
    $holidays[$holiday_date] = $holiday_name;
}
$stmt->close();

// === Build Timesheet Data ===
$data = [];
for ($date = new DateTime($start_date_str); $date <= $endDate; $date->modify('+1 day')) {
    $current = $date->format('Y-m-d');
    $dayOfWeek = $date->format('w');

    $entry = [
        'Date' => $current,
        'Status' => 'Absent',
        'Project / Description' => '',
        'Hours Worked' => '',
        'Billable' => ''
    ];

    $isWeekend = in_array($dayOfWeek, [0, 6]);
    $isHoliday = array_key_exists($current, $holidays);

    if (isset($timesheets[$current])) {
        $entry['Status'] = 'Present';
        $entry['Project / Description'] = $timesheets[$current]['client_project'];
        $entry['Hours Worked'] = $timesheets[$current]['hours_worked'];
        $entry['Billable'] = $timesheets[$current]['billable'];
    } elseif (!$isWeekend && !$isHoliday) {
        foreach ($leaves as $l) {
            if ($current >= $l['start_date'] && $current <= $l['end_date']) {
                $entry['Status'] = 'Leave (' . $l['leave_type'] . ')';
                $entry['Project / Description'] = $l['reason'];
                break;
            }
        }
    } elseif ($isHoliday && $isWeekend) {
        $entry['Status'] = 'Holiday & Weekend';
        $entry['Project / Description'] = $holidays[$current];
    } elseif ($isHoliday) {
        $entry['Status'] = 'Holiday';
        $entry['Project / Description'] = $holidays[$current];
    } elseif ($isWeekend) {
        $entry['Status'] = 'Weekend';
    }

    $data[] = $entry;
}

class MYPDF extends \TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'Weekly Timesheet Report', 0, 1, 'C');
    }
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Generated on ' . date('F j, Y') . ' | Signed by: ' . ($_POST['signature'] ?? ''), 0, 0, 'C');
    }
}

$pdf = new MYPDF();
$pdf->AddPage();
$employeeName = htmlspecialchars($_SESSION['username'] ?? 'Employee');
$employerName = 'Mr J Dhlamini.';
$companyLogo = 'img/investhoodit-logo.jpeg';

$html = '<table width="100%" cellpadding="0" cellspacing="0">
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

$html .= '<h3 style="text-align:center;">Period: ' . $startDate->format('M d') . ' – ' . $endDate->format('M d') . '</h3>';

$html .= '<table border="1" cellpadding="5" width="100%">
            <thead>
              <tr style="background-color:#f2f2f2;">
                <th>Date</th>
                <th>Status</th>
                <th>Project / Description</th>
                <th>Hours Worked</th>
                <th>Billable</th>
              </tr>
            </thead><tbody>';

foreach ($data as $row) {
    $rowStyle = '';
    if ($row['Status'] === 'Present') {
        $rowStyle = ' style="background-color: #d4edda;"';
    } elseif (strpos($row['Status'], 'Leave') === 0) {
        $rowStyle = ' style="background-color: #ffe5b4;"';
    } elseif ($row['Status'] === 'Absent') {
        $rowStyle = ' style="background-color: #f8d7da;"';
    } elseif ($row['Status'] === 'Holiday') {
        $rowStyle = ' style="background-color: #fffae6;"';
    } elseif ($row['Status'] === 'Weekend') {
        $rowStyle = ' style="background-color: #e6f2ff;"';
    } elseif ($row['Status'] === 'Holiday & Weekend') {
        $rowStyle = ' style="background-color: #fcefe0;"';
    }

    $html .= '<tr' . $rowStyle . '>';
    foreach ($row as $cell) {
        $html .= '<td>' . htmlspecialchars($cell) . '</td>';
    }
    $html .= '</tr>';
}

$html .= '</tbody></table>';

$html .= '<br><br>
<table style="font-size: 10pt;">
  <tr>
    <td style="background-color:#d4edda; padding:4px 10px;">Present</td>
    <td style="background-color:#ffe5b4; padding:4px 10px;">Leave</td>
    <td style="background-color:#f8d7da; padding:4px 10px;">Absent</td>
    <td style="background-color:#fffae6; padding:4px 10px;">Holiday</td>
    <td style="background-color:#e6f2ff; padding:4px 10px;">Weekend</td>
    <td style="background-color:#fcefe0; padding:4px 10px;">Holiday & Weekend</td>
  </tr>
</table>
<br><br>';

$html .= '<table width="100%" style="font-size: 10pt;">
  <tr>
    <td width="50%">
      <table width="100%">
        <tr><td><strong>Employee Signature:</strong></td><td style="text-align:right;">___________________</td></tr>
        <tr><td><br></td><td></td></tr>
        <tr><td><strong>Date:</strong></td><td style="text-align:right;">___________________</td></tr>
      </table>
    </td>
    <td width="50%">
      <table width="100%">
        <tr><td><strong>Employer Signature:</strong></td><td style="text-align:right;">___________________</td></tr>
        <tr><td><br></td><td></td></tr>
        <tr><td><strong>Date:</strong></td><td style="text-align:right;">___________________</td></tr>
      </table>
    </td>
  </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('Weekly_Timesheet_Report.pdf', 'I');
exit;