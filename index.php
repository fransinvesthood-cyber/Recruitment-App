<?php
include('config.php');

//Define the base query
$query = "SELECT job_postings.*, companies.company_name, departments.department_name
          FROM job_postings
          LEFT JOIN companies ON job_postings.company_id = companies.company_id
          LEFT JOIN departments ON job_postings.department_id = departments.department_id
          WHERE 1";

//Apply search filters if any
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $query .= " AND (job_postings.position LIKE '%$search%' 
                      OR job_postings.location LIKE '%$search%' 
                      OR companies.company_name LIKE '%$search%' 
                      OR departments.department_name LIKE '%$search%')";
}

$result = $conn->query($query);

// Query for popular jobs from the database for popular posts
$query_popular = "SELECT job_postings.*, companies.company_name, departments.department_name
          FROM job_postings
          LEFT JOIN companies ON job_postings.company_id = companies.company_id
          LEFT JOIN departments ON job_postings.department_id = departments.department_id
          WHERE job_postings.job_status = 'Active'
          ORDER BY job_postings.date_posted DESC LIMIT 6";

$popular_result = $conn->query($query_popular);

// Fetch top 3 shortlisted applicants (replicate fetch_shortlisted_applicants.php logic)
$shortlisted_applicants = [];
$sql = "
    SELECT 
        u.user_id,
        u.fullname,
        ja.application_id,
        ja.job_id,
        jp.position AS job_position,
        jp.location,
        ap.professional_title,
        ap.years_experience,
        0 AS match_percentage  -- Computed below
    FROM users u
    JOIN job_applications ja ON u.user_id = ja.user_id
    JOIN job_postings jp ON ja.job_id = jp.job_id
    LEFT JOIN applicant_profile ap ON u.user_id = ap.user_id
    WHERE u.role = 'Applicant' 
      AND ja.application_status = 'Shortlisted'
    ORDER BY ja.application_id DESC
    LIMIT 3
";

$result_short = $conn->query($sql);
if ($result_short) {
    while ($row = $result_short->fetch_assoc()) {
        // Simple screening match % computation
        $match_pct = 0;
        $job_criteria = $conn->query("SELECT minimum_criteria FROM job_postings WHERE job_id = " . (int)$row['job_id']);
        if ($job_criteria && $criteria_row = $job_criteria->fetch_assoc()) {
        $screening = [];
            if (!empty($criteria_row['minimum_criteria'])) {
                $screening = json_decode($criteria_row['minimum_criteria'], true) ?: [];
            }
            $screening_skills = $screening['required_skills'] ?? [];
            $screening_skills = array_filter(array_map('trim', (array)$screening_skills));
            $total_screen = count($screening_skills);
            
            $match_pct = 0;
            if ($total_screen > 0) {
                $app_skills_q = $conn->query("SELECT technical_skills, soft_skills FROM skills WHERE user_id = " . (int)$row['user_id']);
                $app_skills = [];
                if ($app_skills_q) {
                    while ($s = $app_skills_q->fetch_assoc()) {
                        if ($s['technical_skills']) $app_skills = array_merge($app_skills, array_map('trim', explode(',', $s['technical_skills'])));
                        if ($s['soft_skills']) $app_skills = array_merge($app_skills, array_map('trim', explode(',', $s['soft_skills'])));
                    }
                }
                $app_skills = array_unique(array_filter($app_skills));
                $app_norm = array_map(function($s) { return strtolower(trim($s)); }, $app_skills);
                
                $matched_count = 0;
                foreach ($screening_skills as $req) {
                    $req_norm = strtolower(trim($req));
                    $found = false;
                    foreach ($app_norm as $app) {
                        if (strpos($app, $req_norm) !== false || strpos($req_norm, $app) !== false) {
                            $found = true;
                            break;
                        }
                    }
                    if ($found) $matched_count++;
                }
                $match_pct = round(($matched_count / $total_screen) * 100, 1);
            }
        }
        
$shortlisted_applicants[] = [
            'fullname' => $row['fullname'],
            'professional_title' => $row['professional_title'] ?? 'Applicant',
            'match_percentage' => $match_pct,
            'location' => $row['location'] ?? 'N/A',
            'user_id' => $row['user_id'],
            'job_position' => $row['job_position']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidit | Precision Hiring</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7C3AED;
            --primary-glow: rgba(124, 58, 237, 0.5);
            --dark: #09090B;
            --white: #FFFFFF;
            --gray-50: #FAFAFA;
            --gray-100: #F4F4F5;
            --gray-200: #E4E4E7;
            --gray-400: #A1A1AA;
            --gray-800: #18181B;
            --grid-main: rgba(124, 58, 237, 0.15);
            --grid-sub: rgba(124, 58, 237, 0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--white); color: var(--dark); line-height: 1.6; overflow-x: hidden; }

        /* --- THE REINFORCED GRID --- */
        .bg-canvas {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background-color: var(--white);
            background-image: 
                linear-gradient(var(--grid-main) 1.5px, transparent 1.5px),
                linear-gradient(90deg, var(--grid-main) 1.5px, transparent 1.5px),
                linear-gradient(var(--grid-sub) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-sub) 1px, transparent 1px);
            background-size: 80px 80px, 80px 80px, 20px 20px, 20px 20px;
            animation: gridMove 30s linear infinite;
        }

        .bg-glow {
            position: absolute;
            top: -10%; left: 50%; transform: translateX(-50%);
            width: 120vw; height: 100vh;
            background: radial-gradient(circle at 50% 30%, var(--primary-glow) 0%, transparent 60%);
            z-index: -1; filter: blur(60px);
            opacity: 0.7;
        }

        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 80px 80px; }
        }

        /* --- NAVIGATION --- */
        nav {
            padding: 1.2rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            z-index: 1000;
background: transparent;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--grid-main);
        }
.logo { font-weight: 900; font-size: 1.2rem; letter-spacing: -1px; text-decoration: none; display: inline !important; vertical-align: middle; line-height: 1.2; }
        .logo span { color: var(--primary); }
        
        .nav-links { display: flex; align-items: center; }
.nav-links a.nav-item { 
            text-decoration: none; 
            color: var(--dark); 
            font-weight: 700; 
            margin-left: 25px; 
            font-size: 0.9rem; 
            transition: 0.2s; 
        }
        .nav-links a.nav-item:hover { color: var(--primary); }
        
        .nav-btn {
            background: var(--dark);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            margin-left: 30px;
            transition: 0.3s;
        }
        .nav-btn:hover { background: var(--primary); }

        /* --- HERO SECTION --- */
        .hero { padding: 220px 5% 100px; text-align: center; max-width: 1200px; margin: 0 auto; }
        .badge { background: var(--dark); color: white; padding: 10px 20px; border-radius: 100px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 2.5rem; display: inline-block; }
        
        .hero h1 { font-size: clamp(2.5rem, 7vw, 5rem); font-weight: 900; line-height: 1.1; letter-spacing: -0.05em; margin-bottom: 2rem; }
        .hero h1 span { color: var(--primary); text-shadow: 0 0 30px var(--primary-glow); }
        .hero p { font-size: 1.25rem; color: var(--gray-800); max-width: 800px; margin: 0 auto 40px; font-weight: 500; }

        .cta-group { display: flex; gap: 15px; justify-content: center; margin-bottom: 80px; flex-wrap: wrap; }
        .btn-primary { background: var(--primary); color: white; padding: 1.2rem 2.5rem; border-radius: 12px; text-decoration: none; font-weight: 800; transition: 0.3s; box-shadow: 0 15px 30px var(--primary-glow); display: inline-block;}
        .btn-secondary { background: var(--dark); color: white; padding: 1.2rem 2.5rem; border-radius: 12px; text-decoration: none; font-weight: 800; transition: 0.3s; display: inline-block;}
        .btn-primary:hover, .btn-secondary:hover { transform: translateY(-3px); }



        /* --- LIVE AI PREVIEW (Darkened Containers) --- */
        .preview-header { margin-bottom: 30px; }
        .dashboard-container { 
            max-width: 1000px; margin: 0 auto; text-align: left;
            background: var(--gray-50); /* Darkened to gray-50 */
            border: 1px solid var(--gray-200); /* Stronger border */
            border-radius: 32px; padding: 40px; 
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.1); 
            backdrop-filter: blur(10px); 
        }
        .candidate-row { 
            background: var(--white); /* White pops off the gray-50 container */
            margin-bottom: 20px; padding: 25px; border-radius: 20px; 
            border: 1px solid var(--gray-200); /* Darker border */
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        }
        .candidate-main { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .skills-tags { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px; }
        .tag { background: var(--gray-100); color: var(--gray-800); padding: 4px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; border: 1px solid var(--gray-200); }
        .insight { font-size: 0.85rem; color: var(--gray-600); display: flex; align-items: center; gap: 8px; margin-top: 5px; }
        .insight::before { content: '•'; color: var(--primary); font-weight: bold; }

        /* --- PROBLEM SECTION --- */
        .pain-section { background: var(--dark); padding: 140px 5%; margin-top: 120px; color: white; text-align: center; }
        .pain-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; max-width: 1200px; margin: 60px auto 0; }
        .pain-card { padding: 40px; border-radius: 24px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); text-align: left; }
        .pain-card h4 { font-size: 1.2rem; margin-bottom: 15px; color: var(--primary); }

        /* --- IMPACT TABLE (Darkened Container) --- */
        .impact-section { padding: 120px 5%; max-width: 1000px; margin: 0 auto; text-align: center; }
        .impact-table { 
            width: 100%; border-collapse: collapse; margin-top: 50px; 
            background: var(--gray-50); /* Darkened from pure white */
            border-radius: 24px; overflow: hidden; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.05); 
            border: 1px solid var(--gray-200);
        }
        .impact-table th, .impact-table td { padding: 25px; text-align: left; border-bottom: 1px solid var(--gray-200); }
        .impact-table th { background: var(--gray-100); font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; color: var(--gray-800); }
        .improvement { color: var(--primary); font-weight: 800; }

        /* --- STEPS SECTION (Darkened Container) --- */
        .steps-section { padding: 120px 5%; text-align: center; background: transparent; }
.steps-grid { display: flex; gap: 40px; max-width: 1200px; margin: 60px auto 0; justify-content: center; flex-wrap: wrap; }
        .step-card {
            background: var(--gray-50); /* Darkened from white/transparent */
            padding: 40px;
            border-radius: 24px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            text-align: center;
        }
        .step-num { font-size: 3rem; font-weight: 900; color: var(--gray-400); margin-bottom: 10px; }

        /* --- FINAL CTA --- */
        .cta-final { padding: 160px 5%; text-align: center; }
        .cta-btns-final { display: flex; gap: 15px; justify-content: center; margin-top: 40px; flex-wrap: wrap; }
        .btn-outline { border: 2px solid var(--dark); color: var(--dark); padding: 1.1rem 2.5rem; border-radius: 12px; text-decoration: none; font-weight: 800; transition: 0.3s; }

        .reveal { opacity: 0; transform: translateY(30px); animation: revealUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
        @keyframes revealUp { to { opacity: 1; transform: translateY(0); } }

        /* --- HOW IT WORKS MODAL --- */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(9, 9, 11, 0.75); z-index: 2000;
            justify-content: center; align-items: center;
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease-out;
        }
        .modal-overlay.active { display: flex; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .modal-content {
            background: var(--white); 
            padding: 0;
            border-radius: 28px; 
            max-width: 640px; 
            width: 90%; 
            max-height: 85vh; 
            overflow-y: auto;
            box-shadow: 0 35px 80px -20px rgba(0,0,0,0.3), 0 0 0 1px rgba(124,58,237,0.1);
            position: relative;
            animation: slideUp 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        
        .modal-header {
            padding: 48px 48px 20px;
            border-bottom: 1px solid var(--gray-100);
            text-align: center;
            position: relative;
        }
        .modal-close {
            position: absolute; top: 28px; right: 28px; 
            font-size: 1.6rem; font-weight: 800; color: var(--gray-400);
            cursor: pointer; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;
            border-radius: 12px; transition: all 0.2s ease;
            border: 1px solid var(--gray-200);
        }
        .modal-close:hover { 
            background: var(--gray-50); 
            color: var(--dark); 
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .modal-title { 
            font-size: 2.25rem; font-weight: 900; margin: 0 0 8px 0; 
            background: linear-gradient(135deg, var(--dark), var(--gray-800)); 
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
        }
        .modal-subtitle {
            color: var(--gray-600); font-size: 1.1rem; font-weight: 500; 
            margin: 0; opacity: 0.9;
        }
        
        .steps-container {
            padding: 0 48px 48px;
        }
        .step-item {
            display: flex; gap: 24px; align-items: flex-start; 
            margin-bottom: 36px; padding: 24px 0;
            opacity: 0; transform: translateX(-20px);
            animation: slideInRight 0.6s ease-out forwards;
        }
        .step-item:nth-child(1) { animation-delay: 0.1s; }
        .step-item:nth-child(2) { animation-delay: 0.2s; }
        .step-item:nth-child(3) { animation-delay: 0.3s; }
        .step-item:nth-child(4) { animation-delay: 0.4s; margin-bottom: 0; }
        @keyframes slideInRight {
            to { opacity: 1; transform: translateX(0); }
        }
        
        .step-icon {
            flex-shrink: 0; width: 64px; height: 64px; 
            color: var(--primary); font-size: 24px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 20px; background: rgba(124,58,237,0.1);
            backdrop-filter: blur(10px); border: 1px solid rgba(124,58,237,0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .step-item:hover .step-icon {
            transform: scale(1.05) rotate(5deg); 
            background: rgba(124,58,237,0.15);
            box-shadow: 0 12px 30px rgba(124,58,237,0.25);
        }
        .step-icon svg { width: 28px; height: 28px; }
        
        .step-content { 
            flex: 1; 
        }
        .step-content h4 { 
            font-size: 1.3rem; font-weight: 800; 
            margin: 0 0 12px 0; color: var(--dark);
            letter-spacing: -0.01em;
        }
        .step-content p { 
            color: var(--gray-700); line-height: 1.65; 
            font-size: 1.05rem; margin: 0;
            font-weight: 400;
        }
        
        .modal-footer {
            padding: 0 48px 48px;
            text-align: center;
            border-top: 1px solid var(--gray-100);
        }
        .modal-footer .btn-primary {
            background: linear-gradient(135deg, var(--primary), #6B21A8); 
            color: white; padding: 14px 36px; border-radius: 16px; 
            font-weight: 800; font-size: 1rem; border: none;
            cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 12px 30px rgba(124,58,237,0.4);
            text-decoration: none; display: inline-block;
        }
        .modal-footer .btn-primary:hover {
            transform: translateY(-2px); box-shadow: 0 20px 40px rgba(124,58,237,0.5);
        }
    </style>
</head>
<body>

    <div class="bg-canvas"></div>
    <div class="bg-glow"></div>

    <nav>
        <a href="index.php" class="logo"><img src="img/logo1.png" alt="Candidit Logo" style="height: 3.5rem; margin-right: 0.25rem; vertical-align: middle;">Candi<span>dit</span></a>
        <div class="nav-links">
            <a href="index.php" class="nav-item">Home</a>
            <a href="job_post.php" class="nav-item">Find Jobs</a>
            <a href="how_it_works.php" class="nav-item">How It Works</a>
            <a href="contact_us.php" class="nav-item">Contact Us</a>
            <a href="login_signup.php" class="nav-btn">Login/Register</a>
        </div>
    </nav>

    <section class="hero">
        <h1 class="reveal" style="animation-delay: 0.1s;">Hire top talent faster with <br><span>a recruitment platform that does the screening.</span></h1>
        <p class="reveal" style="animation-delay: 0.2s;">Reduce hiring time by up to 80% by automatically ranking candidates based on skill, experience, and job fit. Built for HR teams and recruitment agencies.</p>
        
        <div class="cta-group reveal" style="animation-delay: 0.3s;">
            <a href="contact_us.php" class="btn-primary">Request Demo</a>
            <a href="how_it_works.php" class="btn-secondary">See How It Works</a>
        </div>

        <div class="dashboard-container reveal" style="animation-delay: 0.4s;">
            <div class="preview-header">
                <h2 style="font-weight: 900;">Top 3 ranked candidates for your next hire</h2>
            </div>
            <?php if (empty($shortlisted_applicants)): ?>
            <div style="text-align: center; padding: 40px; color: var(--gray-600);">
                <i class='bx bx-user-check' style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <p style="font-size: 1.1rem; font-weight: 500;">No shortlisted candidates yet</p>
                <p>Post jobs and start receiving high ranked applicants</p>
            </div>
            <?php else: ?>
            <?php foreach (array_slice($shortlisted_applicants, 0, 3) as $candidate): ?>
            <div class="candidate-row" style="opacity: <?= ($candidate['match_percentage'] < 90) ? '0.9' : '1' ?>">
                <div class="candidate-main">
                    <div>
                        <div style="font-weight: 900; font-size: 1.2rem;"><?= htmlspecialchars($candidate['fullname']) ?></div>
        <div style="color: var(--gray-600); font-size: 0.9rem; font-weight: 600;">
                            <?= htmlspecialchars($candidate['professional_title'] ?? 'Applicant') ?> • <?= htmlspecialchars($candidate['location'] ?? 'Location N/A') ?>
                        </div>
                            <div style="font-size: 0.8rem; color: var(--gray-500); margin-top: 4px;">
                            <?php
                            // Fetch top 3 technical/soft skills for this applicant
                            $skill_q = $conn->query("
                                SELECT technical_skills, soft_skills FROM skills 
                                WHERE user_id = " . (int)$candidate['user_id'] . " 
                                LIMIT 1
                            ");
                            $skills_str = [];
                            if ($skill_q && $skill_row = $skill_q->fetch_assoc()) {
                                if ($skill_row['technical_skills']) {
                                    $skills_str = array_slice(array_map('trim', explode(',', $skill_row['technical_skills'])), 0, 3);
                                }
                                if ($skill_row['soft_skills'] && count($skills_str) < 3) {
                                    $skills_str = array_merge($skills_str, array_slice(array_map('trim', explode(',', $skill_row['soft_skills'])), 0, 3 - count($skills_str)));
                                }
                            }
                            if (!empty($skills_str)): ?>
                                <?php foreach (array_slice($skills_str, 0, 3) as $skill): ?>
                                    <span style="background: var(--gray-100); color: var(--gray-800); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--gray-200); font-size: 0.75rem; font-weight: 600; margin-right: 6px;"><?= htmlspecialchars($skill) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="opacity: 0.6;">Skills not listed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="font-weight: 900; color: var(--primary); font-size: 1.3rem;"><?= $candidate['match_percentage'] ?>% Match</div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <p style="text-align: center; font-size: 0.8rem; color: var(--gray-600); margin-top: 20px;">Ranked using 12,000+ hiring data signals and role-matching intelligence.</p>
        </div>
    </section>

    <section class="pain-section">
        <h2 style="font-size: 3rem; font-weight: 900;">Traditional hiring is slow, expensive, and inconsistent.</h2>
        <div class="pain-grid">
            <div class="pain-card"><h4>Manual Screening</h4><p>HR teams spend hours manually screening CVs that don't fit.</p></div>
            <div class="pain-card"><h4>Missed Talent</h4><p>High-quality candidates are often missed due to sheer volume.</p></div>
            <div class="pain-card"><h4>Long Cycles</h4><p>Hiring cycles take weeks instead of days, costing you top talent.</p></div>
            <div class="pain-card"><h4>Inconsistency</h4><p>Shortlisting quality varies between different recruiters.</p></div>
        </div>
    </section>

    <section class="impact-section">
        <h2 style="font-weight: 900; font-size: 2.5rem;">What Candidit changes for your business</h2>
        <table class="impact-table">
            <thead>
                <tr><th>Business Area</th><th>Improvement</th></tr>
            </thead>
            <tbody>
                <tr><td>Screening time</td><td class="improvement">Up to 80% reduction</td></tr>
                <tr><td>Hiring speed</td><td class="improvement">Up to 10x faster recruitment cycles</td></tr>
                <tr><td>Candidate quality</td><td class="improvement">Higher role-fit accuracy</td></tr>
                <tr><td>Recruitment cost</td><td class="improvement">Reduced manual workload</td></tr>
            </tbody>
        </table>
    </section>

    <section class="steps-section">
        <h2 style="font-weight: 900; font-size: 2.5rem; margin-bottom: 20px;">Simple 4-step process for Job-Seekers</h2>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-num">01</div>
                <h4 style="font-weight: 800; font-size: 1.2rem;">Create an Account</h4>
                <p style="color: var(--gray-600); margin-top: 10px;">Sign up to access the platform and start your job search journey.</p>
            </div>
            <div class="step-card">
                <div class="step-num">02</div>
                <h4 style="font-weight: 800; font-size: 1.2rem;">Update Your Profile</h4>
                <p style="color: var(--gray-600); margin-top: 10px;">Complete your profile with your skills, qualifications, and experience.</p>
            </div>
            <div class="step-card">
                <div class="step-num">03</div>
                <h4 style="font-weight: 800; font-size: 1.2rem;">Apply for Jobs</h4>
                <p style="color: var(--gray-600); margin-top: 10px;">Browse available opportunities and submit your applications.</p>
            </div>
            <div class="step-card">
                <div class="step-num">04</div>
                <h4 style="font-weight: 800; font-size: 1.2rem;">Get Interviewed</h4>
                <p style="color: var(--gray-600); margin-top: 10px;">Receive interview invitations and track your application status.</p>
            </div>
        </div>
    </section>

    <section class="cta-final">
        <h2 style="font-weight: 900; font-size: 2.5rem;">Ready to transform your hiring process?</h2>
        <p style="margin-top: 20px; font-weight: 500; color: var(--gray-800); font-size: 1.1rem;">Join companies already reducing hiring time and improving candidate quality.</p>
        <div class="cta-btns-final">
            <a href="#" class="btn-primary">Book a Demo</a>
            <a href="#" class="btn-secondary">Start Pilot Program</a>
            <a href="#" class="btn-outline">Talk to Sales</a>
        </div>
    </section>

    <!-- Modern How It Works Modal -->
    <div class="modal-overlay" id="howItWorksModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-close" onclick="closeHowItWorksModal()">&times;</div>
                <h2 class="modal-title">How It Works</h2>
                <p class="modal-subtitle">4 simple steps to get started with Candidit as a Job-Seeker</p>
            </div>
            
            <div class="steps-container">
                <div class="step-item">
                    <div class="step-icon">
                        👤
                    </div>
                    <div class="step-content">
                        <h4>Create Your Account</h4>
                        <p>Get started by signing up in a few simple steps.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-icon">
                        📝
                    </div>
                    <div class="step-content">
                        <h4>Update Your Profile</h4>
                        <p>Add your skills, qualifications, and experience.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-icon">
                        🔍
                    </div>
                    <div class="step-content">
                        <h4>Apply for Jobs</h4>
                        <p>Explore opportunities and submit your applications.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-icon">
                        🎯
                    </div>
                    <div class="step-content">
                        <h4>Get Interviewed</h4>
                        <p>Get invited and track your application progress.</p>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn-primary" onclick="closeHowItWorksModal()">Got it!</button>
            </div>
        </div>
    </div>

    <script>
        // Scroll lock helpers
        let scrollBarWidth;
        let bodyOverflow;
        let bodyPaddingRight;

        function openHowItWorksModal() {
            const modal = document.getElementById('howItWorksModal');
            modal.classList.add('active');
            
            // Calculate scrollbar width
            scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
            
            // Save original styles
            bodyOverflow = document.body.style.overflow;
            bodyPaddingRight = document.body.style.paddingRight;
            
            // Apply scroll lock
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = scrollBarWidth + 'px';
        }

        function closeHowItWorksModal() {
            const modal = document.getElementById('howItWorksModal');
            modal.classList.remove('active');
            
            // Restore original styles
            document.body.style.overflow = bodyOverflow || '';
            document.body.style.paddingRight = bodyPaddingRight || '';
        }

        // Close on overlay click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeHowItWorksModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeHowItWorksModal();
            }
        });

    </script>

    <footer class="site-footer" style="margin-top: 80px; padding: 50px 5%; background: var(--dark); color: white;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; gap: 30px; justify-content: space-between; flex-wrap: wrap;">
            <div style="min-width: 260px;">
                <div style="font-weight: 900; font-size: 1.25rem; letter-spacing: -0.5px;">
                    <a href="index.php" class="logo"><img src="img/logo1.png" alt="Candidit Logo" style="height: 3.5rem; margin-right: 0.25rem; vertical-align: middle;">Candi<span>dit</span></a>
                </div>
                <p style="margin-top: 12px; color: rgba(255,255,255,0.75); font-weight: 500; max-width: 420px;">
                    Precision hiring platform that helps HR teams screen faster and hire smarter.
                </p>

<div style="margin-top: 18px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.15);">
                    <div style="font-weight: 900; margin-bottom: 10px;">Contact Information</div>
                    <div style="display: flex; flex-direction: column; gap: 8px; color: rgba(255,255,255,0.85); font-weight: 600; font-size: 0.95rem;">
                        <a href="mailto:admin@investhoodit.co.za" style="color: rgba(255,255,255,0.85); text-decoration: none; display: inline-flex; align-items: center; gap: 10px;">
                            <span style="width: 28px; height: 28px; border-radius: 10px; background: rgba(124,58,237,0.18); border: 1px solid rgba(124,58,237,0.35); display: inline-flex; align-items: center; justify-content: center;">✉️</span>
                            <span>admin@investhoodit.co.za</span>
                        </a>
                        <a href="tel:0682460562" style="color: rgba(255,255,255,0.85); text-decoration: none; display: inline-flex; align-items: center; gap: 10px;">
                            <span style="width: 28px; height: 28px; border-radius: 10px; background: rgba(124,58,237,0.18); border: 1px solid rgba(124,58,237,0.35); display: inline-flex; align-items: center; justify-content: center;">📞</span>
                            <span>068 246 0562</span>
                        </a>
                        <div style="color: rgba(255,255,255,0.75); font-weight: 600; display: inline-flex; align-items: center; gap: 10px;">
                            <span style="width: 28px; height: 28px; border-radius: 10px; background: rgba(124,58,237,0.18); border: 1px solid rgba(124,58,237,0.35); display: inline-flex; align-items: center; justify-content: center;">📍</span>
                            <span>136 2nd St, Randjespark, Midrand, 1685</span>
                        </div>
                    </div>

                    <div style="margin-top: 16px;">
                        <div style="font-weight: 900; margin-bottom: 10px;">Social Media</div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
<a href="#" aria-label="Facebook" style="border: 1px solid rgba(255,255,255,0.25); color: rgba(255,255,255,0.9); padding: 10px 12px; border-radius: 10px; text-decoration: none; font-weight: 900; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
                                <span style="width: 28px; height: 28px; border-radius: 10px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;">f</span> Facebook
                            </a>
                            <a href="#" aria-label="LinkedIn" style="border: 1px solid rgba(255,255,255,0.25); color: rgba(255,255,255,0.9); padding: 10px 12px; border-radius: 10px; text-decoration: none; font-weight: 900; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
                                <span style="width: 28px; height: 28px; border-radius: 10px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); display: inline-flex; align-items: center; justify-content: center; font-size: 0.95rem;">in</span> LinkedIn
                            </a>
                            <a href="#" aria-label="Twitter/X" style="border: 1px solid rgba(255,255,255,0.25); color: rgba(255,255,255,0.9); padding: 10px 12px; border-radius: 10px; text-decoration: none; font-weight: 900; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
                                <span style="width: 28px; height: 28px; border-radius: 10px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;">𝕏</span> Twitter/X
                            </a>
                            <a href="#" aria-label="Instagram" style="border: 1px solid rgba(255,255,255,0.25); color: rgba(255,255,255,0.9); padding: 10px 12px; border-radius: 10px; text-decoration: none; font-weight: 900; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
                                <span style="width: 28px; height: 28px; border-radius: 10px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;">⌁</span> Instagram
                            </a>

                            <style>
footer .site-footer a[aria-label] {
                                    will-change: transform, background-color, border-color, box-shadow;
                                    transition: all 0.2s ease;
                                }
                                footer .site-footer a[aria-label]:hover {
                                    transform: translateY(-2px);
                                    border-color: rgba(124,58,237,0.85);
                                    box-shadow: 0 16px 40px rgba(124,58,237,0.30);
                                    color: #fff;
                                    background: rgba(124,58,237,0.12);
                                }
                                footer .site-footer a[aria-label]:hover span {
                                    background: rgba(124,58,237,0.25) !important;
                                    border-color: rgba(124,58,237,0.55) !important;
                                }
                            </style>
                        </div>
                    </div>
                </div>
            </div>

            <div style="min-width: 220px;">
                <div style="font-weight: 900; margin-bottom: 14px;">Quick Links</div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="index.php" style="color: rgba(255,255,255,0.8); text-decoration: none; font-weight: 700;">Home</a>
                    <a href="job_post.php" style="color: rgba(255,255,255,0.8); text-decoration: none; font-weight: 700;">Find Jobs</a>
                    <a href="how_it_works.php" style="color: rgba(255,255,255,0.8); text-decoration: none; font-weight: 700;">How It Works</a>
                    <a href="contact_us.php" style="color: rgba(255,255,255,0.8); text-decoration: none; font-weight: 700;">Contact Us</a>
                </div>
            </div>

            <div style="min-width: 240px;">
                <div style="font-weight: 900; margin-bottom: 14px;">Get Started</div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="login_signup.php" style="background: var(--primary); color: white; padding: 12px 16px; border-radius: 10px; text-decoration: none; font-weight: 900; text-align: center;">
                        Login / Register
                    </a>
                    <a href="contact_us.php" style="border: 1px solid rgba(255,255,255,0.25); color: rgba(255,255,255,0.9); padding: 12px 16px; border-radius: 10px; text-decoration: none; font-weight: 900; text-align: center;">
                        Request Demo
                    </a>
                </div>
            </div>
        </div>

        <div style="max-width: 1200px; margin: 30px auto 0; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.15); display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="color: rgba(255,255,255,0.7); font-weight: 600; font-size: 0.9rem;">© <?= date('Y') ?> Candidit. All rights reserved.</div>
            <div style="color: rgba(255,255,255,0.7); font-weight: 600; font-size: 0.9rem;">Built for speed, accuracy, and better hiring outcomes.</div>
        </div>
    </footer>

</body>
</html>
