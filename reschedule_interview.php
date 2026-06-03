<?php
include 'config.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $interview_id = $_POST['interview_id'];
    $new_date = $_POST['new_date'];
    $reschedule_reason = $_POST['reschedule_reason'];
    $interviewer = isset($_POST['interviewer']) ? $_POST['interviewer'] : null;

    // Update the interview date and status in the database
    $sql = "UPDATE interviews SET interview_date = ?, interview_status = 'Rescheduled', reschedule_reason = ?, interviewer = ? WHERE interview_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sssi", $new_date, $reschedule_reason, $interviewer, $interview_id);
        $stmt->execute();
        $stmt->close();

        // Retrieve applicant's email
        $query = "SELECT u.email, u.fullname, j.position, c.company_name, i.company_address, i.interviewer
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
            $subject = "Interview Rescheduled: " . $applicant['position'];
            $message = "
                <html>
                <head>
                    <title>Interview Rescheduled</title>
                </head>
                <body>
                    <p>Dear {$applicant['fullname']},</p>
                    <p>We hope this email finds you in good health. We are writing this email to inform you that your interview for the
                    position of <strong>{$applicant['position']}</strong> at <strong>{$applicant['company_name']}</strong> has been rescheduled.
                    Please find the new interview details below:</p>
                    <p><strong>New Date & Time:</strong> " . date('F j, Y, g:i a', strtotime($new_date)) . "</p>
                    <p><strong>Address:</strong> {$applicant['company_address']}</p>
                    <p><strong>Interviewer(s):</strong> {$applicant['interviewer']}</p>
                    <p><strong>Reason to reschedule:</strong> $reschedule_reason</p>
                    <p>We apologize for any inconvenience this may cause and appreciate your flexibility. Please confirm your availability for the
                    new schedule or let us know if another time works better for you.</p>
                    <p>Looking forward to speaking with you soon!</p>
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
                $mail->Password = 'kyuqrccxdsqkkosb'; //Your email password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('delanideco69@gmail.com', 'HR Team');
                $mail->addAddress($to, $applicant['fullname']);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;

                $mail->send();

                // Insert notification for the admin
                $notification_message = "Interview rescheduled for " . htmlspecialchars($applicant['fullname']) . " - " . htmlspecialchars($applicant['position']);
                $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, reference_id, is_read, created_at) VALUES (?, ?, 'interview', NULL, 0, NOW())");
                $notification_stmt->bind_param("is", $_SESSION['user_id'], $notification_message);
                $notification_stmt->execute();
                $notification_stmt->close();

                echo "<script>alert('Interview rescheduled successfully, email sent.'); window.location.href = 'scheduled_interviews.php';</script>";
            } catch (Exception $e) {
                echo "<script>alert('Interview rescheduled but email could not be sent. Error: " . addslashes($mail->ErrorInfo) . "'); window.location.href = 'scheduled_interviews.php';</script>";
            }
        } else {
            echo "<script>alert('Interview rescheduled, but applicant details not found.'); window.location.href = 'scheduled_interviews.php';</script>";
        }
        exit();
    } else {
        die("Error rescheduling interview: " . $conn->error);
    }
}
?>