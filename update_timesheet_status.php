<?php
session_start();
include('config.php');

header('Content-Type: application/json');

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized access. Admin access required."]);
    exit;
}

// Check if rejection_reason column exists, if not add it
$column_check = $conn->query("SHOW COLUMNS FROM consultant_timesheets LIKE 'rejection_reason'");
if ($column_check->num_rows == 0) {
    $conn->query('ALTER TABLE consultant_timesheets ADD COLUMN rejection_reason TEXT NULL');
}

// Collect and sanitize input
$timesheet_id = intval($_POST['timesheet_id'] ?? 0);
$status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
$rejection_reason = mysqli_real_escape_string($conn, $_POST['rejection_reason'] ?? '');

// Validate input
if ($timesheet_id <= 0) {
    echo json_encode(["error" => "Invalid timesheet ID."]);
    exit;
}

if (!in_array($status, ['Approved', 'Rejected'])) {
    echo json_encode(["error" => "Invalid status. Status must be 'Approved' or 'Rejected'."]);
    exit;
}

// If rejecting, require a reason
if ($status === 'Rejected' && empty($rejection_reason)) {
    echo json_encode(["error" => "Please provide a reason for rejection."]);
    exit;
}

// Update the timesheet status with rejection reason if provided
if ($status === 'Rejected') {
    $sql = "UPDATE consultant_timesheets SET status = ?, rejection_reason = ? WHERE consult_timesheet_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssi", $status, $rejection_reason, $timesheet_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["success" => true, "message" => "Timesheet {$status} successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . mysqli_stmt_error($stmt)]);
        }

        mysqli_stmt_close($stmt);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "SQL preparation failed: " . mysqli_error($conn)]);
    }
} else {
    // For approval, just update status
    $sql = "UPDATE consultant_timesheets SET status = ? WHERE consult_timesheet_id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $status, $timesheet_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["success" => true, "message" => "Timesheet {$status} successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . mysqli_stmt_error($stmt)]);
        }

        mysqli_stmt_close($stmt);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "SQL preparation failed: " . mysqli_error($conn)]);
    }
}

mysqli_close($conn);
?>
