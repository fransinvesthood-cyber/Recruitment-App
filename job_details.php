<?php
include ('config.php');

//Get the job ID from the URL
$job_id = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;

if ($job_id > 0) {
    //Fetch job details along with department and company name
    $sql = "SELECT 
                job_postings.*, 
                departments.department_name, 
                companies.company_name 
            FROM job_postings
            INNER JOIN departments ON job_postings.department_id = departments.department_id
            INNER JOIN companies ON job_postings.company_id = companies.company_id
            WHERE job_postings.job_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $job = $result->fetch_assoc();
    } else {
        echo "Job not found.";
        exit;
    }
    
    $stmt->close();
} else {
    echo "Invalid job ID.";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-color: #e74c3c;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #4facfe;
            --error-color: #ff6b6b;
            --warning-color: #feca57;
            --text-color: #2d3748;
            --bg-color: #f5f7fa;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-color);
            transition: background 0.3s ease, color 0.3s ease;
        }

        .card {
            background: var(--card-bg);
            border: none;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideIn 0.5s ease-out;
            transition: background 0.3s ease, border-color 0.3s ease;
            position: relative; /* Needed for absolute positioning of toggle/exit */
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            text-align: center;
            padding: 20px;
            background: transparent; /* Ensure header bg doesn't conflict */
        }

        .card-header h1 {
            margin: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
        }

        p {
			margin: 0 0 1rem 0;
			font-weight: 400;
		}

        .card-body {
            padding: 30px;
        }

        .section-title {
            font-weight: 600;
            color: var(--text-color);
            margin-top: 25px;
            margin-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 5px;
        }

        .btn-exit {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--error-color), #ff4757);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            z-index: 10;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            font-size: 18px;
        }

        .btn-exit:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .apply-button {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-block;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            position: relative;
            overflow: hidden;
        }

        .apply-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .apply-button:hover::before {
            left: 100%;
        }

        .apply-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
            color: white;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        /* --- UNIFIED TOGGLE SWITCH STYLES --- */
        .theme-switch-wrapper {
            position: absolute;
            top: 28px;       /* Aligned vertically with Exit button */
            right: 80px;     /* Positioned to the left of Exit button */
            z-index: 100;
            display: flex;
            align-items: center;
        }

        .theme-switch {
            display: inline-block;
            height: 30px;
            position: relative;
            width: 64px;
        }

        .theme-switch input {
            display: none;
        }

        .slider {
            background-color: #cbd5e1; /* Light gray base */
            bottom: 0;
            cursor: pointer;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            transition: .4s;
            border-radius: 34px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 6px;
        }

        .slider:before {
            background-color: #fff;
            bottom: 3px;
            content: "";
            height: 24px;
            left: 3px;
            position: absolute;
            transition: .4s;
            width: 24px;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 2;
        }

        /* Icons inside the toggle */
        .slider .bx {
            font-size: 16px;
            z-index: 1;
            transition: 0.4s;
        }

        .slider .bx-sun {
            color: #f59e0b; /* Orange/Yellow Sun */
        }

        .slider .bx-moon {
            color: #fff;
            opacity: 0.5;
        }

        /* Checked State (Dark Mode Active) */
        input:checked + .slider {
            background: linear-gradient(135deg, #667eea, #764ba2); /* Purple Gradient */
        }

        input:checked + .slider:before {
            transform: translateX(34px); /* Moves circle right */
        }

        input:checked + .slider .bx-moon {
            opacity: 1;
        }

        input:checked + .slider .bx-sun {
            opacity: 0.5;
            color: #fff;
        }

        /* --- Dark Mode Styles --- */
        body.dark-mode {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #e2e8f0;
        }

        body.dark-mode .card {
            background-color: #1f2937;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6);
            border: 1px solid #374151;
        }

        body.dark-mode .card-header h1 {
            background: linear-gradient(135deg, #667eea, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        body.dark-mode .section-title {
            color: #f0f0f0;
            border-bottom-color: #667eea;
        }

        body.dark-mode p, 
        body.dark-mode strong,
        body.dark-mode .col-md-4, 
        body.dark-mode .col-md-8 {
            color: #d1d5db;
        }

        body.dark-mode .btn-exit {
            background: #374151;
            color: #fff;
            border: 1px solid #555;
        }

        body.dark-mode .btn-exit:hover {
            background: #6366f1;
            border-color: #6366f1;
        }

        /* Share Modal Styles */
        .share-options {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
        }

        .share-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .share-btn i {
            margin-right: 0.5rem;
        }

        .share-btn.whatsapp {
            background: #25d366;
            color: #fff;
        }

        .share-btn.whatsapp:hover {
            background: #128c7e;
        }

        .share-btn.facebook {
            background: #1877f2;
            color: #fff;
        }

        .share-btn.facebook:hover {
            background: #166fe5;
        }

        .share-btn.twitter {
            background: #1da1f2;
            color: #fff;
        }

        .share-btn.twitter:hover {
            background: #1a91da;
        }

        .share-btn.linkedin {
            background: #0077b5;
            color: #fff;
        }

        .share-btn.linkedin:hover {
            background: #005885;
        }

        .share-btn.email {
            background: #ea4335;
            color: #fff;
        }

        .share-btn.email:hover {
            background: #d33b2c;
        }

        .share-btn.copy {
            background: #6c757d;
            color: #fff;
        }

        .share-btn.copy:hover {
            background: #5a6268;
        }

        .share-button {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-block;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .share-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        }

        /* Exit Button */
        .btn-exit {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f3f4f6;
            color: #4f46e5;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            backdrop-filter: blur(10px);
        }
        .btn-exit:hover {
            background: #e0e7ff;
            color: #3730a3;
            transform: scale(1.05);
        }

        /* Modal Dark Mode Fixes */
        body.dark-mode .modal-content {
            background-color: #1f2937;
            color: #f0f0f0;
            border: 1px solid #374151;
        }
        
        body.dark-mode .modal-header {
            border-bottom-color: #374151;
        }
        
        body.dark-mode .btn-close {
            filter: invert(1);
        }

        @media (max-width: 768px) {
            .theme-switch-wrapper {
                right: 70px;
            }
            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid d-flex justify-content-center align-items-center min-vh-100">
        <div class="card w-100" style="max-width: 1000px;">
            <button class="btn-exit" id="exitPage"><i class="fas fa-times"></i></button>
            
            <div class="card-header">
                <h1 class="mb-0"><?php echo htmlspecialchars($job['position']); ?> - Full Job Description</h1>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Department:</strong></div>
                    <div class="col-md-8"><?php echo htmlspecialchars($job['department_name']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Company:</strong></div>
                    <div class="col-md-8"><?php echo htmlspecialchars($job['company_name']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Location:</strong></div>
                    <div class="col-md-8"><?php echo htmlspecialchars($job['location']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Salary:</strong></div>
                    <div class="col-md-8">R<?php echo htmlspecialchars($job['salary']); ?></div>
                </div>

                <h5 class="section-title">Job Description</h5>
                <p><?php echo nl2br(htmlspecialchars($job['job_description'])); ?></p>

                <h5 class="section-title">Duties and Responsibilities</h5>
                <p><?php echo nl2br(htmlspecialchars($job['duties'])); ?></p>

                <h5 class="section-title">Qualifications and Experience</h5>
                <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>

                <h5 class="section-title">Required Skills</h5>
                <p><?php echo nl2br(htmlspecialchars($job['skills'])); ?></p>

                <div class="text-center mt-4">
                    <a href="apply.php?job_id=<?php echo $job['job_id']; ?>&position=<?php echo urlencode($job['position']); ?>" class="apply-button">Apply Now</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share this Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="share-options">
                        <button class="share-btn whatsapp" onclick="shareToWhatsApp()"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                        <button class="share-btn facebook" onclick="shareToFacebook()"><i class="fab fa-facebook-f"></i> Facebook</button>
                        <button class="share-btn twitter" onclick="shareToTwitter()"><i class="fab fa-twitter"></i> Twitter</button>
                        <button class="share-btn linkedin" onclick="shareToLinkedIn()"><i class="fab fa-linkedin-in"></i> LinkedIn</button>
                        <button class="share-btn email" onclick="shareViaEmail()"><i class="fas fa-envelope"></i> Email</button>
                        <button class="share-btn copy" onclick="copyLink()"><i class="fas fa-copy"></i> Copy Link</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<script>
    document.getElementById("exitPage").addEventListener("click", function() {
            window.history.back();
    });

    // --- Dark Mode Logic (Synced) ---
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('dark-mode-toggle');
        const body = document.body;

        function applyTheme(isEnabled) {
            if (isEnabled) {
                body.classList.add('dark-mode');
                toggle.checked = true;
            } else {
                body.classList.remove('dark-mode');
                toggle.checked = false;
            }
        }

        // 1. Check LocalStorage on Load
        const savedSetting = localStorage.getItem('darkMode');
        if (savedSetting === 'enabled') {
            applyTheme(true);
        } else {
            applyTheme(false);
        }

        // 2. Listen for Toggle Changes
        toggle.addEventListener('change', () => {
            if (toggle.checked) {
                localStorage.setItem('darkMode', 'enabled');
                applyTheme(true);
            } else {
                localStorage.setItem('darkMode', 'disabled');
                applyTheme(false);
            }
        });
    });

    let shareUrl = '';
    let shareTitle = '';

    function openShareModal(url, title) {
        shareUrl = decodeURIComponent(url);
        shareTitle = title;
        // Use Bootstrap 5 JS to show modal
        const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
        shareModal.show();
    }

    function shareToWhatsApp() {
        const text = encodeURIComponent(shareTitle + ' ' + shareUrl);
        window.open('https://wa.me/?text=' + text, '_blank');
    }

    function shareToFacebook() {
        const url = encodeURIComponent(shareUrl);
        window.open('https://www.facebook.com/sharer/sharer.php?u=' + url, '_blank');
    }

    function shareToTwitter() {
        const text = encodeURIComponent(shareTitle);
        const url = encodeURIComponent(shareUrl);
        window.open('https://twitter.com/intent/tweet?text=' + text + '&url=' + url, '_blank');
    }

    function shareToLinkedIn() {
        const url = encodeURIComponent(shareUrl);
        window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + url, '_blank');
    }

    function shareViaEmail() {
        const subject = encodeURIComponent(shareTitle);
        const body = encodeURIComponent('Check out this job: ' + shareUrl);
        window.location.href = 'mailto:?subject=' + subject + '&body=' + body;
    }

    function copyLink() {
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('Link copied to clipboard!');
        }).catch(() => {
            prompt('Copy this link:', shareUrl);
        });
    }
</script>
</html>