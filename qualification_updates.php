<?php
session_start();
include ('config.php'); // Connect to your database

if (isset($_POST['update_qualification'])) {
    $school_name = mysqli_real_escape_string($conn, $_POST['school_name']);
    $year_completed = intval($_POST['year_completed']);
    $qualification_name = mysqli_real_escape_string($conn, $_POST['qualification_name']);
    $institution = mysqli_real_escape_string($conn, $_POST['institution']);
    $year_graduated = intval($_POST['year_graduated']);
    $user_id = $_SESSION['user_id'];

    // Check if the qualification already exists
    $check_query = "SELECT * FROM qualifications WHERE user_id = '$user_id' AND qualification_name = '$qualification_name'";
    $result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($result) > 0) {
        // Update existing qualification
        $update_query = "UPDATE qualifications 
                         SET school_name = '$school_name', year_graduated = '$year_graduated', institution = '$institution', year_completed = '$year_completed' 
                         WHERE user_id = '$user_id' AND qualification_name = '$qualification_name'";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['message'] = "Qualification updated successfully!";
            $_SESSION['messageClass'] = "alert alert-success";
        } else {
            $_SESSION['message'] = "Error updating qualification: " . mysqli_error($conn);
            $_SESSION['messageClass'] = "alert alert-danger";
        }

        header("Location: applicant.php");
        exit();
    } else {
        // Insert new qualification
        $insert_query = "INSERT INTO qualifications (user_id, school_name, year_graduated, qualification_name, institution, year_completed) 
                         VALUES ('$user_id', '$school_name', '$year_graduated', '$qualification_name', '$institution', '$year_completed')";

        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['message'] = "Qualification added successfully!";
            $_SESSION['messageClass'] = "alert alert-success";
        } else {
            $_SESSION['message'] = "Error adding qualification: " . mysqli_error($conn);
            $_SESSION['messageClass'] = "alert alert-danger";
        }

        header("Location: applicant.php");
        exit();
    }
}
?>