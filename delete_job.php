<?php
include('config.php');

if (isset($_GET['job_id'])) {
    $job_id = (int)$_GET['job_id']; //Ensure it's an integer for security

    //Get department ID before deleting the job
    $sql = "SELECT department_id FROM job_postings WHERE job_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $department_id = $row['department_id'];

        //Delete the job posting
        $sql = "DELETE FROM job_postings WHERE job_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $job_id);

        if ($stmt->execute()) {
            //Check if the department is used in other job postings
            $sql = "SELECT COUNT(*) as count FROM job_postings WHERE department_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $department_id);
            $stmt->execute();
            $countResult = $stmt->get_result();
            $countRow = $countResult->fetch_assoc();

            //If no other job uses the department, delete it
            if ($countRow['count'] == 0) {
                $sql = "DELETE FROM departments WHERE department_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $department_id);
                $stmt->execute();
            }

            echo "<script>alert('Job listing deleted successfully!!'); window.location.href='manage_jobs.php';</script>";
        } else {
            echo "Error deleting job: " . $conn->error;
        }
    } else {
        echo "Job not found.";
    }

    $stmt->close();
}

$conn->close();
?>
