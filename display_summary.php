<?php
// Include your MySQL connection file
include ('config.php');

// Start the session to get the user's ID
session_start();
$user_id = $_SESSION['user_id']; // Assuming the user ID is stored in the session

// Retrieve the professional summary from the database
$query = "SELECT professional_summary FROM applicant_profile WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);

// Check if the query returns a result
if (mysqli_num_rows($result) > 0) {
    // Fetch the professional summary
    $row = mysqli_fetch_assoc($result);
    $professional_summary = $row['professional_summary'];
} else {
    // If no summary is found, set a default message
    $professional_summary = '';
}

// Close the database connection
mysqli_close($conn);
?>