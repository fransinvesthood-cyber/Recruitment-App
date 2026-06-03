<?php
include('config.php');
session_start();

// Retrieve job_id and position from URL
$job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;
$position = isset($_GET['position']) ? $_GET['position'] : '';

// Validate job_id
if (!$job_id) {
    die("Error: Invalid job ID.");
}

// Validate position
if (empty($position)) {
    die("Error: Position not passed in the URL.");
}

// Check if job exists in the job_postings table
$sql = "SELECT * FROM job_postings WHERE job_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Error: The job posting does not exist.");
}
$job = $result->fetch_assoc();
$stmt->close();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You cannot perform this action. Please login to apply.'); window.location.href='login_signup.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's full name and email from the database
$sql = "SELECT fullname, email, gender, dob, phone FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email, $gender, $dob, $phone);
$stmt->fetch();
$stmt->close();

// Store in session for form usage
$_SESSION['fullname'] = $fullname;
$_SESSION['email'] = $email;
$_SESSION['dob'] = $dob;
$_SESSION['phone'] = $phone;
$_SESSION['gender'] = $gender;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo htmlspecialchars($position); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/> 
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css'  rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css"> 
    <style>
        :root {
            /* Light Theme Defaults */
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #4facfe;
            --error-color: #ff6b6b;
            --warning-color: #feca57;
            --text-color: #2d3748;
            --bg-color: #f7fafc; 
            --bg-gradient-start: #e0e7ff;
            --bg-gradient-end: #f0f2f5;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-color);
            transition: background 0.3s ease, color 0.3s ease;
        }

        .container {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 1000px;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideIn 0.5s ease-out;
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
        }

        .header h2 {
            margin: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); /* Purple Gradient */
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

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            align-items: start;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 10px;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .required {
            color: var(--error-color);
        }

        input[type="text"],
        input[type="email"],
        input[type="date"],
        input[type="file"],
        textarea,
        select {
            padding: 15px 18px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 15px;
            transition: var(--transition);
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: inherit;
        }

        input[type="file"] {
            padding: 10px;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        /* --- Dark Mode Styles --- */
        body.dark-mode {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #e2e8f0;
        }

        body.dark-mode .container {
            background-color: #1f2937;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6);
            border: 1px solid #374151;
        }

        body.dark-mode .header h2 {
            background: linear-gradient(135deg, #667eea, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        body.dark-mode label {
            color: #e2e8f0;
        }

        body.dark-mode input, 
        body.dark-mode textarea, 
        body.dark-mode select {
            background-color: #374151;
            border-color: #4b5563;
            color: #e2e8f0;
        }

        body.dark-mode input:focus, 
        body.dark-mode textarea:focus {
            border-color: #667eea;
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

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1e1b4b, #312e81);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        /* Button Container */
        .button-container {
            display: flex;
            justify-content: center;
            grid-column: 1 / -1;
            margin-top: 20px;
        }

        .btn-submit {
            padding: 15px 30px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            transition: var(--transition);
            min-width: 200px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:disabled {
            background: #a0aec0;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Error Messages */
        .error-message {
            color: var(--error-color);
            font-size: 12px;
            margin-top: 8px;
            display: none;
            font-weight: 500;
        }

        .success-message {
            color: var(--success-color);
            font-size: 12px;
            margin-top: 8px;
            display: none;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .theme-switch-wrapper {
                right: 70px;
            }
            .container {
                padding: 25px;
                margin: 20px;
            }
            form {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .btn-submit {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <button class="btn-exit" id="exitPage"><i class='bx bx-x'></i></button>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1>Apply for a Job</h1>
        <p>Fill out the form below to submit your application</p>
    </div>

    <form id="applicationForm" action="submit_application.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">

        <div class="form-group">
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" value="<?php echo $_SESSION['fullname']; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="gender">Gender</label>
            <input type="text" id="gender" name="gender" value="<?php echo $_SESSION['gender']; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?php echo $_SESSION['phone']; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="dob">Date of Birth</label>
            <input type="text" id="dob" name="dob" value="<?php echo $_SESSION['dob']; ?>" readonly>
        </div>



        <div class="form-group">
            <label for="position">Position</label>
            <input type="text" name="position" id="position" value="<?php echo htmlspecialchars($position); ?>" readonly>
        </div>

        <div class="form-group">
            <label for="availability">Availability <span class="required">*</span></label>
            <input type="date" name="availability" id="availability" required>
            <div class="error-message" id="availabilityError">Please select an availability date.</div>
            <div class="success-message" id="availabilitySuccess">Valid availability selected.</div>
        </div>



        <div class="form-group full-width">
            <label for="cover_letter">Cover Letter <span class="required">*</span></label>
            <textarea rows="6" name="cover_letter" id="cover_letter" required></textarea>
            <div class="error-message" id="coverLetterError">Please write a cover letter.</div>
            <div class="success-message" id="coverLetterSuccess">Cover letter provided.</div>
        </div>

        <div class="form-group full-width">
            <label for="resume">Upload Resume (PDF or DOCX) <span class="required">*</span></label>
            <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
            <div class="error-message" id="resumeError">Please upload your resume.</div>
        </div>

        <div class="button-container">
            <button type="submit" class="btn-submit" id="submitBtn" disabled>
                <i class='bx bx-paper-plane'></i> Submit Application
            </button>
        </div>
    </form>
</div>

<script>
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

    // --- Form Logic ---
    let validationState = {
        availability: false,
        cover_letter: false,
        resume: false
    };

    const patterns = {
        coverLetter: /.+/s
    };

    document.addEventListener('DOMContentLoaded', () => {
        setupEventListeners();
        updateSubmitButton();
    });

    function setupEventListeners() {
        document.getElementById("exitPage").addEventListener("click", () => window.history.back());

        document.getElementById("availability").addEventListener("change", validateAvailability);


        document.getElementById("cover_letter").addEventListener("input", validateCoverLetter);

        document.getElementById("resume").addEventListener("change", validateResume);



        document.getElementById("applicationForm").addEventListener("submit", handleFormSubmit);
    }

    function validateAvailability() {
        const val = document.getElementById("availability").value;
        if (!val) {
            showError("availability", "availabilityError", "Please select an availability date");
            validationState.availability = false;
        } else {
            showSuccess("availability", "availabilitySuccess");
            validationState.availability = true;
        }
        updateSubmitButton();
    }





    function validateCoverLetter() {
        const val = document.getElementById("cover_letter").value.trim();
        if (val.length === 0) {
            showError("cover_letter", "coverLetterError", "Cover letter is required");
            validationState.cover_letter = false;
        } else {
            showSuccess("cover_letter", "coverLetterSuccess");
            validationState.cover_letter = true;
        }
        updateSubmitButton();
    }

    function validateResume() {
        const fileInput = document.getElementById("resume");
        const errorEl = document.getElementById("resumeError");

        if (fileInput.files.length === 0) {
            errorEl.textContent = "Please upload your resume.";
            errorEl.style.display = "block";
            validationState.resume = false;
        } else {
            const fileName = fileInput.files[0].name;
            const validExtensions = /(\.pdf|\.doc|\.docx)$/i;
            if (!validExtensions.test(fileName)) {
                errorEl.textContent = "Only PDF, DOC, or DOCX files are allowed.";
                errorEl.style.display = "block";
                validationState.resume = false;
            } else {
                errorEl.style.display = "none";
                validationState.resume = true;
            }
        }
        updateSubmitButton();
    }

    function showError(inputId, errorId, message = "") {
        const input = document.getElementById(inputId);
        const error = document.getElementById(errorId);
        const success = document.getElementById(errorId.replace("Error", "Success"));
        input.classList.add("error");
        input.classList.remove("success");
        error.textContent = message || "This field is invalid";
        error.style.display = "block";
        success.style.display = "none";
    }

    function showSuccess(inputId, successId) {
        const input = document.getElementById(inputId);
        const success = document.getElementById(successId);
        const error = document.getElementById(successId.replace("Success", "Error"));
        input.classList.add("success");
        input.classList.remove("error");
        success.style.display = "block";
        error.style.display = "none";
    }

    function updateSubmitButton() {
        const btn = document.getElementById("submitBtn");
        const valid = Object.values(validationState).every(v => v === true);
        btn.disabled = !valid;
        btn.style.opacity = valid ? "1" : "0.6";
    }

    function handleFormSubmit(e) {
        e.preventDefault();
        if (!Object.values(validationState).every(v => v === true)) {
            Swal.fire({
                icon: "error",
                title: "Validation Error",
                text: "Please fix all errors before submitting.",
                confirmButtonColor: "#2980b9"
            });
            return;
        }

        // Show loading state
        const btn = document.getElementById("submitBtn");
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Submitting...';
        btn.disabled = true;

        const formData = new FormData(document.getElementById("applicationForm"));

        fetch("submit_application.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Application Submitted!",
                    text: "Thank you for applying.",
                    confirmButtonColor: "#2980b9"
                }).then(() => {
                    window.location.href = 'applicant.php';
                });
            } else {
                throw new Error(data.message || "Failed to submit application");
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                icon: "error",
                title: "Submission Failed",
                text: err.message,
                confirmButtonColor: "#2980b9"
            });
        })
        .finally(() => {
            btn.innerHTML = '<i class="bx bx-paper-plane"></i> Submit Application';
            btn.disabled = false;
        });
    }
</script>
</body>
</html>