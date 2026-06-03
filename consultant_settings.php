<?php
include('config.php');
session_start();

// Check if user is logged in and is a Consultant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Consultant') {
    header("Location: index.php");
    exit();
}

$message = '';
$messageClass = '';

$user_id = $_SESSION['user_id'];

// Handle profile edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    $new_fullname = trim($_POST['fullname']);
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']);
    $new_gender = trim($_POST['gender']);
    $new_address = trim($_POST['address']);
    $new_dob = trim($_POST['dob']);

    // Validate inputs
    if (empty($new_fullname) || empty($new_email)) {
        $message = "Full name and email are required.";
        $messageClass = "error";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageClass = "error";
    } elseif (!empty($new_phone) && !preg_match('/^[0-9+\-\s()]+$/', $new_phone)) {
        $message = "Please enter a valid phone number.";
        $messageClass = "error";
    } elseif (!empty($new_dob) && !strtotime($new_dob)) {
        $message = "Please enter a valid date of birth.";
        $messageClass = "error";
    } else {
        // Check if email is already taken by another user
        $check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $new_email, $user_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "Email address is already in use.";
            $messageClass = "error";
        } else {
            // Update profile
            $update_sql = "UPDATE users SET fullname = ?, email = ?, phone = ?, gender = ?, address = ?, dob = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssssi", $new_fullname, $new_email, $new_phone, $new_gender, $new_address, $new_dob, $user_id);

            if ($update_stmt->execute()) {
                $message = "Profile updated successfully!";
                $messageClass = "success";
                // Update session fullname if changed
                $_SESSION['fullname'] = $new_fullname;
            } else {
                $message = "Error updating profile: " . $conn->error;
                $messageClass = "error";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
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

// Fetch current user data
$sql = "SELECT fullname, email, phone, gender, address, dob FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email, $phone, $gender, $address, $dob);
$stmt->fetch();
$stmt->close();

// Session message handling
if (!empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageClass = $_SESSION['messageClass'];
    unset($_SESSION['message'], $_SESSION['messageClass']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultant Settings</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css">

    <style>
        /* ===========================
           GLOBAL RESET & VARIABLES (copied from admin_settings)
        ============================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
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
        }
        body {
            background-color: #f5f7fb;
            color: #333;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        body.dark-mode {
            background-color: var(--dark);
            color: #e4e6eb;
        }

        /* ===========================
           SIDEBAR (copied from consultant_dashboard)
        ============================ */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: var(--white);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }
        body.dark-mode .sidebar {
            background: #242526;
        }
        .sidebar.collapsed {
            width: 80px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 24px 20px;
            text-decoration: none;
            color: var(--white);
            font-size: 22px;
            font-weight: 700;
        }
        .logo i {
            font-size: 32px;
        }
        .logo-name span {
            white-space: nowrap;
            transition: var(--transition);
        }
        .sidebar.collapsed .logo-name span {
            display: none;
        }
        .side-menu {
            list-style: none;
            padding: 0 15px;
            flex: 1;
            overflow-y: auto;
        }
        .side-menu li {
            margin: 8px 0;
        }
        .side-menu li a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-size: 16px;
        }
        .side-menu li.active a {
            background: rgba(255, 255, 255, 0.15);
        }
        .side-menu li a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .side-menu li a i {
            font-size: 22px;
            min-width: 24px;
            text-align: center;
        }
        .logout {
            margin-top: auto;
            padding: 16px !important;
            background: rgba(0, 0, 0, 0.2);
        }

        /* ===========================
           MAIN CONTENT & NAVBAR
        ============================ */
        .content {
            flex: 1;
            margin-left: 280px;
            transition: var(--transition);
        }
        .sidebar.collapsed ~ .content {
            margin-left: 80px;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 30px;
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 99;
        }
        body.dark-mode nav {
            background: #242526;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        nav .bx-menu {
            font-size: 28px;
            cursor: pointer;
            color: var(--gray);
        }

        .theme-toggle {
            width: 50px;
            height: 24px;
            background: var(--light-gray);
            border-radius: 50px;
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 2px;
        }
        body.dark-mode .theme-toggle {
            background: #3a3b3c;
        }
        .theme-toggle::before {
            content: '';
            width: 20px;
            height: 20px;
            background: var(--white);
            border-radius: 50%;
            transition: var(--transition);
        }
        #theme-toggle:checked + .theme-toggle::before {
            transform: translateX(26px);
            background: var(--primary);
        }

        /* ===========================
           MOBILE NAV LINKS BAR
        ============================ */
        .mobile-nav-links {
            display: none;
            background: var(--white);
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
            white-space: nowrap;
        }
        body.dark-mode .mobile-nav-links {
            background: #242526;
        }
        .mobile-nav-links a {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 4px;
            background: var(--light-gray);
            border-radius: 8px;
            text-decoration: none;
            color: var(--gray);
            font-size: 14px;
            transition: var(--transition);
        }
        body.dark-mode .mobile-nav-links a {
            background: #3a3b3c;
            color: #adb5bd;
        }
        .mobile-nav-links a:hover,
        .mobile-nav-links a.active {
            background: var(--primary);
            color: white;
        }

        @media (max-width: 768px) {
            .mobile-nav-links {
                display: flex;
            }
        }

        /* ===========================
           MAIN
        ============================ */
        main {
            padding: 24px;
        }

        /* WELCOME SECTION */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            box-shadow: var(--box-shadow);
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

        /* ===========================
           CARD & FORM
        ============================ */
        .card {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 24px;
        }
        body.dark-mode .card {
            background: #242526;
        }

        .card h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        body.dark-mode .card h2 {
            color: #e4e6eb;
        }

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

        .btn {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            text-decoration: none;
        }
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* ===========================
           RESPONSIVE
        ============================ */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            .logo-name span,
            .side-menu li a span {
                display: none;
            }
            .side-menu li a {
                justify-content: center;
                padding: 16px;
            }
            .content {
                margin-left: 80px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
            nav {
                padding: 16px 20px;
            }
            .mobile-nav-links {
                display: block;
            }
            .card {
                padding: 20px;
            }
        }

        /* Mobile Menu Overlay */
        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar.active ~ .mobile-menu-overlay {
            display: block;
        }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
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

        /* Theme Customizer Styles */
        .theme-customizer {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .theme-section {
            border: 1px solid #e1e5e9;
            border-radius: 12px;
            padding: 20px;
            background: rgba(255,255,255,0.8);
        }

        body.dark-mode .theme-section {
            background: rgba(45, 55, 72, 0.8);
            border-color: #4a5568;
        }

        .theme-section h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        body.dark-mode .theme-section h3 {
            color: #e4e6eb;
        }

        .theme-section h3 i {
            color: var(--primary);
        }

        .theme-options {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .theme-option {
            flex: 1;
            min-width: 150px;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            background: rgba(255,255,255,0.8);
        }

        body.dark-mode .theme-option {
            background: rgba(45, 55, 72, 0.8);
            border-color: #4a5568;
        }

        .theme-option:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .theme-option.active {
            border-color: var(--primary);
            background: rgba(102, 126, 234, 0.1);
        }

        .theme-preview {
            height: 80px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            overflow: hidden;
        }

        .theme-preview .preview-header {
            height: 20px;
            background: #f8f9fa;
            flex: 1;
        }

        .theme-preview .preview-sidebar {
            width: 30px;
            background: var(--primary);
        }

        .theme-preview .preview-content {
            flex: 1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #666;
        }

        .light-preview .preview-header {
            background: #f8f9fa;
        }

        .light-preview .preview-content {
            background: #ffffff;
            color: #333;
        }

        .dark-preview .preview-header {
            background: #3a3b3c;
        }

        .dark-preview .preview-sidebar {
            background: #5a67d8;
        }

        .dark-preview .preview-content {
            background: #242526;
            color: #e4e6eb;
        }

        .theme-option span {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
        }

        body.dark-mode .theme-option span {
            color: #e4e6eb;
        }

        .color-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 15px;
        }

        .color-option {
            height: 60px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid transparent;
            position: relative;
        }

        .color-option:hover {
            transform: scale(1.05);
        }

        .color-option.active {
            border-color: #ffffff;
            box-shadow: 0 0 0 2px var(--primary);
        }

        .color-option span {
            color: white;
            font-size: 12px;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }

        .layout-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .layout-option {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .layout-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .layout-option label {
            font-size: 14px;
            color: var(--dark);
            cursor: pointer;
            margin: 0;
        }

        body.dark-mode .layout-option label {
            color: #e4e6eb;
        }

        .theme-preview-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e1e5e9;
        }

        body.dark-mode .theme-preview-container {
            background: #2d3748;
            border-color: #4a5568;
        }

        .preview-dashboard {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .dark-preview .preview-dashboard {
            background: #242526;
        }

        .preview-nav {
            height: 30px;
            background: var(--primary);
        }

        .preview-main {
            display: flex;
            min-height: 80px;
        }

        .preview-sidebar {
            width: 60px;
            background: var(--primary);
        }

        .preview-content {
            flex: 1;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .preview-cards {
            display: flex;
            gap: 8px;
        }

        .preview-card {
            flex: 1;
            height: 20px;
            background: #f8f9fa;
            border-radius: 3px;
        }

        .dark-preview .preview-card {
            background: #3a3b3c;
        }

        /* Responsive theme customizer */
        @media (max-width: 768px) {
            .theme-options {
                flex-direction: column;
            }

            .theme-option {
                min-width: auto;
            }

            .color-options {
                grid-template-columns: repeat(3, 1fr);
            }

            .theme-preview-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <script>
        (function() {
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="#" class="logo">
            <i class='bx bx-user-circle'></i>
            <div class="logo-name"><span>Consultant</span></div>
        </a>
        <ul class="side-menu">
            <li><a href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li><a href="consultant_profile.php"><i class='bx bx-user'></i><span>Profile</span></a></li>
            <li><a href="manage_leave.php"><i class='bx bx-calendar-minus'></i><span>Leave</span></a></li>
            <li><a href="manage_timesheets.php"><i class='bx bx-time-five'></i><span>Timesheets</span></a></li>
            <li><a href="manage_task_log.php"><i class='bx bx-file'></i><span>Tasklogs</span></a></li>
            <li><a href="invoices.php"><i class='bx bx-receipt'></i><span>Invoices</span></a></li>
            <li><a href="training_management.php"><i class='bx bx-book-reader'></i><span>Training</span></a></li>
            <li><a href="consultant_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
            <li class="active"><a href="consultant_settings.php"><i class='bx bx-cog'></i><span>Settings</span></a></li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="logout.php" class="logout">
                    <i class='bx bx-log-out-circle'></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <!-- Mobile Nav Links -->
        <div class="mobile-nav-links">
            <a href="consultant_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="consultant_profile.php"><i class='bx bx-user'></i> Profile</a>
            <a href="manage_timesheets.php"><i class='bx bx-time-five'></i> Timesheets</a>
            <a href="manage_task_log.php"><i class='bx bx-file'></i> Tasklogs</a>
            <a href="manage_leave.php"><i class='bx bx-calendar-minus'></i> Leave</a>
            <a href="invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
            <a href="training_management.php"><i class='bx bx-book-reader'></i> Training</a>
            <a href="consultant_chat.php"><i class='bx bx-chat'></i> Chats</a>
            <a class="active" href="consultant_settings.php"><i class='bx bx-cog'></i> Settings</a>
        </div>

        <main>
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Settings</h1>
                <p>Manage your consultant settings</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <i class='bx bx-info-circle'></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Settings Cards -->
            <div class="card">
                <h2><i class='bx bx-user'></i> Account Settings</h2>
                <p>Manage your account preferences and profile information.</p>
                <button class="btn" onclick="openProfileModal()">
                    <i class='bx bx-edit'></i> Edit Profile
                </button>
            </div>

            <div class="card">
                <h2><i class='bx bx-bell'></i> Notification Settings</h2>
                <p>Configure how you receive notifications and alerts.</p>
                <button class="btn" onclick="openNotificationModal()">
                    <i class='bx bx-bell'></i> Manage Notifications
                </button>
            </div>

            <div class="card">
                <h2><i class='bx bx-shield'></i> Security Settings</h2>
                <p>Update your password and security preferences.</p>
                <button class="btn" onclick="openPasswordModal()">
                    <i class='bx bx-lock'></i> Change Password
                </button>
            </div>

            <div class="card">
                <h2><i class='bx bx-palette'></i> Appearance Settings</h2>
                <p>Customize the look and feel of your dashboard.</p>
                <button class="btn" onclick="openThemeModal()">
                    <i class='bx bx-palette'></i> Customize Theme
                </button>
            </div>
        </main>
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
                    <div class="input-box">
                        <i class="fa fa-lock"></i>
                        <input type="password" id="current_password" name="current_password" placeholder="Current Password" required>
                    </div>
                    <div class="input-box" id="passwordBox">
                        <i class="fa fa-lock"></i>
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
                        <i class="fa fa-lock"></i>
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
                        <button type="submit" id="submitBtn" name="change_password">Change Password</button>
                    </div>
                </div>
            </form>
        </div>
            <span class="close" onclick="closePasswordModal()">&times;</span>
        </div>
    </div>

    <!-- Profile Edit Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-user'></i> Edit Profile</h2>
            </div>
            <div class="modal-body">
                <form method="POST" id="profileForm">
                    <div class="form-group">
                        <label for="fullname"><i class='bx bx-user-circle'></i> Full Name</label>
                        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><i class='bx bx-envelope'></i> Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone"><i class='bx bx-phone'></i> Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender"><i class='bx bx-male-female'></i> Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($gender ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($gender ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($gender ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            <option value="Prefer not to say" <?php echo ($gender ?? '') === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="address"><i class='bx bx-map-pin'></i> Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="dob"><i class='bx bx-calendar'></i> Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($dob ?? ''); ?>">
                    </div>
                    <div class="btn-group">
                        <button type="submit" name="edit_profile" class="btn btn-primary">
                            <i class='bx bx-check'></i> Update Profile
                        </button>
                        <button type="button" onclick="closeProfileModal()" class="btn btn-secondary">
                            <i class='bx bx-x'></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
            <span class="close" onclick="closeProfileModal()">&times;</span>
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

    <!-- Notification Settings Modal -->
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-bell'></i> Notification Settings</h2>
            </div>
            <div class="modal-body">
                <div class="notification-settings">
                    <!-- Email Notifications -->
                    <div class="theme-section">
                        <h3><i class='bx bx-envelope'></i> Email Notifications</h3>
                        <div class="layout-options">
                            <div class="layout-option">
                                <input type="checkbox" id="email-job-updates" name="email-job-updates" checked>
                                <label for="email-job-updates">Job application updates</label>
                            </div>
                            <div class="layout-option">
                                <input type="checkbox" id="email-interview-schedules" name="email-interview-schedules" checked>
                                <label for="email-interview-schedules">Interview schedule notifications</label>
                            </div>
                            <div class="layout-option">
                                <input type="checkbox" id="email-system-updates" name="email-system-updates" checked>
                                <label for="email-system-updates">System updates and announcements</label>
                            </div>
                        </div>
                    </div>

                    <!-- Push Notifications -->
                    <div class="theme-section">
                        <h3><i class='bx bx-mobile'></i> Push Notifications</h3>
                        <div class="layout-options">
                            <div class="layout-option">
                                <input type="checkbox" id="push-job-alerts" name="push-job-alerts" checked>
                                <label for="push-job-alerts">Job alerts</label>
                            </div>
                            <div class="layout-option">
                                <input type="checkbox" id="push-messages" name="push-messages" checked>
                                <label for="push-messages">New messages</label>
                            </div>
                            <div class="layout-option">
                                <input type="checkbox" id="push-reminders" name="push-reminders" checked>
                                <label for="push-reminders">Task and deadline reminders</label>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Frequency -->
                    <div class="theme-section">
                        <h3><i class='bx bx-time'></i> Notification Frequency</h3>
                        <div class="layout-options">
                            <div class="layout-option">
                                <input type="radio" id="freq-instant" name="notification-frequency" value="instant" checked>
                                <label for="freq-instant">Instant</label>
                            </div>
                            <div class="layout-option">
                                <input type="radio" id="freq-daily" name="notification-frequency" value="daily">
                                <label for="freq-daily">Daily digest</label>
                            </div>
                            <div class="layout-option">
                                <input type="radio" id="freq-weekly" name="notification-frequency" value="weekly">
                                <label for="freq-weekly">Weekly digest</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="button" onclick="saveNotificationSettings()" class="btn btn-primary">
                        <i class='bx bx-check'></i> Save Settings
                    </button>
                    <button type="button" onclick="closeNotificationModal()" class="btn btn-secondary">
                        <i class='bx bx-x'></i> Cancel
                    </button>
                </div>
            </div>
            <span class="close" onclick="closeNotificationModal()">&times;</span>
        </div>
    </div>

    <script>
        // ===========================
        // MODAL FUNCTIONS
        // ===========================
        
        // Password Modal
        function openPasswordModal() {
            const modal = document.getElementById('passwordModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('show');
                // Reset form
                document.getElementById('passwordForm').reset();
                resetPasswordValidation();
            }
        }

        function closePasswordModal() {
            const modal = document.getElementById('passwordModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
        }

        // Profile Modal
        function openProfileModal() {
            const modal = document.getElementById('profileModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('show');
            }
        }

        function closeProfileModal() {
            const modal = document.getElementById('profileModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
        }

        // Theme Modal
        function openThemeModal() {
            const modal = document.getElementById('themeModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('show');
                loadThemeSettings();
            }
        }

        function closeThemeModal() {
            const modal = document.getElementById('themeModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
        }

        // Notification Modal
        function openNotificationModal() {
            const modal = document.getElementById('notificationModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('show');
            }
        }

        function closeNotificationModal() {
            const modal = document.getElementById('notificationModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                event.target.classList.remove('show');
            }
        }

        // ===========================
        // PASSWORD VALIDATION
        // ===========================
        function resetPasswordValidation() {
            document.getElementById('lengthReq').className = 'requirement neutral';
            document.getElementById('upperReq').className = 'requirement neutral';
            document.getElementById('lowerReq').className = 'requirement neutral';
            document.getElementById('numberReq').className = 'requirement neutral';
            document.getElementById('specialReq').className = 'requirement neutral';
            document.getElementById('strengthBar').className = 'strength-bar';
            document.getElementById('strengthBar').style.width = '0';
            document.getElementById('strengthText').textContent = 'Enter password to see strength';
            document.getElementById('matchIndicator').classList.remove('show', 'valid', 'invalid');
            document.getElementById('passwordBox').classList.remove('valid', 'invalid');
            document.getElementById('confirmBox').classList.remove('valid', 'invalid');
        }

        // Password strength validation
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            const lengthReq = document.getElementById('lengthReq');
            if (password.length >= 8) {
                lengthReq.className = 'requirement valid';
                lengthReq.querySelector('i').className = 'fa fa-check-circle';
                strength++;
            } else {
                lengthReq.className = 'requirement invalid';
                lengthReq.querySelector('i').className = 'fa fa-circle';
            }

            // Uppercase check
            const upperReq = document.getElementById('upperReq');
            if (/[A-Z]/.test(password)) {
                upperReq.className = 'requirement valid';
                upperReq.querySelector('i').className = 'fa fa-check-circle';
                strength++;
            } else {
                upperReq.className = 'requirement invalid';
                upperReq.querySelector('i').className = 'fa fa-circle';
            }

            // Lowercase check
            const lowerReq = document.getElementById('lowerReq');
            if (/[a-z]/.test(password)) {
                lowerReq.className = 'requirement valid';
                lowerReq.querySelector('i').className = 'fa fa-check-circle';
                strength++;
            } else {
                lowerReq.className = 'requirement invalid';
                lowerReq.querySelector('i').className = 'fa fa-circle';
            }

            // Number check
            const numberReq = document.getElementById('numberReq');
            if (/[0-9]/.test(password)) {
                numberReq.className = 'requirement valid';
                numberReq.querySelector('i').className = 'fa fa-check-circle';
                strength++;
            } else {
                numberReq.className = 'requirement invalid';
                numberReq.querySelector('i').className = 'fa fa-circle';
            }

            // Special character check
            const specialReq = document.getElementById('specialReq');
            if (/[\W_]/.test(password)) {
                specialReq.className = 'requirement valid';
                specialReq.querySelector('i').className = 'fa fa-check-circle';
                strength++;
            } else {
                specialReq.className = 'requirement invalid';
                specialReq.querySelector('i').className = 'fa fa-circle';
            }

            // Update strength bar
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const passwordBox = document.getElementById('passwordBox');
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
                strengthText.textContent = 'Enter password to see strength';
                passwordBox.classList.remove('valid', 'invalid');
            } else if (strength <= 2) {
                strengthBar.className = 'strength-bar strength-weak';
                strengthText.textContent = 'Weak';
                passwordBox.classList.remove('valid');
                passwordBox.classList.add('invalid');
            } else if (strength === 3) {
                strengthBar.className = 'strength-bar strength-fair';
                strengthText.textContent = 'Fair';
                passwordBox.classList.remove('valid');
                passwordBox.classList.add('invalid');
            } else if (strength === 4) {
                strengthBar.className = 'strength-bar strength-good';
                strengthText.textContent = 'Good';
                passwordBox.classList.remove('invalid');
                passwordBox.classList.add('valid');
            } else {
                strengthBar.className = 'strength-bar strength-strong';
                strengthText.textContent = 'Strong';
                passwordBox.classList.remove('invalid');
                passwordBox.classList.add('valid');
            }

            // Check confirm password match
            checkPasswordMatch();
        });

        // Confirm password match
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchIndicator = document.getElementById('matchIndicator');
            const confirmBox = document.getElementById('confirmBox');

            if (confirmPassword.length > 0) {
                matchIndicator.classList.add('show');
                if (newPassword === confirmPassword) {
                    matchIndicator.classList.remove('invalid');
                    matchIndicator.classList.add('valid');
                    matchIndicator.innerHTML = '<i class="fa fa-check"></i>';
                    confirmBox.classList.remove('invalid');
                    confirmBox.classList.add('valid');
                } else {
                    matchIndicator.classList.remove('valid');
                    matchIndicator.classList.add('invalid');
                    matchIndicator.innerHTML = '<i class="fa fa-times"></i>';
                    confirmBox.classList.remove('valid');
                    confirmBox.classList.add('invalid');
                }
            } else {
                matchIndicator.classList.remove('show', 'valid', 'invalid');
                confirmBox.classList.remove('valid', 'invalid');
            }
        }

        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

        // Show/hide password toggle
        document.getElementById('show_password').addEventListener('change', function() {
            const passwordInputs = document.querySelectorAll('#passwordForm input[type="password"]');
            passwordInputs.forEach(input => {
                input.type = this.checked ? 'text' : 'password';
            });
        });

        // ===========================
        // THEME FUNCTIONS
        // ===========================
        function loadThemeSettings() {
            // Load saved theme settings
            const savedTheme = localStorage.getItem('theme') || 'light';
            const savedColor = localStorage.getItem('color') || 'blue';
            
            // Update theme options
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('active');
                if (option.dataset.theme === savedTheme) {
                    option.classList.add('active');
                }
            });

            // Update color options
            document.querySelectorAll('.color-option').forEach(option => {
                option.classList.remove('active');
                if (option.dataset.color === savedColor) {
                    option.classList.add('active');
                }
            });

            // Load layout options
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            const compact = localStorage.getItem('compactMode') === 'true';
            document.getElementById('sidebar-collapsed').checked = collapsed;
            document.getElementById('compact-mode').checked = compact;
        }

        // Theme option click handlers
        document.querySelectorAll('.theme-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Color option click handlers
        document.querySelectorAll('.color-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
            });
        });

        function applyTheme() {
            const activeTheme = document.querySelector('.theme-option.active');
            const activeColor = document.querySelector('.color-option.active');
            
            const theme = activeTheme ? activeTheme.dataset.theme : 'light';
            const color = activeColor ? activeColor.dataset.color : 'blue';
            const sidebarCollapsed = document.getElementById('sidebar-collapsed').checked;
            const compactMode = document.getElementById('compact-mode').checked;

            // Save settings
            localStorage.setItem('theme', theme);
            localStorage.setItem('color', color);
            localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
            localStorage.setItem('compactMode', compactMode);

            // Apply theme
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }

            // Apply color scheme
            const colors = {
                blue: '#667eea',
                green: '#11998e',
                purple: '#9c27b0',
                red: '#ff6b6b',
                orange: '#ff9a56',
                teal: '#48cae4'
            };
            document.documentElement.style.setProperty('--primary', colors[color]);
            document.documentElement.style.setProperty('--primary-dark', colors[color]);

            // Apply sidebar collapse
            const sidebar = document.getElementById('sidebar');
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }

            closeThemeModal();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Theme Applied!',
                text: 'Your theme preferences have been saved.',
                timer: 2000,
                showConfirmButton: false
            });
        }

        function resetTheme() {
            // Reset to default
            localStorage.setItem('theme', 'light');
            localStorage.setItem('color', 'blue');
            localStorage.setItem('sidebarCollapsed', 'false');
            localStorage.setItem('compactMode', 'false');

            document.body.classList.remove('dark-mode');
            document.documentElement.style.setProperty('--primary', '#667eea');
            document.documentElement.style.setProperty('--primary-dark', '#5a67d8');
            document.getElementById('sidebar').classList.remove('collapsed');

            loadThemeSettings();

            Swal.fire({
                icon: 'success',
                title: 'Theme Reset!',
                text: 'Theme has been reset to default.',
                timer: 2000,
                showConfirmButton: false
            });
        }

        // ===========================
        // NOTIFICATION FUNCTIONS
        // ===========================
        function saveNotificationSettings() {
            // Save notification settings to localStorage
            const settings = {
                emailJobUpdates: document.getElementById('email-job-updates').checked,
                emailInterviewSchedules: document.getElementById('email-interview-schedules').checked,
                emailSystemUpdates: document.getElementById('email-system-updates').checked,
                pushJobAlerts: document.getElementById('push-job-alerts').checked,
                pushMessages: document.getElementById('push-messages').checked,
                pushReminders: document.getElementById('push-reminders').checked,
                frequency: document.querySelector('input[name="notification-frequency"]:checked').value
            };

            localStorage.setItem('notificationSettings', JSON.stringify(settings));

            closeNotificationModal();

            Swal.fire({
                icon: 'success',
                title: 'Settings Saved!',
                text: 'Your notification preferences have been updated.',
                timer: 2000,
                showConfirmButton: false
            });
        }

        // Load notification settings on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedSettings = localStorage.getItem('notificationSettings');
            if (savedSettings) {
                const settings = JSON.parse(savedSettings);
                document.getElementById('email-job-updates').checked = settings.emailJobUpdates;
                document.getElementById('email-interview-schedules').checked = settings.emailInterviewSchedules;
                document.getElementById('email-system-updates').checked = settings.emailSystemUpdates;
                document.getElementById('push-job-alerts').checked = settings.pushJobAlerts;
                document.getElementById('push-messages').checked = settings.pushMessages;
                document.getElementById('push-reminders').checked = settings.pushReminders;
                
                const freqRadio = document.querySelector(`input[name="notification-frequency"][value="${settings.frequency}"]`);
                if (freqRadio) {
                    freqRadio.checked = true;
                }
            }
        });

        // ===========================
        // SIDEBAR TOGGLE
        // ===========================
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileMenuOverlay');

        document.querySelector('.bx-menu').addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('active');
                if (sidebar.classList.contains('active')) {
                    mobileOverlay.style.display = 'block';
                } else {
                    mobileOverlay.style.display = 'none';
                }
            } else {
                sidebar.classList.toggle('collapsed');
            }
        });

        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            this.style.display = 'none';
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                mobileOverlay.style.display = 'none';
            }
        });
    </script>
</body>
</html>
