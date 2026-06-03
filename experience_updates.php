<?php
session_start();
include ('config.php'); // Connect to your database

if (isset($_POST['update_experience'])) {
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $duties = mysqli_real_escape_string($conn, $_POST['duties']);
    $user_id = $_SESSION['user_id']; // Ensure user is logged in and has an ID stored in session

    // Check if the position already exists for this user
    $check_query = "SELECT * FROM work_experience WHERE user_id = '$user_id' AND position = '$position'";
    $result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($result) > 0) {
        // Update existing record
        $update_query = "UPDATE work_experience 
                         SET company_name = '$company_name', duration = '$duration', duties = '$duties'
                         WHERE user_id = '$user_id' AND position = '$position'";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['message'] = "Work experience updated successfully!";
            $_SESSION['messageClass'] = "alert alert-success";
        } else {
            $_SESSION['message'] = "Error updating work experience: " . mysqli_error($conn);
            $_SESSION['messageClass'] = "alert alert-danger";
        }
    } else {
        // Insert new record if the position doesn't exist
        $insert_query = "INSERT INTO work_experience (user_id, position, company_name, duration, duties) 
                         VALUES ('$user_id', '$position', '$company_name', '$duration', '$duties')";

        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['message'] = "Work experience added successfully!";
            $_SESSION['messageClass'] = "alert alert-success";
        } else {
            $_SESSION['message'] = "Error adding work experience: " . mysqli_error($conn);
            $_SESSION['messageClass'] = "alert alert-danger";
        }
    }

    // Redirect back to the applicant page to show the message
    header("Location: applicant.php");
    exit();
}
?>