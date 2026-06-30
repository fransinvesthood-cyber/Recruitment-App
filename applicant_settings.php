<?php
include('config.php');
session_start();

// Notification flags (used in the notification preferences modal)
$email_notifications = false;
$push_notifications = false;


// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login_signup.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data for display
$sql = "SELECT fullname, email, password FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email, $hashed_password);
$stmt->fetch();
$stmt->close();

// Handle support request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_support'])) {
    $support_fullname = trim($_POST['support_fullname'] ?? '');
    $support_email = trim($_POST['support_email'] ?? '');
    $support_subject = trim($_POST['support_subject'] ?? '');
    $support_message = trim($_POST['support_message'] ?? '');

    if ($support_fullname === '' || $support_email === '' || $support_subject === '' || $support_message === '') {
        $message = 'Please fill in all support fields.';
        $messageClass = 'error';
    } elseif (!filter_var($support_email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageClass = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO contact_messages (fullname, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            if (!$stmt) {
                throw new RuntimeException('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('ssss', $support_fullname, $support_email, $support_subject, $support_message);
            if ($stmt->execute()) {
                $message = 'Support request submitted successfully!';
                $messageClass = 'success';
            } else {
                $message = 'Unable to submit your support request right now.';
                $messageClass = 'error';
            }
            $stmt->close();
        } catch (Throwable $e) {
            // Fallback for older schema without created_at
            try {
                $stmt2 = $conn->prepare("INSERT INTO contact_messages (fullname, email, subject, message) VALUES (?, ?, ?, ?)");
                if (!$stmt2) {
                    throw new RuntimeException('Prepare failed: ' . $conn->error);
                }
                $stmt2->bind_param('ssss', $support_fullname, $support_email, $support_subject, $support_message);
                if ($stmt2->execute()) {
                    $message = 'Support request submitted successfully!';
                    $messageClass = 'success';
                } else {
                    $message = 'Unable to submit your support request right now.';
                    $messageClass = 'error';
                }
                $stmt2->close();
            } catch (Throwable $e2) {
                $message = 'Unable to submit your support request right now. Please try again later.';
                $messageClass = 'error';
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $messageClass = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
        $messageClass = "error";
    } elseif (strlen($new_password) < 8) {
        $message = "New password must be at least 8 characters long.";
        $messageClass = "error";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $message = "New password must contain at least one uppercase letter.";
        $messageClass = "error";
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $message = "New password must contain at least one lowercase letter.";
        $messageClass = "error";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $message = "New password must contain at least one number.";
        $messageClass = "error";
    } elseif (!preg_match('/[\W_]/', $new_password)) {
        $message = "New password must contain at least one special character.";
        $messageClass = "error";
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($current_password, $hashed_password)) {
            // Update password
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_hashed_password, $user_id);

            if ($update_stmt->execute()) {
                $message = "Password changed successfully!";
                $messageClass = "success";
            } else {
                $message = "Error updating password: " . $conn->error;
                $messageClass = "error";
            }
            $update_stmt->close();
        } else {
            $message = "Current password is incorrect.";
            $messageClass = "error";
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #c9a9eaff;
            --dark: #18191a;
            --darker: #121314;
            --light: #f8f9fa;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --white: #ffffff;
            --black: #000000;
            --border-radius: 12px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --text-color: #2d3748;
            --bg-color: #f7fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #e0e7ff 0%, #f0f2f5 100%);
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
            border-radius: 15px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 1200px;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .header h2 {
            margin: 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 28px;
            font-weight: 700;
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
            transition: background 0.3s ease, color 0.3s ease;
        }
        .welcome-section h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .welcome-section p {
            opacity: 0.9;
            font-size: 18px;
        }

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d4a6f 100%);
            border: 1px solid #3d5a80;
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

        /* Settings Container Styles */
        .settings-container {
            margin-top: 20px;
        }

        .settings-section {
            margin-bottom: 30px;
        }

        .settings-section h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        body.dark-mode .settings-section h3 {
            color: #a7b7ff;
        }

        .settings-section h3 i {
            color: var(--primary-color);
        }

        body.dark-mode .settings-section h3 i {
            color: #a7b7ff;
        }

        .settings-list {
            display: grid;
            gap: 15px;
        }

        .settings-item {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .settings-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .settings-item.d-flex {
            align-items: center;
            gap: 20px;
        }

        .settings-item i {
            font-size: 24px;
            color: var(--primary-color);
            min-width: 24px;
        }

        .settings-item .content {
            flex: 1;
        }

        .settings-item .content strong {
            display: block;
            font-size: 18px;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        body.dark-mode .settings-item .content strong {
            color: #f7fafc;
        }

        .settings-item .content p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }

        body.dark-mode .settings-item .content p {
            color: #cbd5e0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 25px; margin: 20px; }
            .btn-exit { top: 15px; right: 15px; }
            .theme-toggle-container { right: 70px !important; top: 20px !important; }
        }

        /* =========================================
           Theme Toggle (Synced Style)
           ========================================= */
        .theme-toggle-container {
            position: absolute;
            top: 28px;
            right: 80px;
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


        /* =========================================
           Dark Mode Overrides
           ========================================= */
        body.dark-mode {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            color: #f7fafc;
        }

        body.dark-mode .container {
            background: #2d3748;
            color: #edf2f7;
            border-color: #4a5568;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        body.dark-mode .header h2 {
            background: linear-gradient(135deg, #667eea, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        body.dark-mode .settings-item {
            background: #2d3748;
            border-color: #4a5568;
        }

        body.dark-mode .settings-item:hover {
            background: #4a5568;
        }

        /* ===========================
           ALERT STYLES
        ============================ */
        .alert {
            padding: 14px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        body.dark-mode .alert.success {
            background: #0a3a1f;
            color: #d4edda;
        }
        body.dark-mode .alert.error {
            background: #3a1a1a;
            color: #f8d7da;
        }

        /* ===========================
           MODAL STYLES
        ============================ */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.4s ease-out;
        }

        .modal-content {
            background: linear-gradient(145deg, var(--white) 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.1);
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform: scale(0.8) translateY(-30px);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            margin: 20px;
            padding: 0;
            border: 1px solid rgba(255,255,255,0.2);
        }

        body.dark-mode .modal-content {
            background: linear-gradient(145deg, #2d3748 0%, #1a202c 100%);
            color: #e4e6eb;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.05);
        }

        .modal.show .modal-content {
            transform: scale(1) translateY(0);
        }

        .modal .close {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray);
            transition: all 0.3s ease;
            z-index: 1001;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }

        .modal .close:hover {
            color: var(--danger);
            transform: rotate(90deg) scale(1.1);
            background: rgba(220, 53, 69, 0.1);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 30px 40px 25px;
            border-radius: 20px 20px 0 0;
            position: relative;
            overflow: hidden;
        }

        .modal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
        }

        .modal h2 {
            margin: 0;
            color: var(--white);
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .modal h2 i {
            font-size: 32px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .modal-body {
            padding: 35px 40px;
            background: var(--white);
        }

        body.dark-mode .modal-body {
            background: #2d3748;
        }

        .modal form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .modal .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative;
        }

        .modal label {
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }

        body.dark-mode .modal label {
            color: #e4e6eb;
        }

        .modal label i {
            font-size: 16px;
            color: var(--primary);
        }

        .modal input,
        .modal select,
        .modal textarea {
            padding: 16px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.8);
            color: var(--dark);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        body.dark-mode .modal input,
        body.dark-mode .modal select,
        body.dark-mode .modal textarea {
            background: rgba(45, 55, 72, 0.8);
            border-color: #4a5568;
            color: #e4e6eb;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .modal input:focus,
        .modal select:focus,
        .modal textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15), 0 4px 12px rgba(102, 126, 234, 0.1);
            background: var(--white);
            transform: translateY(-1px);
        }

        body.dark-mode .modal input:focus,
        body.dark-mode .modal select:focus,
        body.dark-mode .modal textarea:focus {
            background: #2d3748;
        }

        .modal textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.5;
        }

        .modal .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            padding-top: 25px;
            border-top: 1px solid #e1e5e9;
        }

        body.dark-mode .modal .btn-group {
            border-color: #4a5568;
        }

        .modal .btn {
            flex: 1;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .modal .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .modal .btn:hover::before {
            left: 100%;
        }

        .modal .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .modal .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .modal .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .modal .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.7) translateY(-50px) rotate(-5deg);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0) rotate(0deg);
            }
        }

        /* Form field animations */
        .form-group {
            animation: slideUp 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 8px;
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

        /* Responsive modal */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 20px;
                max-height: 80vh;
            }

            .modal h2 {
                font-size: 20px;
            }

            .modal .btn-group {
                flex-direction: column;
            }

            .modal .btn {
                padding: 16px 20px;
            }
        }

        /* Additional styles for password modal */
        .input-boxes {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            background: rgba(255,255,255,0.8);
            transition: all 0.3s ease;
        }

        body.dark-mode .input-box {
            background: rgba(45, 55, 72, 0.8);
            border-color: #4a5568;
        }

        .input-box i {
            color: var(--primary);
            font-size: 18px;
        }

        .input-box input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            font-size: 16px;
            color: var(--dark);
        }

        body.dark-mode .input-box input {
            color: #e4e6eb;
        }

        .input-box.valid {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }

        .input-box.invalid {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        .validation-feedback {
            font-size: 14px;
            margin-top: 5px;
            padding: 8px 12px;
            border-radius: 6px;
            display: none;
        }

        .validation-feedback.valid {
            display: block;
            color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }

        .validation-feedback.invalid {
            display: block;
            color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        .match-indicator {
            position: absolute;
            right: 20px;
            display: none;
            font-size: 16px;
        }

        .match-indicator.show {
            display: block;
        }

        .match-indicator.show.valid {
            color: #28a745;
        }

        .match-indicator.show.invalid {
            color: #dc3545;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .checkbox-container input {
            width: auto;
            margin: 0;
        }

        .checkbox-container label {
            font-size: 14px;
            color: var(--dark);
            cursor: pointer;
        }

        body.dark-mode .checkbox-container label {
            color: #e4e6eb;
        }

        .button.input-box {
            border: none;
            background: transparent;
            padding: 0;
            margin-top: 20px;
        }

        .button.input-box button,
        .button.input-box input {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .button.input-box button:hover,
        .button.input-box input:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .button.input-box button.enabled,
        .button.input-box input.enabled {
            opacity: 1;
            cursor: pointer;
        }

        .button.input-box button.disabled,
        .button.input-box input.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="btn-exit" id="exitPage"><i class='bx bx-x'></i></button>
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Settings</h1>
            <p>Manage your account preferences and settings</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageClass; ?>">
                <i class='bx bx-info-circle'></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <div class="settings-section">
                <h3><i class="bx bx-user"></i> Account</h3>
                <div class="settings-list">
                    <div class="settings-item d-flex" onclick="window.location.href='update_profile.php'">
                        <i class="bx bx-edit"></i>
                        <div class="content">
                            <strong>Account Settings</strong>
                            <p>Manage your account information and profile details</p>
                        </div>
                        <i class="bx bx-chevron-right"></i>
                    </div>
                    <div class="settings-item d-flex" onclick="openPasswordModal()">
                        <i class="bx bx-lock-alt"></i>
                        <div class="content">
                            <strong>Change Password</strong>
                            <p>Update your password for better security</p>
                        </div>
                        <i class="bx bx-chevron-right"></i>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h3><i class="bx bx-palette"></i> Appearance</h3>
                <div class="settings-list">
                    <div class="settings-item d-flex" onclick="openThemeModal()">
                        <i class="bx bx-moon"></i>
                        <div class="content">
                            <strong>Theme Settings</strong>
                            <p>Customize your theme and appearance</p>
                        </div>
                        <i class="bx bx-chevron-right"></i>
                    </div>

                    <div class="settings-item" style="cursor: default;">
                        <div class="content">
                            <strong>Preview</strong>
                            <p>Changes are applied instantly to this page and will persist using your browser settings.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h3><i class="bx bx-bell"></i> Notifications</h3>
                <div class="settings-list">
                    <div class="settings-item d-flex" onclick="showNotificationSettings()">
                        <i class="bx bx-bell"></i>
                        <div class="content">
                            <strong>Notification Preferences</strong>
                            <p>Manage how you receive notifications</p>
                        </div>
                        <i class="bx bx-chevron-right"></i>
                    </div>
                </div>
            </div>



            <div class="settings-section">
                <h3><i class="bx bx-help-circle"></i> Support</h3>
                <div class="settings-list">
                    <div class="settings-item d-flex" onclick="showHelpSupport()">
                        <i class="bx bx-help-circle"></i>
                        <div class="content">
                            <strong>Help & Support</strong>
                            <p>Get help and contact our support team</p>
                        </div>
                        <i class="bx bx-chevron-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-lock'></i> Change Password</h2>
            </div>
        <div class="modal-body">
            <form action="" method="post" id="passwordForm">
                <div class="input-boxes">
                    <div class="input-box" data-password-eye>
                        <i class="fa fa-lock"></i>
                        <input type="password" id="current_password" name="current_password" placeholder="Current Password" required>
                        <i class="fa fa-eye password-toggle" aria-hidden="true" title="Show password"></i>
                    </div>
                    <div class="input-box" id="passwordBox" data-password-eye>
                        <i class="fa fa-lock"></i>
                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
                        <i class="fa fa-eye password-toggle" aria-hidden="true" title="Show password"></i>
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
                    </div>

                    <div class="input-box" id="confirmBox" data-password-eye>
                        <i class="fa fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required>
                        <i class="fa fa-eye password-toggle" aria-hidden="true" title="Show password"></i>
                        <div class="match-indicator" id="matchIndicator">
                            <i class="fa fa-check"></i>
                        </div>
                    </div>
                    <div class="validation-feedback" id="confirmFeedback"></div>

                    <div class="button input-box">
                        <button type="submit" id="submitBtn" name="change_password">Change Password</button>
                    </div>
                </div>
            </form>
        </div>
            <span class="close" onclick="closePasswordModal()">&times;</span>
        </div>
    </div>

    <!-- Theme Customization Modal -->
    <div id="themeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-palette'></i> Customize Theme</h2>
            </div>
            <div class="modal-body">
                <div class="theme-customizer">
                    <!-- Theme Mode Toggle -->
                    <div class="theme-section">
                        <h3><i class='bx bx-moon'></i> Theme Mode</h3>
                        <div class="theme-options">
                            <div class="theme-option" data-theme="light">
                                <div class="theme-preview light-preview">
                                    <div class="preview-header"></div>
                                    <div class="preview-sidebar"></div>
                                    <div class="preview-content"></div>
                                </div>
                                <span>Light Mode</span>
                            </div>
                            <div class="theme-option" data-theme="dark">
                                <div class="theme-preview dark-preview">
                                    <div class="preview-header"></div>
                                    <div class="preview-sidebar"></div>
                                    <div class="preview-content"></div>
                                </div>
                                <span>Dark Mode</span>
                            </div>
                        </div>
                    </div>

                    <!-- Color Scheme -->
                    <div class="theme-section">
                        <h3><i class='bx bx-color-fill'></i> Color Scheme</h3>
                        <div class="color-options">
                            <div class="color-option" data-color="blue" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                <span>Blue</span>
                            </div>
                            <div class="color-option" data-color="green" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                                <span>Green</span>
                            </div>
                            <div class="color-option" data-color="purple" style="background: linear-gradient(135deg, #9c27b0, #673ab7);">
                                <span>Purple</span>
                            </div>
                            <div class="color-option" data-color="red" style="background: linear-gradient(135deg, #ff6b6b, #ee5a24);">
                                <span>Red</span>
                            </div>
                            <div class="color-option" data-color="orange" style="background: linear-gradient(135deg, #ff9a56, #ff6b6b);">
                                <span>Orange</span>
                            </div>
                            <div class="color-option" data-color="teal" style="background: linear-gradient(135deg, #48cae4, #023e8a);">
                                <span>Teal</span>
                            </div>
                        </div>
                    </div>

                    <!-- Layout Options -->
                    <div class="theme-section">
                        <h3><i class='bx bx-layout'></i> Layout Options</h3>
                        <div class="layout-options">
                            <div class="layout-option">
                                <input type="checkbox" id="sidebar-collapsed" name="sidebar-collapsed">
                                <label for="sidebar-collapsed">Collapsed Sidebar by Default</label>
                            </div>
                            <div class="layout-option">
                                <input type="checkbox" id="compact-mode" name="compact-mode">
                                <label for="compact-mode">Compact Mode</label>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Section -->
                    <div class="theme-section">
                        <h3><i class='bx bx-show'></i> Preview</h3>
                        <div class="theme-preview-container">
                            <div class="preview-dashboard">
                                <div class="preview-nav"></div>
                                <div class="preview-main">
                                    <div class="preview-cards">
                                        <div class="preview-card"></div>
                                        <div class="preview-card"></div>
                                        <div class="preview-card"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="button" onclick="applyTheme()" class="btn btn-primary">
                        <i class='bx bx-check'></i> Apply Changes
                    </button>
                    <button type="button" onclick="resetTheme()" class="btn btn-secondary">
                        <i class='bx bx-reset'></i> Reset to Default
                    </button>
                    <button type="button" onclick="closeThemeModal()" class="btn btn-secondary">
                        <i class='bx bx-x'></i> Cancel
                    </button>
                </div>
            </div>
            <span class="close" onclick="closeThemeModal()">&times;</span>
        </div>
    </div>

    <!-- Support Modal -->
    <div id="supportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-support'></i> Help & Support</h2>
            </div>
            <div class="modal-body"></div>
            <span class="close" onclick="closeSupportModal()">&times;</span>
        </div>
    </div>

    <!-- Notification Settings Modal -->
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-bell'></i> Notification Preferences</h2>
            </div>
            <div class="modal-body">
                <form action="" method="post" id="notificationForm">
                    <div class="form-group">
                        <label><i class='bx bx-envelope'></i> Email Notifications</label>
                        <div class="checkbox-container">
                            <input type="checkbox" id="email_notifications" name="email_notifications" <?php echo $email_notifications ? 'checked' : ''; ?>>
                            <label for="email_notifications">Receive notifications via email</label>
                        </div>
                        <small style="color: #6c757d; font-size: 12px;">Get notified about job updates, interview schedules, and important announcements via email.</small>
                    </div>

                    <div class="form-group">
                        <label><i class='bx bx-bell'></i> Push Notifications</label>
                        <div class="checkbox-container">
                            <input type="checkbox" id="push_notifications" name="push_notifications" <?php echo $push_notifications ? 'checked' : ''; ?>>
                            <label for="push_notifications">Receive push notifications</label>
                        </div>
                        <small style="color: #6c757d; font-size: 12px;">Get instant notifications in your browser for real-time updates.</small>
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="update_notifications" class="btn btn-primary">
                            <i class='bx bx-save'></i> Save Preferences
                        </button>
                        <button type="button" onclick="closeNotificationModal()" class="btn btn-secondary">
                            <i class='bx bx-x'></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
            <span class="close" onclick="closeNotificationModal()">&times;</span>
        </div>
    </div>

    <script>
        document.getElementById("exitPage").addEventListener("click", function() {
            window.location.href = 'applicant.php';
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
        }

        // 1. Check LocalStorage on Load - Using 'darkMode' key to match applicant.php
        window.addEventListener('DOMContentLoaded', () => {
            const savedSetting = localStorage.getItem("darkMode");
            if (savedSetting === "enabled") {
                applyTheme(true);
            } else {
                applyTheme(false);
            }
        });

        // 2. Listen for Toggle Changes - Using 'darkMode' key to match applicant.php
        themeToggle.addEventListener('change', () => {
            if (themeToggle.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem("darkMode", "enabled");
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem("darkMode", "disabled");
            }
        });

        // Theme toggle functionality
        function toggleTheme() {
            themeToggle.checked = !themeToggle.checked;
            themeToggle.dispatchEvent(new Event('change'));
        }

        // Password Modal Functions
        function openPasswordModal() {
            const modal = document.getElementById('passwordModal');
            modal.style.display = 'flex';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closePasswordModal() {
            const modal = document.getElementById('passwordModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePasswordModal();
            }
        });

        // Get DOM elements
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordBox = document.getElementById('passwordBox');
        const confirmBox = document.getElementById('confirmBox');
        const passwordFeedback = document.getElementById('passwordFeedback');
        const confirmFeedback = document.getElementById('confirmFeedback');
        const submitBtn = document.getElementById('submitBtn');

        // NOTE: there is NO #show_password checkbox in the modal markup.
        // Visibility toggling is handled only via the .password-toggle icons.

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
                submitBtn.textContent = 'Change Password';
            } else {
                submitBtn.className = '';
                submitBtn.disabled = true;
                submitBtn.textContent = 'Please complete all requirements';
            }
        }

        function setPasswordVisibility(isVisible) {
            const type = isVisible ? 'text' : 'password';
            newPassword.type = type;
            confirmPassword.type = type;
            document.getElementById('current_password').type = type;

            // Sync checkbox UI (if it exists in markup)
            // (modal does not include a checkbox, so this is guarded)
            if (typeof showPasswordCheckbox !== 'undefined' && showPasswordCheckbox) {
                showPasswordCheckbox.checked = isVisible;
            }


            // Sync eye icons
            document.querySelectorAll('#passwordModal .password-toggle').forEach(icon => {
                icon.classList.toggle('fa-eye', isVisible);
                icon.classList.toggle('fa-eye-slash', !isVisible);
            });
        }

        // Attach a click handler for eye icons (current/new/confirm)
        // Toggle visibility based on current icon state.
        document.querySelectorAll('#passwordModal .password-toggle').forEach(icon => {
            icon.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const isCurrentlyShowing = icon.classList.contains('fa-eye');
                setPasswordVisibility(!isCurrentlyShowing);
            });
        });



        // Event listeners
        newPassword.addEventListener('input', validatePassword);
        newPassword.addEventListener('focus', validatePassword);
        confirmPassword.addEventListener('input', validateConfirmPassword);
        confirmPassword.addEventListener('focus', validateConfirmPassword);


        // Form submission validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            if (!passwordValid || !confirmValid) {
                e.preventDefault();
                alert('Please ensure all password requirements are met and passwords match.');
                return false;
            }
        });

        // Initialize
        updateSubmitButton();

        // ESC key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePasswordModal();
            }
        });

        // Theme Modal Functions
        function openThemeModal() {
            const modal = document.getElementById('themeModal');
            modal.style.display = 'flex';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            loadCurrentThemeSettings();

            // Apply active selection immediately for instant feedback
            applyThemeSettings();
        }


        function closeThemeModal() {
            const modal = document.getElementById('themeModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            document.body.style.overflow = 'auto';
        }

        function loadCurrentThemeSettings() {
            // Use 'darkMode' key to match applicant.php
            const currentDarkMode = localStorage.getItem('darkMode');
            const currentTheme = currentDarkMode === 'enabled' ? 'dark' : 'light';
            const currentColor = localStorage.getItem('colorScheme') || 'blue';
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            const compactMode = localStorage.getItem('compactMode') === 'true';

            // Set theme mode
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('active');
            });
            document.querySelector(`[data-theme="${currentTheme}"]`).classList.add('active');

            // Set color scheme
            document.querySelectorAll('.color-option').forEach(option => {
                option.classList.remove('active');
            });
            document.querySelector(`[data-color="${currentColor}"]`).classList.add('active');

            // Set layout options
            document.getElementById('sidebar-collapsed').checked = sidebarCollapsed;
            document.getElementById('compact-mode').checked = compactMode;

            updatePreview();
        }

        function applyThemeSettings() {
            const selectedTheme = document.querySelector('.theme-option.active').dataset.theme;
            const selectedColor = document.querySelector('.color-option.active').dataset.color;
            const sidebarCollapsed = document.getElementById('sidebar-collapsed').checked;
            const compactMode = document.getElementById('compact-mode').checked;

            // Apply theme mode using 'darkMode' key to match applicant.php
            if (selectedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }

            // Apply color scheme
            applyColorScheme(selectedColor);
            localStorage.setItem('colorScheme', selectedColor);

            // Apply layout options
            if (sidebarCollapsed) {
                document.getElementById('sidebar').classList.add('collapsed');
                localStorage.setItem('sidebarCollapsed', 'true');
            } else {
                document.getElementById('sidebar').classList.remove('collapsed');
                localStorage.setItem('sidebarCollapsed', 'false');
            }

            if (compactMode) {
                document.body.classList.add('compact-mode');
                localStorage.setItem('compactMode', 'true');
            } else {
                document.body.classList.remove('compact-mode');
                localStorage.setItem('compactMode', 'false');
            }

            closeThemeModal();
            showSuccessMessage('Theme settings applied successfully!');
        }

        function resetTheme() {
            // Reset to default theme
            document.body.classList.remove('dark-mode', 'compact-mode');
            document.getElementById('sidebar').classList.remove('collapsed');

            // Reset CSS variables to defaults
            document.documentElement.style.setProperty('--primary', '#667eea');
            document.documentElement.style.setProperty('--primary-dark', '#5a67d8');
            document.documentElement.style.setProperty('--secondary', '#c9a9eaff');

            // Clear localStorage - use 'darkMode' key to match applicant.php
            localStorage.setItem('darkMode', 'disabled');
            localStorage.removeItem('colorScheme');
            localStorage.removeItem('sidebarCollapsed');
            localStorage.removeItem('compactMode');

            // Reload current settings
            loadCurrentThemeSettings();
            showSuccessMessage('Theme reset to default!');
        }

        function applyColorScheme(color) {
            const colorSchemes = {
                blue: { primary: '#667eea', primaryDark: '#5a67d8', secondary: '#764ba2' },
                green: { primary: '#11998e', primaryDark: '#0f8a7a', secondary: '#38ef7d' },
                purple: { primary: '#9c27b0', primaryDark: '#7b1fa2', secondary: '#ba68c8' },
                red: { primary: '#ff6b6b', primaryDark: '#ee5a24', secondary: '#ff9a56' },
                orange: { primary: '#ff9a56', primaryDark: '#ff6b6b', secondary: '#ee5a24' },
                teal: { primary: '#48cae4', primaryDark: '#023e8a', secondary: '#0077b6' }
            };

            const scheme = colorSchemes[color];
            document.documentElement.style.setProperty('--primary', scheme.primary);
            document.documentElement.style.setProperty('--primary-dark', scheme.primaryDark);
            document.documentElement.style.setProperty('--secondary', scheme.secondary);
        }

        function updatePreview() {
            const selectedTheme = document.querySelector('.theme-option.active').dataset.theme;
            const selectedColor = document.querySelector('.color-option.active').dataset.color;
            const previewContainer = document.querySelector('.theme-preview-container');

            if (selectedTheme === 'dark') {
                previewContainer.classList.add('dark-preview');
                previewContainer.classList.remove('light-preview');
            } else {
                previewContainer.classList.add('light-preview');
                previewContainer.classList.remove('dark-preview');
            }

            // Apply color to preview
            const colorSchemes = {
                blue: 'linear-gradient(135deg, #667eea, #764ba2)',
                green: 'linear-gradient(135deg, #11998e, #38ef7d)',
                purple: 'linear-gradient(135deg, #9c27b0, #673ab7)',
                red: 'linear-gradient(135deg, #ff6b6b, #ee5a24)',
                orange: 'linear-gradient(135deg, #ff9a56, #ff6b6b)',
                teal: 'linear-gradient(135deg, #48cae4, #023e8a)'
            };

            document.querySelector('.preview-nav').style.background = colorSchemes[selectedColor];
            document.querySelector('.preview-sidebar').style.background = colorSchemes[selectedColor];
        }

        function showSuccessMessage(message) {
            // Create a temporary success message
            const alert = document.createElement('div');
            alert.className = 'alert success';
            alert.innerHTML = `<i class='bx bx-check-circle'></i><span>${message}</span>`;
            document.querySelector('.container').prepend(alert);

            setTimeout(() => {
                alert.remove();
            }, 3000);
        }

        // Theme selection event listeners
        document.getElementById('themeModal').querySelector('.modal-content').addEventListener('click', function(e) {
            const themeOption = e.target.closest('.theme-option');
            if (themeOption) {
                e.stopPropagation();
                document.querySelectorAll('#themeModal .theme-option').forEach(option => {
                    option.classList.remove('active');
                });
                themeOption.classList.add('active');
                updatePreview();
            }

            const colorOption = e.target.closest('.color-option');
            if (colorOption) {
                e.stopPropagation();
                document.querySelectorAll('#themeModal .color-option').forEach(option => {
                    option.classList.remove('active');
                });
                colorOption.classList.add('active');
                updatePreview();
            }
        });

        // Close theme modal when clicking outside
        document.getElementById('themeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeThemeModal();
            }
        });

        // ESC key to close modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePasswordModal();
                closeThemeModal();
                closeNotificationModal();
            }
        });

        // Notification Modal Functions
        function showNotificationSettings() {
            const modal = document.getElementById('notificationModal');
            modal.style.display = 'flex';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeNotificationModal() {
            const modal = document.getElementById('notificationModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            document.body.style.overflow = 'auto';
        }

        // Close notification modal when clicking outside
        document.getElementById('notificationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeNotificationModal();
            }
        });

        // Support Modal
        function showHelpSupport() {
            const html = `
                <div class="modal-body" style="padding: 20px 0 0 0;">
                    <div class="settings-container" style="margin-top:0;">
                        <div class="settings-section" style="margin-bottom: 0;">
                            <h3 style="margin:0 0 18px 0; font-size:18px;">
                                <i class='bx bx-support' style="margin-right:8px;"></i> Help & Support
                            </h3>

                            <form method="POST" action="" id="supportForm" class="mt-2">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label><i class='bx bx-user'></i> Full name</label>
<input type="text" name="support_fullname" value="<?php echo htmlspecialchars($fullname ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly style="width:100%;" />
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label><i class='bx bx-envelope'></i> Email</label>
<input type="email" name="support_email" value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly style="width:100%;" />
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label><i class='bx bx-detail'></i> Subject</label>
                                    <input type="text" name="support_subject" placeholder="e.g., Password reset, Job application issue" required style="width:100%;" />
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label><i class='bx bx-chat'></i> Message</label>
                                    <textarea name="support_message" placeholder="Describe the issue" required style="width:100%; min-height:110px;"></textarea>
                                </div>

                                <div class="btn-group" style="margin-top: 10px;">
                                    <button type="submit" name="submit_support" class="btn btn-primary" style="flex:1;">
                                        <i class='bx bx-send'></i> Submit Request
                                    </button>
                                    <button type="button" onclick="closeSupportModal()" class="btn btn-secondary" style="flex:1;">
                                        <i class='bx bx-x'></i> Cancel
                                    </button>
                                </div>
                            </form>

                            <div class="mt-3" style="color:#6c757d; font-size:13px; line-height:1.5;">
                                You can also visit <a href="contact_us.php" style="color: var(--primary); font-weight:700; text-decoration:none;">Contact Us</a>.
                            </div>
                        </div>
                    </div>
                </div>`;

            const modal = document.getElementById('supportModal');
            modal.querySelector('.modal-body').innerHTML = html;

            modal.style.display = 'flex';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSupportModal() {
            const modal = document.getElementById('supportModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            document.body.style.overflow = 'auto';
        }


    </script>

</body>
</html>
