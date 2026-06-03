<?php
include('config.php');
session_start();

if (!isset($_GET['application_id'])) {
    die("No application ID provided");
}

$application_id = (int)$_GET['application_id'];

// Fetch snapshot
$sql = "SELECT ss.profile_data, ja.position, ja.submission_date, u.fullname as applicant_name, 
               jp.company_id, c.company_name
        FROM application_snapshots ss
        JOIN job_applications ja ON ss.application_id = ja.application_id
        JOIN users u ON ja.user_id = u.user_id
        JOIN job_postings jp ON ja.job_id = jp.job_id
        JOIN companies c ON jp.company_id = c.company_id
        WHERE ss.application_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Application snapshot not found");
}

$row = $result->fetch_assoc();
$profile = json_decode($row['profile_data'], true) ?: [];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Profile Snapshot</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; text-align: center; }
        .section { background: white; margin-bottom: 25px; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .section h2 { color: #667eea; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .info-item { padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .info-label { font-weight: 600; color: #666; margin-bottom: 5px; text-transform: uppercase; font-size: 0.85rem; }
        .info-value { font-size: 1.1rem; color: #333; }
        .skills-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .skill-tag { background: #e3f2fd; color: #1976d2; padding: 6px 12px; border-radius: 20px; font-size: 0.9rem; }
        .back-btn { background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class='bx bx-user-circle'></i> Profile Snapshot</h1>
        <p>Application for <strong><?= htmlspecialchars($row['position']) ?></strong> at <strong><?= htmlspecialchars($row['company_name']) ?></strong></p>
        <p>Snapshot captured: <?= date('F j, Y g:i A', strtotime($row['submission_date'])) ?></p>
    </div>

    <div class="section">
        <h2><i class='bx bx-user'></i> Personal Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Applicant Name</div>
                <div class="info-value"><?= htmlspecialchars($profile['fullname'] ?? $row['applicant_name']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value"><?= htmlspecialchars($profile['email'] ?? '—') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Phone</div>
                <div class="info-value"><?= htmlspecialchars($profile['phone'] ?? '—') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Gender</div>
                <div class="info-value"><?= htmlspecialchars($profile['gender'] ?? '—') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">DOB</div>
                <div class="info-value"><?= $profile['dob'] ? date('M j, Y', strtotime($profile['dob'])) : '—' ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Address</div>
                <div class="info-value"><?= htmlspecialchars($profile['address'] ?? '—') ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($profile['professional_title'])): ?>
    <div class="section">
        <h2><i class='bx bx-briefcase'></i> Professional Summary</h2>
        <div class="info-grid">
            <div class="info-item" style="grid-column: 1/-1;">
                <div class="info-label">Title</div>
                <div class="info-value"><?= htmlspecialchars($profile['professional_title']) ?></div>
            </div>
            <div class="info-item" style="grid-column: 1/-1;">
                <div class="info-label">Summary</div>
                <div class="info-value"><?= nl2br(htmlspecialchars($profile['professional_summary'] ?? '')) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Experience</div>
                <div class="info-value"><?= ($profile['years_experience'] ?? 0) ?> years</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($profile['tech_skills'])): ?>
    <div class="section">
        <h2><i class='bx bx-code-alt'></i> Technical Skills</h2>
        <div>
            <?php $skills = array_map('trim', explode(',', $profile['tech_skills'])); ?>
            <div class="skills-grid">
                <?php foreach ($skills as $skill): if ($skill): ?>
                    <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($profile['soft_skills'])): ?>
    <div class="section">
        <h2><i class='bx bx-star'></i> Soft Skills</h2>
        <div>
            <?php $skills = array_map('trim', explode(',', $profile['soft_skills'])); ?>
            <div class="skills-grid">
                <?php foreach ($skills as $skill): if ($skill): ?>
                    <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($profile['quals'])): ?>
    <div class="section">
        <h2><i class='bx bx-graduation'></i> Qualifications</h2>
        <div class="info-grid">
            <?php $quals = array_map('trim', explode(',', $profile['quals'])); ?>
            <?php foreach (array_slice($quals, 0, 4) as $qual): if ($qual): ?>
                <div class="info-item">
                    <div class="info-value"><?= htmlspecialchars($qual) ?></div>
                </div>
            <?php endif; endforeach; ?>
            <?php if (count($quals) > 4): ?>
                <div class="info-item" style="grid-column: 1/-1;">
                    <div class="info-value" style="font-style: italic;">... and <?= count($quals) - 4 ?> more</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <a href="javascript:history.back()" class="back-btn">
        <i class='bx bx-arrow-back'></i> Back to Applications
    </a>
</body>
</html>
<?php $conn->close(); ?>

