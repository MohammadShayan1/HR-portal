<?php
/**
 * Candidate Management Actions
 * Additional handlers for accept/reject/email functions
 */

/**
 * Accept candidate and set meeting link
 */
function accept_candidate() {
    $pdo = get_db();
    $user_id = get_current_user_id();
    
    $candidate_id = intval($_POST['candidate_id'] ?? 0);
    $meeting_link = trim($_POST['meeting_link'] ?? '');
    $send_email_now = isset($_POST['send_email_now']);
    
    if (!$candidate_id || !$meeting_link) {
        $_SESSION['error'] = 'Candidate ID and meeting link are required';
        header('Location: ../index.php?page=candidates&tab=pending');
        exit;
    }
    
    // Verify candidate belongs to user's jobs
    $stmt = $pdo->prepare("
        SELECT c.*, j.title as job_title, j.user_id 
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        WHERE c.id = ?
    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate || $candidate['user_id'] != $user_id) {
        $_SESSION['error'] = 'Candidate not found or access denied';
        header('Location: ../index.php?page=candidates&tab=pending');
        exit;
    }
    
    // Update candidate status
    $stmt = $pdo->prepare("
        UPDATE candidates 
        SET candidate_status = 'accepted', 
            meeting_link = ?
        WHERE id = ?
    ");
    $stmt->execute([$meeting_link, $candidate_id]);
    
    // Send email if requested
    if ($send_email_now) {
        $email_sent = send_meeting_invitation_email($candidate_id);
        if ($email_sent) {
            $stmt = $pdo->prepare("UPDATE candidates SET email_sent_at = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d H:i:s'), $candidate_id]);
        }
    }
    
    header('Location: ../index.php?page=candidates&tab=accepted&success=1&message=' . urlencode('Candidate accepted successfully!'));
    exit;
}

/**
 * Reject candidate with optional reason
 */
function reject_candidate() {
    $pdo = get_db();
    $user_id = get_current_user_id();
    
    $candidate_id = intval($_POST['candidate_id'] ?? 0);
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    
    if (!$candidate_id) {
        $_SESSION['error'] = 'Candidate ID is required';
        header('Location: ../index.php?page=candidates&tab=pending');
        exit;
    }
    
    // Verify candidate belongs to user's jobs
    $stmt = $pdo->prepare("
        SELECT c.*, j.user_id 
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        WHERE c.id = ?
    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate || $candidate['user_id'] != $user_id) {
        $_SESSION['error'] = 'Candidate not found or access denied';
        header('Location: ../index.php?page=candidates&tab=pending');
        exit;
    }
    
    // Update candidate status
    $stmt = $pdo->prepare("
        UPDATE candidates 
        SET candidate_status = 'rejected', 
            rejection_reason = ?
        WHERE id = ?
    ");
    $stmt->execute([$rejection_reason, $candidate_id]);
    
    header('Location: ../index.php?page=candidates&tab=rejected&success=1&message=' . urlencode('Candidate rejected'));
    exit;
}

/**
 * Update meeting link for accepted candidate
 */
function update_meeting_link() {
    $pdo = get_db();
    $user_id = get_current_user_id();
    
    $candidate_id = intval($_POST['candidate_id'] ?? 0);
    $meeting_link = trim($_POST['meeting_link'] ?? '');
    
    if (!$candidate_id || !$meeting_link) {
        $_SESSION['error'] = 'Candidate ID and meeting link are required';
        header('Location: ../index.php?page=candidates&tab=accepted');
        exit;
    }
    
    // Verify candidate belongs to user's jobs
    $stmt = $pdo->prepare("
        SELECT c.*, j.user_id 
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        WHERE c.id = ? AND c.candidate_status = 'accepted'
    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate || $candidate['user_id'] != $user_id) {
        $_SESSION['error'] = 'Candidate not found or access denied';
        header('Location: ../index.php?page=candidates&tab=accepted');
        exit;
    }
    
    // Update meeting link
    $stmt = $pdo->prepare("UPDATE candidates SET meeting_link = ? WHERE id = ?");
    $stmt->execute([$meeting_link, $candidate_id]);
    
    header('Location: ../index.php?page=candidates&tab=accepted&success=1&message=' . urlencode('Meeting link updated!'));
    exit;
}

/**
 * Send meeting invitation email
 */
function send_meeting_email_action() {
    header('Content-Type: application/json');
    
    $pdo = get_db();
    $user_id = get_current_user_id();
    
    $candidate_id = intval($_POST['candidate_id'] ?? 0);
    
    if (!$candidate_id) {
        echo json_encode(['error' => 'Candidate ID is required']);
        exit;
    }
    
    // Verify candidate belongs to user's jobs and is accepted
    $stmt = $pdo->prepare("
        SELECT c.*, j.title as job_title, j.user_id, u.company_name, u.email as company_email
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        JOIN users u ON j.user_id = u.id
        WHERE c.id = ? AND c.candidate_status = 'accepted'
    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate || $candidate['user_id'] != $user_id) {
        echo json_encode(['error' => 'Candidate not found or access denied']);
        exit;
    }
    
    if (!$candidate['meeting_link']) {
        echo json_encode(['error' => 'No meeting link set for this candidate']);
        exit;
    }
    
    if ($candidate['email_sent_at']) {
        echo json_encode(['error' => 'Email already sent to this candidate']);
        exit;
    }
    
    // Send email
    $email_sent = send_meeting_invitation_email($candidate_id);
    
    if ($email_sent) {
        $stmt = $pdo->prepare("UPDATE candidates SET email_sent_at = ? WHERE id = ?");
        $stmt->execute([date('Y-m-d H:i:s'), $candidate_id]);
        
        echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
    } else {
        echo json_encode(['error' => 'Failed to send email. Please check your server mail configuration.']);
    }
    exit;
}

/**
 * Move rejected candidate back to accepted
 */
function move_to_accepted() {
    $pdo = get_db();
    $user_id = get_current_user_id();
    
    $candidate_id = intval($_GET['candidate_id'] ?? 0);
    
    if (!$candidate_id) {
        header('Location: ../index.php?page=candidates&tab=rejected');
        exit;
    }
    
    // Verify candidate belongs to user's jobs
    $stmt = $pdo->prepare("
        SELECT c.*, j.user_id 
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        WHERE c.id = ? AND c.candidate_status = 'rejected'
    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate || $candidate['user_id'] != $user_id) {
        header('Location: ../index.php?page=candidates&tab=rejected');
        exit;
    }
    
    // Move back to accepted
    $stmt = $pdo->prepare("
        UPDATE candidates 
        SET candidate_status = 'accepted', 
            rejection_reason = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$candidate_id]);
    
    header('Location: ../index.php?page=candidates&tab=accepted&success=1&message=' . urlencode('Candidate moved to accepted'));
    exit;
}

/**
 * Helper function to send meeting invitation email
 */
function send_meeting_invitation_email($candidate_id) {
    $pdo = get_db();
    
    $stmt = $pdo->prepare("
        SELECT c.*, j.title as job_title, u.company_name, u.email as company_email
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        JOIN users u ON j.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        return false;
    }
    
    $to = $candidate['email'];
    $subject = "Congratulations! Interview Invitation - " . $candidate['job_title'];
    
    $company_name = $candidate['company_name'] ?: 'Our Company';
    $company_email = $candidate['company_email'];
    $meeting_link = $candidate['meeting_link'];
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background-color: #f8f9fa; }
            .meeting-link { background-color: #0d6efd; color: white; padding: 15px; text-align: center; margin: 20px 0; border-radius: 5px; }
            .meeting-link a { color: white; text-decoration: none; font-weight: bold; font-size: 18px; }
            .footer { padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Interview Invitation</h1>
            </div>
            <div class='content'>
                <p>Dear {$candidate['name']},</p>
                
                <p>Congratulations! We are pleased to inform you that you have been selected for the next round of interviews for the position of <strong>{$candidate['job_title']}</strong> at {$company_name}.</p>
                
                <p>Based on your application and interview performance, we would like to invite you to a virtual meeting with our team.</p>
                
                <div class='meeting-link'>
                    <a href='{$meeting_link}' target='_blank'>JOIN MEETING</a>
                </div>
                
                <p><strong>Meeting Link:</strong><br>
                <a href='{$meeting_link}'>{$meeting_link}</a></p>
                
                <p><strong>What to expect:</strong></p>
                <ul>
                    <li>Duration: Approximately 30-45 minutes</li>
                    <li>Format: Virtual video call</li>
                    <li>Discussion: Role expectations, technical skills, and team culture</li>
                </ul>
                
                <p><strong>Preparation tips:</strong></p>
                <ul>
                    <li>Test your audio and video before the meeting</li>
                    <li>Ensure stable internet connection</li>
                    <li>Find a quiet, well-lit space</li>
                    <li>Have a copy of your resume handy</li>
                    <li>Prepare questions about the role</li>
                </ul>
                
                <p>If you have any questions or need to reschedule, please don't hesitate to reach out to us.</p>
                
                <p>We look forward to speaking with you!</p>
                
                <p>Best regards,<br>
                <strong>{$company_name}</strong><br>
                {$company_email}</p>
            </div>
            <div class='footer'>
                <p>This is an automated email from the HR Portal. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Use enhanced email sending with better error handling
    require_once __DIR__ . '/email_helper.php';
    $result = send_email_enhanced($to, $subject, $message, $company_name . ' HR Team', $company_email, $candidate_id);
    
    return $result['success'];
}

/**
 * Log email activity for tracking
 */
function log_email_activity($candidate_id, $recipient, $subject, $status) {
    try {
        $pdo = get_db();
        
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (candidate_id, recipient, subject, status, sent_at, user_agent, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $stmt->execute([
            $candidate_id,
            $recipient,
            $subject,
            $status,
            date('Y-m-d H:i:s'),
            $user_agent,
            $ip_address
        ]);
    } catch (Exception $e) {
        // Silently fail logging to not break email sending
        error_log("Email logging failed: " . $e->getMessage());
    }
}
