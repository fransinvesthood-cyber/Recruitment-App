<?php
//Database connection (replace with your own credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "recruitment_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Gemini API Key for chatbot
define('GEMINI_API_KEY', 'AIzaSyDri5QBA-FmavS4f5kxMETlcCMuYc3FvFA');

// Affinda API Key for resume parsing
define('AFFINDA_API_KEY', 'aff_4eb5affdf737a1aa632dada39a566863d1ecaa54'); // Replace with your actual Affinda API key

// Alternative: Use Gemini as fallback if Affinda credits are exhausted
define('USE_AFFINDA', false); // Set to true when you have valid Affinda credits

// Google OAuth Configuration
// Replace these with your actual Google OAuth credentials from Google Cloud Console
define('GOOGLE_CLIENT_ID', 'your-actual-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'your-actual-client-secret');
define('GOOGLE_REDIRECT_URI', 'http://localhost/recruitment-project-phps/google_callback.php');
?>
