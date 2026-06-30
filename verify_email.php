<?php
session_start();
include('config.php');

$message = "";
$messageType = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if token exists and is valid
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE verification_token = ? AND email_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        // Token is valid, verify the email
        $stmt->bind_result($user_id);
        $stmt->fetch();

        // Update email_verified to 1 and clear the token
        $updateStmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE user_id = ?");
        $updateStmt->bind_param("i", $user_id);

        if ($updateStmt->execute()) {
            $message = "Your email has been successfully verified! You can now log in.";
            $messageType = "success";
        } else {
            $message = "Error verifying email. Please try again.";
            $messageType = "error";
        }

        $updateStmt->close();
    } else {
        $message = "Invalid or expired verification token.";
        $messageType = "error";
    }

    $stmt->close();
} else {
    $message = "No verification token provided.";
    $messageType = "error";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

        :root {
            /* Match forgot_password.php / index.php palette */
            --primary: #7C3AED;
            --primary-glow: rgba(124, 58, 237, 0.45);
            --accent: #8B5CF6;
            --dark: #09090B;
            --white: #FFFFFF;
            --gray-50: #FAFAFA;
            --gray-100: #F4F4F5;
            --gray-200: #E4E4E7;
            --gray-400: #A1A1AA;
            --gray-600: #52525B;
            --gray-700: #404040;
            --gray-800: #18181B;
            --grid-main: rgba(124, 58, 237, 0.15);
            --grid-sub: rgba(124, 58, 237, 0.05);
        }

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
            background: var(--white);
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
        }

        .bg-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
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
            top: -10%;
            left: 50%;
            transform: translateX(-50%);
            width: 120vw;
            height: 100vh;
            background: radial-gradient(circle at 50% 30%, var(--primary-glow) 0%, transparent 60%);
            z-index: -1;
            filter: blur(60px);
            opacity: 0.7;
        }

        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 80px 80px; }
        }

        .container {
            position: relative;
            max-width: 850px;
            width: 100%;
            background: rgba(250, 250, 250, 0.85);
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--gray-200);
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

        .container {
            margin-top: 30px;
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

        .form-content .verify-form {
            width: 100%;
        }

        .forms .title {
            position: relative;
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            text-align: center;
        }

        .forms .title:before {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -5px;
            height: 4px;
            width: 50px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            transform: translateX(-50%);
            border-radius: 2px;
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
            background-color: rgba(124, 58, 237, 0.08);
            color: #2E1065;
            border-left: 4px solid var(--primary);
        }

        .alert-danger {
            background-color: rgba(109, 40, 217, 0.09);
            color: #4C1D95;
            border-left: 4px solid var(--accent-dark, #6D28D9);
        }

        .verification-body {
            margin-top: 22px;
            text-align: center;
        }

        .verification-icon {
            font-size: 64px;
            margin-bottom: 10px;
            display: inline-block;
        }

        .verification-icon.success { color: #28a745; }
        .verification-icon.error { color: #dc3545; }

        .verification-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: #2c3e50;
            font-weight: 700;
        }

        .verification-message {
            font-size: 15px;
            line-height: 1.6;
            margin: 0 auto 25px auto;
            color: #555;
            max-width: 520px;
        }

        .button-wrap {
            text-align: center;
        }

        .primary-action {
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark, #6D28D9) 100%);
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
            text-decoration: none;
            display: inline-block;
            max-width: 320px;
        }

        .primary-action:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.35);
            background: linear-gradient(135deg, var(--accent-dark, #6D28D9) 0%, var(--primary) 100%);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            body { padding: 10px; }

            .container {
                max-width: 100%;
                padding: 30px 20px;
                margin: 10px;
            }

            .container .cover {
                display: none;
            }

            .forms .title { font-size: 24px; }

            .cover .text .text-1 { font-size: 24px; }
            .cover .text .text-2 { font-size: 14px; }
        }

        @media (max-width: 480px) {
            .container { padding: 20px 15px; }
            .verification-message { font-size: 14px; }
            .primary-action { padding: 12px 25px; font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="bg-canvas"></div>
    <div class="bg-glow"></div>

    <div class="container">

        <div class="forms">
            <div class="form-content">
                <div class="verify-form">
                    <div class="title">Account Status</div>

                    <?php if (!empty($message)): ?>
                        <div class="alert <?= $messageType === 'error' ? 'alert-danger' : 'alert-success' ?>" id="alertBox">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="verification-body">
                        <?php if ($messageType === 'success'): ?>
                            <div class="verification-icon success"><i class="fas fa-check-circle"></i></div>
                            <h1 class="verification-title">Email Verified!</h1>
                            <p class="verification-message">
                                Your email address has been successfully verified. You can now log in to your account and start using our platform.
                            </p>
                            <div class="button-wrap">
                                <a href="login_signup.php" class="primary-action">Go to Login</a>
                            </div>
                        <?php else: ?>
                            <div class="verification-icon error"><i class="fas fa-times-circle"></i></div>
                            <h1 class="verification-title">Verification Failed</h1>
                            <p class="verification-message">
                                The verification link is invalid or has expired. Please try registering again or contact support if the problem persists.
                            </p>
                            <div class="button-wrap">
                                <a href="login_signup.php" class="primary-action">Go to Login</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fade out alert (consistent UX with forgot_password.php)
        setTimeout(function () {
            const alertBox = document.getElementById('alertBox');
            if (alertBox) {
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }
        }, 10000);

        // Redirect after success (optional, matches forgot_password.php style)
        const alertBox = document.getElementById('alertBox');
        if (alertBox && alertBox.classList.contains('alert-success')) {
            setTimeout(() => {
                window.location.href = 'login_signup.php';
            }, 5000);
        }
    </script>
</body>
</html>
