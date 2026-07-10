<?php
declare(strict_types=1);

/**
 * Central PHPMailer SMTP configuration + helper send method.
 *
 * This file exists to avoid duplicating SMTP code across the project.
 *
 * IMPORTANT:
 * - Do NOT use PHP's mail() anywhere in this project.
 * - This service uses Gmail SMTP and the same credentials already used elsewhere.
 */

namespace Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

final class EmailService
{
    private static function createBaseMailer(): PHPMailer
    {
        // Use exceptions for meaningful error handling.
        $mail = new PHPMailer(true);

        // Gmail SMTP (matches existing working PHPMailer usage).
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        // Gmail credentials already used elsewhere in this project
        $mail->Username = 'delanideco69@gmail.com';
        $mail->Password = 'kyuqrccxdsqkkosb';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Common headers / defaults.
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(false); // default to plain text; caller can override.

        return $mail;
    }

    /**
     * Send a text email.
     *
     * @throws Exception Re-throws PHPMailer exceptions so callers can return the exception message.
     */
    public static function sendText(string $toEmail, string $toName, string $subject, string $bodyText, string $fromEmail, string $fromName): void
    {
        $mail = self::createBaseMailer();

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $bodyText;

        $mail->send();
    }

    /**
     * Send an HTML email.
     *
     * @throws Exception Re-throws PHPMailer exceptions so callers can return the exception message.
     */
    public static function sendHtml(string $toEmail, string $toName, string $subject, string $htmlBody, string $fromEmail, string $fromName): void
    {
        $mail = self::createBaseMailer();

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
    }
}

