<?php
session_start();
include 'config.php'; // your DB connection

$resumeExists = false;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT resume FROM resume WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $resumeExists = true;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <title>Upload CV/Resume</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #e0e7ff 0%, #f0f2f5 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .resume-reset-container {
            background: rgba(255,255,255,0.92);
            padding: 38px 38px 32px 38px;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(60, 72, 88, 0.15), 0 1.5px 4px rgba(60, 72, 88, 0.07);
            width: 100%;
            max-width: 600px;
            backdrop-filter: blur(8px);
            position: relative; /* Added to contain absolute buttons */
        }
        .header-section {
            text-align: center;
            margin-bottom: 18px;
            margin-top: 20px; /* Added spacing so header doesn't overlap buttons */
        }
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #3730a3;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .page-subtitle {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 300;
            color: #4b5563;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .welcome-section h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .welcome-section p {
            opacity: 0.9;
            font-size: 18px;
        }
        .resume-status {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #6366f1;
        }
        .status-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .status-title {
            font-weight: 600;
            color: #2c3e50;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .badge-success {
            background: #e6f4ea;
            color: #188038;
        }
        .badge-warning {
            background: #fdecea;
            color: #d93025;
        }
        .status-text {
            color: #6b7280;
            font-size: 14px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .upload-section {
            margin-bottom: 30px;
        }
        .file-input-wrapper {
            position: relative;
            margin-bottom: 25px;
        }
        .file-input {
            display: none;
        }
        .file-input-label {
            display: block;
            padding: 20px;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        .file-input-label:hover {
            border-color: #6366f1;
            background: #eef2ff;
            transform: translateY(-2px);
        }
        .file-input-label.dragover {
            border-color: #6366f1;
            background: #eef2ff;
        }
        .upload-icon {
            font-size: 48px;
            color: #9ca3af;
            margin-bottom: 12px;
            display: block;
        }
        .file-input-label:hover .upload-icon {
            color: #6366f1;
            transform: scale(1.1);
        }
        .upload-text {
            font-size: 16px;
            color: #374151;
            font-weight: 500;
            margin-bottom: 5px;
        }
        .upload-subtext {
            font-size: 14px;
            color: #6b7280;
        }
        .file-info {
            display: none;
            background: #f0f9ff;
            border: 1px solid #6366f1;
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
            color: #3730a3;
            font-size: 14px;
        }
        .upload-btn {
            width: 100%;
            padding: 13px 0;
            background: linear-gradient(90deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 16.5px;
            font-weight: 600;
            letter-spacing: 0.2px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(99,102,241,0.08);
            transition: background 0.2s, box-shadow 0.2s;
            margin-bottom: 20px;
        }
        .upload-btn:hover {
            background: linear-gradient(90deg, #4f46e5 0%, #6366f1 100%);
            box-shadow: 0 4px 16px rgba(99,102,241,0.13);
            transform: translateY(-1px);
        }
        .upload-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .actions-section {
            border-top: 1px solid #e5e7eb;
            padding-top: 25px;
        }
        .action-btn {
            width: 100%;
            padding: 14px 20px;
            margin-bottom: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 2px solid transparent;
        }
        .btn-preview {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        .btn-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            color: white;
            text-decoration: none;
        }
        .btn-download {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
            color: white;
            text-decoration: none;
        }
        .btn-disabled {
            background: #f3f4f6 !important;
            color: #9ca3af !important;
            cursor: not-allowed !important;
            border-color: #e5e7eb !important;
        }
        .btn-disabled:hover {
            transform: none !important;
            box-shadow: none !important;
            color: #9ca3af !important;
        }
        .alert {
            padding: 15px 20px;
            background-color: #e8f0fe;
            color: #1a73e8;
            border-radius: 8px;
            margin-bottom: 18px;
            font-family: inherit;
            box-shadow: 0 2px 6px rgba(99,102,241,0.07);
            font-size: 15.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.success {
            background-color: #e6f4ea;
            color: #188038;
            border-left: 4px solid #188038;
        }
        .alert.error {
            background-color: #fdecea;
            color: #d93025;
            border-left: 4px solid #d93025;
        }
        .alert.info {
            background: #eff6ff;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        @media (max-width: 600px) {
            .resume-reset-container {
                padding: 18px 4vw 14px 4vw;
                max-width: 99vw;
            }
            .page-title {
                font-size: 1.2rem;
            }
            .upload-btn {
                font-size: 15px;
            }
            /* Responsive adjustment for buttons */
            .theme-toggle-container {
                right: 70px !important;
                top: 20px !important;
            }
        }
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
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Dark Mode Variables */
        :root {
            --bg-color: linear-gradient(135deg, #e0e7ff 0%, #f0f2f5 100%);
            --text-color: #333;
            --card-bg: rgba(255,255,255,0.92);
            --file-bg: #f8fafc;
            --file-border: #d1d5db;
            --file-hover-bg: #eef2ff;
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background: linear-gradient(135deg, #0d1117 0%, #161b22 100%);
            color: #f0f0f0;
        }

        body.dark-mode .resume-reset-container {
            background: #1f1f1f;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }

        body.dark-mode .page-title, 
        body.dark-mode .section-title {
            color: #ffffff !important;
        }

        body.dark-mode .page-subtitle,
        body.dark-mode .upload-text,
        body.dark-mode .status-title {
            color: #e0e0e0 !important;
        }

        body.dark-mode .upload-subtext,
        body.dark-mode .status-text {
            color: #b0b0b0 !important;
        }

        body.dark-mode .resume-status {
            background: #2a2a2a;
            border-left: 4px solid #6366f1;
            color: #f0f0f0;
        }

        body.dark-mode .file-input-label {
            background: #2a2a2a;
            border: 2px dashed #555;
            color: #f0f0f0;
        }

        body.dark-mode .file-input-label:hover {
            background: #333;
            border-color: #6366f1;
        }

        body.dark-mode .badge-success {
            background: #064e3b;
            color: #34d399;
        }

        body.dark-mode .badge-warning {
            background: #450a0a;
            color: #fca5a5;
        }

        body.dark-mode .btn-exit {
            background: #333;
            color: #fff;
            border: 1px solid #555;
        }

        body.dark-mode .btn-exit:hover {
            background: #6366f1;
            border-color: #6366f1;
        }

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1a1a2e 0%, #2d1f3d 100%);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        /* --- Theme Toggle Positioned Next to Exit Button --- */
        .theme-toggle-container {
            position: absolute;
            top: 25px;       /* Aligns vertically with the Exit button */
            right: 80px;     /* Positions it to the left of the Exit button */
            z-index: 100;
        }

        #theme-toggle {
            display: none;
        }

        .theme-label {
            width: 50px;
            height: 26px;
            background-color: #ccc;
            border-radius: 50px;
            display: inline-block;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .theme-label::after {
            content: '';
            width: 20px;
            height: 20px;
            background-color: white;
            border-radius: 50%;
            position: absolute;
            top: 3px;
            left: 3px;
            transition: transform 0.3s;
        }

        #theme-toggle:checked + .theme-label {
            background-color: #6366f1;
        }

        #theme-toggle:checked + .theme-label::after {
            transform: translateX(24px);
        }

        .toggle-icon {
            position: absolute;
            top: 5px;
            font-size: 14px;
            color: white;
            z-index: 10;
        }
        .bx-sun { left: 6px; }
        .bx-moon { right: 6px; }

    </style>
</head>
<body>
    
    <div class="resume-reset-container">
        <button class="btn-exit" id="exitPage" title="Exit to Dashboard">
            <i class='bx bx-x'></i>
        </button>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Resume Upload</h1>
            <p>Manage and upload your professional resume</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $messageClass ?>" id="alertBox">
                <?php if ($messageClass === 'success'): ?>
                    <i class='bx bx-check-circle'></i>
                <?php elseif ($messageClass === 'error'): ?>
                    <i class='bx bx-error-circle'></i>
                <?php else: ?>
                    <i class='bx bx-info-circle'></i>
                <?php endif; ?>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="resume-status">
            <div class="status-header">
                <i class='bx bx-file-find'></i>
                <span class="status-title">Resume Status</span>
                <?php if ($resumeExists): ?>
                    <span class="status-badge badge-success">Uploaded</span>
                <?php else: ?>
                    <span class="status-badge badge-warning">Not Uploaded</span>
                <?php endif; ?>
            </div>
            <p class="status-text">
                <?php if ($resumeExists): ?>
                    Your resume is successfully uploaded and ready for applications.
                <?php else: ?>
                    Upload your resume to start applying for jobs.
                <?php endif; ?>
            </p>
        </div>

        <div class="upload-section">
            <h3 class="section-title">
                <i class='bx bx-upload'></i>
                <?= $resumeExists ? 'Update Resume' : 'Upload Resume' ?>
            </h3>
            
            <form method="post" action="upload_resume.php" enctype="multipart/form-data" id="uploadForm">
                <div class="file-input-wrapper">
                    <input type="file" name="resume" id="resume" class="file-input" accept=".pdf,.doc,.docx" required>
                    <label for="resume" class="file-input-label" id="fileLabel">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <div class="upload-text">Choose Resume File</div>
                        <div class="upload-subtext">PDF, DOC, or DOCX (Max 10MB)</div>
                    </label>
                    <div class="file-info" id="fileInfo">
                        <i class='bx bx-file'></i>
                        <span id="fileName">No file selected</span>
                    </div>
                </div>

                <button type="submit" class="upload-btn" id="uploadButton">
                    <i class="fas fa-upload"></i>
                    <?= $resumeExists ? 'Update Resume' : 'Upload Resume' ?>
                </button>
            </form>

            <div class="loading" id="loadingDiv">
                <div class="loading-spinner"></div>
                <p>Processing your resume...</p>
            </div>
        </div>

        <div class="actions-section">
            <h3 class="section-title">
                <i class='bx bx-cog'></i>
                Resume Actions
            </h3>
            
            <a href="preview_resume.php" 
               class="action-btn btn-preview <?= !$resumeExists ? 'btn-disabled' : '' ?>" 
               <?= !$resumeExists ? 'onclick="return false;"' : '' ?>>
                <i class='bx bx-show'></i>
                Preview Resume
            </a>

            <a href="resume_download.php" 
               class="action-btn btn-download <?= !$resumeExists ? 'btn-disabled' : '' ?>" 
               <?= !$resumeExists ? 'onclick="return false;"' : '' ?>
               download>
                <i class='bx bx-download'></i>
                Download Resume
            </a>
        </div>
    </div>

    <script>
        // Exit button functionality
        document.getElementById("exitPage").addEventListener("click", function() {
            window.location.href = "applicant.php";
        });

        // File input enhancement
        const fileInput = document.getElementById('resume');
        const fileLabel = document.getElementById('fileLabel');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const uploadForm = document.getElementById('uploadForm');
        const uploadButton = document.getElementById('uploadButton');
        const loadingDiv = document.getElementById('loadingDiv');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileInfo.style.display = 'block';
                fileLabel.style.borderColor = '#6366f1';
                fileLabel.style.background = '#eef2ff';
            } else {
                fileInfo.style.display = 'none';
                fileLabel.style.borderColor = '#d1d5db';
                fileLabel.style.background = '#f8fafc';
            }
        });

        // Drag and drop functionality
        fileLabel.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        fileLabel.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        fileLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                const changeEvent = new Event('change');
                fileInput.dispatchEvent(changeEvent);
            }
        });

        // Form submission with loading
        uploadForm.addEventListener('submit', function() {
            uploadButton.style.display = 'none';
            loadingDiv.style.display = 'block';
        });

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });

        // File validation
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (file.size > maxSize) {
                    alert('File size too large. Please select a file under 10MB.');
                    this.value = '';
                    fileInfo.style.display = 'none';
                    return;
                }
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please select a PDF, DOC, or DOCX file.');
                    this.value = '';
                    fileInfo.style.display = 'none';
                    return;
                }
            }
        });

        // Enhanced button interactions
        document.querySelectorAll('.action-btn').forEach(btn => {
            if (!btn.classList.contains('btn-disabled')) {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            }
        });

        // ============================================
        // DARK MODE SYNC LOGIC
        // ============================================
        const themeToggle = document.getElementById('theme-toggle');

        function applyTheme(isEnabled) {
            if (isEnabled) {
                document.body.classList.add('dark-mode');
                themeToggle.checked = true;
            } else {
                document.body.classList.remove('dark-mode');
                themeToggle.checked = false;
            }
            updateInputStyles();
        }

        // 1. Check LocalStorage on Load
        window.addEventListener('DOMContentLoaded', () => {
            const savedSetting = localStorage.getItem('darkMode');
            if (savedSetting === 'enabled') {
                applyTheme(true);
            } else {
                applyTheme(false);
            }
        });

        // 2. Listen for Toggle Changes
        themeToggle.addEventListener('change', () => {
            if (themeToggle.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
            updateInputStyles();
        });

        // 3. Re-apply input styles dynamically if needed
        function updateInputStyles() {
            const isDark = document.body.classList.contains('dark-mode');
            if (!fileInput.files.length) {
                fileLabel.style.background = isDark ? '#2a2a2a' : '#f8fafc';
                fileLabel.style.borderColor = isDark ? '#555' : '#d1d5db';
            }
        }

    </script>
</body>
</html>