<?php
include ('config.php'); // Database connection
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_picture"])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = "Error: User not logged in.";
        $_SESSION['messageClass'] = "alert alert-danger";
        header("Location: my_profile.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    if ($_FILES["profile_picture"]["error"] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error: File upload failed.";
        $_SESSION['messageClass'] = "alert alert-danger";
        header("Location: my_profile.php");
        exit();
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($_FILES["profile_picture"]["tmp_name"]);
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['message'] = "Error: Invalid file format.";
        $_SESSION['messageClass'] = "alert alert-danger";
        header("Location: my_profile.php");
        exit();
    }

    // Read image data
    $image = file_get_contents($_FILES["profile_picture"]["tmp_name"]);
    if (!$image) {
        $_SESSION['message'] = "Error: Unable to read image data.";
        $_SESSION['messageClass'] = "alert alert-danger";
    header("Location: my_profile.php");
    exit();
}

    // Check if user profile already exists
    $checkUser = $conn->prepare("SELECT user_id FROM applicant_profile WHERE user_id = ?");
    $checkUser->bind_param("i", $user_id);
    $checkUser->execute();
    $result = $checkUser->get_result();
    $checkUser->close();

    if ($result->num_rows === 0) {
        // INSERT new record
        $sql = "INSERT INTO applicant_profile (user_id, profile_picture) VALUES (?, ?)";
    } else {
        // UPDATE existing record
        $sql = "UPDATE applicant_profile SET profile_picture = ? WHERE user_id = ?";
    }

    $stmt = $conn->prepare($sql);

    if ($result->num_rows === 0) {
        $stmt->bind_param("ib", $user_id, $null);
        $stmt->send_long_data(1, $image); // Send BLOB data
    } else {
        $stmt->bind_param("bi", $null, $user_id);
        $stmt->send_long_data(0, $image); // Send BLOB data
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Profile picture uploaded successfully!";
        $_SESSION['messageClass'] = "alert alert-success";
    } else {
        $_SESSION['message'] = "Error uploading profile picture: " . $stmt->error;
        $_SESSION['messageClass'] = "alert alert-danger";
    }

    $stmt->close();
    $conn->close();

    header("Location: my_profile.php");
    exit();
}
?>