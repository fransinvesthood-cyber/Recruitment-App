<?php
/**
 * Run once to add minimum_criteria column to job_postings if it doesn't exist.
 * Also optionally backfills empty values with a default JSON structure.
 */
include('config.php');

// Check if column exists
$res = $conn->query("SHOW COLUMNS FROM job_postings LIKE 'minimum_criteria'");
if ($res === false) {
    echo "Error checking columns: " . $conn->error . PHP_EOL;
    exit(1);
}
if ($res->num_rows > 0) {
    echo "Column 'minimum_criteria' already exists.\n";
} else {
    // Try adding JSON column first
    $sql = "ALTER TABLE job_postings ADD COLUMN minimum_criteria JSON DEFAULT NULL";
    if ($conn->query($sql) === TRUE) {
        echo "Added JSON column minimum_criteria.\n";
    } else {
        // If JSON not supported, fall back to TEXT
        echo "JSON column add failed (" . $conn->error . "). Trying TEXT fallback...\n";
        $sql2 = "ALTER TABLE job_postings ADD COLUMN minimum_criteria TEXT DEFAULT NULL";
        if ($conn->query($sql2) === TRUE) {
            echo "Added TEXT column minimum_criteria.\n";
        } else {
            echo "Failed to add minimum_criteria column: " . $conn->error . PHP_EOL;
            exit(1);
        }
    }
}

// Optional: backfill existing NULLs to an empty criteria structure if desired
$doBackfill = true; // set false to skip
if ($doBackfill) {
    $default = json_encode(['required_skills' => [], 'required_qualification' => '', 'min_years_experience' => 0]);
    // For JSON column, set JSON string; for TEXT column, same
    $stmt = $conn->prepare("UPDATE job_postings SET minimum_criteria = ? WHERE minimum_criteria IS NULL");
    $stmt->bind_param('s', $default);
    if ($stmt->execute()) {
        echo "Backfilled existing jobs with default minimum_criteria.\n";
    } else {
        echo "Backfill failed: " . $stmt->error . PHP_EOL;
    }
    $stmt->close();
}

$conn->close();
echo "Done.\n";
?>