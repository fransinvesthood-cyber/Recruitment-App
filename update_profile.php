<?php
// Include database connection
include ('config.php');

// Start session to get user ID and store messages
session_start();
$user_id = $_SESSION['user_id'];

// Get and sanitize input data from POST, default to empty string
$professional_title_post = trim($_POST['professional_title'] ?? '');
$professional_summary_post = trim($_POST['professional_summary'] ?? '');

// Fetch current values from database
$current_title = '';
$current_summary = '';
$check_stmt = $conn->prepare("SELECT professional_title, professional_summary FROM applicant_profile WHERE user_id = ?");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
if ($check_result->num_rows > 0) {
    $row = $check_result->fetch_assoc();
    $current_title = $row['professional_title'] ?? '';
    $current_summary = $row['professional_summary'] ?? '';
}
$check_stmt->close();

// Use POST values if provided, otherwise keep current values
$final_title = $professional_title_post !== '' ? $professional_title_post : $current_title;
$final_summary = $professional_summary_post !== '' ? $professional_summary_post : $current_summary;

// Always update both fields
$app_check = $conn->prepare("SELECT app_profile_id FROM applicant_profile WHERE user_id=?");
$app_check->bind_param("i", $user_id);
$app_check->execute();
$app_check->store_result();

if ($app_check->num_rows > 0) {
    // Update both fields always
    $app_update = $conn->prepare("UPDATE applicant_profile SET professional_title=?, professional_summary=?, updated_at=NOW() WHERE user_id=?");
    $app_update->bind_param("ssi", $final_title, $final_summary, $user_id);
    if ($app_update->execute()) {
        $_SESSION['message'] = "Professional profile updated successfully!";
        $_SESSION['messageClass'] = "success";
    } else {
        $_SESSION['message'] = "Error updating profile.";
        $_SESSION['messageClass'] = "error";
    }
    $app_update->close();
} else {
    // Insert new record
    $app_insert = $conn->prepare("INSERT INTO applicant_profile (user_id, professional_title, professional_summary, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $app_insert->bind_param("iss", $user_id, $final_title, $final_summary);
    if ($app_insert->execute()) {
        $_SESSION['message'] = "Professional profile created successfully!";
        $_SESSION['messageClass'] = "success";
    } else {
        $_SESSION['message'] = "Error creating profile.";
        $_SESSION['messageClass'] = "error";
    }
    $app_insert->close();
}
$app_check->close();

// Fetch updated values and set session vars for immediate display
$sql = "SELECT professional_title, professional_summary FROM applicant_profile WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($updated_title, $updated_summary);
$stmt->fetch();
$stmt->close();

$_SESSION['professional_title'] = $updated_title ?? '';
$_SESSION['professional_summary'] = $updated_summary ?? '';

// Close the database connection
$conn->close();

// Redirect back to my_profile page
header("Location: my_profile.php");
exit();
?>