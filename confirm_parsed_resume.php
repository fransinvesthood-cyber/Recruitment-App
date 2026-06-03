<?php
session_start();
$data = $_SESSION['parsed_resume'] ?? null;
$user_id = $_SESSION['resume_user_id'] ?? null;

if (!$data || !$user_id) {
    die("No parsed data found.");
}
?>

<h2>Confirm Parsed Resume Information</h2>

<form method="POST" action="save_parsed_data.php">
    <h3>Skills</h3>
    <ul>
        <?php foreach ($data['skills'] ?? [] as $skill): ?>
            <li><?= htmlspecialchars($skill['name']) ?></li>
            <input type="hidden" name="skills[]" value="<?= htmlspecialchars($skill['name']) ?>">
        <?php endforeach; ?>
    </ul>

    <h3>Education</h3>
    <ul>
        <?php foreach ($data['education'] ?? [] as $edu): ?>
            <li><?= htmlspecialchars($edu['accreditation']) ?> - <?= htmlspecialchars($edu['organization']) ?> (<?= htmlspecialchars($edu['date_graduated'] ?? '') ?>)</li>
            <input type="hidden" name="education[]" value="<?= htmlspecialchars(json_encode($edu)) ?>">
        <?php endforeach; ?>
    </ul>

    <h3>Work Experience</h3>
    <ul>
        <?php foreach ($data['work_experience'] ?? [] as $job): ?>
            <li><?= htmlspecialchars($job['job_title']) ?> @ <?= htmlspecialchars($job['organization']) ?> (<?= htmlspecialchars($job['start_date']) ?> - <?= htmlspecialchars($job['end_date'] ?? 'Present') ?>)</li>
            <input type="hidden" name="work_experience[]" value="<?= htmlspecialchars(json_encode($job)) ?>">
        <?php endforeach; ?>
    </ul>

    <button type="submit">Save to Database</button>
</form>