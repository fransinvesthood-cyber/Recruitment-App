<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['resumeUpload'])) {
    $file = $_FILES['resumeUpload'];
    $uploadDir = 'uploads/'; 
    
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));


    if ($fileExtension == 'pdf') {

        $uploadFile = $uploadDir . basename($file['name']);
        
        if ($file['error'] === UPLOAD_ERR_OK) {
          
            if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                echo "Resume uploaded successfully!";
            } else {
                echo "Error moving the uploaded file.";
            }
        } else {
            echo "File upload error. Code: " . $file['error'];
        }
    } else {
        echo "Invalid file type. Only PDF files are allowed.";
    }
} else {
    echo "No file uploaded or invalid request.";
}
?>
