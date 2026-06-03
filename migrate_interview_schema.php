<?php
/**
 * Migration script to add modern interview scheduling columns
 * Run this once to update the database schema
 */
include 'config.php';

$queries = [
    "ALTER TABLE interviews 
     ADD COLUMN IF NOT EXISTS interview_type ENUM('Online','In-person') DEFAULT 'In-person' AFTER interview_status,
     ADD COLUMN IF NOT EXISTS meeting_link VARCHAR(500) DEFAULT NULL AFTER interview_type,
     ADD COLUMN IF NOT EXISTS duration_minutes INT DEFAULT 30 AFTER meeting_link,
     ADD COLUMN IF NOT EXISTS interviewer_ids JSON DEFAULT NULL AFTER interviewer",

    "CREATE TABLE IF NOT EXISTS interviewers (
        interviewer_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        department VARCHAR(100) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS interviewer_availability (
        availability_id INT AUTO_INCREMENT PRIMARY KEY,
        interviewer_id INT NOT NULL,
        unavailable_date DATE NOT NULL,
        reason VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (interviewer_id) REFERENCES interviewers(interviewer_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$success = true;
foreach ($queries as $i => $sql) {
    if ($conn->query($sql)) {
        echo "✅ Query " . ($i + 1) . " executed successfully.<br>";
    } else {
        echo "❌ Query " . ($i + 1) . " failed: " . $conn->error . "<br>";
        $success = false;
    }
}

// Seed interviewers from existing Admin/Consultant users
$seed_sql = "INSERT INTO interviewers (user_id, name, email, department, is_active)
             SELECT user_id, fullname, email, role, 1 
             FROM users 
             WHERE role IN ('Admin', 'Consultant')
             AND user_id NOT IN (SELECT user_id FROM interviewers WHERE user_id IS NOT NULL)";

if ($conn->query($seed_sql)) {
    echo "✅ Interviewers seeded from existing users.<br>";
} else {
    echo "⚠️ Seeder notice: " . $conn->error . "<br>";
}

echo $success ? "<br><strong>Migration completed successfully!</strong>" : "<br><strong>Migration completed with errors.</strong>";
?>

