<?php
// download_project.php

$conn = new mysqli("localhost", "root", "", "recruitment_db");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "SELECT file_name, file_path FROM submitted_projects WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($file_name, $file_path);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && file_exists($file_path)) {
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=\"" . basename($file_name) . "\"");
        header("Content-Length: " . filesize($file_path));
        readfile($file_path);
        exit;
    } else {
        echo "File not found.";
    }
}
