<?php
include('config.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to view this page.");
}

$user_id = (int)$_SESSION['user_id'];

// --------------------
// Helpers
// --------------------
function getSingleValue(mysqli $conn, string $sql, string $types = '', array $params = []): ?string {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    if ($types !== '' && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) return null;
    return array_values($row)[0] ?? null;
}

function hasAnyValue(mysqli $conn, string $sql, string $types = '', array $params = []): bool {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    if ($types !== '' && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $stmt->store_result();
    $num = $stmt->num_rows;
    $stmt->close();
    return $num > 0;
}

function isFilled(?string $v): bool {
    return $v !== null && trim($v) !== '';
}

// --------------------
// Fetch applicant data for dynamic completion
// --------------------
$fullname = (string)getSingleValue($conn, "SELECT fullname FROM users WHERE user_id = ?", "i", [$user_id]);
$email    = (string)getSingleValue($conn, "SELECT email FROM users WHERE user_id = ?", "i", [$user_id]);
$gender   = (string)getSingleValue($conn, "SELECT gender FROM users WHERE user_id = ?", "i", [$user_id]);
$dob      = (string)getSingleValue($conn, "SELECT dob FROM users WHERE user_id = ?", "i", [$user_id]);
$phone    = (string)getSingleValue($conn, "SELECT phone FROM users WHERE user_id = ?", "i", [$user_id]);
$address  = (string)getSingleValue($conn, "SELECT address FROM users WHERE user_id = ?", "i", [$user_id]);

$personalOk = (
    isFilled($fullname) &&
    isFilled($gender) &&
    isFilled($dob) &&
    isFilled($email) &&
    isFilled($phone) &&
    isFilled($address)
);

$educationOk = hasAnyValue(
    $conn,
    "SELECT 1 FROM qualifications WHERE user_id=? AND (qualification_name IS NOT NULL AND qualification_name<>'' AND institution IS NOT NULL AND institution<>'' AND year_completed IS NOT NULL)",
    "i",
    [$user_id]
);

$skillsRow = null;
$skillsOk = false;
$skillsStmt = $conn->prepare("SELECT soft_skills, technical_skills FROM skills WHERE user_id=? LIMIT 1");
if ($skillsStmt) {
    $skillsStmt->bind_param("i", $user_id);
    $skillsStmt->execute();
    $skillsRes = $skillsStmt->get_result();
    $skillsRow = $skillsRes ? $skillsRes->fetch_assoc() : null;
    $skillsStmt->close();
}

$softSkills = $skillsRow ? ($skillsRow['soft_skills'] ?? '') : '';
$technicalSkills = $skillsRow ? ($skillsRow['technical_skills'] ?? '') : '';
$skillsOk = (trim($softSkills) !== '' && trim($technicalSkills) !== '');

// Work experience can be stored in varying completeness.
// To avoid false negatives, treat it as completed when at least one row exists
// with non-empty position and company.
$workOk = hasAnyValue(
    $conn,
    "SELECT 1 FROM work_experience WHERE user_id=? AND (position IS NOT NULL AND position<>'' AND company_name IS NOT NULL AND company_name<>'')",
    "i",
    [$user_id]
);

// Languages can have varying completeness.
// Treat as completed when at least one non-empty language name exists.
$languageOk = hasAnyValue(
    $conn,
    "SELECT 1 FROM language_proficiency WHERE user_id=? AND (language_name IS NOT NULL AND language_name<>'' AND speaking_level IS NOT NULL AND speaking_level<>'')",
    "i",
    [$user_id]
);

// Computer skills (computer_literacy)
// Treat as completed when at least one non-empty computer skill is stored.
$computerOk = hasAnyValue(
    $conn,
    "SELECT 1 FROM computer_literacy WHERE user_id=? AND (skill_name IS NOT NULL AND skill_name<>'' )",
    "i",
    [$user_id]
);


// --------------------
// Applications & Interviews status
// --------------------
// Use the actual tables used by this app:
// - job_applications (for applicant journey)
// - interviews (for interview journey)
//
// This fixes the issue where applicant_access_guide.php was checking a non-existing table `applications`,
// causing status to always appear "Pending" even when the user already applied.

$appRow = null;
$appStatus = null;

try {
    $appStmt = $conn->prepare("SELECT application_status FROM job_applications WHERE user_id=? ORDER BY submission_date DESC, created_at DESC LIMIT 1");
    if ($appStmt) {
        $appStmt->bind_param("i", $user_id);
        $appStmt->execute();
        $appRes = $appStmt->get_result();
        $appRow = $appRes ? $appRes->fetch_assoc() : null;
        $appStmt->close();
    }
    $appStatus = $appRow['application_status'] ?? null;
} catch (mysqli_sql_exception $e) {
    $appStatus = null;
}

$interviewStatus = null;
try {
    $intStmt = $conn->prepare("SELECT interview_status FROM interviews WHERE user_id=? ORDER BY interview_date DESC LIMIT 1");
    if ($intStmt) {
        $intStmt->bind_param("i", $user_id);
        $intStmt->execute();
        $intRes = $intStmt->get_result();
        $intRow = $intRes ? $intRes->fetch_assoc() : null;
        $intStmt->close();
        $interviewStatus = $intRow['interview_status'] ?? null;
    }
} catch (mysqli_sql_exception $e) {
    $interviewStatus = null;
}

// Determine progress per stage.
$appExists = $appRow !== null; // Apply for Jobs based on existence of job_applications row.
$searchJobsDone = $appExists; // best-effort inference
$applyDone = $appExists;

// Application Under Review
$underReviewDone = false;
if ($appStatus !== null) {
    $s = strtolower((string)$appStatus);
    // Support common app statuses used in my_applications.php
    $underReviewDone = (strpos($s, 'under') !== false && strpos($s, 'review') !== false)
        || (strpos($s, 'review') !== false)
        || (strpos($s, 'pending') !== false);
}

// Interview Scheduled/Completed
$interviewScheduledDone = false;
$interviewCompletedDone = false;
if ($interviewStatus !== null) {
    $s = strtolower((string)$interviewStatus);
    $interviewScheduledDone = (strpos($s, 'scheduled') !== false) || (strpos($s, 'rescheduled') !== false) || (strpos($s, 'schedule') !== false);
    $interviewCompletedDone = (strpos($s, 'completed') !== false) || (strpos($s, 'complete') !== false) || (strpos($s, 'done') !== false);
}

// Offer/Rejected
$offerRejectedDone = false;
if ($appStatus !== null) {
    $s = strtolower((string)$appStatus);
    $offerRejectedDone = (strpos($s, 'offer') !== false)
        || (strpos($s, 'rejected') !== false)
        || (strpos($s, 'declin') !== false)
        || (strpos($s, 'reject') !== false);
}


// Profile stages
$addQualificationsDone = $educationOk;
$addSkillsDone = $skillsOk;
$addWorkExperienceDone = $workOk;
$addLanguageProficiencyDone = $languageOk;
$addComputerSkillsDone = $computerOk;

$personalInfoDone = $personalOk;

$stages = [
    ['key' => 'personal_information', 'title' => 'Personal Information', 'desc' => 'Add your basic personal details (name, gender, date of birth, email, phone, and address) so recruiters can verify your profile.',
        'done' => $personalInfoDone, 'action' => 'my_profile.php'],
    // ['key' => 'complete_profile', 'title' => 'Complete Profile', 'desc' => 'Fill in your personal details, education, work experience, skills, languages, and computer skills so your profile matches the role.',
    //     'done' => false, 'action' => 'my_profile.php'],
    ['key' => 'add_qualifications', 'title' => 'Qualifications', 'desc' => 'Confirm your highest qualification(s), institution, and year. This helps recruiters validate your background.',
        'done' => $addQualificationsDone, 'action' => 'my_profile.php?section=education'],
    ['key' => 'add_skills', 'title' => 'Skills', 'desc' => 'Add both soft and technical skills. Your skills improve the matching score for job opportunities.',
        'done' => $addSkillsDone, 'action' => 'my_profile.php?section=skills'],
    ['key' => 'add_work_experience', 'title' => 'Work Experience', 'desc' => 'Add your previous roles, companies, duration, and key duties. This gives recruiters context about your background.',
        'done' => $addWorkExperienceDone, 'action' => 'my_profile.php?section=work_experience'],
    ['key' => 'add_language_proficiency', 'title' => 'Language Proficiency', 'desc' => 'Add the languages you speak and your proficiency level. This helps recruiters assess communication fit for the role.',
        'done' => $addLanguageProficiencyDone, 'action' => 'my_profile.php?section=languages'],
    ['key' => 'add_computer_skills', 'title' => 'Computer Skills', 'desc' => 'Add your computer literacy/skills and rate your proficiency. This helps recruiters evaluate your technical readiness.',
        'done' => $addComputerSkillsDone, 'action' => 'my_profile.php?section=computer_skills'],
    ['key' => 'apply_jobs', 'title' => 'Apply for Jobs', 'desc' => 'Submit your application for the position you want to be considered for.',
        'done' => $applyDone, 'action' => 'applicant.php'],


    ['key' => 'under_review', 'title' => 'Application Under Review', 'desc' => 'Your application is being evaluated by the recruitment team.',
        'done' => $underReviewDone, 'action' => 'my_applications.php'],
    ['key' => 'interview_scheduled', 'title' => 'Interview Scheduled', 'desc' => 'If shortlisted, you will receive an interview schedule and next steps.',
        'done' => $interviewScheduledDone, 'action' => 'my_interviews.php'],
    ['key' => 'interview_completed', 'title' => 'Interview Completed', 'desc' => 'Your interview has been completed. The recruitment team reviews results.',
        'done' => $interviewCompletedDone, 'action' => 'my_interviews.php'],
    ['key' => 'offer_rejected', 'title' => 'Offer/Rejected', 'desc' => 'You will receive a final decision. If rejected, you can continue applying and improving your profile.',
        'done' => $offerRejectedDone, 'action' => 'my_applications.php'],
];


// Overall progress (for progress indicator) — PROFILE ONLY
$profileStages = array_filter($stages, function($s){
    return in_array($s['key'], ['personal_information','add_qualifications','add_skills','add_work_experience','add_language_proficiency','add_computer_skills'], true);
});



$doneCount = 0;
foreach ($profileStages as $s) { if (!empty($s['done'])) $doneCount++; }
$totalCount = count($profileStages);
$progressPct = $totalCount > 0 ? (int)round(($doneCount / $totalCount) * 100) : 0;

$firstIncomplete = null;
foreach ($stages as $s) {
    if (empty($s['done'])) { $firstIncomplete = $s; break; }
}


function stageBadge($done) {
    return $done ? '<span class="badge badge-complete">Completed</span>' : '<span class="badge badge-pending">Pending</span>';
}

function h($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Applicant Access Guide</title>
    <style>
        .guide-exit-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            color: #fff;
            font-size: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
            z-index: 2;
        }
        .guide-exit-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.04);
        }
        body.dark-mode .guide-exit-btn {
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.16);
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="applicant.css">

<style>
/* Applicant Access Guide - modernized visuals (keep layout/logic unchanged) */
:root{
  --brand1:#667eea;
  --brand2:#764ba2;
  --complete:#198754;
  --pending:#6c757d;
  --line:rgba(233,236,239,.95);
  --card:#ffffff;

  --bg-soft: rgba(102,126,234,0.08);
  --bg-soft-2: rgba(118,75,162,0.05);

  --text-muted:#495057;
  --shadow: 0 12px 30px rgba(0,0,0,.10);

  --radius: 18px;
  --radius-sm: 14px;
  --blur: 10px;
}

body{
  background: linear-gradient(135deg, var(--bg-soft), var(--bg-soft-2));
}

.guide-wrap{
  padding: 30px 0 60px;
}

.guide-header{
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, var(--brand1) 0%, var(--brand2) 100%);
  color: #fff;
  border-radius: var(--radius);
  padding: 22px;
  box-shadow: 0 16px 40px rgba(0,0,0,.14);
  margin-bottom: 18px;
}
.guide-header::after{
  content:"";
  position:absolute;
  inset:-40px -60px auto auto;
  width: 260px;
  height: 260px;
  background: radial-gradient(circle at center, rgba(255,255,255,.35), rgba(255,255,255,0) 60%);
  transform: rotate(15deg);
}

.guide-header h2{ font-weight: 900; margin:0; letter-spacing:-.2px; }
.guide-header p{ margin:8px 0 0; opacity:.95; font-weight:600; max-width: 70ch; }

.progress-shell{
  background: rgba(255,255,255,.15);
  border: 1px solid rgba(255,255,255,.28);
  border-radius: 16px;
  padding: 14px;
  margin-top: 14px;
  backdrop-filter: blur(var(--blur));
}

.progress-shell .stat{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap: 10px;
  margin-bottom: 10px;
}

/* Typography polish */
.stage-desc{ margin:10px 0 0; color: var(--text-muted); font-weight:600; line-height:1.45; }

/* Timeline */
.container-wide{
  width: min(1100px, 92vw);
}

.timeline{
  position: relative;
  margin-top: 18px;
  padding-left: 28px;
}
.timeline::before{
  content:"";
  position:absolute;
  left: 12px;
  top: 8px;
  bottom: 8px;
  width:2px;
  background: linear-gradient(to bottom, rgba(233,236,239,.3), rgba(233,236,239,1), rgba(233,236,239,.3));
}

.stage-card{
  position: relative;
  margin-bottom: 16px;
  transform: translateY(8px);
  transition: transform .35s ease, opacity .35s ease;
  opacity: 1;
}
.stage-card.visible{ transform: translateY(0); }

.stage-card .dot{
  position:absolute;
  left: 3px;
  top: 18px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: rgba(233,236,239,.95);
  border: 3px solid #fff;
  box-shadow: 0 10px 24px rgba(0,0,0,.12);
}

.stage-card.completed .dot{ background: var(--complete); }
.stage-card.pending .dot{ background: var(--pending); }

.stage-card .card{
  border: 1px solid rgba(255,255,255,.0);
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0,0,0,.06);
  background: var(--card);
  transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
}
.stage-card:hover .card{
  transform: translateY(-3px);
  box-shadow: 0 18px 45px rgba(0,0,0,.12);
}

.stage-card.completed .card{ border-color: rgba(25,135,84,.25); }
.stage-card.pending .card{ border-color: rgba(108,117,125,.25); }

.stage-card .card-body{ padding: 16px 16px 14px; }

.stage-title{ display:flex; gap:10px; align-items:flex-start; justify-content:space-between; }
.stage-title h5{ margin:0; font-weight:900; letter-spacing:-.2px; }

.badge{
  font-weight: 800;
  border-radius: 999px;
  padding: 7px 12px;
  letter-spacing: .1px;
  box-shadow: 0 8px 22px rgba(0,0,0,.04);
}
.badge-complete{ background: rgba(25,135,84,.12); color: var(--complete); border: 1px solid rgba(25,135,84,.22); }
.badge-pending{ background: rgba(108,117,125,.12); color:#495057; border: 1px solid rgba(108,117,125,.22); }

.warning-box{
  border-radius: 16px;
  border: 1px solid rgba(220,53,69,.25);
  background: rgba(220,53,69,.08);
  padding: 14px;
  margin: 18px 0 22px;
  box-shadow: 0 10px 25px rgba(0,0,0,.05);
}
.warning-box strong{ color: #b02a37; }

.timeline-actions{ margin-top: 10px; display:flex; flex-wrap:wrap; gap:10px; align-items:center; }

/* Soft button style (consistent with app palette) */
.btn-soft{
  background: rgba(102,126,234,.12);
  border: 1px solid rgba(102,126,234,.25);
  color:#3d3aa8;
  font-weight:900;
  padding: 10px 18px;
  border-radius: 12px;
}
.btn-soft:hover{ background: rgba(102,126,234,.18); }

/* Mobile */
@media (max-width: 767.98px){
  .timeline{ padding-left: 24px; }
  .timeline::before{ left: 10px; }
  .stage-card .dot{ left: 1px; }
  .stage-card .card-body{ padding: 14px 14px 12px; }
  .stage-title{ flex-direction: column; gap: 8px; }
}

/* Dark mode compatibility if other pages set body.dark-mode */
body.dark-mode,
body.dark-mode *{
  color: #f0f0f0;
}
body.dark-mode{
  background: #0d1117;
}


body.dark-mode .guide-header{ color:#fff; background: linear-gradient(135deg, #1f2937 0%, #111827 100%); border: 1px solid rgba(147, 197, 253, 0.2); box-shadow: 0 16px 40px rgba(0,0,0,.28); }
body.dark-mode .guide-header::after{ opacity:.55; }
body.dark-mode .progress-shell{ background: rgba(17,24,39,0.75); border-color: rgba(147, 197, 253, 0.18); }
body.dark-mode .stage-card .card{ background:#1f1f1f; border-color: rgba(255,255,255,.06); }
body.dark-mode .stage-desc{ color:#d0d0d0; }
body.dark-mode .badge-pending{ background: rgba(255,255,255,.08); color:#e6e6e6; border-color: rgba(255,255,255,.14); }
body.dark-mode .badge-complete{ background: rgba(25,135,84,.18); color: #1fe18c; border: 1px solid rgba(31,225,140,.25); }
body.dark-mode .warning-box{ background: rgba(220,53,69,.12); }
body.dark-mode .progress-shell{ background: rgba(255,255,255,.10); border-color: rgba(255,255,255,.18); }
body.dark-mode .btn-primary{ background:#3b82f6; border-color:#3b82f6; }
body.dark-mode .btn-soft{ background: rgba(102,126,234,.18); border-color: rgba(102,126,234,.28); color:#a7b9ff; }
body.dark-mode .btn-outline-danger{ border-color: rgba(220,53,69,.55); color: #ff6b77; }
body.dark-mode .timeline::before{ background: linear-gradient(to bottom, rgba(233,236,239,.14), rgba(233,236,239,.55), rgba(233,236,239,.14)); }
body.dark-mode .stage-card .dot{ background: rgba(233,236,239,.35); border-color: #1f1f1f; }
body.dark-mode .warning-box strong{ color: #ff6b77; }
body.dark-mode a{ color: #93c5fd; }
body.dark-mode a:hover{ color: #bfdbfe; }

</style>



</head>
<body>
<script>
(function () {
    function applyThemeFromStorage() {
        const theme = localStorage.getItem('theme');
        const darkMode = localStorage.getItem('darkMode');
        const shouldUseDark = theme === 'dark' || darkMode === 'enabled';
        document.body.classList.toggle('dark-mode', shouldUseDark);
    }

    applyThemeFromStorage();
    window.addEventListener('storage', applyThemeFromStorage);
})();
</script>

<div class="guide-wrap">
    <div class="container container-wide">
        <div class="guide-header">
            <button type="button" class="guide-exit-btn" onclick="window.location.href='applicant.php'" aria-label="Exit guide">
                X
            </button>

            <div class="text-center">
                <h2>Applicant Access Guide</h2>
                <p style="margin-left:auto; margin-right:auto;">Understand your recruitment journey from completing your profile to receiving the final decision.</p>
            </div>


            <div class="progress-shell">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3" style="margin-bottom:10px;">
                    <div class="d-flex flex-column">
                        <div style="font-weight:800;">Overall Progress</div>
                    </div>
                </div>

                <div class="stat">
                    <div style="font-weight:800;"> </div>
                    <div style="font-weight:900; font-size: 20px;"><?= $progressPct ?>%</div>
                </div>

                <div class="progress" style="height: 10px; border-radius: 999px; overflow:hidden;">
                    <div class="progress-bar" style="width: <?= $progressPct ?>%; background: linear-gradient(90deg, var(--brand1), var(--brand2));"></div>
                </div>
                <div class="mt-2" style="display:flex; justify-content:space-between; gap:10px; font-weight:700; opacity:.95;">
                    <span><?= $doneCount ?> of <?= $totalCount ?> steps completed</span>
                    <span>Keep going - your next steps are below.</span>
                </div>
            </div>
        </div>

<?php
// --------------------
// Professional Summary (shown right after Personal Information section)
// --------------------
$professionalSummary = null;
try {
    $sumStmt = $conn->prepare("SELECT professional_summary FROM users WHERE user_id=? LIMIT 1");
    if ($sumStmt) {
        $sumStmt->bind_param("i", $user_id);
        $sumStmt->execute();
        $sumRes = $sumStmt->get_result();
        $sumRow = $sumRes ? $sumRes->fetch_assoc() : null;
        $sumStmt->close();
        $professionalSummary = $sumRow['professional_summary'] ?? null;
    }
} catch (Throwable $e) {
    $professionalSummary = null;
}

$isSummaryFilled = isFilled($professionalSummary) || (is_string($professionalSummary) && trim(strip_tags($professionalSummary)) !== '');
?>

<?php if ($firstIncomplete && in_array($firstIncomplete['key'], ['complete_profile','add_qualifications','add_skills','add_computer_skills','personal_information'], true)) : ?>
            <div class="warning-box" role="alert">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div>
                        <div style="font-weight:900;">⚠️ Profile incomplete</div>
                        <div class="mt-1">
                            <strong><?= h($firstIncomplete['title']) ?></strong> is still pending.
                            Update it now to improve your matching and avoid delays.
                        </div>
                    </div>
                    <div>
                        <a class="btn btn-danger fw-bold" href="<?= h($firstIncomplete['action']) ?>">Go to step</a>
                    </div>
                </div>
            </div>
        <?php elseif ($firstIncomplete) : ?>
            <div class="warning-box" role="alert" style="background: rgba(108,117,125,.10); border-color: rgba(108,117,125,.25);">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div>
                        <div style="font-weight:900;">🧭 Next up</div>
                        <div class="mt-1">
                            <strong><?= h($firstIncomplete['title']) ?></strong> is pending.
                        </div>
                    </div>
                    <div>
                        <a class="btn btn-soft fw-bold" href="<?= h($firstIncomplete['action']) ?>">Open</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="timeline">
<?php
// Render Professional Summary card right after Personal Information stage
// (even if Personal Information is incomplete, as long as the summary has content)
$renderSummaryCard = $isSummaryFilled;
?>
<?php foreach ($stages as $stage): ?>

                <?php
                    $done = !empty($stage['done']);
                    $class = $done ? 'stage-card completed' : 'stage-card pending';
                ?>
                <div class="<?= $class ?>" data-stage="<?= h($stage['key']) ?>">
                    <div class="dot"></div>
                    <div class="card">
                        <div class="card-body">
                            <div class="stage-title">
                                <h5><?= h($stage['title']) ?></h5>
                                <div>
                                    <?= stageBadge($done) ?>
                                </div>
                            </div>
                            <p class="stage-desc"><?= h($stage['desc']) ?></p>

                            <div class="timeline-actions">
                                <?php if ($done): ?>
                                    <span class="badge" style="background: rgba(25,135,84,.10); color: var(--complete); border: 1px solid rgba(25,135,84,.22);">✓ Good job</span>
                                <?php else: ?>
                                    <a class="btn btn-primary" href="<?= h($stage['action']) ?>">Complete this step</a>
                                <?php endif; ?>

                                <?php if (!$done && in_array($stage['key'], ['complete_profile','add_qualifications','add_skills','add_language_proficiency','add_computer_skills'], true)): ?>
                                    <button type="button" class="btn btn-outline-danger" onclick="jumpToWarning('<?= h($stage['key']) ?>')">Why it matters</button>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal (simple) for profile warnings -->
<div class="modal fade" id="whyMattersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--brand1), var(--brand2)); color:#fff; border-bottom: none;">
                <h5 class="modal-title" id="whyMattersTitle">Why it matters</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="whyMattersBody" style="font-weight:600; color:#2b2b2b;">
                —
            </div>
        </div>
    </div>
</div>

<script>
const profileReasons = {
        'complete_profile': 'A complete profile helps recruiters evaluate you faster and accurately. Missing basics can slow down screening.',
        'add_qualifications': 'Qualifications provide proof of your academic background and eligibility for the role.',
        'add_skills': 'Skills improve matching and help you pass technical/competency screening.',
        'add_language_proficiency': 'Language proficiency helps recruiters assess communication and collaboration fit for the role.',
        'add_computer_skills': 'Computer skills and proficiency show you can use common tools and systems effectively for the role.'
    };


    function jumpToWarning(stageKey){
        const title = document.getElementById('whyMattersTitle');
        const body = document.getElementById('whyMattersBody');

        title.textContent = 'Why this step matters';
        body.textContent = profileReasons[stageKey] || 'Updating your profile improves your recruitment matching and reduces delays.';

        const modalEl = document.getElementById('whyMattersModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    // Optional: smooth animation when cards become visible (basic)
    const cards = document.querySelectorAll('.stage-card');
    const io = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) e.target.style.transform = 'translateY(0)';
        });
    }, { threshold: 0.12 });
    cards.forEach(c => { io.observe(c); });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>