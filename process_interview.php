<?php
session_start();
include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer and Google API are installed

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $job_id = $_POST['job_id'];
    $company_address = $_POST['company_address'] ?? '';
    $interview_date = $_POST['interview_date'];
    $interviewer = $_POST['interviewer'];
    $interview_type = $_POST['interview_type'] ?? 'In-person';
    $meeting_link = $_POST['meeting_link'] ?? null;
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 30);

    // Get the logged-in company's ID
    $company_id = $_SESSION['company_id'] ?? null;

    // Validate required fields
    if (empty($user_id) || empty($job_id) || empty($interview_date) || empty($interviewer)) {
        echo "<script>alert('Please fill in all required fields.'); window.location.href = 'schedule_interview.php';</script>";
        exit();
    }

    // Check if the applicant is shortlisted
    $checkShortlist = $conn->prepare("SELECT application_id FROM job_applications WHERE user_id = ? AND job_id = ? AND application_status = 'Shortlisted'");
    if (!$checkShortlist) {
        die("Error preparing statement: " . $conn->error);
    }
    $checkShortlist->bind_param("ii", $user_id, $job_id);
    $checkShortlist->execute();
    $result = $checkShortlist->get_result();

    if ($result->num_rows == 0) {
        echo "<script>alert('This applicant has not been shortlisted and cannot be scheduled for an interview.'); window.location.href = 'schedule_interview.php';</script>";
        exit();
    }
    $checkShortlist->close();

    // Check if the applicant already has an active interview scheduled for the same position
    $checkDuplicate = $conn->prepare("SELECT interview_id FROM interviews WHERE user_id = ? AND job_id = ? AND interview_status NOT IN ('Cancelled', 'Completed')");
    if (!$checkDuplicate) {
        die("Error preparing statement: " . $conn->error);
    }
    $checkDuplicate->bind_param("ii", $user_id, $job_id);
    $checkDuplicate->execute();
    $result = $checkDuplicate->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('This applicant already has an active interview scheduled for this position.'); window.location.href = 'schedule_interview.php';</script>";
        exit();
    }
    $checkDuplicate->close();

    // Check for double-booking with same interviewers
    $req_start = new DateTime($interview_date);
    $req_end = clone $req_start;
    $req_end->modify("+{$duration_minutes} minutes");

    $checkConflict = $conn->prepare("
        SELECT interview_id, interview_date, duration_minutes, interviewer 
        FROM interviews 
        WHERE interview_status NOT IN ('Cancelled', 'Completed')
        AND interview_date BETWEEN ? AND ?
    ");
    $window_start = (clone $req_start)->modify('-2 hours')->format('Y-m-d H:i:s');
    $window_end = (clone $req_end)->modify('+2 hours')->format('Y-m-d H:i:s');
    $checkConflict->bind_param("ss", $window_start, $window_end);
    $checkConflict->execute();
    $conflictResult = $checkConflict->get_result();

    $interviewer_names = array_map('strtolower', array_map('trim', explode(',', $interviewer)));
    while ($c = $conflictResult->fetch_assoc()) {
        $exist_start = new DateTime($c['interview_date']);
        $exist_dur = (int)($c['duration_minutes'] ?? 30);
        $exist_end = clone $exist_start;
        $exist_end->modify("+{$exist_dur} minutes");

        if ($req_start < $exist_end && $req_end > $exist_start) {
            $exist_names = array_map('strtolower', array_map('trim', explode(',', $c['interviewer'])));
            foreach ($interviewer_names as $name) {
                if (in_array($name, $exist_names)) {
                    echo "<script>alert('Double-booking detected: Interviewer \\\"$name\\\" already has an interview during this time slot.'); window.location.href = 'schedule_interview.php';</script>";
                    exit();
                }
            }
        }
    }
    $checkConflict->close();

    // Insert interview into the database
    $sql = "INSERT INTO interviews 
            (user_id, job_id, interview_date, company_address, interview_status, interviewer, interview_type, meeting_link, duration_minutes) 
            VALUES (?, ?, ?, ?, 'Scheduled', ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("iisssssi", $user_id, $job_id, $interview_date, $company_address, $interviewer, $interview_type, $meeting_link, $duration_minutes);
    if ($stmt->execute()) {
        $interview_id = $stmt->insert_id;
        $stmt->close();

        // Get applicant and job info for email
        $infoQuery = $conn->prepare("
            SELECT u.email, u.fullname, j.position, c.company_name
            FROM users u
            JOIN job_postings j ON j.job_id = ?
            JOIN companies c ON j.company_id = c.company_id
            WHERE u.user_id = ?
        ");
        $infoQuery->bind_param("ii", $job_id, $user_id);
        $infoQuery->execute();
        $result = $infoQuery->get_result();
        $applicant = $result->fetch_assoc();
        $infoQuery->close();

        if ($applicant) {
            $to = $applicant['email'];
            $subject = "Interview Scheduled: " . $applicant['position'];
            $formatted_date = date('F j, Y, g:i a', strtotime($interview_date));
            $end_time = (new DateTime($interview_date))->modify("+{$duration_minutes} minutes")->format('g:i A');
            $location_text = ($interview_type === 'Online') 
                ? "<p><strong>Meeting Link:</strong> " . ($meeting_link ?: 'Will be shared shortly') . "</p>"
                : "<p><strong>Address:</strong> {$company_address}</p>";

            $message = "
                <html>
                <head><title>Interview Invitation</title></head>
                <body>
                    <p>Dear {$applicant['fullname']},</p>
                    <p>You have been scheduled for an interview for the position of
                    <strong>{$applicant['position']}</strong> at <strong>{$applicant['company_name']}</strong>.</p>
                    <p><strong>Date & Time:</strong> {$formatted_date} - {$end_time}</p>
                    <p><strong>Duration:</strong> {$duration_minutes} minutes</p>
                    <p><strong>Type:</strong> {$interview_type}</p>
                    {$location_text}
                    <p><strong>Interviewer(s):</strong> {$interviewer}</p>
                    <p>Please confirm your availability. We look forward to meeting you!</p>
                    <p>Best regards,<br>HR Team</p>
                </body>
                </html>";

            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'delanideco69@gmail.com';
                $mail->Password = 'kyuqrccxdsqkkosb';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('delanideco69@gmail.com', 'HR Team');
                $mail->addAddress($to, $applicant['fullname']);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;

                $mail->send();

                // Insert notification for the admin
                $notification_message = "New interview scheduled for " . htmlspecialchars($applicant['fullname']) . " - " . htmlspecialchars($applicant['position']);
                $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, reference_id, is_read, created_at) VALUES (?, ?, 'interview', NULL, 0, NOW())");
                $notification_stmt->bind_param("is", $_SESSION['user_id'], $notification_message);
                $notification_stmt->execute();
                $notification_stmt->close();

                echo "<script>alert('Interview scheduled and email sent successfully.'); window.location.href = 'schedule_interview.php';</script>";
            } catch (Exception $e) {
                echo "<script>alert('Interview scheduled, but email could not be sent. Error: " . addslashes($mail->ErrorInfo) . "'); window.location.href = 'scheduled_interviews.php';</script>";
            }
        } else {
            echo "<script>alert('Interview scheduled, but applicant details not found.'); window.location.href = 'schedule_interview.php';</script>";
        }
    } else {
        echo "<script>alert('Error scheduling interview: " . addslashes($conn->error) . "'); window.location.href = 'schedule_interview.php';</script>";
    }
}
?>
