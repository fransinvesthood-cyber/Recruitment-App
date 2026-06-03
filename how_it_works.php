<?php
// How It Works - Applicant/Job-seeker and Admin/Employer perspectives
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>How Candidit Works</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <style>
    :root{
      --primary:#7C3AED;
      --primary2:#4F46E5;
      --dark:#0B0B10;
      --bg:#FFFFFF;
      --muted:#6B7280;
      --card:#F8FAFC;
      --border:rgba(15, 23, 42, .12);
      --shadow:0 18px 45px rgba(2, 6, 23, .08);
      --radius:18px;
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
    body{background: var(--white); color: var(--dark); line-height: 1.6; overflow-x: hidden; min-height: 100vh;}

    :root {
      --primary:#7C3AED;
      --primary-glow: rgba(124, 58, 237, 0.5);
      --dark:#09090B;
      --white:#FFFFFF;
      --gray-50:#FAFAFA;
      --gray-100:#F4F4F5;
      --grid-main: rgba(124, 58, 237, 0.15);
      --grid-sub: rgba(124, 58, 237, 0.05);
    }

    .bg-canvas {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
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
      top: -10%; left: 50%;
      transform: translateX(-50%);
      width: 120vw; height: 100vh;
      background: radial-gradient(circle at 50% 30%, var(--primary-glow) 0%, transparent 60%);
      z-index: -1;
      filter: blur(60px);
      opacity: 0.7;
    }

    @keyframes gridMove {
      0% { background-position: 0 0; }
      100% { background-position: 80px 80px; }
    }

    .wrap{max-width:1120px;margin:0 auto;padding:28px 16px 60px;}

    .topbar{
      position:sticky;top:0;z-index:10;
      background:rgba(255,255,255,.75);backdrop-filter:blur(14px);
      border-bottom:1px solid rgba(17,24,39,.06);
    }
    .topbar-inner{max-width:1120px;margin:0 auto;padding:16px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;}

    .logo { font-weight: 900; font-size: 1.2rem; letter-spacing: -1px; text-decoration: none; display: inline !important; vertical-align: middle; line-height: 1.2; }
        .logo span { color: var(--primary); } 

    /* Keep nav styling compatible with index.php/contact_us.php */
    nav {
      padding: 1.2rem 5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      width: 100%;
      z-index: 1000;
      background: transparent;
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(124, 58, 237, 0.15);
    }

    .nav-links { display: flex; align-items: center; }
    .nav-links a.nav-item {
      text-decoration: none;
      color: #09090B;
      font-weight: 700;
      margin-left: 25px;
      font-size: 0.9rem;
      transition: 0.2s;
    }
    .nav-links a.nav-item:hover { color: var(--primary); }

    .nav-btn {
      background: #09090B;
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

    /* Legacy classes (unused after header match) */
    .navlinks{display:flex;gap:16px;align-items:center;flex-wrap:wrap;justify-content:flex-end;}
    .navlinks a{color:#111827;text-decoration:none;font-weight:700;font-size:14px;opacity:.85;}
    .navlinks a:hover{opacity:1;color:var(--primary);} 
    .btn{display:inline-flex;align-items:center;gap:10px;padding:10px 16px;border-radius:12px;text-decoration:none;font-weight:800;font-size:14px;box-shadow:0 10px 25px rgba(124,58,237,.18);}
    .btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;}
    .btn-outline{background:transparent;border:1px solid rgba(124,58,237,.35);color:var(--primary);box-shadow:none;}

    .hero{padding:34px 0 18px;}
    .hero h1{font-size:40px;line-height:1.05;letter-spacing:-1.2px;margin-bottom:10px;}
    .hero p{color:var(--muted);font-weight:600;font-size:16px;max-width:860px;}

    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:22px;}
    @media (max-width: 900px){.grid-2{grid-template-columns:1fr;}}

    .panel{background:rgba(255,255,255,.75);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;}
    .panel-head{padding:18px 18px;border-bottom:1px solid rgba(17,24,39,.08);display:flex;align-items:flex-start;gap:12px;}
    .badge-role{width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.24);color:var(--primary);font-size:20px;flex-shrink:0;}
    .panel-head h2{font-size:18px;letter-spacing:-.3px;}
    .panel-head p{color:var(--muted);font-weight:600;font-size:13.5px;margin-top:4px;}
    .panel-body{padding:18px;}

    .step-list{display:flex;flex-direction:column;gap:12px;}
    .step{
      display:flex;gap:12px;align-items:flex-start;
      padding:14px;border-radius:16px;
      border:1px solid rgba(17,24,39,.08);
      background:linear-gradient(180deg,#ffffff 0%, #fbfbff 100%);
    }
    .step:hover{border-color:rgba(124,58,237,.28);}

    .step-num{width:40px;height:40px;border-radius:14px;background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.22);display:flex;align-items:center;justify-content:center;font-weight:900;color:var(--primary);}
    .step h3{font-size:15px;margin-bottom:6px;letter-spacing:-.2px;}
    .step p{color:var(--muted);font-weight:600;font-size:13.5px;line-height:1.55;}

    .bullets{margin-top:12px;display:grid;grid-template-columns:1fr;gap:10px;}
    .bullet{display:flex;gap:10px;align-items:flex-start;padding:12px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:#fff;}
    .bullet i{color:var(--primary);font-size:18px;margin-top:1px;}
    .bullet b{display:block;font-size:13.5px;margin-bottom:4px;}
    .bullet span{color:var(--muted);font-size:13.5px;font-weight:600;line-height:1.5;}

    .cta{
      margin-top:22px;
      padding:18px;
      border-radius:var(--radius);
      border:1px solid rgba(124,58,237,.22);
      background:linear-gradient(135deg, rgba(124,58,237,.12), rgba(79,70,229,.08));
      display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;
    }

    /* Timeline diagram */
    .timeline-wrap{margin-top:14px;}
    .timeline-header{margin-bottom:14px;}
    .timeline-header h2{font-size:22px;letter-spacing:-.4px;margin-bottom:6px;}
    .timeline-header p{color:var(--muted);font-weight:600;max-width:760px;font-size:14.5px;}

    .timeline{position:relative;padding:8px 0 6px;margin:0 auto;max-width:820px;}
    .timeline-line{position:absolute;top:18px;bottom:18px;left:18px;width:4px;border-radius:999px;background:linear-gradient(180deg, rgba(124,58,237,.9), rgba(79,70,229,.35));opacity:.55;}

    .timeline-step{display:flex;gap:16px;align-items:flex-start;margin:18px 0;}
    .timeline-dot{
      width:44px;height:44px;border-radius:16px;
      background:linear-gradient(135deg, rgba(124,58,237,.18), rgba(255,255,255,.7));
      border:1px solid rgba(124,58,237,.35);
      color:var(--primary);
      display:flex;align-items:center;justify-content:center;
      font-weight:950;letter-spacing:-.2px;flex-shrink:0;
      box-shadow:0 18px 35px rgba(124,58,237,.12);
      position:relative;
      overflow:hidden;
    }
    .timeline-dot{background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.22);color:var(--primary);box-shadow:0 18px 35px rgba(124,58,237,.12);}

    .timeline-card{
      flex:1;
      padding:14px 16px;
      border-radius:16px;
      background:rgba(255,255,255,.75);
      border:1px solid rgba(17,24,39,.08);
      box-shadow:0 14px 35px rgba(2,6,23,.06);
      position:relative;
      transition:none;
    }
    .timeline-card h3{font-size:15px;letter-spacing:-.2px;margin-bottom:6px;}
    .timeline-card p{color:var(--muted);font-weight:600;font-size:13.5px;line-height:1.55;margin-top:0;}



    @media (max-width:900px){
      .timeline-line{left:16px;}
      .timeline-dot{width:40px;height:40px;border-radius:14px;}
      .timeline-step{gap:12px;}
      .timeline-card{padding:13px 14px;}
    }

    .cta h3{font-size:18px;letter-spacing:-.3px;}
    .cta p{color:var(--muted);font-weight:600;}

    .footer{margin-top:30px;color:rgba(17,24,39,.55);font-weight:600;font-size:13px;text-align:center;}

    /* Pretty anchor highlights */
    .kbd{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace;font-weight:800;font-size:12px;padding:3px 8px;border-radius:10px;border:1px solid rgba(17,24,39,.12);background:rgba(255,255,255,.8);}
  </style>
</head>
<body>



    <div class="bg-canvas"></div>
    <div class="bg-glow"></div>

    <nav>
            <a href="index.php" class="logo"><img src="img/logo1.png" alt="Candidit Logo" style="height: 3.5rem; margin-right: 0.25rem; vertical-align: middle;">Candidit</a>
        <div class="nav-links">
            <a href="index.php" class="nav-item">Home</a>
            <a href="job_post.php" class="nav-item">Find Jobs</a>
            <a href="how_it_works.php" class="nav-item">How It Works</a>
            <a href="contact_us.php" class="nav-item">Contact Us</a>
            <a href="login_signup.php" class="nav-btn">Login/Register</a>
        </div>
    </nav>

  <div class="wrap">
    <section class="hero">
      <h1>How it works</h1>
      <p>
        Built for both sides of hiring: applicants get a clear, guided workflow to apply and track progress; admins get a structured workflow to review, shortlist, and schedule interviews.
      </p>
    </section>

    <section class="timeline-wrap" aria-label="Workflow timeline">
      <div class="timeline-header">
        <h2>Workflow timeline (both sides)</h2>
        <p>A clear 4-step journey—from first setup to interviews—so applicants and admins always know what’s next.</p>
      </div>

      <div class="timeline">
        <div class="timeline-line" aria-hidden="true"></div>

        <div class="timeline-step">
          <div class="timeline-dot" style="--accent: var(--primary)" aria-hidden="true">01</div>
          <div class="timeline-card">
            <h3>Step 1: Create & define</h3>
            <p>
              Applicants create an account and set up a profile. Admins post roles and requirements.
            </p>
          </div>
        </div>

        <div class="timeline-step">
          <div class="timeline-dot" style="--accent: var(--primary2)" aria-hidden="true">02</div>
          <div class="timeline-card">
            <h3>Step 2: Complete & evaluate</h3>
            <p>
              Applicants update skills and apply with a resume. The system evaluates candidates to help shortlist faster.
            </p>
          </div>
        </div>

        <div class="timeline-step">
          <div class="timeline-dot" style="--accent: #22C55E" aria-hidden="true">03</div>
          <div class="timeline-card">
            <h3>Step 3: Shortlist & review</h3>
            <p>
              Admin reviews profiles and shortlist candidates. Applicants can track status changes in their dashboard.
            </p>
          </div>
        </div>

        <div class="timeline-step">
          <div class="timeline-dot" style="--accent: #F59E0B" aria-hidden="true">04</div>
          <div class="timeline-card">
            <h3>Step 4: Schedule & interview</h3>
            <p>
              Admin schedules interviews (with conflict checks). Applicants view details and respond to availability.
            </p>
          </div>
        </div>

      </div>

    </section>

    <section class="grid-2">
      <!-- Applicant / Job-seeker -->
      <div class="panel">

        <div class="panel-head">
          <div class="badge-role" title="Job-seeker">👤</div>
          <div>
            <h2>From an Applicant / Job-Seeker side</h2>
            <p>4 steps from account creation to interview tracking.</p>
          </div>
        </div>
        <div class="panel-body">
          <div class="step-list">
            <div class="step">
              <div class="step-num">01</div>
              <div>
                <h3>Create your account</h3>
                <p>
                  Sign up and set up your basic details. Your profile becomes the “source of truth” for applications.
                </p>
              </div>
            </div>

            <div class="step">
              <div class="step-num">02</div>
              <div>
                <h3>Complete & update your profile</h3>
                <p>
                  Add your skills, qualifications, and experience. This helps the system rank you for roles you apply to.
                </p>
              </div>
            </div>

            <div class="step">
              <div class="step-num">03</div>
              <div>
                <h3>Apply for jobs (with a resume)</h3>
                <p>
                  Pick a job, fill in required fields (availability, address, cover letter), and upload your resume.
                  After submission, you’ll see your application status updates in your dashboard.
                </p>
              </div>
            </div>

            <div class="step">
              <div class="step-num">04</div>
              <div>
                <h3>Get interviewed & track everything</h3>
                <p>
                  If shortlisted, the admin schedules an interview. You can open <span class="kbd">My Interviews</span> to view date/time, location or meeting link, and respond to availability.
                </p>
              </div>
            </div>
          </div>

          <div class="bullets">
            <div class="bullet">
              <i class='bx bx-check-circle'></i>
              <div>
                <b>Status clarity</b>
                <span>Submitted → Under Review → Shortlisted → Rejected / Hired. Your app shows the current state.</span>
              </div>
            </div>
            <div class="bullet">
              <i class='bx bx-lock-open'></i>
              <div>
                <b>Faster decisions</b>
                <span>The platform auto-evaluates applications and helps recruiters focus on best-fit candidates first.</span>
              </div>
            </div>
            <div class="bullet">
              <i class='bx bx-bell'></i>
              <div>
                <b>Notifications</b>
                <span>You’ll receive updates when applications are processed or interviews are scheduled.</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Admin / Employer -->
      <div class="panel">
        <div class="panel-head">
          <div class="badge-role" title="Employer/Admin">🏢</div>
          <div>
            <h2>From an Admin / Employer side</h2>
            <p>Review applications, shortlist candidates, and schedule interviews.</p>
          </div>
        </div>
        <div class="panel-body">
          <div class="step-list">
            <div class="step">
              <div class="step-num">01</div>
              <div>
                <h3>Post roles & define requirements</h3>
                <p>
                  Add job details (position, department, criteria). These requirements help determine what “good fit” looks like.
                </p>
              </div>
            </div>

            <div class="step">
              <div class="step-num">02</div>
              <div>
                <h3>Manage applications & shortlist</h3>
                <p>
                  Go to <span class="kbd">Manage Applications</span> to filter by status/skills/qualification level.
                  The system can auto-evaluate a batch to recommend shortlist vs reject.
                </p>
              </div>
            </div>

            <div class="step">
              <div class="step-num">03</div>
              <div>
                <h3>Review candidate profiles & skills</h3>
                <p>
                  Open the candidate profile and inspect skills + a snapshot of extracted resume data.
                  This reduces manual CV reading while preserving decision control.
                </p>
              </div>
            </div>

            <div class="step">
              <div class="step-num">04</div>
              <div>
                <h3>Schedule interviews with conflict checks</h3>
                <p>
                  Use <span class="kbd">Schedule Interview</span> to select a shortlisted applicant, set interview time/type, and choose interviewer(s).
                  The system prevents double-booking and sends interview invitations.
                </p>
              </div>
            </div>
          </div>

          <div class="bullets">
            <div class="bullet">
              <i class='bx bx-user-voice'></i>
              <div>
                <b>Auto-evaluation assist</b>
                <span>Run evaluation per candidate or in bulk to speed up first-round decisions.</span>
              </div>
            </div>
            <div class="bullet">
              <i class='bx bx-calendar'></i>
              <div>
                <b>Interview logistics</b>
                <span>In-person or online (meeting link). Admin can reschedule/cancel based on availability.</span>
              </div>
            </div>
            <div class="bullet">
              <i class='bx bx-file-export'></i>
              <div>
                <b>Export & reporting</b>
                <span>Export applications (CSV/PDF) for record-keeping or offline review.</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="cta" aria-label="Quick actions">
      <div>
        <h3>Ready to try the workflow?</h3>
        <p>
          Job-seekers can apply and track progress. Admins can shortlist and schedule interviews.
        </p>
      </div>
      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a class="btn btn-primary" href="login_signup.php"><i class='bx bx-log-in'></i> Login</a>
        <a class="btn btn-outline" href="job_post.php"><i class='bx bx-briefcase'></i> Browse Jobs</a>
      </div>
    </section>

<footer class="site-footer" style="margin-top: 80px; padding: 50px 5% 60px; background: var(--dark); color: white; width: 100vw; margin-left: calc(50% - 50vw); position: relative; z-index: 1;">
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

  </div>

</body>
</html>


