<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is loaded
include('config.php');

if (isset($_POST['token'], $_POST['new_password'])) {
    $token = $_POST['token'];
    $rawPassword = $_POST['new_password'];
    $newPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();

    if ($reset) {
        $email = $reset['email'];

        // Update user's password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $newPassword, $email);
        $stmt->execute();

        // Delete the used token
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();

        // Send the new password via email
        $mail = new PHPMailer(true);

        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'delanideco69@gmail.com';
            $mail->Password = 'kyuqrccxdsqkkosb'; // App password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Email content
            $mail->setFrom('delanideco69@gmail.com', 'Recruitment System');
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset Successful';
            $mail->Body = "Password reset was successful.\n\nYou can now log in.";

            $mail->send();
            $_SESSION['message'] = "Password reset successful. Please check your email.";
            $_SESSION['messageClass'] = "success";
        } catch (Exception $e) {
            $_SESSION['message'] = "Password reset was successful, but the email could not be sent. Mailer Error: " . $mail->ErrorInfo;
            $_SESSION['messageClass'] = "error";
        }

    } else {
        $_SESSION['message'] = "Invalid or expired token.";
        $_SESSION['messageClass'] = "error";
    }

    // Redirect back to reset page (preserve token in case of retry)
    header("Location: reset_password.php?token=" . urlencode($token));
    exit();
}
?>