<?php
session_start();
include('config.php');

// Google OAuth setup
require_once 'vendor/autoload.php';
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

$message = "";
$messageType = "";

// Check if authorization code is present
if (isset($_GET['code'])) {
    try {
        // Exchange authorization code for access token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (!isset($token['error'])) {
            $client->setAccessToken($token['access_token']);

            // Get user info from Google
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();

            $google_id = $google_account_info->id;
            $email = $google_account_info->email;
            $name = $google_account_info->name;

            // Check if user already exists with this Google ID
            $stmt = $conn->prepare("SELECT user_id, role FROM users WHERE google_id = ?");
            $stmt->bind_param("s", $google_id);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                // User exists, log them in
                $stmt->bind_result($user_id, $role);
                $stmt->fetch();

                $_SESSION['username'] = $email; // Use email as username for Google users
                $_SESSION['role'] = $role;
                $_SESSION['user_id'] = $user_id;

                $_SESSION['message'] = "Welcome back! You have successfully logged in with Google.";
                $_SESSION['messageClass'] = "success";

                // Redirect based on role
                if ($role === 'Admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($role === 'Consultant') {
                    header("Location: consultant_dashboard.php");
                } else {
                    header("Location: applicant.php");
                }
                exit();
            } else {
                // Check if email already exists with traditional signup
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND google_id IS NULL");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    // Email exists with traditional signup
                    $message = "An account with this email already exists. Please log in using your password or reset your password if forgotten.";
                    $messageType = "error";
                } else {
                    // New Google user - store data and redirect to role selection
                    $_SESSION['google_signup'] = [
                        'google_id' => $google_id,
                        'email' => $email,
                        'name' => $name
                    ];

                    header("Location: google_role_selection.php");
                    exit();
                }
            }

            $stmt->close();
        } else {
            $message = "Google authentication failed: " . $token['error'];
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "An error occurred during Google authentication: " . $e->getMessage();
        $messageType = "error";
    }
} else {
    $message = "Authorization code not received from Google.";
    $messageType = "error";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Authentication - Recruitment System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <style>
        .auth-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }

        .auth-container h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 16px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .back-link:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Google Authentication</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType === 'error' ? 'danger' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <a href="login_signup.php" class="back-link">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Login
        </a>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds and redirect
        document.addEventListener("DOMContentLoaded", function () {
            const alerts = document.querySelectorAll(".alert");
            if (alerts.length > 0) {
                setTimeout(() => {
                    window.location.href = 'login_signup.php';
                }, 5000);
            }
        });
    </script>
</body>
</html>
