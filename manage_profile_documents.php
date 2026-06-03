<?php
include('config.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$user_id = (int)$_SESSION['user_id'];

$resumeExists = false;
$resumeFilename = null;

$stmt = $conn->prepare("SELECT resume_filename FROM resume WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($resumeFilename);
    $stmt->fetch();
    $resumeExists = true;
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Appstyle.css">
    <link rel="stylesheet" href="personalStyle.css">
    <link rel="stylesheet" href="applicant.css">
    <link rel="stylesheet" href="job.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="assets/logo1.png" type="image/x-icon">
    <title>Resume & Documents</title>
    <style>
        body{background:#f6f7fb;}
        .page-wrap{max-width:1100px;margin:0 auto;padding:20px;}
        .card{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:20px;}
        .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:14px;}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 18px;border-radius:12px;text-decoration:none;font-weight:700;border:1px solid transparent;}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .btn-success{background:linear-gradient(135deg,#10b981,#059669);color:#fff;}
        .btn-secondary{background:#f3f4f6;color:#374151;border-color:#e5e7eb;}
        .btn[aria-disabled="true"]{opacity:.6;pointer-events:none;}
        .hint{color:#6b7280;margin-top:8px;}
    </style>
</head>
<body>
<div class="page-wrap">
    <div class="card">
        <h2 style="margin:0 0 6px;">Resume &amp; Documents</h2>
        <div class="hint">Use the existing resume upload/download features. Resume is stored in the system and used by applications.</div>

        <hr style="border:none;border-top:1px solid #eef2f7;margin:16px 0;"/>

        <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start;">
            <div style="flex:1;min-width:280px;">
                <div style="font-weight:800;display:flex;align-items:center;gap:10px;">
                    <i class='bx bx-file' style="color:#667eea;"></i> Resume
                </div>
                <div class="hint">
                    <?php if ($resumeExists): ?>
                        Uploaded: <b><?php echo htmlspecialchars($resumeFilename ?? 'Resume'); ?></b>
                    <?php else: ?>
                        Not uploaded yet.
                    <?php endif; ?>
                </div>
            </div>

            <div class="actions">
                <a class="btn btn-primary" href="resume.php">
                    <i class='bx bx-upload'></i> <?php echo $resumeExists ? 'Update Resume' : 'Upload Resume'; ?>
                </a>

                <a class="btn btn-success" href="resume_download.php" aria-disabled="<?php echo $resumeExists ? 'false' : 'true'; ?>" <?php echo $resumeExists ? '' : 'onclick="return false;"'; ?> download>
                    <i class='bx bx-download'></i> Download
                </a>

                <a class="btn btn-secondary" href="preview_resume.php" aria-disabled="<?php echo $resumeExists ? 'false' : 'true'; ?>" <?php echo $resumeExists ? '' : 'onclick="return false;"'; ?> target="_blank">
                    <i class='bx bx-show'></i> Preview
                </a>
            </div>
        </div>

    </div>
</div>
</body>
</html>

