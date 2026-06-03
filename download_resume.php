<?php
include('config.php');

if (isset($_GET['application_id'])) {
    $application_id = intval($_GET['application_id']);

    //Fetch the resume from the database
    $sql = "SELECT resume, resume_filename FROM job_applications WHERE application_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($resume, $resume_filename);
        $stmt->fetch();

        //Set headers for download
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$resume_filename\"");
        header("Content-Length: " . strlen($resume));

        echo $resume;
        exit;
    } else {
        echo "File not found!";
    }

    $stmt->close();
} else {
    echo "Invalid request!";
}

$conn->close();
?>