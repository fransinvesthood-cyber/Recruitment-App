<?php
session_start();
include('config.php');

// Check if user is logged in and is a Consultant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Consultant' || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documents'])) {
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $upload_dir = "uploads/";

    foreach ($_FILES['documents']['name'] as $i => $name) {
        $tmp_name = $_FILES['documents']['tmp_name'][$i];
        $type = $_FILES['documents']['type'][$i];
        $size = $_FILES['documents']['size'][$i];

        // Validate file type
        if (!in_array($type, $allowed_types)) {
            echo "<script>alert('Invalid file type: $name');</script>";
            continue;
        }

        // Read file content
        $file_data = file_get_contents($tmp_name);
        $file_name = basename($name);
        $upload_date = date('Y-m-d');
        $document_type = pathinfo($file_name, PATHINFO_FILENAME); // e.g., ID Document

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO consultant_documents (user_id, document_type, upload_date, file_name, file_data) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssb", $user_id, $document_type, $upload_date, $file_name, $file_data);
        $stmt->send_long_data(4, $file_data);

        if ($stmt->execute()) {
            echo "<script>alert('$file_name uploaded successfully!');</script>";
        } else {
            echo "<script>alert('Failed to upload $file_name: " . mysqli_error($conn) . "');</script>";
        }
        $stmt->close();
    }

    // Redirect back to profile page
    echo "<script>window.location.href='consultant_profile.php';</script>";
} else {
    echo "<script>alert('No files selected.'); window.location.href='consultant_profile.php';</script>";
}
?>