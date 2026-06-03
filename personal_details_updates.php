<?php
session_start();
include ('config.php'); // Connect to your database

if (isset($_POST['update_personal_details'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $user_id = $_SESSION['user_id']; // Ensure user is logged in and has an ID stored in session

    $query = "UPDATE users SET 
                fullname='$fullname', 
                gender='$gender', 
                dob='$dob', 
                email='$email', 
                phone='$phone', 
                address='$address' 
              WHERE user_id='$user_id'";

    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Personal details updated successfully!";
        $_SESSION['messageClass'] = "alert alert-success"; // Bootstrap success class
    } else {
        $_SESSION['message'] = "Error updating profile: " . mysqli_error($conn);
        $_SESSION['messageClass'] = "alert alert-success"; // Bootstrap success class
    }
    header("Location: applicant.php");
    exit();
}
?>