<?php
session_start();

// Ensure user is logged in and has Consultant role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Consultant') {
    die("Unauthorized access.");
}

include('config.php');

$user_id = $_SESSION['user_id'];

// Set date range (e.g., current week)
$startDate = new DateTime(); // This week
$startDate->modify('this week Monday');
$endDate = clone $startDate;
$endDate->modify('+6 days');

$start_date_str = $startDate->format('Y-m-d');
$end_date_str = $endDate->format('Y-m-d');

// Get submitted timesheets
$stmt = $conn->prepare("SELECT work_date, client_project, hours_worked, billable FROM consultant_timesheets WHERE user_id = ? AND work_date BETWEEN ? AND ?");
$stmt->bind_param("iss", $user_id, $start_date_str, $end_date_str);
$stmt->execute();
$stmt->bind_result($work_date, $client_project, $hours_worked, $billable);

$timesheets = [];
while ($stmt->fetch()) {
    $timesheets[] = [
        'work_date' => $work_date,
        'client_project' => $client_project,
        'hours_worked' => $hours_worked,
        'billable' => $billable
    ];
}
$stmt->close();

// Get approved leaves
$stmt = $conn->prepare("SELECT leave_type, start_date, end_date, reason FROM consultant_leaves WHERE user_id = ? AND status = 'Approved' AND start_date <= ? AND end_date >= ?");
$stmt->bind_param("iss", $user_id, $end_date_str, $start_date_str);
$stmt->execute();
$stmt->bind_result($leave_type, $start_date, $end_date, $reason);

$leaves = [];
while ($stmt->fetch()) {
    $leaves[] = [
        'leave_type' => $leave_type,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'reason' => $reason
    ];
}
$stmt->close();

// Get holidays
$stmt = $conn->prepare("SELECT holiday_name, holiday_date FROM public_holidays WHERE holiday_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date_str, $end_date_str);
$stmt->execute();
$stmt->bind_result($holiday_name, $holiday_date);

$holidays = [];
$holidayDates = [];
while ($stmt->fetch()) {
    $holidays[] = [
        'holiday_name' => $holiday_name,
        'holiday_date' => $holiday_date
    ];
    $holidayDates[] = $holiday_date;
}
$stmt->close();
?>

<h3>Weekly Timesheet Report (<?= $startDate->format('M d') ?> – <?= $endDate->format('M d') ?>)</h3>

<table border="1" cellspacing="0" cellpadding="8" style="width:100%; border-collapse:collapse;">
    <thead>
        <tr style="background-color: #f2f2f2;">
            <th>Date</th>
            <th>Status</th>
            <th>Project / Description</th>
            <th>Hours Worked</th>
            <th>Billable</th>
        </tr>
    </thead>
    <tbody>
        <?php
        for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
            $currentDate = $date->format('Y-m-d');
            $dayOfWeek = $date->format('w');

            $entry = [
                'status' => 'Absent',
                'project' => '',
                'hours' => '',
                'billable' => ''
            ];
            $rowStyle = 'background-color: #ffe6e6;'; // light red for absent

            $isWeekend = in_array($dayOfWeek, [0, 6]);
            $isHoliday = false;
            $holidayName = '';

            foreach ($holidays as $holiday) {
                if ($holiday['holiday_date'] === $currentDate) {
                    $isHoliday = true;
                    $holidayName = $holiday['holiday_name'];
                    break;
                }
            }

            $hasTimesheet = false;
            foreach ($timesheets as $ts) {
                if ($ts['work_date'] === $currentDate) {
                    $entry['status'] = 'Present';
                    $entry['project'] = $ts['client_project'];
                    $entry['hours'] = $ts['hours_worked'];
                    $entry['billable'] = $ts['billable'];
                    $hasTimesheet = true;
                    $rowStyle = 'background-color: #e6ffe6;'; // light green
                    break;
                }
            }

            if (!$hasTimesheet && !$isHoliday && !$isWeekend) {
                foreach ($leaves as $leave) {
                    if ($currentDate >= $leave['start_date'] && $currentDate <= $leave['end_date']) {
                        $entry['status'] = 'Leave (' . $leave['leave_type'] . ')';
                        $entry['project'] = $leave['reason'];
                        $rowStyle = 'background-color: #fff0cc;'; // light orange
                        break;
                    }
                }
            }

            if (!$hasTimesheet && $entry['status'] === 'Absent') {
                if ($isHoliday && $isWeekend) {
                    $entry['status'] = 'Holiday & Weekend';
                    $entry['project'] = $holidayName;
                    $rowStyle = 'background-color: #fcefe0;';
                } elseif ($isHoliday) {
                    $entry['status'] = 'Holiday';
                    $entry['project'] = $holidayName;
                    $rowStyle = 'background-color: #fff5cc;';
                } elseif ($isWeekend) {
                    $entry['status'] = 'Weekend';
                    $rowStyle = 'background-color: #e6f2ff;';
                }
            }
        ?>
        <tr style="border-bottom:1px solid #ccc; <?= $rowStyle ?>">
            <td><?= htmlspecialchars($currentDate) ?></td>
            <td><?= htmlspecialchars($entry['status']) ?></td>
            <td><?= htmlspecialchars($entry['project']) ?></td>
            <td><?= htmlspecialchars($entry['hours']) ?></td>
            <td><?= htmlspecialchars($entry['billable']) ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<p><strong>Legend:</strong></p>
<ul style="list-style:none; padding-left:0;">
    <li style="background-color:#e6ffe6; display:inline-block; padding:4px 12px; margin-right:10px;">Present</li>
    <li style="background-color:#ffe6e6; display:inline-block; padding:4px 12px; margin-right:10px;">Absent</li>
    <li style="background-color:#fff0cc; display:inline-block; padding:4px 12px; margin-right:10px;">Leave</li>
    <li style="background-color:#fff5cc; display:inline-block; padding:4px 12px; margin-right:10px;">Holiday</li>
    <li style="background-color:#e6f2ff; display:inline-block; padding:4px 12px; margin-right:10px;">Weekend</li>
    <li style="background-color: #fcefe0; display:inline-block; padding:4px 12px;">Holiday & Weekend</li>
</ul>

<form method="POST" action="export_timesheet.php" onsubmit="return validateForm(this)" style="margin-top:20px;">
    <label for="signature">Enter your name to sign:</label>
    <input type="text" id="signature" name="signature" required />

    <label for="export_format" style="margin-left:10px;">Export as:</label>
    <select name="export_format" id="export_format" required>
        <option value="">-- Select --</option>
        <option value="csv">PDF</option>
    </select>

    <button type="submit" class="btn" style="margin-left:10px;">
        <i class='bx bx-download'></i> Export
    </button>
</form>

<script>
function validateForm(form) {
    const signature = form.signature.value.trim();
    if (!signature) {
        alert("Please enter your name to digitally sign the timesheet before exporting.");
        return false;
    }
    return true;
}
</script>