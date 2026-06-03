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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <style>
        .verification-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }

        .verification-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .verification-icon.success {
            color: #28a745;
        }

        .verification-icon.error {
            color: #dc3545;
        }

        .verification-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        .verification-message {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #666;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #0056b3;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: left;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <?php if (!empty($message)): ?>
            <div class="alert <?= $messageType === 'error' ? 'alert-danger' : 'alert-success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($messageType === 'success'): ?>
            <div class="verification-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="verification-title">Email Verified!</h1>
            <p class="verification-message">
                Your email address has been successfully verified. You can now log in to your account and start using our platform.
            </p>
            <a href="login_signup.php" class="btn">Go to Login</a>
        <?php else: ?>
            <div class="verification-icon error">
                <i class="fas fa-times-circle"></i>
            </div>
            <h1 class="verification-title">Verification Failed</h1>
            <p class="verification-message">
                The verification link is invalid or has expired. Please try registering again or contact support if the problem persists.
            </p>
            <a href="login_signup.php" class="btn">Back to Registration</a>
        <?php endif; ?>
    </div>
</body>
</html>
