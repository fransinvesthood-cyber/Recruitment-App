<?php
// Show all errors while troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set JSON header
header('Content-Type: application/json');

// Connect to the database
$conn = new mysqli("localhost", "root", "", "recruitment_db");

// Check for connection errors
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Use only confirmed columns: client_name, communication_rating, professionalism_rating, collaboration_rating, comments, time
$sql = "SELECT 
    client_name, 
    communication_rating AS communication, 
    professionalism_rating AS professionalism, 
    collaboration_rating AS collaboration, 
    comments, 
    submitted_at
FROM client_feedback
ORDER BY submitted_at DESC"; // Order by 'time' if you want latest feedback first

$result = $conn->query($sql);

// Handle SQL error
if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => "SQL error: " . $conn->error]);
    exit;
}

// Fetch feedback data
$feedbacks = [];

while ($row = $result->fetch_assoc()) {
    $feedbacks[] = $row;
}

// Close connection
$conn->close();

// Return as JSON
echo json_encode($feedbacks);
?>
