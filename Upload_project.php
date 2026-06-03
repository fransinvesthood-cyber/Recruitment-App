<?php
session_start(); 
header('Content-Type: application/json');

// 1. Connect to DB
$conn = new mysqli("localhost", "root", "", "recruitment_db");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// 2. Check if file was uploaded
if (!isset($_FILES['project_zip']) || $_FILES['project_zip']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "No file uploaded or upload error"]);
    exit;
}

// 3. Validate ZIP file
$fileTmpPath = $_FILES['project_zip']['tmp_name'];
$fileName = $_FILES['project_zip']['name'];
$fileSize = $_FILES['project_zip']['size'];
$fileType = $_FILES['project_zip']['type'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileExtension !== 'zip') {
    echo json_encode(["status" => "error", "message" => "Only ZIP files are allowed"]);
    exit;
}

// 4. Create upload directory if not exists
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 5. Move file
$newFileName = time() . "_" . $fileName; // unique name
$destPath = $uploadDir . $newFileName;

if (move_uploaded_file($fileTmpPath, $destPath)) {
    // 6. Insert into database (store only file name/path)
    $stmt = $conn->prepare("INSERT INTO submitted_projects (file_name, file_path, uploaded_by, uploaded_at) VALUES (?, ?, ?, NOW())");
    $uploadedBy = $_SESSION['consultant_name'] ?? "Unknown"; // if session is set
    $filePathDB = "uploads/" . $newFileName;
    $stmt->bind_param("sss", $fileName, $filePathDB, $uploadedBy);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Project uploaded successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database insert failed"]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "File could not be saved"]);
}

$conn->close();
?>
