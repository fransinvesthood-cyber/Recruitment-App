<?php
session_start();
include('config.php'); // Connect to database

if (isset($_POST['update_skills'])) {
    $soft_skills = mysqli_real_escape_string($conn, $_POST['soft_skills']);
    $technical_skills = mysqli_real_escape_string($conn, $_POST['technical_skills']);
    $user_id = $_SESSION['user_id'];

    // Check if skills already exist for the user
    $check_query = "SELECT * FROM skills WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($result) > 0) {
        // Update existing skills
        $update_query = "UPDATE skills 
                         SET soft_skills = '$soft_skills', technical_skills = '$technical_skills' 
                         WHERE user_id = '$user_id'";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['message'] = "Skills updated successfully!";
            $_SESSION['messageClass'] = "alert alert-success";
        } else {
            $_SESSION['message'] = "Error updating skills: " . mysqli_error($conn);
            $_SESSION['messageClass'] = "alert alert-danger";
        }

    } else {
        // Insert new skills
        $insert_query = "INSERT INTO skills (user_id, soft_skills, technical_skills) 
                         VALUES ('$user_id', '$soft_skills', '$technical_skills')";

        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['message'] = "Skills added successfully!";
            $_SESSION['messageClass'] = "alert alert-success";
        } else {
            $_SESSION['message'] = "Error adding skills: " . mysqli_error($conn);
            $_SESSION['messageClass'] = "alert alert-danger";
        }
    }

    // Redirect back to applicant page to show message
    header("Location: applicant.php");
    exit();
}
?>