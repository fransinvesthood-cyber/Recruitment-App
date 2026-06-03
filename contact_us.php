<?php
session_start();
include('config.php');

$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($fullname !== '' && $email !== '' && $subject !== '' && $message !== '') {
        // Store contact message in DB (contact_messages table)
        // Expected schema: contact_messages(fullname, email, subject, message, created_at)

        // Minimal email validation to avoid garbage rows
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO contact_messages (fullname, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())"
                );

                if (!$stmt) {
                    throw new RuntimeException('Prepare failed: ' . $conn->error);
                }

                $stmt->bind_param('ssss', $fullname, $email, $subject, $message);

                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $error = 'Unable to submit your message right now.';
                }

                $stmt->close();
            } catch (Throwable $e) {
                // Fall back for older installs without created_at column
                try {
                    $stmt2 = $conn->prepare(
                        "INSERT INTO contact_messages (fullname, email, subject, message) VALUES (?, ?, ?, ?)"
                    );

                    if (!$stmt2) {
                        throw new RuntimeException('Prepare failed: ' . $conn->error);
                    }

                    $stmt2->bind_param('ssss', $fullname, $email, $subject, $message);

                    if ($stmt2->execute()) {
                        $success = true;
                    } else {
                        $error = 'Unable to submit your message right now.';
                    }

                    $stmt2->close();
                } catch (Throwable $e2) {
                    $error = 'Unable to submit your message right now. Please try again later.';
                }
            }
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Candidit</title>
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
            --gray-600: #6B7280;
            --gray-800: #18181B;
            --grid-main: rgba(124, 58, 237, 0.15);
            --grid-sub: rgba(124, 58, 237, 0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--white); color: var(--dark); line-height: 1.6; overflow-x: hidden; }

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

        .page {
            padding-top: 110px;
            padding-bottom: 80px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 460px;
            gap: 30px;
            align-items: start;
        }

        @media (max-width: 980px) {
            .container { grid-template-columns: 1fr; }
        }

        .panel {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 32px;
            padding: 32px;
            box-shadow: 0 30px 80px -40px rgba(0,0,0,0.08);
            backdrop-filter: blur(10px);
        }

        h1 {
            font-size: 2.3rem;
            font-weight: 900;
            letter-spacing: -0.03em;
            margin-bottom: 10px;
        }
        .subtitle { color: var(--gray-600); font-weight: 600; margin-bottom: 22px; }

        label {
            display: block;
            font-weight: 800;
            font-size: 0.85rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            color: var(--gray-400);
            letter-spacing: 0.02em;
        }

        input, textarea {
            width: 100%;
            padding: 14px 14px;
            border-radius: 14px;
            border: 1px solid var(--gray-200);
            background: #fff;
            font-size: 1rem;
            margin-bottom: 14px;
            transition: 0.3s;
        }

        textarea { min-height: 150px; resize: vertical; }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            width: 100%;
            padding: 14px 18px;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 900;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 15px 30px var(--primary-glow);
        }
        .btn-primary:hover { transform: translateY(-2px); }

        .alert {
            padding: 12px 14px;
            border-radius: 14px;
            margin-bottom: 16px;
            font-weight: 800;
        }
        .alert.success { background: rgba(34,197,94,0.12); color: #166534; border: 1px solid rgba(34,197,94,0.25); }
        .alert.error { background: rgba(239,68,68,0.12); color: #991B1B; border: 1px solid rgba(239,68,68,0.25); }

        .side-card {
            background: var(--dark);
            color: white;
            border-radius: 26px;
            padding: 22px;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            position: sticky;
            top: 120px;
        }
        @media (max-width: 980px) { .side-card { position: relative; top: 0; } }

        .side-title {
            font-size: 1.1rem;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .contact-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .contact-row:last-child { border-bottom: none; }
        .icon {
            width: 42px; height: 42px;
            border-radius: 14px;
            background: rgba(124,58,237,0.2);
            border: 1px solid rgba(124,58,237,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .contact-row a { color: white; text-decoration: none; font-weight: 800; }
        .contact-row a:hover { color: #E9D5FF; }
        .contact-row .meta { color: rgba(255,255,255,0.75); font-weight: 700; font-size: 0.9rem; margin-top: 2px; }

        .fineprint {
            margin-top: 18px;
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            font-weight: 700;
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

    <main class="page">
        <div class="container">
            <section class="panel">
                <h1>Contact Us</h1>
                <p class="subtitle">Send a message and our team will get back to you as soon as possible.</p>

                <?php if ($success): ?>
                    <div class="alert success">Your message has been submitted successfully.</div>
                <?php elseif ($error): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="contact_us.php" autocomplete="on">
                    <div>
                        <label for="fullname">Full Name</label>
                        <input id="fullname" name="fullname" type="text" placeholder="Enter your name and surname" required>
                    </div>

                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" placeholder="Enter email address" required>
                    </div>

                    <div>
                        <label for="subject">Subject</label>
                        <input id="subject" name="subject" type="text" placeholder="Enter your subject" required>
                    </div>

                    <div>
                        <label for="message">Message</label>
                        <textarea id="message" name="message" placeholder="How can we help?" required></textarea>
                    </div>

                    <button class="btn-primary" type="submit">Send Message</button>
                </form>
            </section>

            <aside class="side-card">
                <div class="side-title">Reach us directly</div>

                <div class="contact-row">
                    <div class="icon">✉️</div>
                    <div>
                        <div style="font-weight: 900;">Email</div>
                        <div class="meta"><a href="mailto:admin@investhoodit.co.za">admin@investhoodit.co.za</a></div>
                    </div>
                </div>

                <div class="contact-row">
                    <div class="icon">📞</div>
                    <div>
                        <div style="font-weight: 900;">Phone</div>
                        <div class="meta"><a href="tel:0682460562">068 246 0562</a></div>
                    </div>
                </div>

                <div class="contact-row">
                    <div class="icon">📍</div>
                    <div>
                        <div style="font-weight: 900;">Address</div>
                        <div class="meta">136 2nd St, Randjespark, Midrand, 1685</div>
                    </div>
                </div>

                <div class="map-wrap">
                    <div class="map-embed">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3581.679385043763!2d28.1219652!3d-25.9889752!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1e956e178381e3b9%3A0x1cb0aca2fa587590!2s136%202nd%20St%2C%20Randjespark%2C%20Midrand%2C%201685!5e0!3m2!1sen!2sza!4v1694255193184!5m2!1sen!2sza"
                            width="100%"
                            height="240"
                            style="border:0; border-radius: 16px;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>

                <div class="fineprint">We’ll respond as soon as possible. For job applications, use the “Find Jobs” page.</div>
            </aside>
        </div>
</main>

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
