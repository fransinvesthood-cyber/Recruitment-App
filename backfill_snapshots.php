<?php
include('config.php');

// This script backfills application_snapshots for existing applications
// WARNING: Only run once! Add ?confirm=1 to actually execute

echo "<h2>Application Snapshot Backfill Tool</h2>";
echo "<p><strong>Current Working Directory:</strong> " . getcwd() . "</p>";

if (!isset($_GET['confirm']) || $_GET['confirm'] !== '1') {
    echo "<div style='background: yellow; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>BACKFILL SCRIPT - CONFIRMATION REQUIRED</h3>";
    echo "<p>This will create snapshot records for all existing applications that don't have them.</p>";
    echo "<p><strong>DRY RUN:</strong></p>";
    
    // Dry run: count only
    $count_sql = "SELECT COUNT(*) as total FROM job_applications ja 
                  LEFT JOIN application_snapshots ss ON ja.application_id = ss.application_id 
                  WHERE ss.application_id IS NULL";
    $count_result = $conn->query($count_sql);
    $total = $count_result->fetch_assoc()['total'];
    
    echo "<p><strong>Found $total applications without snapshots.</strong></p>";
    echo "<p><a href='?confirm=1' style='background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>✅ CONFIRM & RUN BACKFILL</a></p>";
    echo "</div>";
    exit;
}

// Confirm & Execute
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🚀 EXECUTING BACKFILL (Live Run)</h3>";

$backfill_sql = "
    INSERT INTO application_snapshots (application_id, profile_data, created_at)
    SELECT 
        ja.application_id,
        JSON_OBJECT(
            'fullname', u.fullname,
            'email', u.email,
            'phone', u.phone,
            'gender', u.gender,
            'dob', u.dob,
            'address', u.address,
            'professional_title', ap.professional_title,
            'professional_summary', ap.professional_summary,
            'technical_skills', s.technical_skills,
            'soft_skills', s.soft_skills
        ),
        NOW()
    FROM job_applications ja
    LEFT JOIN users u ON ja.user_id = u.user_id
    LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
    LEFT JOIN skills s ON u.user_id = s.user_id
    LEFT JOIN application_snapshots ss ON ja.application_id = ss.application_id
    WHERE ss.application_id IS NULL
";

if ($conn->multi_query($backfill_sql)) {
    $inserted = $conn->affected_rows;
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ SUCCESS: Created $inserted snapshot records!</p>";
    
    // Show stats
    $check_sql = "SELECT COUNT(*) as total FROM job_applications";
    $total_apps = $conn->query($check_sql)->fetch_assoc()['total'];
    
    $with_snapshots_sql = "SELECT COUNT(*) as with_snap FROM job_applications ja JOIN application_snapshots ss ON ja.application_id = ss.application_id";
    $with_snapshots = $conn->query($with_snapshots_sql)->fetch_assoc()['with_snap'];
    
    echo "<p><strong>Total Applications:</strong> $total_apps</p>";
    echo "<p><strong>With Snapshots:</strong> $with_snapshots (" . round(($with_snapshots/$total_apps)*100, 1) . "%)</p>";
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ ERROR: " . $conn->error . "</p>";
}

echo "</div>";

echo "<p><a href='backfill_snapshots.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px;'>← Back to Dry Run</a></p>";
$conn->close();
?>

