<?php
include('config.php'); // Connect to database

// Debugging step: check POST data
var_dump($_POST); // This will display the contents of the POST request

// Check if both application_id and comments are set in the POST data
if (!isset($_POST['application_id']) || !isset($_POST['comments'])) {
    echo "Missing application_id or comments.";
    exit();
}

// Get the application ID and comments from the POST request
$application_id = $_POST['application_id'];
$comments = $_POST['comments'];

// Proceed with your database operations (Insert or Update)...
$sql = "INSERT INTO job_applications (application_id, comments) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE comments = ?";

$stmt = $conn->prepare($sql);

// Check if the prepare failed
if ($stmt === false) {
    echo "Error preparing the query: " . $conn->error;
    exit();
}

// Bind the parameters to the prepared statement
$stmt->bind_param("iss", $application_id, $comments, $comments);

// Execute the statement
if ($stmt->execute()) {
    // Redirect back to the applications page after saving the comment
    header("Location: manage_applications.php");
    exit();
} else {
    // Handle error if the insert/update fails
    echo "Error saving comment: " . $stmt->error;
}
?>