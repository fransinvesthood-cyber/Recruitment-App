<?php
include('config.php');
$conn->query('ALTER TABLE consultant_timesheets ADD COLUMN rejection_reason TEXT NULL');
echo "Column added successfully";
$conn->close();
?>
