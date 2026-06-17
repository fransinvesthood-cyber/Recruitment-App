<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include('config.php');

$message = '';
$messageClass = '';

if (isset($_POST['email'])) {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expires);
        $stmt->execute();

        $resetLink = "https://investhoodit.com/recruitment-project-phps/reset_password.php?token=$token";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'delanideco69@gmail.com';
            $mail->Password = 'kyuqrccxdsqkkosb';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('delanideco69@gmail.com', 'Recruitment Team');
            $mail->addAddress($email);
            $mail->Subject = 'Reset Your Password';
            $mail->Body    = "Click the following link to reset your password:\n$resetLink";

            $mail->send();
            $message = "Reset link has been sent to your email.";
            $messageClass = "success";
        } catch (Exception $e) {
            $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            $messageClass = "error";
        }
    } else {
        $message = "Email not found.";
        $messageClass = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

        :root {
            /* Match index.php palette */
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
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            transform: translateX(-50%);
            border-radius: 2px;
        }


        .forms .form-content .input-boxes {
            margin-top: 30px;
        }

        .forms .form-content .input-box {
            display: flex;
            align-items: center;
            height: 55px;
            width: 100%;
            margin: 15px 0;
            position: relative;
            background-color: #f7fafc;
            border: 1.5px solid #e2e8f0;
            padding: 12px 16px;
            border-radius: 10px;
            transition: border-color 0.3s;
            box-shadow: 0 1px 2px rgba(60,72,88,0.03);
        }

        .forms .form-content .input-box:focus-within {
            border-color: var(--primary);
            background: rgba(124, 58, 237, 0.08);
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.12);
        }


        .form-content .input-box input {
            height: 100%;
            width: 100%;
            outline: none;
            border: none;
            border-radius: 12px;
            padding: 0 45px 0 45px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            color: #333;
        }

        .form-content .input-box input:focus {
            border-color: #e74c3c;
            transform: translateY(-2px);
        }

        .form-content .input-box input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .form-content .input-box i {
            position: absolute;
            left: 15px;
            color: var(--primary);
            font-size: 18px;
            z-index: 1;
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
        }

        .forms .form-content .button input:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.35);
            background: linear-gradient(135deg, var(--accent-dark, #6D28D9) 0%, var(--primary) 100%);
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
    <div class="bg-canvas"></div>
    <div class="bg-glow"></div>

    <div class="container">

        <div class="cover">
            <div class="front">
                <img src="img/lock.jpeg" alt="">
                <div class="text">
                    <span class="text-1">Reset Your Password</span>
                    <span class="text-2">Enter your email to receive a reset link</span>
                </div>
            </div>
        </div>
        <div class="forms">
            <div class="form-content">
                <div class="forgot-form">
                    <div class="title">Forgot Your Password?</div>
                    <form action="#" method="post">
                        <?php if (!empty($message)): ?>
                            <div class="alert <?= $messageClass === 'error' ? 'alert-danger' : 'alert-success' ?>" id="alertBox">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>
                        <div class="input-boxes">
                            <div class="input-box">
                                <i class="fa fa-envelope" aria-hidden="true"></i>
                                <input type="email" name="email" id="email" placeholder="Enter your email address" required value="<?= htmlspecialchars($email ?? '') ?>">
                            </div>
                            <div class="button input-box">
                                <input type="submit" value="Send Reset Link">
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
        setTimeout(function () {
            const alertBox = document.getElementById('alertBox');
            if (alertBox) {
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500); // Remove from DOM after fade
            }
        }, 10000); // 10000ms = 10 seconds
    </script>

    <script>
        const alertBox = document.getElementById('alertBox');

        if (alertBox) {
            // Fade out alert after 10s
            setTimeout(() => {
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 1000);
            }, 10000);

            // Redirect to login after 10s if success
            if (alertBox.classList.contains('success')) {
                setTimeout(() => {
                    window.location.href = 'login_signup.php';
                }, 5000);
            }
        }
    </script>

</body>
</html>