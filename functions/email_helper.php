<?php
/**
 * Email Helper Functions
 * Centralized email sending with better error handling and logging
 */

/**
 * Send email with enhanced error handling and logging
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @param string $from_name Sender name
 * @param string $from_email Sender email
 * @param int|null $candidate_id Optional candidate ID for logging
 * @return array ['success' => bool, 'error' => string|null]
 */
function send_email_enhanced($to, $subject, $message, $from_name, $from_email = 'noreply@hr.qlabs.pk', $candidate_id = null) {
    // Validate inputs
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid recipient email: $to";
        if ($candidate_id) {
            log_email_activity($candidate_id, $to, $subject, 'failed', $error);
        }
        return ['success' => false, 'error' => $error];
    }
    
    if (empty($from_email) || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        $from_email = 'noreply@hr.qlabs.pk';
    }
    
    // Build headers
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=UTF-8";
    $headers[] = "From: {$from_name} <{$from_email}>";
    $headers[] = "Reply-To: {$from_email}";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    
    // Send email using PHP mail() function
    $email_sent = @mail($to, $subject, $message, implode("\r\n", $headers));
    
    // Log the email attempt
    if ($candidate_id) {
        log_email_activity($candidate_id, $to, $subject, $email_sent ? 'sent' : 'failed', $email_sent ? null : 'Mail function returned false');
    }
    
    return [
        'success' => $email_sent,
        'error' => $email_sent ? null : 'Failed to send email. Please check server configuration.'
    ];
}

/**
 * Enhanced log_email_activity with error message support
 */
function log_email_activity($candidate_id, $recipient, $subject, $status, $error_message = null) {
    try {
        require_once __DIR__ . '/db.php';
        $pdo = get_db();
        
        // Check if error_message column exists, if not, just log without it
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (candidate_id, recipient, subject, status, sent_at, user_agent, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $result = $stmt->execute([
            $candidate_id,
            $recipient,
            $subject,
            $status,
            date('Y-m-d H:i:s'),
            $user_agent,
            $ip_address
        ]);
        
        // Log to error log if failed to insert
        if (!$result) {
            error_log("Failed to log email activity: " . json_encode($pdo->errorInfo()));
        }
        
        // Also log error to PHP error log for debugging
        if ($status === 'failed' && $error_message) {
            error_log("Email failed to $recipient: $error_message");
        }
        
    } catch (Exception $e) {
        // Log the error but don't break email sending
        error_log("Email logging failed: " . $e->getMessage());
    }
}

/**
 * Test email configuration
 * @return array Status and details
 */
function test_email_configuration() {
    $config = [
        'SMTP' => ini_get('SMTP'),
        'smtp_port' => ini_get('smtp_port'),
        'sendmail_from' => ini_get('sendmail_from'),
        'sendmail_path' => ini_get('sendmail_path')
    ];
    
    $issues = [];
    
    // Check for common configuration problems
    if (strpos($config['sendmail_from'], 'wampserver.invalid') !== false) {
        $issues[] = "sendmail_from is set to invalid address: " . $config['sendmail_from'];
    }
    
    if ($config['SMTP'] === 'localhost' && $config['smtp_port'] == 25) {
        $issues[] = "Using localhost:25 which may not be configured on Windows/WAMP";
    }
    
    $status = empty($issues) ? 'configured' : 'needs_configuration';
    
    return [
        'status' => $status,
        'config' => $config,
        'issues' => $issues,
        'recommendation' => empty($issues) ? 
            'Email configuration looks good' : 
            'Configure SMTP in php.ini or use Gmail SMTP (see documentation)'
    ];
}
