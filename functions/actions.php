<?php
/**
 * Actions Handler
 * Processes all form submissions and AJAX requests
 */

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/theme.php';
require_once __DIR__ . '/linkedin.php';
require_once __DIR__ . '/zoom.php';
require_once __DIR__ . '/calendar_sync.php';

// Get action from query parameter
$action = $_GET['action'] ?? '';

// Public actions (no authentication required)
$public_actions = ['submit_application', 'get_interview_question', 'submit_answer'];

// Check authentication for non-public actions
if (!in_array($action, $public_actions)) {
    if (!is_authenticated()) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
}

// Route to appropriate action handler
switch ($action) {
    
    // === SETTINGS ACTIONS ===
    case 'update_profile':
        update_profile();
        break;
        
    case 'save_settings':
        save_settings();
        break;
    
    case 'save_linkedin_settings':
        save_linkedin_settings();
        break;
    
    case 'test_linkedin':
        test_linkedin_connection_ajax();
        break;
    
    case 'save_zoom_settings':
        save_zoom_settings();
        break;
    
    case 'test_zoom':
        test_zoom_connection_ajax();
        break;
    
    case 'schedule_meeting':
        schedule_meeting();
        break;
    
    case 'test_google_calendar':
        test_google_calendar_ajax();
        break;
    
    case 'test_outlook_calendar':
        test_outlook_calendar_ajax();
        break;
    
    case 'disconnect_google_calendar':
        disconnect_google_calendar();
        break;
    
    case 'disconnect_outlook_calendar':
        disconnect_outlook_calendar();
        break;
    
    case 'save_calendar_settings':
        save_calendar_settings();
        break;
    
    // === JOB ACTIONS ===
    case 'gen_job_desc':
        generate_job_desc_ajax();
        break;
    
    case 'create_job':
        create_job();
        break;
    
    case 'delete_job':
        delete_job();
        break;
    
    // === CANDIDATE ACTIONS ===
    case 'submit_application':
        submit_application();
        break;
    
    case 'gen_report':
        generate_report();
        break;
    
    // === INTERVIEW ACTIONS ===
    case 'get_interview_question':
        get_interview_question();
        break;
    
    case 'submit_answer':
        submit_answer();
        break;
    
    case 'generate_report':
        generate_report_ajax();
        break;
    
    default:
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Action not found']);
        exit;
}

/**
 * Update user profile (company name)
 */
function update_profile() {
    $company_name = trim($_POST['company_name'] ?? '');
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        header('Location: ../gui/login.php');
        exit;
    }
    
    $pdo = get_db();
    $stmt = $pdo->prepare("UPDATE users SET company_name = ? WHERE id = ?");
    $stmt->execute([$company_name, $user_id]);
    
    header('Location: ../index.php?page=settings&success=1');
    exit;
}

/**
 * Save settings (API key and logo)
 */
function save_settings() {
    $user_id = get_current_user_id();
    
    // Save Gemini API key
    if (isset($_POST['gemini_key'])) {
        set_setting('gemini_key', trim($_POST['gemini_key']), $user_id);
    }
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Create user-specific logo filename
        $logo_filename = 'logo_user_' . $user_id . '.png';
        $logo_path = $upload_dir . $logo_filename;
        
        // Validate image
        $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
        $file_type = mime_content_type($_FILES['logo']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
            
            // Extract colors from logo
            $colors = extract_colors_from_image($logo_path, 3);
            set_setting('theme_primary', $colors[0], $user_id);
            set_setting('theme_secondary', $colors[1] ?? $colors[0], $user_id);
            set_setting('theme_accent', $colors[2] ?? $colors[0], $user_id);
            set_setting('logo_path', 'assets/uploads/' . $logo_filename, $user_id);
        }
    }
    
    header('Location: ../index.php?page=settings&success=1');
    exit;
}

/**
 * Save LinkedIn settings
 */
function save_linkedin_settings() {
    $user_id = get_current_user_id();
    
    $linkedin_access_token = trim($_POST['linkedin_access_token'] ?? '');
    $linkedin_org_id = trim($_POST['linkedin_org_id'] ?? '');
    $linkedin_auto_post = isset($_POST['linkedin_auto_post']) ? '1' : '0';
    
    // Save LinkedIn settings
    if (!empty($linkedin_access_token)) {
        set_setting('linkedin_access_token', $linkedin_access_token, $user_id);
    }
    
    if (!empty($linkedin_org_id)) {
        set_setting('linkedin_org_id', $linkedin_org_id, $user_id);
    }
    
    set_setting('linkedin_auto_post', $linkedin_auto_post, $user_id);
    
    header('Location: ../index.php?page=settings&success=1');
    exit;
}

/**
 * Test LinkedIn connection via AJAX
 */
function test_linkedin_connection_ajax() {
    header('Content-Type: application/json');
    
    $user_id = get_current_user_id();
    $result = test_linkedin_connection($user_id);
    
    echo json_encode($result);
    exit;
}

/**
 * Save Zoom settings
 */
function save_zoom_settings() {
    $user_id = get_current_user_id();
    
    $zoom_api_key = trim($_POST['zoom_api_key'] ?? '');
    $zoom_api_secret = trim($_POST['zoom_api_secret'] ?? '');
    
    // Save Zoom settings
    if (!empty($zoom_api_key)) {
        set_setting('zoom_api_key', $zoom_api_key, $user_id);
    }
    
    if (!empty($zoom_api_secret)) {
        set_setting('zoom_api_secret', $zoom_api_secret, $user_id);
    }
    
    header('Location: ../index.php?page=settings&success=1');
    exit;
}

/**
 * Test Zoom connection via AJAX
 */
function test_zoom_connection_ajax() {
    header('Content-Type: application/json');
    
    $user_id = get_current_user_id();
    $result = test_zoom_connection($user_id);
    
    echo json_encode($result);
    exit;
}

/**
 * Schedule meeting with candidate
 */
function schedule_meeting() {
    header('Content-Type: application/json');
    
    $user_id = get_current_user_id();
    $candidate_id = (int)($_POST['candidate_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $meeting_date = trim($_POST['meeting_date'] ?? '');
    $meeting_time = trim($_POST['meeting_time'] ?? '');
    $duration = (int)($_POST['duration'] ?? 30);
    
    if (!$candidate_id || !$title || !$meeting_date || !$meeting_time) {
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }
    
    $pdo = get_db();
    
    // Verify candidate belongs to user's job
    $stmt = $pdo->prepare("
        SELECT c.*, j.title as job_title 
        FROM candidates c 
        JOIN jobs j ON c.job_id = j.id 
        WHERE c.id = ? AND j.user_id = ?
    ");
    $stmt->execute([$candidate_id, $user_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        echo json_encode(['error' => 'Candidate not found']);
        exit;
    }
    
    // Create Zoom meeting
    $start_time = $meeting_date . 'T' . $meeting_time . ':00';
    $zoom_result = create_zoom_meeting($title, $start_time, $duration, $user_id);
    
    if (isset($zoom_result['error'])) {
        echo json_encode(['error' => 'Failed to create Zoom meeting: ' . $zoom_result['error']]);
        exit;
    }
    
    // Save meeting to database
    $stmt = $pdo->prepare("
        INSERT INTO meetings (user_id, candidate_id, title, description, meeting_date, meeting_time, duration, 
                             zoom_meeting_id, zoom_join_url, zoom_start_url, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $user_id,
        $candidate_id,
        $title,
        $description,
        $meeting_date,
        $meeting_time,
        $duration,
        $zoom_result['meeting_id'],
        $zoom_result['join_url'],
        $zoom_result['start_url']
    ]);
    
    $meeting_id = $pdo->lastInsertId();
    
    // Sync to Google Calendar and Outlook if enabled
    $meeting_data = [
        'meeting_id' => $meeting_id,
        'title' => $title,
        'description' => $description,
        'meeting_date' => $meeting_date,
        'meeting_time' => $meeting_time,
        'duration' => $duration,
        'zoom_join_url' => $zoom_result['join_url'],
        'zoom_start_url' => $zoom_result['start_url'],
        'candidate_name' => $candidate['name'],
        'candidate_email' => $candidate['email']
    ];
    
    $sync_results = sync_meeting_to_calendars($meeting_data, $user_id);
    
    // TODO: Send email notification to candidate with meeting details
    
    echo json_encode([
        'success' => true,
        'meeting_id' => $meeting_id,
        'zoom_join_url' => $zoom_result['join_url'],
        'calendar_sync' => $sync_results
    ]);
    exit;
}

/**
 * Test Google Calendar connection
 */
function test_google_calendar_ajax() {
    header('Content-Type: application/json');
    
    $user_id = get_current_user_id();
    $result = test_google_calendar_connection($user_id);
    
    echo json_encode($result);
    exit;
}

/**
 * Test Outlook Calendar connection
 */
function test_outlook_calendar_ajax() {
    header('Content-Type: application/json');
    
    $user_id = get_current_user_id();
    $result = test_outlook_calendar_connection($user_id);
    
    echo json_encode($result);
    exit;
}

/**
 * Disconnect Google Calendar
 */
function disconnect_google_calendar() {
    header('Content-Type: application/json');
    
    $user_id = get_current_user_id();
    set_setting('google_calendar_token', '', $user_id);
    set_setting('google_calendar_sync', '0', $user_id);
    
    echo json_encode(['success' => true]);
    exit;
}

/**
 * Disconnect Outlook Calendar
 */
function disconnect_outlook_calendar() {
    header('Content-Type: application/json');
    
    $user_id = get_current_user_id();
    set_setting('outlook_calendar_token', '', $user_id);
    set_setting('outlook_calendar_sync', '0', $user_id);
    
    echo json_encode(['success' => true]);
    exit;
}

/**
 * Save calendar settings
 */
function save_calendar_settings() {
    header('Content-Type: application/json');
    
    $user_id = get_current_user_id();
    $timezone = trim($_POST['timezone'] ?? 'UTC');
    $google_sync = isset($_POST['google_calendar_sync']) && $_POST['google_calendar_sync'] === '1' ? '1' : '0';
    $outlook_sync = isset($_POST['outlook_calendar_sync']) && $_POST['outlook_calendar_sync'] === '1' ? '1' : '0';
    
    set_setting('timezone', $timezone, $user_id);
    set_setting('google_calendar_sync', $google_sync, $user_id);
    set_setting('outlook_calendar_sync', $outlook_sync, $user_id);
    
    echo json_encode(['success' => true]);
    exit;
}

/**
 * Generate job description via AJAX
 */
function generate_job_desc_ajax() {
    header('Content-Type: application/json');
    
    $title = $_POST['title'] ?? '';
    $brief = $_POST['brief'] ?? '';
    
    if (empty($title) || empty($brief)) {
        echo json_encode(['error' => 'Title and brief description are required']);
        exit;
    }
    
    $description = generate_job_description($title, $brief);
    echo json_encode(['description' => $description]);
    exit;
}

/**
 * Create a new job
 */
function create_job() {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title) || empty($description)) {
        header('Location: ../index.php?page=jobs&error=missing_fields');
        exit;
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        header('Location: ../gui/login.php');
        exit;
    }
    
    $pdo = get_db();
    $stmt = $pdo->prepare("INSERT INTO jobs (user_id, title, description, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $description, date('Y-m-d H:i:s')]);
    $job_id = $pdo->lastInsertId();
    
    // Check if LinkedIn auto-post is enabled
    $linkedin_auto_post = get_setting('linkedin_auto_post', $user_id) === '1';
    
    if ($linkedin_auto_post) {
        // Get application URL
        $apply_url = get_base_url() . 'public/apply.php?job_id=' . $job_id;
        
        // Post to LinkedIn
        $linkedin_result = post_job_to_linkedin($title, $description, $apply_url, $user_id);
        
        if ($linkedin_result['success']) {
            header('Location: ../index.php?page=jobs&success=created&linkedin=posted');
        } else {
            header('Location: ../index.php?page=jobs&success=created&linkedin=failed');
        }
    } else {
        header('Location: ../index.php?page=jobs&success=created');
    }
    
    exit;
}

/**
 * Delete a job
 */
function delete_job() {
    $job_id = $_GET['job_id'] ?? 0;
    $user_id = get_current_user_id();
    
    if ($job_id > 0 && $user_id) {
        $pdo = get_db();
        // Delete job only if it belongs to current user
        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND user_id = ?");
        $stmt->execute([$job_id, $user_id]);
    }
    
    header('Location: ../index.php?page=jobs&deleted=1');
    exit;
}

/**
 * Submit a job application (public)
 */
function submit_application() {
    $job_id = $_POST['job_id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    
    if (empty($name) || empty($email) || empty($phone) || empty($experience) || $job_id <= 0) {
        header('Location: ../public/apply.php?job_id=' . $job_id . '&error=missing_fields');
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../public/apply.php?job_id=' . $job_id . '&error=invalid_email');
        exit;
    }
    
    // Handle resume upload
    $resume_path = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/uploads/resumes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Validate file size (5MB max)
        if ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
            header('Location: ../public/apply.php?job_id=' . $job_id . '&error=file_too_large');
            exit;
        }
        
        // Validate file type
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = mime_content_type($_FILES['resume']['tmp_name']);
        $file_ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_type, $allowed_types) && !in_array($file_ext, ['pdf', 'doc', 'docx'])) {
            header('Location: ../public/apply.php?job_id=' . $job_id . '&error=invalid_file_type');
            exit;
        }
        
        $resume_filename = 'resume_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
        $resume_path = $upload_dir . $resume_filename;
        move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path);
        $resume_path = 'assets/uploads/resumes/' . $resume_filename;
    } else {
        // Resume is required
        header('Location: ../public/apply.php?job_id=' . $job_id . '&error=resume_required');
        exit;
    }
    
    // Generate interview token
    $token = generate_token();
    
    // Save candidate with additional fields
    $pdo = get_db();
    $stmt = $pdo->prepare("
        INSERT INTO candidates (job_id, name, email, phone, experience, resume_path, status, interview_token, applied_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'Applied', ?, ?)
    ");
    $stmt->execute([$job_id, $name, $email, $phone, $experience, $resume_path, $token, date('Y-m-d H:i:s')]);
    
    // Redirect to success page with interview link
    $interview_link = get_base_url() . 'public/interview.php?token=' . $token;
    header('Location: ../public/apply.php?job_id=' . $job_id . '&success=1&token=' . urlencode($token));
    exit;
}

/**
 * Get interview question (AJAX)
 */
function get_interview_question() {
    header('Content-Type: application/json');
    
    $token = $_GET['token'] ?? '';
    
    if (empty($token)) {
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    $pdo = get_db();
    
    // Validate token and get candidate
    $stmt = $pdo->prepare("
        SELECT c.*, j.description as job_description, j.user_id 
        FROM candidates c 
        JOIN jobs j ON c.job_id = j.id 
        WHERE c.interview_token = ?
    ");
    $stmt->execute([$token]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    // Check if interview is already completed
    if ($candidate['status'] === 'Interview Completed' || $candidate['status'] === 'Report Ready') {
        echo json_encode(['completed' => true]);
        exit;
    }
    
    // Check how many questions have been answered
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM interview_answers WHERE candidate_id = ?");
    $stmt->execute([$candidate['id']]);
    $answered_count = $stmt->fetch()['count'];
    
    // Check if questions have been generated
    $stmt = $pdo->prepare("SELECT * FROM interview_questions WHERE candidate_id = ? ORDER BY question_order");
    $stmt->execute([$candidate['id']]);
    $questions = $stmt->fetchAll();
    
    // Generate questions if not already done
    if (empty($questions)) {
        $generated_questions = generate_interview_questions($candidate['job_description'], 5, $candidate['user_id']);
        
        foreach ($generated_questions as $i => $q) {
            $stmt = $pdo->prepare("
                INSERT INTO interview_questions (candidate_id, question, question_order) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$candidate['id'], $q, $i + 1]);
        }
        
        // Reload questions
        $stmt = $pdo->prepare("SELECT * FROM interview_questions WHERE candidate_id = ? ORDER BY question_order");
        $stmt->execute([$candidate['id']]);
        $questions = $stmt->fetchAll();
    }
    
    // Check if all questions answered
    if ($answered_count >= count($questions)) {
        // Mark interview as completed
        $stmt = $pdo->prepare("UPDATE candidates SET status = 'Interview Completed' WHERE id = ?");
        $stmt->execute([$candidate['id']]);
        
        echo json_encode(['completed' => true]);
        exit;
    }
    
    // Return next question
    $next_question = $questions[$answered_count];
    echo json_encode([
        'question' => $next_question['question'],
        'question_number' => $answered_count + 1,
        'total_questions' => count($questions)
    ]);
    exit;
}

/**
 * Submit interview answer (AJAX)
 */
function submit_answer() {
    header('Content-Type: application/json');
    
    require_once __DIR__ . '/ai_detection.php';
    
    $token = $_POST['token'] ?? '';
    $answer = trim($_POST['answer'] ?? '');
    $typing_metadata = $_POST['typing_metadata'] ?? '{}';
    $suspicion_score = (int)($_POST['suspicion_score'] ?? 0);
    
    if (empty($token) || empty($answer)) {
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    $pdo = get_db();
    
    // Validate token and get candidate
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE interview_token = ?");
    $stmt->execute([$token]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    // Get current question
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM interview_answers WHERE candidate_id = ?");
    $stmt->execute([$candidate['id']]);
    $answered_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("
        SELECT * FROM interview_questions 
        WHERE candidate_id = ? 
        ORDER BY question_order 
        LIMIT 1 OFFSET ?
    ");
    $stmt->execute([$candidate['id'], $answered_count]);
    $question = $stmt->fetch();
    
    if (!$question) {
        echo json_encode(['error' => 'No question found']);
        exit;
    }
    
    // Perform AI detection
    $metadata = json_decode($typing_metadata, true) ?: [];
    $detection_result = detect_ai_response($answer, $metadata);
    
    // Save answer with AI detection data
    $stmt = $pdo->prepare("
        INSERT INTO interview_answers (candidate_id, question, answer, typing_metadata, ai_detection_score, ai_detection_flags) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $candidate['id'], 
        $question['question'], 
        $answer,
        $typing_metadata,
        $detection_result['ai_probability'],
        json_encode($detection_result['flags'])
    ]);
    
    echo json_encode(['success' => true]);
    exit;
}

/**
 * Generate evaluation report
 */
function generate_report() {
    $candidate_id = $_GET['candidate_id'] ?? 0;
    
    if ($candidate_id <= 0) {
        header('Location: ../index.php?page=jobs&error=invalid_candidate');
        exit;
    }
    
    // Generate the report
    $report = generate_evaluation_report($candidate_id);
    
    if (!$report) {
        header('Location: ../index.php?page=candidates&error=report_failed');
        exit;
    }
    
    // Save report to database
    $pdo = get_db();
    $stmt = $pdo->prepare("
        INSERT INTO reports (candidate_id, report_content, score, generated_at) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $candidate_id,
        $report['report_content'],
        $report['score'],
        date('Y-m-d H:i:s')
    ]);
    
    // Update candidate status
    $stmt = $pdo->prepare("UPDATE candidates SET status = 'Report Ready' WHERE id = ?");
    $stmt->execute([$candidate_id]);
    
    // Get job_id for redirect
    $stmt = $pdo->prepare("SELECT job_id FROM candidates WHERE id = ?");
    $stmt->execute([$candidate_id]);
    $job_id = $stmt->fetch()['job_id'];
    
    header('Location: ../index.php?page=candidates&job_id=' . $job_id . '&report_generated=1');
    exit;
}

/**
 * Generate evaluation report via AJAX (for regeneration)
 */
function generate_report_ajax() {
    // Suppress any errors/warnings that might output HTML
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Clear any output buffering to prevent HTML errors
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    ob_start();
    
    header('Content-Type: application/json');
    
    try {
        $candidate_id = $_GET['candidate_id'] ?? 0;
        
        if ($candidate_id <= 0) {
            ob_end_clean();
            echo json_encode(['error' => 'Invalid candidate ID']);
            exit;
        }
        
        // Verify candidate belongs to current user
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            ob_end_clean();
            echo json_encode(['error' => 'User not authenticated']);
            exit;
        }
        
        $pdo = get_db();
        
        $stmt = $pdo->prepare("
            SELECT c.id FROM candidates c 
            JOIN jobs j ON c.job_id = j.id 
            WHERE c.id = ? AND j.user_id = ?
        ");
        $stmt->execute([$candidate_id, $user_id]);
        
        if (!$stmt->fetch()) {
            ob_end_clean();
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }
        
        // Check regeneration limit
        $stmt = $pdo->prepare("SELECT regeneration_count FROM reports WHERE candidate_id = ? ORDER BY generated_at DESC LIMIT 1");
        $stmt->execute([$candidate_id]);
        $existing_report = $stmt->fetch();
        
        if ($existing_report && $existing_report['regeneration_count'] >= 5) {
            ob_end_clean();
            echo json_encode(['error' => 'Maximum regeneration limit (5) reached for this report']);
            exit;
        }
        
        $regeneration_count = $existing_report ? ($existing_report['regeneration_count'] + 1) : 0;
        
        // Generate the report
        $report = generate_evaluation_report($candidate_id);
        
        if (!$report) {
            ob_end_clean();
            echo json_encode(['error' => 'Failed to generate report']);
            exit;
        }
        
        if (isset($report['error'])) {
            ob_end_clean();
            echo json_encode(['error' => $report['error']]);
            exit;
        }
        
        // Delete old report(s) for this candidate
        $stmt = $pdo->prepare("DELETE FROM reports WHERE candidate_id = ?");
        $stmt->execute([$candidate_id]);
        
        // Save new report to database with regeneration count
        $stmt = $pdo->prepare("
            INSERT INTO reports (candidate_id, report_content, score, generated_at, regeneration_count) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $candidate_id,
            $report['report_content'],
            $report['score'],
            date('Y-m-d H:i:s'),
            $regeneration_count
        ]);
        
        // Update candidate status
        $stmt = $pdo->prepare("UPDATE candidates SET status = 'Report Ready' WHERE id = ?");
        $stmt->execute([$candidate_id]);
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'score' => $report['score'],
            'message' => 'Report regenerated successfully'
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

