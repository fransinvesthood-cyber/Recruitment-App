<?php
$conn = new mysqli("localhost", "root", "", "recruitment_db");

if ($conn->connect_error) {
    http_response_code(500);
    echo "Database connection failed: " . $conn->connect_error;
    exit;
}

$client_name = trim($_POST['client_name'] ?? '');
$communication_rating = intval($_POST['communication'] ?? 0);
$professionalism_rating = intval($_POST['professionalism'] ?? 0);
$collaboration_rating = intval($_POST['collaboration'] ?? 0);
$comments = trim($_POST['comments'] ?? '');


// Validate
if (
    $communication_rating < 1 || $communication_rating > 5 ||
    $professionalism_rating < 1 || $professionalism_rating > 5 ||
    $collaboration_rating < 1 || $collaboration_rating > 5
) {
    http_response_code(400);
    echo "Ratings must be between 1 and 5.";
    exit;
}

$stmt = $conn->prepare("INSERT INTO client_feedback (
    client_name, communication_rating, professionalism_rating, collaboration_rating, comments
) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    http_response_code(500);
    echo "Prepare failed: " . $conn->error;
    exit;
}

$stmt->bind_param("siiis", $client_name, $communication_rating, $professionalism_rating, $collaboration_rating, $comments);

if ($stmt->execute()) {
    echo "success";
} else {
    http_response_code(500);
    echo "Execute failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
