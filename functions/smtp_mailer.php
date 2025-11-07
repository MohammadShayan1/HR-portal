<?php
/**
 * SMTP Email Sender using PHPMailer
 * This replaces PHP's mail() function with actual SMTP sending
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Send email via SMTP
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @param string $from_name Sender name
 * @param string $from_email Sender email
 * @return array ['success' => bool, 'error' => string|null]
 */
function send_smtp_email($to, $subject, $message, $from_name = 'HR Portal', $from_email = 'noreply@hr.qlabs.pk') {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Gmail SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'YOUR_GMAIL@gmail.com';  // Your Gmail address
        $mail->Password   = 'YOUR_APP_PASSWORD';      // Gmail App Password (not regular password!)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo($from_email, $from_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return ['success' => true, 'error' => null];
    } catch (Exception $e) {
        return ['success' => false, 'error' => "Email sending failed: {$mail->ErrorInfo}"];
    }
}
