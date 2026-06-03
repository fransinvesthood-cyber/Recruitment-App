<?php
session_start();
include('config.php');

// Fetch job postings from database with company name from companies table
$jobs = [];
$sql = "SELECT j.job_id, j.position, c.company_name, j.location, j.job_description, j.duties, j.requirements, j.skills, j.salary, j.date_posted, j.job_status 
        FROM job_postings j 
        LEFT JOIN companies c ON j.company_id = c.company_id 
        ORDER BY j.date_posted DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings | Candidit Dashboard</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
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
.logo { font-weight: 900; font-size: 1.2rem; letter-spacing: -1px; text-decoration: none; }
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

        .container { max-width: 1200px; margin: 0 auto; padding-top: 100px; padding-bottom: 40px; padding-left: 20px; padding-right: 20px; display: grid; grid-template-columns: 1fr 400px; gap: 30px; }

        .card { background: white; padding: 32px; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.04); }
        h1 { font-size: 2rem; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.02em; }
        .subtitle { color: var(--gray-400); margin-bottom: 30px; font-weight: 500; }

        label { display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 8px; text-transform: uppercase; color: var(--gray-400); }
        input, select {
            width: 100%; padding: 14px; border-radius: 12px; border: 1px solid #E4E4E7;
            background: #FAFAFA; font-size: 1rem; margin-bottom: 20px; transition: 0.3s;
        }
        input:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 4px var(--primary-glow); }

        .tag-container { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .tag { background: var(--dark); color: white; padding: 6px 14px; border-radius: 100px; font-size: 0.8rem; font-weight: 600; }

        .btn-main {
            background: var(--primary); color: white; width: 100%; padding: 16px; border: none;
            border-radius: 12px; font-size: 1rem; font-weight: 800; cursor: pointer; transition: 0.3s;
        }
        .btn-main:hover { transform: translateY(-2px); box-shadow: 0 10px 20px var(--primary-glow); }

        /* --- PREVIEW SIDEBAR --- */
        .preview-sticky { position: sticky; top: 100px; }
        .preview-card { background: var(--dark); color: white; padding: 24px; border-radius: 20px; position: relative; overflow: hidden; }
        .preview-card::after { content: ''; position: absolute; top: -50%; right: -50%; width: 200px; height: 200px; background: var(--primary); filter: blur(70px); opacity: 0.3; }
        
.candidate-mini { background: rgba(255,255,255,0.05); padding: 12px; border-radius: 12px; margin-top: 12px; border: 1px solid rgba(255,255,255,0.1); }
        .match-score { color: var(--primary); font-weight: 900; float: right; }

        /* --- TABS --- */
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; }
        .tab-btn {
            padding: 12px 24px; border: none; background: var(--gray-100); border-radius: 100px;
            font-size: 0.9rem; font-weight: 700; cursor: pointer; transition: 0.3s;
        }
        .tab-btn.active { background: var(--primary); color: white; }
        .tab-btn:hover:not(.active) { background: #E4E4E7; }

        .view-toggle { display: flex; gap: 10px; margin-bottom: 24px; }
        .view-toggle button {
            background: transparent; border: 2px solid var(--primary); color: var(--primary);
            padding: 10px 20px; border-radius: 100px; font-weight: 700; cursor: pointer; transition: 0.3s;
        }
        .view-toggle button.active { background: var(--primary); color: white; }

        /* --- JOB LIST --- */
        .jobs-list { display: flex; flex-direction: column; gap: 20px; }
        .job-card {
            background: white; padding: 24px; border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.08); transition: 0.3s;
        }
        .job-card:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .job-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .job-title { font-size: 1.25rem; font-weight: 800; color: var(--dark); }
        .job-status {
            padding: 4px 12px; border-radius: 100px; font-size: 0.75rem; font-weight: 700;
        }
        .job-status.active { background: #DEF7EC; color: #03543F; }
        .job-status.inactive { background: #FDE8E8; color: #9B1C1C; }
        
        .job-details { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 12px; font-size: 0.85rem; color: var(--gray-400); }
        .job-detail { display: flex; align-items: center; gap: 6px; }
        
        .job-desc {
            font-size: 0.9rem; color: var(--gray-400); line-height: 1.6;
            margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        
.job-skills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .skill-tag {
            background: var(--dark); color: white; padding: 4px 12px; border-radius: 100px;
            font-size: 0.75rem; font-weight: 600;
        }

        .no-jobs { text-align: center; padding: 60px 20px; color: var(--gray-400); }

.job-card-left { text-align: left; }
        .no-jobs h3 { font-size: 1.25rem; margin-bottom: 8px; }

.jobs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }

        /* --- VIEW DETAILS BUTTON --- */
        .view-details-btn {
            background: transparent; border: 2px solid var(--primary); color: var(--primary);
            padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer;
            font-size: 0.85rem; margin-bottom: 12px; transition: 0.3s;
        }
        .view-details-btn:hover { background: var(--primary); color: white; }

/* --- MODAL --- */
.modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: flex-start;
            padding: 40px 20px; overflow-y: auto;
        }
        .modal-overlay.active { display: flex; }
.modal-content {
max-width: 450px;
            max-height: 85vh; overflow-y: auto; position: relative; margin: auto;
        }
.modal-close {
            position: absolute; top: 20px; right: 20px; font-size: 1.5rem;
            cursor: pointer; color: #EF4444;
        }
        .modal-close:hover { color: #DC2626; }
.detail-section { margin-bottom: 24px; }
        .detail-section h3 {
            font-size: 0.85rem; text-transform: uppercase; color: var(--gray-400);
            font-weight: 700; margin-bottom: 8px;
        }
        .detail-section p { font-size: 1rem; line-height: 1.6; color: var(--dark); }
        .modal-header { border-bottom: 2px solid var(--primary); padding-bottom: 16px; margin-bottom: 24px; }
        .modal-header h2 { font-size: 1.5rem; font-weight: 800; color: var(--dark); margin: 0; }
        .modal-header .company-badge { 
            display: inline-block; background: var(--primary); color: white; 
            padding: 4px 12px; border-radius: 100px; font-size: 0.75rem; 
            font-weight: 700; margin-top: 8px; 
        }
.job-details-container { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 32px; border-radius: 16px; display: flex; flex-direction: column; gap: 20px; }
        .job-details-container .info-row { display: flex; justify-content: space-between; align-items: center; padding-bottom: 16px; border-bottom: 1px solid rgba(0,0,0,0.08); }
        .job-details-container .info-row:last-child { border-bottom: none; padding-bottom: 0; }
        .job-details-container .info-row label { font-size: 0.75rem; text-transform: uppercase; color: var(--gray-400); font-weight: 700; letter-spacing: 0.5px; }
        .job-details-container .info-row span { font-size: 1rem; font-weight: 600; color: var(--dark); }
        .job-details-container .detail-section { margin-top: 8px; }
        .job-details-container .detail-section h3 { font-size: 0.8rem; text-transform: uppercase; color: var(--primary); font-weight: 800; margin-bottom: 10px; letter-spacing: 0.5px; }
        .job-details-container .detail-section p { font-size: 0.95rem; line-height: 1.7; color: var(--dark); white-space: pre-wrap; }
        .modal-footer { border-top: 1px solid #E4E4E7; padding-top: 16px; margin-top: 8px; }
        .modal-footer p { font-size: 0.85rem; color: var(--gray-400); }
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

<div class="container">
        <div class="card">
            <!-- View Jobs Section -->
            <div id="viewJobsSection">
                <h1>Available Jobs</h1>
                <p class="subtitle">Browse available positions and apply with ease.</p>
                
                <div class="search-container" style="margin-bottom: 20px;">
                    <input type="text" id="jobSearch" placeholder="Search jobs by position, company, location, or skills..." onkeyup="searchJobs()">
                </div>
                
<div class="jobs-list" id="jobsList">
                    <?php if (empty($jobs)): ?>
                        <div class="no-jobs">
                            <h3>No job postings yet</h3>
                            <p>Create a new job posting to get started.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <div class="job-card">
                                <div class="job-header">
                                    <div class="job-title"><?php echo htmlspecialchars($job['position']); ?></div>
                                    <span class="job-status <?php echo strtolower($job['job_status']); ?>"><?php echo htmlspecialchars($job['job_status']); ?></span>
                                </div>
                                <div class="job-details">
                                    <span class="job-detail">📍 <?php echo htmlspecialchars($job['location']); ?></span>
                                    <span class="job-detail">🏢 <?php echo htmlspecialchars($job['company_name']); ?></span>
                                    <span class="job-detail">💰 R<?php echo number_format($job['salary']); ?></span>
                                </div>
<div class="job-desc"><?php echo htmlspecialchars($job['job_description']); ?></div>
                                <div class="job-skills">
                                    <?php 
                                    $skills = explode(',', $job['skills']);
                                    foreach ($skills as $skill): 
                                        if (trim($skill)):
                                    ?>
                                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
<div class="job-card-left">
                                    <button class="view-details-btn" onclick="showJobDetails(<?php echo $job['job_id']; ?>)">View Full Details</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Create Job Section -->
            <div id="createJobSection" style="display: none;">
                <h1>Create a New Job</h1>
                <p class="subtitle">Fill in the details to trigger the Precision AI matching engine.</p>
                
                <form id="jobForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Job Title</label>
                            <input type="text" id="jobTitle" placeholder="e.g. Senior Software Engineer" required>
                        </div>
                        <div>
                            <label>Company</label>
                            <input type="text" placeholder="e.g. Investhood Digital">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label>Location</label>
                            <input type="text" placeholder="e.g. Sandton, Gauteng">
                        </div>
                        <div>
                            <label>Job Type</label>
                            <select>
                                <option>Full-time</option>
                                <option>Contract</option>
                                <option>Remote</option>
                            </select>
                        </div>
                    </div>

                    <label>Required Skills (Press Enter)</label>
                    <input type="text" id="skillInput" placeholder="Add skills like React, SQL, Figma...">
                    <div class="tag-container" id="tagBox">
                        <span class="tag">AI Matching</span>
                    </div>

                    <button type="button" class="btn-main" onclick="location.href='find-candidates.html'">Generate AI Candidate Matches</button>
                </form>
            </div>
        </div>

        <div class="preview-sticky">
            <div class="preview-card">
                <h3 style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-400);">AI Engine Live Preview</h3>
                <h2 id="previewTitle" style="margin: 15px 0 5px;">New Position</h2>
                <p style="font-size: 0.8rem; color: var(--gray-400);">Matches will refresh in real-time...</p>

                <div class="candidate-mini">
                    <span class="match-score">99%</span>
                    <div style="font-weight: 700;">Alex Rivera</div>
                    <div style="font-size: 0.7rem; opacity: 0.6;">High skill relevance</div>
                </div>
                <div class="candidate-mini">
                    <span class="match-score">96%</span>
                    <div style="font-weight: 700;">Jordan Smith</div>
                    <div style="font-size: 0.7rem; opacity: 0.6;">Local candidate</div>
                </div>
            </div>
        </div>
    </div>

<script>
        // Job data for modal
const jobData = <?php echo json_encode($jobs); ?>;

        // Search jobs function
        function searchJobs() {
            const query = document.getElementById('jobSearch').value.toLowerCase();
            const jobsList = document.getElementById('jobsList');
            const jobCards = jobsList.querySelectorAll('.job-card');
            
            jobCards.forEach(card => {
                const position = card.querySelector('.job-title').textContent.toLowerCase();
                const company = card.querySelector('.job-detail')?.textContent.toLowerCase() || '';
                const desc = card.querySelector('.job-desc').textContent.toLowerCase();
                const skills = card.querySelector('.job-skills')?.textContent.toLowerCase() || '';
                
                if (position.includes(query) || company.includes(query) || desc.includes(query) || skills.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Tab switching
        function switchTab(tab) {
            document.getElementById('viewJobsSection').style.display = tab === 'view' ? 'block' : 'none';
            document.getElementById('createJobSection').style.display = tab === 'create' ? 'block' : 'none';
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        // Real-time title sync
        document.getElementById('jobTitle').addEventListener('input', (e) => {
            document.getElementById('previewTitle').innerText = e.target.value || "New Position";
        });

        // Simple tag system
        const skillInput = document.getElementById('skillInput');
        const tagBox = document.getElementById('tagBox');
        skillInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && skillInput.value !== '') {
                e.preventDefault();
                const tag = document.createElement('span');
                tag.className = 'tag';
                tag.innerText = skillInput.value;
                tagBox.appendChild(tag);
                skillInput.value = '';
            }
        });

// Show job details in modal - single container layout
        function showJobDetails(jobId) {
            const job = jobData.find(j => j.job_id == jobId);
            if (!job) return;

            const modal = document.getElementById('jobDetailsModal');
            modal.innerHTML = `
                <span class="modal-close" onclick="closeModal()">&times;</span>

<div class="job-details-container">
                    <div class="info-row">
                        <label>💼 Position</label>
                        <span>${job.position}</span>
                    </div>
                    <div class="info-row">
                        <label>🏢 Company</label>
                        <span>${job.company_name || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <label>📍 Location</label>
                        <span>${job.location || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <label>💰 Salary</label>
                        <span>R${Number(job.salary).toLocaleString()}</span>
                    </div>
                    <div class="info-row">
                        <label>📊 Status</label>
                        <span>${job.job_status}</span>
                    </div>
                    <div class="info-row">
                        <label>📅 Posted</label>
                        <span>${new Date(job.date_posted).toLocaleDateString()}</span>
                    </div>
                    
                    <div class="detail-section">
                        <h3>📝 Job Description</h3>
                        <p>${job.job_description || 'No description provided.'}</p>
                    </div>

                    <div class="detail-section">
                        <h3>⚙️ Duties & Responsibilities</h3>
                        <p>${job.duties || 'No duties specified.'}</p>
                    </div>

                    <div class="detail-section">
                        <h3>✅ Requirements</h3>
                        <p>${job.requirements || 'No requirements specified.'}</p>
                    </div>

                    <div class="detail-section">
                        <h3>🚀 Skills</h3>
                        <p>${job.skills || 'No skills specified.'}</p>
                    </div>
                    
                    <div class="info-row" style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 16px; margin-top: 8px;">
                        <label>Job ID</label>
                        <span>#${job.job_id}</span>
                    </div>
                </div>
            `;
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('jobDetailsModal').classList.remove('active');
        }

        // Close modal on overlay click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
        });
    </script>

    <!-- Job Details Modal -->
    <div class="modal-overlay" id="jobDetailsModal">
        <div class="modal-content"></div>
    </div>

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
