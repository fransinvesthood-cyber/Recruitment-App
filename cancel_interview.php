<?php
include 'config.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; //Ensure PHPMailer is installed via Composer

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $interview_id = $_POST['interview_id'];
    $cancellation_reason = $_POST['cancellation_reason'];

    //Update interview status in the database
    $sql = "UPDATE interviews SET interview_status = 'Cancelled', cancellation_reason = ? WHERE interview_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("si", $cancellation_reason, $interview_id);
        $stmt->execute();
        $stmt->close();

        //Retrieve applicant's email and job details
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
            $subject = "Interview Cancelled: " . $applicant['position'];
            $message = "
                <html>
                <head>
                    <title>Interview Cancelled</title>
                </head>
                <body>
                    <p>Dear {$applicant['fullname']},</p>
                    <p>We regret to inform you that your interview for <strong>{$applicant['position']}</strong> position at <strong>{$applicant['company_name']}</strong> has been cancelled.</p>
                    <p><strong>Reason to cancel:</strong> $cancellation_reason</p>
                    <p>We apologize for any inconvenience this may cause. If there are any future opportunities, we will be in touch.</p>
                    <p>Thank you for your time and interest in our company.</p>
                    <p>Best regards,<br>HR Team</p>
                </body>
                </html>";

            //Send Email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; //Replace with your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'delanideco69@gmail.com'; //Your email
                $mail->Password = 'kyuqrccxdsqkkosb'; //Your email password (Consider using app passwords)
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('delanideco69@gmail.com', 'HR Team');
                $mail->addAddress($to, $applicant['fullname']);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;

                $mail->send();

                // Notification insert removed to avoid FK violation:
                // notifications.reference_id is constrained to job_applications.application_id.
                // For interview cancellations, we do not reliably have a matching application_id here.

                echo "<script>alert('Interview cancelled successfully, email sent.'); window.location.href = 'scheduled_interviews.php';</script>";
            } catch (Exception $e) {
                echo "<script>alert('Interview cancelled but email could not be sent. Error: " . addslashes($mail->ErrorInfo) . "'); window.location.href = 'scheduled_interviews.php';</script>";
            }
        } else {
            echo "<script>alert('Interview canceled, but applicant details not found.'); window.location.href = 'scheduled_interviews.php';</script>";
        }
        exit();
    } else {
        die("Error canceling interview: " . $conn->error);
    }
}
?>