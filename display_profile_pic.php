<?php
include ('config.php'); // Database connection
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in.");
}

$user_id = $_SESSION['user_id'];

// Fetch the profile picture from the database
$sql = "SELECT profile_picture FROM applicant_profile WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_picture);
$stmt->fetch();
$stmt->close();
$conn->close();

if ($profile_picture) {
    // Display the image
    header("Content-Type: image/jpeg"); // Change to image/png or image/gif if needed
    echo $profile_picture;
} else {
    // Display a default profile picture if no image is found
    readfile("default-profile.png"); // Make sure this file exists in your project
}
?>