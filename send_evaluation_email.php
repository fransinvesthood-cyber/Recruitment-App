<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendEvaluationEmail($conn, $application_id, $evaluation_result) {
    // Get applicant details
    $sql = "SELECT u.fullname, u.email, ja.position, c.company_name
            FROM job_applications ja
            JOIN users u ON ja.user_id = u.user_id
            JOIN job_postings j ON ja.job_id = j.job_id
            JOIN companies c ON j.company_id = c.company_id
            WHERE ja.application_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Applicant not found'];
    }

    $applicant = $result->fetch_assoc();
    $stmt->close();

    $applicant_name = $applicant['fullname'];
    $applicant_email = $applicant['email'];
    $position = $applicant['position'];
    $company = $applicant['company_name'];
    $status = $evaluation_result['status'];

    // Create email content based on status
    if ($status === 'Shortlisted') {
        $subject = "Congratulations! You've been shortlisted for {$position} at {$company}";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                .highlight { color: #28a745; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🎉 Congratulations!</h1>
                <p>You've been shortlisted for the next round</p>
            </div>
            <div class='content'>
                <p>Dear <strong>{$applicant_name}</strong>,</p>

                <p>Great news! After careful review of your application for the <strong>{$position}</strong> position at <strong>{$company}</strong>, we are pleased to inform you that you have been <span class='highlight'>shortlisted</span> for the next stage of our recruitment process.</p>

                <p><strong>What happens next?</strong></p>
                <ul>
                    <li>You will receive an invitation for an interview soon</li>
                    <li>Our HR team will contact you to schedule a suitable time</li>
                    <li>Please keep an eye on your email for further instructions</li>
                </ul>

                <p>We were impressed by your qualifications and experience. We look forward to learning more about you in the upcoming interview.</p>

                <p>Best regards,<br>
                <strong>HR Team</strong><br>
                {$company}</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </body>
        </html>
        ";
    } else { // Rejected
        $subject = "Update on your application for {$position} at {$company}";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                .highlight { color: #dc3545; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Application Update</h1>
                <p>Thank you for your interest in our company</p>
            </div>
            <div class='content'>
                <p>Dear <strong>{$applicant_name}</strong>,</p>

                <p>Thank you for your interest in the <strong>{$position}</strong> position at <strong>{$company}</strong> and for taking the time to submit your application.</p>

                <p>After careful consideration of all applications received, we regret to inform you that we have decided not to proceed with your application at this time.</p>

                <p>We appreciate your interest in joining our team and encourage you to apply for future opportunities that match your qualifications and experience.</p>

                <p>We wish you the best in your career endeavors.</p>

                <p>Best regards,<br>
                <strong>HR Team</strong><br>
                {$company}</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </body>
        </html>
        ";
    }

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'delanideco69@gmail.com'; // Your email
        $mail->Password = 'kyuqrccxdsqkkosb'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('delanideco69@gmail.com', 'HR Team - ' . $company);
        $mail->addAddress($applicant_email, $applicant_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];

    } catch (Exception $e) {
        error_log("Email sending failed for application {$application_id}: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo];
    }
}
?>
