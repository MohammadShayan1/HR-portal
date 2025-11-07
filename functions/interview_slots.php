<?php
/**
 * Interview Slots Functions
 * Handle slot creation, booking, and management
 */

/**
 * Create multiple interview slots
 */
function create_interview_slots() {
    $user_id = get_current_user_id();
    $pdo = get_db();
    
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? $start_date;
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $duration = intval($_POST['duration'] ?? 30);
    $create_zoom = isset($_POST['create_zoom']) && $_POST['create_zoom'] === 'yes';
    $days = $_POST['days'] ?? [1, 2, 3, 4, 5]; // Default to weekdays
    
    if (empty($start_date) || empty($start_time) || empty($end_time)) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $slots_created = 0;
    $zoom_error = null;
    $zoom_failed_count = 0;
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    while ($current_date <= $end_date_obj) {
        $day_of_week = $current_date->format('w'); // 0 = Sunday, 1 = Monday, etc.
        
        // Check if this day is selected
        if (in_array($day_of_week, $days)) {
            // Generate time slots for this day
            $current_time = new DateTime($start_time);
            $end_time_obj = new DateTime($end_time);
            
            while ($current_time < $end_time_obj) {
                $meeting_link = '';
                
                // Create Zoom meeting if requested
                if ($create_zoom) {
                    require_once __DIR__ . '/zoom.php';
                    $slot_datetime = $current_date->format('Y-m-d') . 'T' . $current_time->format('H:i:s');
                    $meeting_title = "Interview Slot - " . $current_date->format('M d, Y') . " at " . $current_time->format('g:i A');
                    
                    $zoom_result = create_zoom_meeting($meeting_title, $slot_datetime, $duration, $user_id);
                    
                    if (isset($zoom_result['success']) && $zoom_result['success'] && isset($zoom_result['join_url'])) {
                        $meeting_link = $zoom_result['join_url'];
                    } else {
                        // Store the first error message
                        if ($zoom_error === null && isset($zoom_result['error'])) {
                            $zoom_error = $zoom_result['error'];
                        }
                        $zoom_failed_count++;
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO interview_slots (user_id, slot_date, slot_time, duration, meeting_link, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'available', ?)
                ");
                
                $stmt->execute([
                    $user_id,
                    $current_date->format('Y-m-d'),
                    $current_time->format('H:i:s'),
                    $duration,
                    $meeting_link,
                    date('Y-m-d H:i:s')
                ]);
                
                $slots_created++;
                $current_time->modify("+{$duration} minutes");
            }
        }
        
        $current_date->modify('+1 day');
    }
    
    $response = [
        'success' => true,
        'count' => $slots_created,
        'zoom_enabled' => $create_zoom
    ];
    
    // Add warning if Zoom meetings failed
    if ($create_zoom && $zoom_failed_count > 0) {
        $response['warning'] = "Created $slots_created slots, but $zoom_failed_count Zoom meetings failed to create.";
        $response['zoom_error'] = $zoom_error;
    }
    
    echo json_encode($response);
}

/**
 * Delete an interview slot
 */
function delete_interview_slot() {
    $user_id = get_current_user_id();
    $pdo = get_db();
    
    $slot_id = $_GET['slot_id'] ?? '';
    
    // Check if slot belongs to user and is available
    $stmt = $pdo->prepare("SELECT status FROM interview_slots WHERE id = ? AND user_id = ?");
    $stmt->execute([$slot_id, $user_id]);
    $slot = $stmt->fetch();
    
    if (!$slot) {
        echo json_encode(['error' => 'Slot not found']);
        exit;
    }
    
    if ($slot['status'] !== 'available') {
        echo json_encode(['error' => 'Cannot delete booked slots']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM interview_slots WHERE id = ? AND user_id = ?");
    $stmt->execute([$slot_id, $user_id]);
    
    echo json_encode(['success' => true]);
}

/**
 * Get booking details for a slot
 */
function get_booking_details() {
    $user_id = get_current_user_id();
    $pdo = get_db();
    
    $slot_id = $_GET['slot_id'] ?? '';
    
    if (empty($slot_id)) {
        echo json_encode(['error' => 'Missing slot ID']);
        exit;
    }
    
    // Get slot with candidate information
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            c.name as candidate_name,
            c.email as candidate_email,
            c.phone as candidate_phone,
            j.title as job_title
        FROM interview_slots s
        LEFT JOIN candidates c ON s.candidate_id = c.id
        LEFT JOIN jobs j ON c.job_id = j.id
        WHERE s.id = ? AND s.user_id = ?
    ");
    $stmt->execute([$slot_id, $user_id]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$slot) {
        echo json_encode(['error' => 'Slot not found']);
        exit;
    }
    
    // Format the response
    $response = [
        'slot_date' => date('F j, Y', strtotime($slot['slot_date'])),
        'slot_time' => date('h:i A', strtotime($slot['slot_time'])),
        'duration' => $slot['duration'],
        'status' => ucfirst($slot['status']),
        'meeting_link' => $slot['meeting_link'],
        'candidate_name' => $slot['candidate_name'],
        'candidate_email' => $slot['candidate_email'],
        'candidate_phone' => $slot['candidate_phone'],
        'job_title' => $slot['job_title'],
        'booked_at' => $slot['booked_at']
    ];
    
    echo json_encode($response);
}

/**
 * Get available slots for a candidate
 */
function get_available_slots_for_candidate() {
    $scheduling_token = $_GET['token'] ?? '';
    $pdo = get_db();
    
    if (empty($scheduling_token)) {
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    // Get candidate info
    $stmt = $pdo->prepare("
        SELECT c.*, j.user_id, j.title as job_title 
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        WHERE c.scheduling_token = ?
    ");
    $stmt->execute([$scheduling_token]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    // Get available slots for this user (next 30 days)
    $stmt = $pdo->prepare("
        SELECT * FROM interview_slots 
        WHERE user_id = ? 
        AND status = 'available'
        AND slot_date >= date('now')
        AND slot_date <= date('now', '+30 days')
        ORDER BY slot_date, slot_time
    ");
    $stmt->execute([$candidate['user_id']]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'candidate' => [
            'name' => $candidate['name'],
            'email' => $candidate['email'],
            'job_title' => $candidate['job_title']
        ],
        'slots' => $slots
    ]);
}

/**
 * Book an interview slot
 */
function book_interview_slot() {
    $pdo = get_db();
    
    $slot_id = $_POST['slot_id'] ?? '';
    $scheduling_token = $_POST['token'] ?? '';
    
    if (empty($slot_id) || empty($scheduling_token)) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    // Get candidate
    $stmt = $pdo->prepare("SELECT id, name, email FROM candidates WHERE scheduling_token = ?");
    $stmt->execute([$scheduling_token]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    // Check if slot is available
    $stmt = $pdo->prepare("SELECT * FROM interview_slots WHERE id = ? AND status = 'available'");
    $stmt->execute([$slot_id]);
    $slot = $stmt->fetch();
    
    if (!$slot) {
        echo json_encode(['error' => 'Slot not available']);
        exit;
    }
    
    // Book the slot
    $pdo->beginTransaction();
    try {
        // Update slot
        $stmt = $pdo->prepare("
            UPDATE interview_slots 
            SET candidate_id = ?, status = 'booked', booked_at = ?
            WHERE id = ? AND status = 'available'
        ");
        $stmt->execute([$candidate['id'], date('Y-m-d H:i:s'), $slot_id]);
        
        // Update candidate
        $stmt = $pdo->prepare("
            UPDATE candidates 
            SET slot_id = ?, meeting_link = ?, candidate_status = 'scheduled'
            WHERE id = ?
        ");
        $stmt->execute([$slot_id, $slot['meeting_link'], $candidate['id']]);
        
        $pdo->commit();
        
        // Get user_id for calendar sync
        $stmt = $pdo->prepare("
            SELECT j.user_id FROM candidates c
            JOIN jobs j ON c.job_id = j.id
            WHERE c.id = ?
        ");
        $stmt->execute([$candidate['id']]);
        $user_data = $stmt->fetch();
        $user_id = $user_data['user_id'];
        
        // Sync to calendar if enabled
        $google_sync = get_setting('google_calendar_sync', $user_id) === '1';
        $outlook_sync = get_setting('outlook_calendar_sync', $user_id) === '1';
        
        if ($google_sync || $outlook_sync) {
            require_once __DIR__ . '/calendar_sync.php';
            
            $meeting_data = [
                'title' => 'Interview: ' . $candidate['name'],
                'description' => 'Interview scheduled via candidate self-booking',
                'meeting_date' => $slot['slot_date'],
                'meeting_time' => $slot['slot_time'],
                'duration' => $slot['duration'],
                'candidate_email' => $candidate['email'],
                'zoom_join_url' => $slot['meeting_link']
            ];
            
            $sync_result = sync_meeting_to_calendars($meeting_data, $user_id);
            
            // Log sync results
            $log_dir = __DIR__ . '/../logs';
            $log_file = $log_dir . '/calendar_sync.log';
            $log_entry = sprintf(
                "[%s] Slot ID: %s | Candidate: %s | Google: %s | Outlook: %s\n",
                date('Y-m-d H:i:s'),
                $slot_id,
                $candidate['name'],
                isset($sync_result['google']['success']) ? 'SUCCESS' : (isset($sync_result['google']['error']) ? $sync_result['google']['error'] : 'DISABLED'),
                isset($sync_result['outlook']['success']) ? 'SUCCESS' : (isset($sync_result['outlook']['error']) ? $sync_result['outlook']['error'] : 'DISABLED')
            );
            @file_put_contents($log_file, $log_entry, FILE_APPEND);
        }
        
        // Send confirmation email
        send_booking_confirmation_email($candidate, $slot);
        
        echo json_encode([
            'success' => true,
            'message' => 'Interview scheduled successfully!',
            'slot' => $slot
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Failed to book slot: ' . $e->getMessage()]);
    }
}

/**
 * Send booking confirmation email to candidate
 */
function send_booking_confirmation_email($candidate, $slot) {
    $to = $candidate['email'];
    $subject = "Interview Scheduled - Confirmation";
    
    $slot_datetime = date('l, F j, Y \a\t g:i A', strtotime($slot['slot_date'] . ' ' . $slot['slot_time']));
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background-color: #f8f9fa; }
            .meeting-details { background-color: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #28a745; }
            .button { background-color: #0d6efd; color: white; padding: 15px 30px; text-align: center; margin: 20px 0; border-radius: 5px; display: inline-block; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ Interview Confirmed!</h1>
            </div>
            <div class='content'>
                <p>Dear {$candidate['name']},</p>
                
                <p>Great news! Your interview has been successfully scheduled.</p>
                
                <div class='meeting-details'>
                    <h3 style='margin-top: 0; color: #28a745;'>Interview Details</h3>
                    <p><strong>📅 Date & Time:</strong><br>{$slot_datetime}</p>
                    <p><strong>⏱️ Duration:</strong><br>{$slot['duration']} minutes</p>
                    " . ($slot['meeting_link'] ? "<p><strong>🔗 Meeting Link:</strong><br><a href='{$slot['meeting_link']}'>{$slot['meeting_link']}</a></p>" : "") . "
                </div>
                
                " . ($slot['meeting_link'] ? "<div style='text-align: center;'><a href='{$slot['meeting_link']}' class='button'>Join Meeting (on interview day)</a></div>" : "") . "
                
                <p><strong>Important reminders:</strong></p>
                <ul>
                    <li>Join the meeting 5 minutes early</li>
                    <li>Test your audio and video beforehand</li>
                    <li>Ensure stable internet connection</li>
                    <li>Have your resume ready</li>
                    <li>Prepare questions about the role</li>
                </ul>
                
                <p>If you need to reschedule, please contact us as soon as possible.</p>
                
                <p>We look forward to speaking with you!</p>
                
                <p>Best regards,<br>HR Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: HR Portal <noreply@hr.qlabs.pk>" . "\r\n";
    
    // Use enhanced email sending
    require_once __DIR__ . '/email_helper.php';
    send_email_enhanced($to, $subject, $message, 'HR Portal', 'noreply@hr.qlabs.pk', $candidate['id']);
}

/**
 * Send scheduling invitation to candidate
 */
function send_scheduling_invitation($candidate_id) {
    $pdo = get_db();
    
    // Get candidate info
    $stmt = $pdo->prepare("
        SELECT c.*, j.title as job_title, u.email as company_email, u.company_name
        FROM candidates c
        JOIN jobs j ON c.job_id = j.id
        JOIN users u ON j.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        return ['error' => 'Candidate not found'];
    }
    
    // Generate or get scheduling token
    if (empty($candidate['scheduling_token'])) {
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE candidates SET scheduling_token = ? WHERE id = ?");
        $stmt->execute([$token, $candidate_id]);
    } else {
        $token = $candidate['scheduling_token'];
    }
    
    // Build URL properly - avoid including /functions/ in the path
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scheduling_url = $protocol . '://' . $host . "/schedule.php?token=" . $token;
    
    $to = $candidate['email'];
    $subject = "Schedule Your Interview - " . $candidate['job_title'];
    $company_name = $candidate['company_name'] ?: 'Our Company';
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background-color: #f8f9fa; }
            .button { background-color: #28a745; color: white; padding: 15px 30px; text-align: center; margin: 20px 0; border-radius: 5px; display: inline-block; text-decoration: none; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📅 Schedule Your Interview</h1>
            </div>
            <div class='content'>
                <p>Dear {$candidate['name']},</p>
                
                <p>Congratulations! We would like to invite you for an interview for the position of <strong>{$candidate['job_title']}</strong> at {$company_name}.</p>
                
                <p>Please choose a convenient time slot from our available options by clicking the button below:</p>
                
                <div style='text-align: center;'>
                    <a href='{$scheduling_url}' class='button'>
                        CHOOSE YOUR INTERVIEW TIME
                    </a>
                </div>
                
                <p style='font-size: 12px; color: #666;'>Or copy this link: <br>{$scheduling_url}</p>
                
                <p><strong>What to expect:</strong></p>
                <ul>
                    <li>Browse available time slots</li>
                    <li>Select the most convenient time for you</li>
                    <li>Receive instant confirmation</li>
                    <li>Get meeting details via email</li>
                </ul>
                
                <p>We recommend scheduling your interview as soon as possible, as slots are filling up quickly.</p>
                
                <p>We look forward to meeting you!</p>
                
                <p>Best regards,<br>
                <strong>{$company_name}</strong><br>
                {$candidate['company_email']}</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Use enhanced email sending with better error handling
    require_once __DIR__ . '/email_helper.php';
    $result = send_email_enhanced($to, $subject, $message, $company_name, 'noreply@hr.qlabs.pk', $candidate_id);
    
    return $result;
}
