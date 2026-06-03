<?php
include('config.php');

if (isset($_POST['application_id']) && isset($_POST['application_status'])) {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['application_status'];

    // Prepare and execute the update query
    $stmt = $conn->prepare("UPDATE job_applications SET application_status = ? WHERE application_id = ?");
    $stmt->bind_param("si", $new_status, $application_id);

    if ($stmt->execute()) {
        // Fetch counts for all statuses
        $result = $conn->query("SELECT 
            SUM(CASE WHEN application_status = 'Shortlisted' THEN 1 ELSE 0 END) AS shortlisted_count,
            SUM(CASE WHEN application_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count,
            SUM(CASE WHEN application_status = 'Hired' THEN 1 ELSE 0 END) AS hired_count
            FROM job_applications");

        $row = $result->fetch_assoc();
        $shortlisted_count = $row['shortlisted_count'];
        $rejected_count = $row['rejected_count'];
        $hired_count = $row['hired_count'];

        echo "<script>
            alert('Status updated successfully!!'); window.location.href='manage_applications.php';
        </script>";
    } else {
        echo "Error updating status: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request! Missing application_id or application_status.";
}
?>