<?php
session_start(); // Start the session
include ('config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Google OAuth setup
require_once 'vendor/autoload.php';
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

// Generate Google login URL
$google_login_url = $client->createAuthUrl();

// Lockout configuration
$max_attempts = 3;
$lockout_seconds = 1 * 60;

// Initialize per-user session arrays if not already set
if (!isset($_SESSION['login_attempts']) || !is_array($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}
if (!isset($_SESSION['last_attempt_time']) || !is_array($_SESSION['last_attempt_time'])) {
    $_SESSION['last_attempt_time'] = [];
}

// Calculate lockout status based on posted username if available
$username_for_check = $_POST['username'] ?? '';
$remaining_attempts = 3;
$locked_out = false;
$time_remaining = 0;

if (!empty($username_for_check)) {
    $user_attempts = $_SESSION['login_attempts'][$username_for_check] ?? 0;
    $user_last_time = $_SESSION['last_attempt_time'][$username_for_check] ?? 0;

    $remaining_attempts = $max_attempts - $user_attempts;
    $locked_out = $user_attempts >= $max_attempts;
    $time_remaining = $lockout_seconds - (time() - $user_last_time);
    if ($time_remaining < 0) $time_remaining = 0;
}

//Registration
$message = "";
$messageType = "";

// Initialize variables to prevent defaulting to unexpected values like "root"
$role = $username = $fullname = $gender = $dob = $email = $phone = $address = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-signup'])) {
    // Public registration is Applicant-only. Ignore any submitted role value.
    $role = 'Applicant';
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check for duplicate username or email
    $duplicate = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' OR email = '$email'");
    if (mysqli_num_rows($duplicate) > 0) {
        $message = "Username or Email is already taken.";
        $messageType = "error";
    } else {
        // Check if passwords match
        if ($password !== $confirm_password) {
            $message = "Passwords do not match.";
            $messageType = "error";
        }
        // Check password strength
        elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
            $message = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
            $messageType = "error";
        } else {
            // Hash and store the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));

            $stmt = $conn->prepare("INSERT INTO users (role, username, fullname, gender, dob, email, phone, address, password, verification_token, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("ssssssssss", $role, $username, $fullname, $gender, $dob, $email, $phone, $address, $hashedPassword, $verificationToken);

            if ($stmt->execute()) {
                // Send verification email
                $verificationLink = "http://localhost/recruitment-project-phps/verify_email.php?token=$verificationToken";

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
                    $mail->Subject = 'Verify Your Email Address';
                    $mail->Body = "Welcome to our platform!\n\nPlease click the following link to verify your email address:\n$verificationLink\n\nIf you did not create this account, please ignore this email.";

                    $mail->send();
                    $message = "Account created successfully! Please check your email to verify your account before logging in.";
                    $messageType = "success";
                } catch (Exception $e) {
                    $message = "Account created but verification email could not be sent. Please contact support.";
                    $messageType = "error";
                }
            } else {
                $message = "Error: " . $stmt->error;
                $messageType = "error";
            }

            $stmt->close();
        }
    }
}

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-login'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Check if the user is currently locked out
  $lockoutTime = 1 * 60; // 1 minute in seconds
  $currentTime = time();

  // Initialize per-user arrays if not set
  if (!isset($_SESSION['login_attempts'][$username])) $_SESSION['login_attempts'][$username] = 0;
  if (!isset($_SESSION['last_attempt_time'][$username])) $_SESSION['last_attempt_time'][$username] = 0;

  if ($_SESSION['login_attempts'][$username] >= 3 && ($currentTime - $_SESSION['last_attempt_time'][$username]) < $lockoutTime) {
      $remaining = $lockoutTime - ($currentTime - $_SESSION['last_attempt_time'][$username]);
      $message = "Too many failed login attempts. Please try again in " . ceil($remaining / 60) . " minute.";
      $messageType = "error";
  } else {
      // Proceed with login
      $stmt = $conn->prepare("SELECT user_id, password, role, email_verified, account_status FROM users WHERE username = ?");
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $stmt->store_result();

      if ($stmt->num_rows === 0) {
          $message = "Username does not exist.";
          $messageType = "error";
          $_SESSION['login_attempts'][$username] = ($_SESSION['login_attempts'][$username] ?? 0) + 1;
          $_SESSION['last_attempt_time'][$username] = $currentTime;
      } else {
          $stmt->bind_result($user_id, $hashedPassword, $role, $email_verified, $account_status);
          $stmt->fetch();

          if ($account_status === 'Inactive') {
              $message = "Your account is inactive. Please contact the support team.";
              $messageType = "error";
              $_SESSION['login_attempts'][$username] = ($_SESSION['login_attempts'][$username] ?? 0) + 1;
              $_SESSION['last_attempt_time'][$username] = $currentTime;
          } elseif (!password_verify($password, $hashedPassword)) {
              $message = "Incorrect password.";
              $messageType = "error";
              $_SESSION['login_attempts'][$username] = ($_SESSION['login_attempts'][$username] ?? 0) + 1;
              $_SESSION['last_attempt_time'][$username] = $currentTime;
          } elseif ($email_verified == 0) {
              $message = "Please verify your email address before logging in. Check your email for the verification link.";
              $messageType = "error";
          } else {
              // Successful login (account must be Active at this point)
              $_SESSION['username'] = $username;
              $_SESSION['role'] = $role;
              $_SESSION['user_id'] = $user_id;


              // Set success message to display on next page
              $_SESSION['message'] = "You have successfully logged in.";
                $_SESSION['messageClass'] = "success";

              // Reset login attempts for this user
              $_SESSION['login_attempts'][$username] = 0;

              // (Optional) remember last successful login timestamp if column exists
              try {
                  $upd = $conn->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                  if ($upd) {
                      $upd->bind_param('i', $user_id);
                      $upd->execute();
                      $upd->close();
                  }
              } catch (Throwable $e) {}


              // Redirect based on role
              if ($role === 'Admin') {
                  header("Location: admin_dashboard.php");
              }  else if ($role === 'Consultant') {
                  header("Location: consultant_dashboard.php");
              } else{
                  header("Location: applicant.php");
              }
              exit();
          }
      }

      $stmt->close();
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&amp;display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
:root {
            --primary: #7C3AED;
            --accent: #8B5CF6;
            --accent-dark: #6D28D9;
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

        /* Google Font Link */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
          font-family: "Poppins", sans-serif;
        }

body { 
            background: var(--white); 
            color: var(--dark); 
            line-height: 1.6; 
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }

        .background-video {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          object-fit: cover;
          z-index: -1;
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

        .container #flip:checked ~ .cover {
          transform: rotateY(-180deg);
        }

        .container #flip:checked ~ .forms .login-form {
          pointer-events: none;
        }

        .container .cover .front,
        .container .cover .back {
          position: absolute;
          top: 0;
          left: 0;
          height: 100%;
          width: 100%;
          border-radius: 20px;
        }

        .cover .back {
          transform: rotateY(180deg);
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

        .form-content .login-form,
        .form-content .signup-form,
        .form-content .forgot-form {
          width: calc(50% - 25px);
        }

        .form-content .signup-form {
          max-height: 600px;
          overflow-y: auto;
          scrollbar-width: thin;
          scrollbar-color: var(--primary) #f1f1f1;
        }

        .form-content .signup-form::-webkit-scrollbar {
          width: 6px;
        }

        .form-content .signup-form::-webkit-scrollbar-track {
          background: #f1f1f1;
          border-radius: 10px;
        }

        .form-content .signup-form::-webkit-scrollbar-thumb {
          background: var(--primary);
          border-radius: 10px;
        }

        .form-content .signup-form::-webkit-scrollbar-thumb:hover {
          background: var(--accent-dark);
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
          border-radius: 12px;
          transition: border-color 0.3s, box-shadow 0.3s;
          box-shadow: 0 1px 2px rgba(60,72,88,0.03);
        }

        .forms .form-content .input-box:focus-within {
          border-color: var(--primary);
          background: #f5f3ff;
          box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
        }

        .form-content .input-box input,
        .form-content .input-box select {
          height: 100%;
          width: 100%;
          outline: none;
          border: none;
          border-radius: 12px;
          padding: 0 45px 0 50px;
          font-size: 16px;
          font-weight: 500;
          transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
          background: transparent;
          color: #333;
        }

        .form-content .input-box input:focus,
        .form-content .input-box input:valid,
        .form-content .input-box select:focus {
          background: transparent;
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
          display: flex;
          align-items: center;
          justify-content: center;
          width: 20px;
          text-align: center;
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
          color: var(--primary);
          font-weight: 600;
          transition: color 0.3s ease;
        }

        .forms .form-content .text a:hover {
          color: var(--accent-dark);
          text-decoration: underline;
        }

        .forms .form-content .button {
          margin-top: 40px;
          text-align: center;
        }

        .forms .form-content .button input {
          color: #fff;
          background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
          border: none;
          border-radius: 25px;
          padding: 15px 30px;
          font-size: 16px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
          box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
          width: 100%;
          text-align: center;
          min-height: 55px;
        }

        .forms .form-content .button input:hover {
          transform: translateY(-3px) scale(1.05);
          box-shadow: 0 8px 25px rgba(124, 58, 237, 0.4);
          background: linear-gradient(135deg, var(--accent-dark) 0%, var(--primary) 100%);
        }

        .forms .form-content .button input:disabled {
          opacity: 0.6;
          cursor: not-allowed;
          transform: none;
        }

        .forms .form-content label {
          color: #e74c3c;
          cursor: pointer;
          font-weight: 600;
          transition: color 0.3s ease;
        }

        .forms .form-content .input-boxes label {
          display: block;
          margin-bottom: 5px;
          font-weight: 500;
          color: #333;
          font-size: 14px;
        }

        .forms .form-content label:hover {
          color: #c0392b;
          text-decoration: underline;
        }

        .forms .form-content .login-text,
        .forms .form-content .sign-up-text {
          text-align: center;
          margin-top: 25px;
        }

        .container #flip {
          display: none;
        }

        /* Animations */
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

        /* Responsive Design */
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

          .form-content .login-form,
          .form-content .signup-form,
          .form-content .forgot-form {
            width: 100%;
          }

          .form-content .signup-form {
            display: none;
            max-height: 500px;
          }

          .container #flip:checked ~ .forms .signup-form {
            display: block;
          }

          .container #flip:checked ~ .forms .login-form {
            display: none;
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

          .form-content .input-box input,
          .form-content .input-box select {
            font-size: 14px;
            padding: 0 40px;
          }

          .forms .form-content .button input {
            padding: 12px 25px;
            font-size: 14px;
          }
        }

        /* Password Reset Specific Styles - removed duplicate to avoid conflicts */

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

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
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

        /* Custom styles for this file */
        .form-content .signup-form{
            height: 500px;  /* Set the height of the container */
            overflow: auto; /* Enable scrolling if the content overflows */
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: opacity 0.5s ease-in-out;
        }

        .input-box i#toggle-password,
        .input-box i#toggle-signup-password,
        .input-box i#toggle-confirm-password {
          position: absolute;
          right: 12px;
          left: auto;
          top: 50%;
          transform: translateY(-50%);
          cursor: pointer;
          color: #888;
          font-size: 18px;
          transition: color 0.3s ease;
        }

        .input-box i#toggle-password:hover,
        .input-box i#toggle-signup-password:hover,
        .input-box i#toggle-confirm-password:hover {
          color: #007bff;
        }


        /* Enhanced Custom Dropdown Styles */
        .custom-select {
            position: relative;
            margin-bottom: 20px;
        }

        .custom-select select {
            width: 100%;
            padding: 12px 45px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            font-size: 16px;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .custom-select select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 4px 20px rgba(0,123,255,0.2);
            transform: translateY(-2px);
        }

        .custom-select select:hover {
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .custom-select i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
            font-size: 18px;
            z-index: 1;
        }

        .custom-select::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
            font-size: 18px;
            pointer-events: none;
            transition: transform 0.3s ease;
        }

        .custom-select:hover::after {
            transform: translateY(-50%) rotate(180deg);
        }

        .custom-select select option {
            padding: 12px;
            background: #fff;
            color: #333;
            border: none;
        }

        .custom-select select option:hover {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        /* Role Select Specific Styling */

        .role-select {
            margin-bottom: 25px;
        }

        .role-select label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .role-select .custom-select select {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 500;
        }

        .role-select .custom-select select option {
            background: #fff;
            color: #333;
        }

        .role-select .custom-select i {
            color: white;
        }

        .role-select .custom-select::after {
            color: white;
        }

        /* Password Confirmation Validation Styles */
        .password-match-indicator {
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .password-match-indicator.match {
            color: #28a745;
        }

        .password-match-indicator.no-match {
            color: #dc3545;
        }

        .password-feedback {
            margin-top: 5px;
            font-size: 12px;
            transition: all 0.3s ease;
            animation: slideIn 0.3s ease;
        }

        .password-feedback.match {
            color: #28a745;
        }

        .password-feedback.no-match {
            color: #dc3545;
        }

        /* Enhanced Input Box for Password Confirmation */
        .input-box.password-confirm {
            position: relative;
        }

        .input-box.password-confirm input {
            padding-right: 80px;
        }

        /* Username and Email Validation Styles */
        .validation-indicator {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .validation-indicator.valid {
            color: #28a745;
        }

        .validation-indicator.invalid {
            color: #dc3545;
        }

        .validation-indicator.checking {
            color: #007bff;
        }

        .validation-feedback {
            margin-top: 5px;
            font-size: 12px;
            transition: all 0.3s ease;
            animation: slideIn 0.3s ease;
        }

        .validation-feedback.valid {
            color: #28a745;
        }

        .validation-feedback.invalid {
            color: #dc3545;
        }

        .validation-feedback.checking {
            color: #007bff;
        }

        /* Fullscreen video */
        #bg-video {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        /* Loading spinner animation */
        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Smooth animations */
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

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
   </head>
<body>

    <div class="bg-canvas"></div>
    <div class="bg-glow"></div>
  <!-- Background Video
    <video autoplay muted loop id="bg-video">
        <source src="img/bg-video1.mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>-->
  <div class="container">
    <input type="checkbox" id="flip">
    <div class="cover">
      <div class="front">
        <img src="img/frontImg02.jfif" alt="">
        <div class="text">
          <span class="text-1">Welcome!!</span>
        </div>
      </div>
      <div class="back">
        <img class="backImg" src="img/back1.jpg" alt="Recruitment background">
        <div class="text">
          <span class="text-1"></span>
          <span class="text-2"></span>
        </div>
      </div>
    </div>
    <div class="forms">
        <div class="form-content">
          <div class="login-form">

            <?php if (!empty($message)): ?>
                <div class="alert <?= $messageType === 'error' ? 'alert-danger' : 'alert-success' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="title">Login</div>
          <form action="#" method="post">
            <div class="input-boxes">
                <div class="input-box">
                    <i class="fa fa-user" aria-hidden="true"></i>
                    <input type="text" name="username" id="username" placeholder="Enter your username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="input-box">
                  <i class="fas fa-lock"></i>
                  <input type="password" name="password" id="password" placeholder="Enter your password" required>
                  <i class="fas" id="toggle-password"></i>
                </div>
              <div id="login-status" style="color: red; font-size: 14px;"></div>
              <div class="text"><a href="forgot_password.php">Forgot password?</a></div>
              <div class="button input-box">
                <input type="submit" name="submit-login" value="Login">
              </div>
              <div class="text sign-up-text">Don't have an account? <label for="flip">Register now</label></div>
              <div class="home-link" style="text-align: center; margin-top: 15px;">
                <a href="index.php" style="color: var(--primary); text-decoration: none; font-size: 18px;"><i class="fa fa-home" aria-hidden="true"></i></a>
              </div>
            </div>

            <script>
                const form = document.querySelector("form");

                form.addEventListener("submit", function(e) {
                  if (lockedOut && timeRemaining > 0) {
                    e.preventDefault(); // Stop the form from submitting
                    alert("You are temporarily locked out. Please wait until the countdown ends.");
                  }
                });
            </script>
        </form>
      </div>
      <div class="signup-form">

        <?php if (!empty($message)): ?>
            <div class="alert <?= $messageType === 'error' ? 'alert-danger' : 'alert-success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="title">Fill out the registration form to create an account</div>
        <form action="#" method="post" id="signup-form">
            
            <!-- Role is NOT user-selectable: public signup always creates Applicant accounts -->
            <div class="role-select">
                <label for="role">Choose your role:</label>
                <div class="custom-select">
                    <i class="fas fa-user-tie"></i>
                    <select id="role" name="role" required disabled>
                        <option value="Applicant" selected>Applicant (Job Seeker)</option>
                    </select>
                </div>
                <input type="hidden" name="role" value="Applicant">
            </div>
            
            
            <div class="input-boxes">
                <!-- Username Field with Live Validation -->
                <div class="input-box">
                    <i class="fa fa-user" aria-hidden="true"></i>
                    <input type="text" name="username" id="signup-username" placeholder="Enter your username" required value="<?= htmlspecialchars($username ?? '') ?>">
                    <span class="validation-indicator" id="username-indicator"></span>
                </div>
                <div class="validation-feedback" id="username-feedback"></div>
                
                <div class="input-box">
                    <i class="fa fa-user" aria-hidden="true"></i>
                    <input type="text" name="fullname" id="fullname" placeholder="Enter your full name" required value="<?= htmlspecialchars($fullname ?? '') ?>">
                </div>
                
                <!-- Enhanced Gender Selection -->
                <div class="input-box">
                    <div class="custom-select">
                        <i class="fas fa-venus-mars"></i>
                        <select name="gender" id="gender" required>
                            <option value="" disabled <?= empty($gender) ? 'selected' : '' ?>>Select your gender</option>
                            <option value="Male" <?= ($gender === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($gender === 'Female') ? 'selected' : '' ?>> Female</option>
                            
                        </select>
                    </div>
                </div>
                
                <div class="input-box">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                    <input type="date" name="dob" id="dob" placeholder="Enter your date of birth" required value="<?= htmlspecialchars($dob ?? '') ?>">
                </div>
                
                <!-- Email Field with Live Validation -->
                <div class="input-box">
                    <i class="fa fa-envelope" aria-hidden="true"></i>
                    <input type="email" name="email" id="email" placeholder="Enter your email" required value="<?= htmlspecialchars($email ?? '') ?>">
                    <span class="validation-indicator" id="email-indicator"></span>
                </div>
                <div class="validation-feedback" id="email-feedback"></div>
                
                <div class="input-box">
                    <i class="fa fa-phone" aria-hidden="true"></i>
                    <input type="text" name="phone" id="phone" placeholder="Enter your phone number" required value="<?= htmlspecialchars($phone ?? '') ?>">
                </div>
                <div class="input-box">
                    <i class="fa fa-home" aria-hidden="true"></i>
                    <input type="text" name="address" id="address" placeholder="Enter your street address" required value="<?= htmlspecialchars($address ?? '') ?>">
                </div>
                <div class="input-box">
                  <i class="fa fa-lock" aria-hidden="true"></i>
                  <input type="password" name="password" id="signup-password" placeholder="Enter your password" required>
                  <i class="fas" id="toggle-signup-password"></i>
                </div>

                <div class="password-requirements" style="margin-top: 5px; font-size: 12px; color: #666;">Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.</div>

                <!-- Enhanced Password Confirmation with Live Validation -->
                <div class="input-box password-confirm">
                  <i class="fa fa-lock" aria-hidden="true"></i>
                  <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
                  <i class="fas" id="toggle-confirm-password" style="pointer-events: auto;"></i>
                  <span class="password-match-indicator" id="password-match-indicator"></span>
                </div>


                <div class="password-feedback" id="password-feedback"></div>
                
                <div class="button input-box">
                    <input type="submit" name="submit-signup" value="Submit" id="submit-btn">
                </div>
                <div class="text sign-up-text" style="margin-left: -45px;">Already have an account? <label for="flip">Login now</label></div>
                <br>
                <!-- Google Sign Up Button for Signup Form -->
                <!--<div class="google-signup" style="text-align: center; margin: 20px 0;">
                    <a href="<?= htmlspecialchars($google_login_url) ?>" style="display: inline-block; padding: 12px 24px; background: #4285f4; color: white; text-decoration: none; border-radius: 8px; font-size: 16px; transition: background 0.3s ease;">
                        <i class="fab fa-google" style="margin-right: 10px;"></i>Sign up with Google
                    </a>
                </div>-->
            </div>
        </form>
    </div>

    </div>
    </div>
  </div>

  <script>
      // Login password toggle
      const passwordInput = document.getElementById("password");
      const togglePassword = document.getElementById("toggle-password");

      if (passwordInput && togglePassword) {
        // Start with hidden password and eye-slash icon
        togglePassword.classList.add("fa-eye-slash");
        togglePassword.addEventListener("click", function () {
          const isHidden = passwordInput.type === "password";

          // Toggle input type
          passwordInput.type = isHidden ? "text" : "password";

          // Set correct icon
          this.classList.toggle("fa-eye-slash", !isHidden);
          this.classList.toggle("fa-eye", isHidden);
        });
      }

      // Signup password toggle (registration)
      const signupPasswordInput = document.getElementById("signup-password");
      const toggleSignupPassword = document.getElementById("toggle-signup-password");

      if (signupPasswordInput && toggleSignupPassword) {
        toggleSignupPassword.classList.add("fa-eye-slash");
        toggleSignupPassword.addEventListener("click", function () {
          const isHidden = signupPasswordInput.type === "password";
          signupPasswordInput.type = isHidden ? "text" : "password";

          this.classList.toggle("fa-eye-slash", !isHidden);
          this.classList.toggle("fa-eye", isHidden);
        });
      }

      // Confirm password toggle (registration)
      const confirmPasswordInput = document.getElementById("confirm_password");
      const toggleConfirmPassword = document.getElementById("toggle-confirm-password");

      if (confirmPasswordInput && toggleConfirmPassword) {
        toggleConfirmPassword.classList.add("fa-eye-slash");
        toggleConfirmPassword.addEventListener("click", function () {
          const isHidden = confirmPasswordInput.type === "password";
          confirmPasswordInput.type = isHidden ? "text" : "password";

          this.classList.toggle("fa-eye-slash", !isHidden);
          this.classList.toggle("fa-eye", isHidden);
        });
      }
  </script>


  <!-- Live Username and Email Validation Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const usernameInput = document.getElementById('signup-username');
        const emailInput = document.getElementById('email');
        const usernameIndicator = document.getElementById('username-indicator');
        const emailIndicator = document.getElementById('email-indicator');
        const usernameFeedback = document.getElementById('username-feedback');
        const emailFeedback = document.getElementById('email-feedback');
        
        let usernameTimeout;
        let emailTimeout;
        let usernameValid = false;
        let emailValid = false;

        // Debounced validation function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Check availability function
        async function checkAvailability(type, value, indicator, feedback) {
            if (!value.trim()) {
                indicator.innerHTML = '';
                feedback.innerHTML = '';
                indicator.className = 'validation-indicator';
                feedback.className = 'validation-feedback';
                return false;
            }

            // Show loading indicator
            indicator.innerHTML = '<i class="fas fa-spinner spinner"></i>';
            indicator.className = 'validation-indicator checking';
            feedback.innerHTML = 'Checking availability...';
            feedback.className = 'validation-feedback checking';

            try {
                const response = await fetch('check_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: type,
                        value: value
                    })
                });

                const data = await response.json();

                if (data.available) {
                    indicator.innerHTML = '<i class="fas fa-check-circle"></i>';
                    indicator.className = 'validation-indicator valid';
                    feedback.innerHTML = data.message;
                    feedback.className = 'validation-feedback valid';
                    
                    // Update input border
                    if (type === 'username') {
                        usernameInput.style.borderColor = '#28a745';
                        usernameValid = true;
                    } else {
                        emailInput.style.borderColor = '#28a745';
                        emailValid = true;
                    }
                    
                    return true;
                } else {
                    indicator.innerHTML = '<i class="fas fa-times-circle"></i>';
                    indicator.className = 'validation-indicator invalid';
                    feedback.innerHTML = data.message;
                    feedback.className = 'validation-feedback invalid';
                    
                    // Update input border
                    if (type === 'username') {
                        usernameInput.style.borderColor = '#dc3545';
                        usernameValid = false;
                    } else {
                        emailInput.style.borderColor = '#dc3545';
                        emailValid = false;
                    }
                    
                    return false;
                }
            } catch (error) {
                console.error('Error checking availability:', error);
                indicator.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                indicator.className = 'validation-indicator invalid';
                feedback.innerHTML = 'Error checking availability. Please try again.';
                feedback.className = 'validation-feedback invalid';
                return false;
            }
        }

        // Debounced validation functions
        const debouncedUsernameCheck = debounce((value) => {
            checkAvailability('username', value, usernameIndicator, usernameFeedback);
        }, 800);

        const debouncedEmailCheck = debounce((value) => {
            checkAvailability('email', value, emailIndicator, emailFeedback);
        }, 800);

        // Username validation
        usernameInput.addEventListener('input', function() {
            const value = this.value.trim();
            
            // Reset validation state while typing
            usernameValid = false;
            this.style.borderColor = '#e0e0e0';
            
            if (value.length < 3) {
                usernameIndicator.innerHTML = '<i class="fas fa-info-circle"></i>';
                usernameIndicator.className = 'validation-indicator checking';
                usernameFeedback.innerHTML = value.length === 0 ? '' : 'Username must be at least 3 characters long';
                usernameFeedback.className = 'validation-feedback checking';
                return;
            }

            debouncedUsernameCheck(value);
        });

        // Email validation
        emailInput.addEventListener('input', function() {
            const value = this.value.trim();
            
            // Reset validation state while typing
            emailValid = false;
            this.style.borderColor = '#e0e0e0';
            
            // Basic email format validation first
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (value && !emailRegex.test(value)) {
                emailIndicator.innerHTML = '<i class="fas fa-times-circle"></i>';
                emailIndicator.className = 'validation-indicator invalid';
                emailFeedback.innerHTML = 'Please enter a valid email format';
                emailFeedback.className = 'validation-feedback invalid';
                this.style.borderColor = '#dc3545';
                return;
            }

            if (value) {
                debouncedEmailCheck(value);
            } else {
                emailIndicator.innerHTML = '';
                emailFeedback.innerHTML = '';
                emailIndicator.className = 'validation-indicator';
                emailFeedback.className = 'validation-feedback';
            }
        });

        // Enhanced form submission validation
        const signupForm = document.getElementById('signup-form');
        if (signupForm) {
            signupForm.addEventListener('submit', function(e) {
                const username = usernameInput.value.trim();
                const email = emailInput.value.trim();
                
                // Check if username and email are valid
                if (!usernameValid) {
                    e.preventDefault();
                    alert('Please wait for username validation to complete or choose a different username.');
                    usernameInput.focus();
                    return false;
                }
                
                if (!emailValid) {
                    e.preventDefault();
                    alert('Please wait for email validation to complete or use a different email address.');
                    emailInput.focus();
                    return false;
                }
                
                // Additional validation checks
                if (username.length < 3) {
                    e.preventDefault();
                    alert('Username must be at least 3 characters long.');
                    usernameInput.focus();
                    return false;
                }
                
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address.');
                    emailInput.focus();
                    return false;
                }
            });
        }
    });
  </script>

  <!-- Live Password Confirmation Validation Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('signup-password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const matchIndicator = document.getElementById('password-match-indicator');
        const feedback = document.getElementById('password-feedback');
        const submitBtn = document.getElementById('submit-btn');

        function validatePasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            // Only show validation if user has started typing in confirm password field
            if (confirmPassword.length === 0) {
                matchIndicator.innerHTML = '';
                feedback.innerHTML = '';
                matchIndicator.className = 'password-match-indicator';
                feedback.className = 'password-feedback';
                return;
            }

            if (password === confirmPassword && password.length > 0) {
                matchIndicator.innerHTML = '<i class="fas fa-check-circle"></i>';
                matchIndicator.className = 'password-match-indicator match';
                feedback.innerHTML = 'Passwords match perfectly!';
                feedback.className = 'password-feedback match';
                submitBtn.style.opacity = '1';
                submitBtn.style.pointerEvents = 'auto';
            } else {
                matchIndicator.innerHTML = '<i class="fas fa-times-circle"></i>';
                matchIndicator.className = 'password-match-indicator no-match';
                feedback.innerHTML = 'Passwords do not match. Please try again.';
                feedback.className = 'password-feedback no-match';
                submitBtn.style.opacity = '0.6';
                submitBtn.style.pointerEvents = 'none';
            }
        }

        // Add event listeners for real-time validation
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
        passwordInput.addEventListener('input', validatePasswordMatch);

        // Prevent form submission if passwords don't match
        document.getElementById('signup-form').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Please make sure your passwords match before submitting.');
                confirmPasswordInput.focus();
            }
        });
    });
  </script>

  <script>
    // Get values from PHP
    const lockedOut = <?= json_encode($locked_out) ?>;
    const remainingAttempts = <?= json_encode($remaining_attempts) ?>;
    let timeRemaining = <?= json_encode($time_remaining) ?>;

    const statusDiv = document.getElementById('login-status');

    function updateCountdown() {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        statusDiv.textContent = `Too many failed login attempts. Try again in ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        if (timeRemaining > 0) {
            timeRemaining--;
            setTimeout(updateCountdown, 1000);
        } else {
            statusDiv.textContent = '';
            window.location.href = window.location.pathname; // Reload page after countdown ends
        }
    }

    if (lockedOut) {
        updateCountdown();
    } else if (remainingAttempts <= 2) {
        statusDiv.textContent = `Remaining login attempts: ${remainingAttempts}`;
    }
  </script>

  <script>
    // Auto-hide alerts after 5 seconds
    document.addEventListener("DOMContentLoaded", function () {
      const alerts = document.querySelectorAll(".alert");
      alerts.forEach(function (alert) {
        setTimeout(() => {
          alert.style.opacity = "0";
          alert.style.transition = "opacity 0.5s ease-out";
          setTimeout(() => alert.style.display = "none", 500); // remove after fade
        }, 10000); // 10 seconds
      });
    });
  </script>

  <script>
// Phone Number Validation Script
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone');
    
    // Create validation indicator element
    const phoneValidationIndicator = document.createElement('span');
    phoneValidationIndicator.className = 'phone-validation-indicator';
    phoneValidationIndicator.style.cssText = `
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 16px;
        transition: all 0.3s ease;
    `;
    
    // Create feedback element
    const phoneFeedback = document.createElement('div');
    phoneFeedback.className = 'phone-feedback';
    phoneFeedback.style.cssText = `
        margin-top: 5px;
        font-size: 12px;
        transition: all 0.3s ease;
        animation: slideIn 0.3s ease;
    `;
    
    // Make phone input box relative and add elements
    const phoneInputBox = phoneInput.parentElement;
    phoneInputBox.style.position = 'relative';
    phoneInputBox.appendChild(phoneValidationIndicator);
    phoneInputBox.insertAdjacentElement('afterend', phoneFeedback);
    
    function validatePhoneNumber() {
        const phoneValue = phoneInput.value.replace(/\D/g, ''); // Remove non-digits
        const phoneLength = phoneValue.length;
        
        // Clear validation if empty
        if (phoneInput.value.length === 0) {
            phoneValidationIndicator.innerHTML = '';
            phoneFeedback.innerHTML = '';
            phoneValidationIndicator.className = 'phone-validation-indicator';
            phoneFeedback.className = 'phone-feedback';
            phoneInput.style.borderColor = '#e0e0e0';
            return;
        }
        
        if (phoneLength === 10) {
            // Valid 10-digit phone number
            phoneValidationIndicator.innerHTML = '<i class="fas fa-check-circle"></i>';
            phoneValidationIndicator.className = 'phone-validation-indicator valid';
            phoneValidationIndicator.style.color = '#28a745';
            phoneFeedback.innerHTML = 'Valid phone number format!';
            phoneFeedback.className = 'phone-feedback valid';
            phoneFeedback.style.color = '#28a745';
            phoneInput.style.borderColor = '#28a745';
        } else {
            // Invalid phone number
            phoneValidationIndicator.innerHTML = '<i class="fas fa-times-circle"></i>';
            phoneValidationIndicator.className = 'phone-validation-indicator invalid';
            phoneValidationIndicator.style.color = '#dc3545';
            
            if (phoneLength < 10) {
                phoneFeedback.innerHTML = `Phone number too short. ${10 - phoneLength} more digit${10 - phoneLength !== 1 ? 's' : ''} needed.`;
            } else {
                phoneFeedback.innerHTML = 'Phone number too long. Please enter exactly 10 digits.';
            }
            
            phoneFeedback.className = 'phone-feedback invalid';
            phoneFeedback.style.color = '#dc3545';
            phoneInput.style.borderColor = '#dc3545';
        }
    }
    
    // Add real-time validation
    phoneInput.addEventListener('input', validatePhoneNumber);
    phoneInput.addEventListener('blur', validatePhoneNumber);
    
    // Optional: Format phone number as user types (xxx-xxx-xxxx)
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
        
        // Limit to 10 digits
        if (value.length > 10) {
            value = value.slice(0, 10);
        }
        
        // Format as xxx-xxx-xxxx
        if (value.length >= 6) {
            value = value.replace(/(\d{3})(\d{3})(\d{0,4})/, '$1-$2-$3');
        } else if (value.length >= 3) {
            value = value.replace(/(\d{3})(\d{0,3})/, '$1-$2');
        }
        
        e.target.value = value;
    });
    
    // Prevent form submission if phone number is invalid
    const signupForm = document.getElementById('signup-form');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            const phoneValue = phoneInput.value.replace(/\D/g, '');
            
            if (phoneValue.length !== 10) {
                e.preventDefault();
                alert('Please enter a valid 10-digit phone number before submitting.');
                phoneInput.focus();
                return false;
            }
        });
    }
});
</script>

</body>
</html>