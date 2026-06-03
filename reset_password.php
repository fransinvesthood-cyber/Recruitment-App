<?php
session_start();
$message = $_SESSION['message'] ?? '';
$messageClass = $_SESSION['messageClass'] ?? '';
unset($_SESSION['message'], $_SESSION['messageClass']);
?>

<?php
$token = $_GET['token'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            /*background-image: url(./img/bg101.jpg);*/
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
        }

        .container {
            position: relative;
            max-width: 850px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 50px 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            perspective: 2700px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 1s ease-out;
        }

        .container .cover {
            position: absolute;
            top: 0;
            left: 50%;
            height: 100%;
            width: 50%;
            z-index: 98;
            transition: all 1.2s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: left;
            transform-style: preserve-3d;
            backface-visibility: hidden;
            border-radius: 20px;
        }

        .container .cover img {
            position: absolute;
            height: 100%;
            width: 100%;
            object-fit: cover;
            z-index: 10;
            border-radius: 20px;
        }

        .container .cover .text {
            position: absolute;
            z-index: 10;
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container .cover .text::before {
            content: '';
            position: absolute;
            height: 100%;
            width: 100%;
            opacity: 0.6;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
        }

        .cover .text .text-1,
        .cover .text .text-2 {
            z-index: 20;
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInUp 1.5s ease-out;
        }

        .cover .text .text-2 {
            font-size: 16px;
            font-weight: 500;
            margin-top: 10px;
        }

        .container .forms {
            height: 100%;
            width: 100%;
            background: transparent;
        }

        .container .form-content {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 50px;
        }

        .form-content .forgot-form {
            width: calc(50% - 25px);
        }

        .forms .form-content .title {
            position: relative;
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            text-align: center;
        }

        .forms .form-content .title:before {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -5px;
            height: 4px;
            width: 50px;
            background: linear-gradient(90deg, #e74c3c 0%, #3498db 100%);
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .forms .form-content .input-boxes {
            margin-top: 30px;
        }

        .forms .form-content .input-box {
            display: flex;
            align-items: center;
            height: 50px;
            width: 100%;
            margin: 15px 0;
            position: relative;
            background-color: #f7fafc;
            border: 1.5px solid #e2e8f0;
            padding: 0;
            border-radius: 10px;
            transition: border-color 0.3s;
            box-shadow: 0 1px 2px rgba(60,72,88,0.03);
        }

        .forms .form-content .input-box:focus-within {
            border-color: #e74c3c;
            background: #fef2f2;
        }

        .form-content .input-box input {
            flex: 1;
            height: 100%;
            width: 100%;
            outline: none;
            border: none;
            padding: 0 15px 0 45px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            color: #333;
        }

        .form-content .input-box input:focus {
            border-color: #e74c3c;
        }

        .form-content .input-box input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .form-content .input-box i {
            position: absolute;
            left: 12px;
            color: #e74c3c;
            font-size: 16px;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .forms .form-content .text {
            font-size: 14px;
            font-weight: 500;
            color: #555;
            text-align: center;
            margin-top: 20px;
        }

        .forms .form-content .text a {
            text-decoration: none;
            color: #e74c3c;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .forms .form-content .text a:hover {
            color: #c0392b;
            text-decoration: underline;
        }

        .forms .form-content .button {
            margin-top: 40px;
            text-align: center;
        }

        .forms .form-content .button input {
            color: #fff;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border: none;
            border-radius: 25px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            width: 100%;
            text-align: center;
            min-height: 55px;
        }

        .forms .form-content .button input:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
        }

        .forms .form-content .button input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .forms .form-content .button input.enabled {
            background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
            cursor: pointer;
        }

        .forms .form-content .button input.enabled:hover {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            box-shadow: 0 4px 16px rgba(231, 76, 60, 0.13);
            transform: translateY(-1px);
        }

        .alert {
            width: 100%;
            margin: 0 0 20px 0;
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 15px;
            text-align: center;
            opacity: 1;
            transition: opacity 1s ease;
        }

        .alert-success {
            background-color: #e6f4ea;
            color: #188038;
            border-left: 4px solid #188038;
        }

        .alert-danger {
            background-color: #fdecea;
            color: #d93025;
            border-left: 4px solid #d93025;
        }


        .validation-feedback {
            font-size: 13px;
            margin-top: 5px;
            margin-left: 5px;
            min-height: 16px;
            transition: all 0.3s ease;
        }

        .validation-feedback.valid {
            color: #28a745;
        }

        .validation-feedback.invalid {
            color: #dc3545;
        }

        .password-requirements {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            transition: all 0.3s ease;
        }

        .requirements-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            font-size: 14px;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin: 6px 0;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .requirement i {
            width: 16px;
            margin-right: 10px;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .requirement.valid {
            color: #28a745;
        }

        .requirement.invalid {
            color: #dc3545;
        }

        .requirement.neutral {
            color: #6c757d;
        }

        .strength-indicator {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin: 10px 0;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .strength-weak { width: 25%; background: #dc3545; }
        .strength-fair { width: 50%; background: #ffc107; }
        .strength-good { width: 75%; background: #17a2b8; }
        .strength-strong { width: 100%; background: #28a745; }

        .strength-text {
            font-size: 12px;
            text-align: center;
            margin-top: 5px;
            font-weight: bold;
        }

        .checkbox-container {
            margin: 15px 0;
            color: #333;
            display: flex;
            align-items: center;
        }

        .checkbox-container input[type="checkbox"] {
            margin-right: 8px;
            transform: scale(1.1);
        }

        .checkbox-container label {
            cursor: pointer;
            user-select: none;
        }

        .match-indicator {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .match-indicator.show {
            opacity: 1;
        }

        .match-indicator.valid {
            color: #28a745;
        }

        .match-indicator.invalid {
            color: #dc3545;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                max-width: 100%;
                padding: 30px 20px;
                margin: 10px;
            }

            .container .cover {
                display: none;
            }

            .form-content .forgot-form {
                width: 100%;
            }

            .forms .form-content .title {
                font-size: 24px;
            }

            .cover .text .text-1 {
                font-size: 24px;
            }

            .cover .text .text-2 {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px 15px;
            }

            .forms .form-content .input-box {
                height: 50px;
                margin: 12px 0;
            }

            .form-content .input-box input {
                font-size: 14px;
                padding: 0 40px;
            }

            .forms .form-content .button input {
                padding: 12px 25px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cover">
            <div class="front">
                <img src="img/frontImg.jpg" alt="">
                <div class="text">
                    <span class="text-1">Create New Password</span>
                    <span class="text-2">Enter your new password to secure your account</span>
                </div>
            </div>
        </div>
        <div class="forms">
            <div class="form-content">
                <div class="forgot-form">
                    <div class="title">Reset Your Password</div>
                    <?php if (!empty($message)): ?>
                        <div class="alert <?= $messageClass === 'error' ? 'alert-danger' : 'alert-success' ?>" id="alertBox">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                    <form action="#" method="post" id="resetForm">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <div class="input-boxes">
                            <div class="input-box" id="passwordBox">
                                <i class="fa fa-lock" aria-hidden="true"></i>
                                <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
                            </div>
                            <div class="validation-feedback" id="passwordFeedback"></div>

                            <div class="password-requirements">
                                <div class="requirements-title">Password Requirements:</div>
                                <div class="requirement neutral" id="lengthReq">
                                    <i class="fa fa-circle"></i>
                                    <span>At least 8 characters long</span>
                                </div>
                                <div class="requirement neutral" id="upperReq">
                                    <i class="fa fa-circle"></i>
                                    <span>Contains uppercase letter (A-Z)</span>
                                </div>
                                <div class="requirement neutral" id="lowerReq">
                                    <i class="fa fa-circle"></i>
                                    <span>Contains lowercase letter (a-z)</span>
                                </div>
                                <div class="requirement neutral" id="numberReq">
                                    <i class="fa fa-circle"></i>
                                    <span>Contains number (0-9)</span>
                                </div>
                                <div class="requirement neutral" id="specialReq">
                                    <i class="fa fa-circle"></i>
                                    <span>Contains special character (!@#$%^&*)</span>
                                </div>

                                <div class="strength-indicator">
                                    <div class="strength-bar" id="strengthBar"></div>
                                </div>
                                <div class="strength-text" id="strengthText">Enter password to see strength</div>
                            </div>

                            <div class="input-box" id="confirmBox">
                                <i class="fa fa-lock" aria-hidden="true"></i>
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required>
                                <div class="match-indicator" id="matchIndicator">
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                            <div class="validation-feedback" id="confirmFeedback"></div>

                            <div class="checkbox-container">
                                <input type="checkbox" id="show_password">
                                <label for="show_password">Show Passwords</label>
                            </div>

                            <div class="button input-box">
                                <input type="submit" id="submitBtn" value="Create New Password">
                            </div>
                        </div>
                    </form>
                    <div class="text sign-up-text">
                        Remember your password? <a href="login_signup.php">Login now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Get DOM elements
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordBox = document.getElementById('passwordBox');
        const confirmBox = document.getElementById('confirmBox');
        const passwordFeedback = document.getElementById('passwordFeedback');
        const confirmFeedback = document.getElementById('confirmFeedback');
        const submitBtn = document.getElementById('submitBtn');
        const showPasswordCheckbox = document.getElementById('show_password');
        const matchIndicator = document.getElementById('matchIndicator');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        // Requirement elements
        const lengthReq = document.getElementById('lengthReq');
        const upperReq = document.getElementById('upperReq');
        const lowerReq = document.getElementById('lowerReq');
        const numberReq = document.getElementById('numberReq');
        const specialReq = document.getElementById('specialReq');

        let passwordValid = false;
        let confirmValid = false;

        function updateRequirement(element, isValid, isNeutral = false) {
            const icon = element.querySelector('i');
            
            if (isNeutral) {
                element.className = 'requirement neutral';
                icon.className = 'fa fa-circle';
            } else if (isValid) {
                element.className = 'requirement valid';
                icon.className = 'fa fa-check-circle';
            } else {
                element.className = 'requirement invalid';
                icon.className = 'fa fa-times-circle';
            }
        }

        function calculatePasswordStrength(password) {
            let score = 0;
            let feedback = [];

            if (password.length >= 8) score += 20;
            else feedback.push('Use at least 8 characters');

            if (/[A-Z]/.test(password)) score += 20;
            else feedback.push('Add uppercase letters');

            if (/[a-z]/.test(password)) score += 20;
            else feedback.push('Add lowercase letters');

            if (/\d/.test(password)) score += 20;
            else feedback.push('Add numbers');

            if (/[\W_]/.test(password)) score += 20;
            else feedback.push('Add special characters');

            return { score, feedback };
        }

        function updatePasswordStrength(password) {
            const { score } = calculatePasswordStrength(password);
            
            strengthBar.className = 'strength-bar';
            
            if (password === '') {
                strengthText.textContent = 'Enter password to see strength';
                strengthText.style.color = '#6c757d';
            } else if (score < 40) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Weak Password';
                strengthText.style.color = '#dc3545';
            } else if (score < 60) {
                strengthBar.classList.add('strength-fair');
                strengthText.textContent = 'Fair Password';
                strengthText.style.color = '#ffc107';
            } else if (score < 80) {
                strengthBar.classList.add('strength-good');
                strengthText.textContent = 'Good Password';
                strengthText.style.color = '#17a2b8';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Strong Password';
                strengthText.style.color = '#28a745';
            }
        }

        function validatePassword() {
            const password = newPassword.value;
            
            if (password === '') {
                // Reset all requirements to neutral
                updateRequirement(lengthReq, false, true);
                updateRequirement(upperReq, false, true);
                updateRequirement(lowerReq, false, true);
                updateRequirement(numberReq, false, true);
                updateRequirement(specialReq, false, true);
                
                passwordBox.className = 'input-box';
                passwordFeedback.textContent = '';
                passwordFeedback.className = 'validation-feedback';
                passwordValid = false;
                updatePasswordStrength('');
                updateSubmitButton();
                return;
            }

            // Check individual requirements
            const hasLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[\W_]/.test(password);

            updateRequirement(lengthReq, hasLength);
            updateRequirement(upperReq, hasUpper);
            updateRequirement(lowerReq, hasLower);
            updateRequirement(numberReq, hasNumber);
            updateRequirement(specialReq, hasSpecial);

            passwordValid = hasLength && hasUpper && hasLower && hasNumber && hasSpecial;
            updatePasswordStrength(password);

            if (passwordValid) {
                passwordBox.className = 'input-box valid';
                passwordFeedback.textContent = '✓ Password meets all requirements';
                passwordFeedback.className = 'validation-feedback valid';
            } else {
                passwordBox.className = 'input-box invalid';
                const { feedback } = calculatePasswordStrength(password);
                passwordFeedback.textContent = '✗ ' + feedback.join(', ');
                passwordFeedback.className = 'validation-feedback invalid';
            }

            // Re-validate confirm password if it has content
            if (confirmPassword.value !== '') {
                validateConfirmPassword();
            }

            updateSubmitButton();
        }

        function validateConfirmPassword() {
            const password = newPassword.value;
            const confirm = confirmPassword.value;

            if (confirm === '') {
                confirmBox.className = 'input-box';
                confirmFeedback.textContent = '';
                confirmFeedback.className = 'validation-feedback';
                matchIndicator.className = 'match-indicator';
                confirmValid = false;
                updateSubmitButton();
                return;
            }

            const passwordsMatch = password === confirm;
            confirmValid = passwordsMatch && passwordValid;

            if (passwordsMatch) {
                confirmBox.className = 'input-box valid';
                confirmFeedback.textContent = '✓ Passwords match';
                confirmFeedback.className = 'validation-feedback valid';
                matchIndicator.className = 'match-indicator show valid';
                matchIndicator.innerHTML = '<i class="fa fa-check"></i>';
            } else {
                confirmBox.className = 'input-box invalid';
                confirmFeedback.textContent = '✗ Passwords do not match';
                confirmFeedback.className = 'validation-feedback invalid';
                matchIndicator.className = 'match-indicator show invalid';
                matchIndicator.innerHTML = '<i class="fa fa-times"></i>';
            }

            updateSubmitButton();
        }

        function updateSubmitButton() {
            if (passwordValid && confirmValid) {
                submitBtn.className = 'enabled';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create New Password';
            } else {
                submitBtn.className = '';
                submitBtn.disabled = true;
                submitBtn.textContent = 'Please complete all requirements';
            }
        }

        function togglePassword() {
            const type = newPassword.type === 'password' ? 'text' : 'password';
            newPassword.type = type;
            confirmPassword.type = type;
        }

        // Event listeners
        newPassword.addEventListener('input', validatePassword);
        newPassword.addEventListener('focus', validatePassword);
        confirmPassword.addEventListener('input', validateConfirmPassword);
        confirmPassword.addEventListener('focus', validateConfirmPassword);
        showPasswordCheckbox.addEventListener('change', togglePassword);

        // Form submission validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            if (!passwordValid || !confirmValid) {
                e.preventDefault();
                alert('Please ensure all password requirements are met and passwords match.');
                return false;
            }
        });

        // Alert box auto-hide
        const alertBox = document.getElementById('alertBox');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 1000);
            }, 10000);

            if (alertBox.classList.contains('success')) {
                setTimeout(() => {
                    window.location.href = 'login_signup.php';
                }, 7000);
            }
        }

        // Initialize
        updateSubmitButton();
    </script>
</body>
</html>