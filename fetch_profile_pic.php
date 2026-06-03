<?php
include('config.php'); // Ensure database connection is included

if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']); // Ensure it's an integer

    // Retrieve the image from the database
    $sql = "SELECT profile_picture FROM applicant_profile WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($imageData);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    // Ensure image data exists
    if ($imageData) {
        header("Content-Type: image/jpeg"); // Change to image/png if needed
        echo $imageData;
    } else {
        // If no image is found, serve a default image
        header("Content-Type: image/jpeg");
        readfile("uploads/default.jpg"); // Ensure "default.jpg" exists
    }
}
?>
