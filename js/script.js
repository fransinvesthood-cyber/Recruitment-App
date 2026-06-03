<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $cv = $_FILES['cv'];

    if ($cv['error'] == 0) {
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($cv['name']);

        if (move_uploaded_file($cv['tmp_name'], $uploadFile)) {
            echo "CV uploaded successfully!";
        } else {
            echo "Error uploading CV.";
        }
    } else {
        echo "Please select a CV to upload.";
    }
}
?>
