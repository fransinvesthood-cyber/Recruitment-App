<?php
include 'config.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $interview_id = $_POST['interview_id'];

    // Update interview status to 'Completed' in the database
    $sql = "UPDATE interviews SET interview_status = 'Completed' WHERE interview_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $interview_id);
        $stmt->execute();
        $stmt->close();

        // Retrieve applicant's email and relevant info
        $query = "SELECT u.email, u.fullname, j.position, c.company_name
                  FROM interviews i
                  JOIN users u ON i.user_id = u.user_id
                  JOIN job_postings j ON i.job_id = j.job_id
                  JOIN companies c ON j.company_id = c.company_id
                  WHERE i.interview_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $interview_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $applicant = $result->fetch_assoc();
        $stmt->close();

        if ($applicant) {
            $to = $applicant['email'];
            $subject = "Interview Feedback - " . $applicant['position'];
            $message = "
                <html>
                <head>
                    <title>Thank You for Attending</title>
                </head>
                <body>
                    <p>Dear {$applicant['fullname']},</p>
                    <p>Thank you for attending your interview for the <strong>{$applicant['position']}</strong> position at <strong>{$applicant['company_name']}</strong>.</p>
                    <p>We appreciate the time and effort you put into the interview process. Our team will review your interview and get back to you with the next steps soon.</p>
                    <p>If you have any questions or would like to provide feedback, feel free to reply to this email.</p>
                    <p>Best regards,<br>HR Team</p>
                </body>
                </html>";

            //Send Email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'delanideco69@gmail.com'; // Your email
                $mail->Password = 'kyuqrccxdsqkkosb'; // Your app password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('delanideco69@gmail.com', 'HR Team');
                $mail->addAddress($to, $applicant['fullname']);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;

                $mail->send();
                echo "<script>alert('Interview completed and confirmation email sent.'); window.location.href = 'scheduled_interviews.php';</script>";
            } catch (Exception $e) {
                echo "<script>alert('Interview completed but email could not be sent. Error: " . addslashes($mail->ErrorInfo) . "'); window.location.href = 'scheduled_interviews.php';</script>";
            }
        } else {
            echo "<script>alert('Interview completed, but applicant details not found.'); window.location.href = 'scheduled_interviews.php';</script>";
        }
        exit();
    } else {
        die("Error updating interview status: " . $conn->error);
    }
}
?>