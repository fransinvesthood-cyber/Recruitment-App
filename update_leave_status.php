<?php
session_start();
include('config.php');

// Only allow admin to update
if ($_SESSION['role'] !== 'Admin') {
    $_SESSION['message'] = "Unauthorized access.";
    $_SESSION['messageClass'] = "error";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = intval($_POST['consult_leave_id']);
    $status = $_POST['status'];

    // Validate status
    if (!in_array($status, ['Pending', 'Approved', 'Rejected'])) {
        $_SESSION['message'] = "Invalid status value.";
        $_SESSION['messageClass'] = "error";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Update the leave request status
    $stmt = $conn->prepare("UPDATE consultant_leaves SET status = ? WHERE consult_leave_id = ?");
    $stmt->bind_param("si", $status, $leave_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Leave request status updated successfully.";
        $_SESSION['messageClass'] = "success";
    } else {
        $_SESSION['message'] = "Failed to update leave request.";
        $_SESSION['messageClass'] = "error";
    }

    $stmt->close();
    $conn->close();

    header("Location: admin_dashboard.php");
    exit();
}
?>