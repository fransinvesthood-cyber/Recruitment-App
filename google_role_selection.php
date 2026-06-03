<?php
session_start();
include('config.php');

// Check if user has Google signup data
if (!isset($_SESSION['google_signup'])) {
    header("Location: login_signup.php");
    exit();
}

$google_data = $_SESSION['google_signup'];
$message = "";
$messageType = "";

// Handle role selection and account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-google-role'])) {
    $role = $_POST['role'];

    // Validate role
    if (!in_array($role, ['Applicant', 'Admin', 'Consultant'])) {
        $message = "Invalid role selected.";
        $messageType = "error";
    } else {
        // Create user account
        $username = $google_data['email']; // Use email as username
        $fullname = $google_data['name'];
        $email = $google_data['email'];
        $google_id = $google_data['google_id'];

        // Generate a random password for Google users (they won't use it)
        $random_password = bin2hex(random_bytes(16));
        $hashedPassword = password_hash($random_password, PASSWORD_DEFAULT);

        // Set default values for required fields
        $gender = 'Male'; // Default, can be updated later
        $dob = null; // Can be updated later
        $phone = ''; // Can be updated later
        $address = ''; // Can be updated later

        $stmt = $conn->prepare("INSERT INTO users (role, username, fullname, gender, dob, email, phone, address, password, google_id, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)");
        $stmt->bind_param("ssssssssss", $role, $username, $fullname, $gender, $dob, $email, $phone, $address, $hashedPassword, $google_id);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id;

            // Set session variables
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['user_id'] = $user_id;

            // Clear Google signup data
            unset($_SESSION['google_signup']);

            // Set success message
            $_SESSION['message'] = "Account created successfully with Google! Welcome to our platform.";
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
            $message = "Error creating account: " . $stmt->error;
            $messageType = "error";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Role - Google Signup</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <style>
        .role-selection-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .role-selection-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .role-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-card:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .role-card.selected {
            border-color: #007bff;
            background-color: #e7f3ff;
        }

        .role-card input[type="radio"] {
            display: none;
        }

        .role-card label {
            display: block;
            cursor: pointer;
            margin: 0;
        }

        .role-title {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .role-description {
            color: #666;
            font-size: 14px;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
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
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #007bff;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="role-selection-container">
        <h2>Complete Your Google Signup</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType === 'error' ? 'danger' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <p>Welcome, <strong><?= htmlspecialchars($google_data['name']) ?></strong>! Please select your role to complete your account setup.</p>

        <form action="#" method="post" id="role-selection-form">
            <div class="role-card" onclick="selectRole('Applicant')">
                <input type="radio" id="applicant" name="role" value="Applicant" required>
                <label for="applicant">
                    <div class="role-title">Job Applicant</div>
                    <div class="role-description">Looking for job opportunities and want to apply for positions.</div>
                </label>
            </div>

            <div class="role-card" onclick="selectRole('Consultant')">
                <input type="radio" id="consultant" name="role" value="Consultant" required>
                <label for="consultant">
                    <div class="role-title">Consultant</div>
                    <div class="role-description">Professional consultant managing projects and client relationships.</div>
                </label>
            </div>

            <div class="role-card" onclick="selectRole('Admin')">
                <input type="radio" id="admin" name="role" value="Admin" required>
                <label for="admin">
                    <div class="role-title">Administrator</div>
                    <div class="role-description">System administrator managing users, jobs, and platform settings.</div>
                </label>
            </div>

            <button type="submit" name="submit-google-role" class="submit-btn" id="submit-btn" disabled>
                Create Account
            </button>
        </form>

        <div class="back-link">
            <a href="login_signup.php">← Back to Login</a>
        </div>
    </div>

    <script>
        function selectRole(role) {
            // Remove selected class from all cards
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');

            // Check the radio button
            document.getElementById(role.toLowerCase()).checked = true;

            // Enable submit button
            document.getElementById('submit-btn').disabled = false;
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener("DOMContentLoaded", function () {
            const alerts = document.querySelectorAll(".alert");
            alerts.forEach(function (alert) {
                setTimeout(() => {
                    alert.style.opacity = "0";
                    alert.style.transition = "opacity 0.5s ease-out";
                    setTimeout(() => alert.style.display = "none", 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>
