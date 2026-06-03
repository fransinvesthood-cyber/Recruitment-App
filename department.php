<?php
// Step 1: Connect to your database
include ('config.php');

// Step 2: Get the department from the query string (if set)
$department = isset($_GET['department']) ? $_GET['department'] : '';

// Step 3: Write the SQL query to fetch jobs based on the department
if ($department) {
    $sql = "SELECT * FROM job_postings WHERE department = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $department);  // "s" denotes a string parameter
} else {
    // If no department is selected, show all jobs
    $sql = "SELECT * FROM job_postings";
    $stmt = $conn->prepare($sql);
}

// Step 4: Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Step 5: Display the jobs
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='job'>";
        echo "<h2>" . htmlspecialchars($row['job_title']) . "</h2>";
        echo "<p>" . htmlspecialchars($row['description']) . "</p>";
        echo "<p><strong>Department:</strong> " . htmlspecialchars($row['department']) . "</p>";
        echo "<p><strong>Location:</strong> " . htmlspecialchars($row['location']) . "</p>";
        echo "<p><strong>Posted on:</strong> " . $row['posted_date'] . "</p>";
        echo "</div>";
    }
} else {
    echo "No job postings found.";
}

// Step 6: Close the connection
$conn->close();
?>
