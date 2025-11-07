<?php
/**
 * Public Interview Page
 */
session_start();
require_once __DIR__ . '/../functions/db.php';
require_once __DIR__ . '/../functions/core.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('Invalid interview token');
}

// Validate token
$pdo = get_db();
$stmt = $pdo->prepare("
    SELECT c.*, j.title as job_title, j.user_id 
    FROM candidates c 
    JOIN jobs j ON c.job_id = j.id 
    WHERE c.interview_token = ?
");
$stmt->execute([$token]);
$candidate = $stmt->fetch();

if (!$candidate) {
    die('Invalid interview token');
}

// Get logo and theme colors from job owner's settings
$logo_filename = 'logo_user_' . $candidate['user_id'] . '.png';
$logo_path = __DIR__ . '/../assets/uploads/' . $logo_filename;
// Check for different extensions
if (!file_exists($logo_path)) {
    $logo_filename = 'logo_user_' . $candidate['user_id'] . '.jpg';
    $logo_path = __DIR__ . '/../assets/uploads/' . $logo_filename;
}
if (!file_exists($logo_path)) {
    $logo_filename = 'logo_user_' . $candidate['user_id'] . '.gif';
    $logo_path = __DIR__ . '/../assets/uploads/' . $logo_filename;
}
$has_logo = file_exists($logo_path);
$is_completed = in_array($candidate['status'], ['Interview Completed', 'Report Ready']);

// Get theme colors from job owner's settings
$pdo = get_db();
$theme_primary = get_setting('theme_primary', $candidate['user_id']) ?? '#667eea';
$theme_secondary = get_setting('theme_secondary', $candidate['user_id']) ?? '#764ba2';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Interview - <?php echo sanitize($candidate['job_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../assets/ai-detection.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
            min-height: 100vh;
        }
        .interview-card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .question-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-left: 5px solid <?php echo $theme_primary; ?>;
            transition: all 0.3s ease;
        }
        .question-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .progress-step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .progress-step.active {
            background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
            color: white;
            transform: scale(1.2);
        }
        .progress-step.completed {
            background: #28a745;
            color: white;
        }
        .btn-interview {
            background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-interview:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px <?php echo $theme_primary; ?>66;
        }
        .portal-header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        textarea.form-control {
            border-radius: 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        textarea.form-control:focus {
            border-color: <?php echo $theme_primary; ?>;
            box-shadow: 0 0 0 0.25rem <?php echo $theme_primary; ?>40;
            transform: scale(1.01);
        }
        .progress-bar {
            background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%) !important;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-9">
                <div class="text-center mb-4">
                    <div class="portal-header">
                        <?php if ($has_logo): ?>
                            <img src="../assets/uploads/<?php echo $logo_filename; ?>?<?php echo time(); ?>" alt="Company Logo" style="max-height: 60px;">
                        <?php else: ?>
                            <h2 class="mb-0" style="color: <?php echo $theme_primary; ?>;">
                                <i class="bi bi-building"></i> Company Name
                            </h2>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card interview-card border-0">
                    <div class="card-body p-5">
                        <?php if ($is_completed): ?>
                            <!-- Interview Already Completed -->
                            <div class="text-center py-5">
                                <i class="bi bi-check-circle text-success" style="font-size: 6rem; animation: scaleIn 0.5s ease-out;"></i>
                                <h2 class="mt-4 mb-3 fw-bold">Interview Completed!</h2>
                                <p class="lead text-muted">
                                    Thank you, <?php echo sanitize($candidate['name']); ?>. 
                                    You have already completed your interview for the 
                                    <strong><?php echo sanitize($candidate['job_title']); ?></strong> position.
                                </p>
                                <p class="text-muted">
                                    Our HR team will review your responses and contact you soon.
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- Interview Interface -->
                            <div id="welcomeScreen">
                                <h2 class="mb-3 fw-bold">
                                    <i class="bi bi-chat-dots text-primary"></i> AI Virtual Interview
                                </h2>
                                <h4 class="text-primary mb-4"><?php echo sanitize($candidate['job_title']); ?></h4>
                                
                                <div class="alert alert-info border-0" style="background: linear-gradient(135deg, #d4edff 0%, #c3e5ff 100%);">
                                    <h5><i class="bi bi-info-circle"></i> Welcome, <?php echo sanitize($candidate['name']); ?>!</h5>
                                    <p class="mb-0">
                                        This is an automated text-based interview. You will be asked 5 questions 
                                        related to the position. Please answer each question thoughtfully. 
                                        Your responses will be reviewed by our HR team.
                                    </p>
                                </div>
                                
                                <div class="alert alert-warning border-0" style="background: linear-gradient(135deg, #fff4d5 0%, #ffe9a6 100%);">
                                    <strong><i class="bi bi-clock"></i> Interview Tips:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Take your time to provide detailed answers</li>
                                        <li>Be honest and specific in your responses</li>
                                        <li>You can complete the interview at your own pace</li>
                                        <li>All questions are AI-generated based on the job requirements</li>
                                    </ul>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button class="btn btn-interview btn-lg" id="startBtn">
                                        <i class="bi bi-play-circle-fill"></i> Start AI Interview
                                    </button>
                                </div>
                            </div>
                            
                            <div id="interviewScreen" style="display: none;">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Interview Progress</h5>
                                        <span class="badge bg-primary" id="progressBadge" style="background: <?php echo $theme_primary; ?> !important;">Question 0 of 5</span>
                                    </div>
                                    <div class="progress" style="height: 30px; border-radius: 15px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" 
                                             id="progressBar" 
                                             style="width: 0%;">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="question-card p-4 rounded mb-4">
                                    <h5 class="text-primary mb-3">
                                        <i class="bi bi-question-circle-fill"></i> Question <span id="questionNumber">1</span> of 5
                                    </h5>
                                    <p class="lead mb-0" id="questionText">Loading...</p>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="answerText" class="form-label fw-semibold">
                                        <i class="bi bi-pencil-square"></i> Your Answer:
                                    </label>
                                    <textarea class="form-control" id="answerText" rows="10" 
                                              placeholder="Type your detailed answer here..."></textarea>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle"></i> Provide a comprehensive response. There is no character limit.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button class="btn btn-interview btn-lg" id="submitBtn">
                                        <i class="bi bi-send-fill"></i> Submit Answer & Continue
                                    </button>
                                </div>
                                
                                <div id="statusMessage" class="mt-3"></div>
                            </div>
                            
                            <div id="completeScreen" style="display: none;">
                                <div class="text-center py-5">
                                    <i class="bi bi-check-circle text-success" style="font-size: 6rem; animation: scaleIn 0.5s ease-out;"></i>
                                    <h2 class="mt-4 mb-3 fw-bold">Interview Completed!</h2>
                                    <p class="lead text-muted">
                                        Thank you for completing the interview, <?php echo sanitize($candidate['name']); ?>!
                                    </p>
                                    <p class="text-muted">
                                        Your responses have been submitted successfully. 
                                        Our HR team will review your interview and contact you soon.
                                    </p>
                                    <div class="alert alert-success border-0 mt-4" style="background: linear-gradient(135deg, #d4f4dd 0%, #b8e6c3 100%);">
                                        <h5><strong><i class="bi bi-calendar-check"></i> What's next?</strong></h5>
                                        <p class="mb-0">
                                            We will carefully evaluate your responses using our AI-powered analysis system 
                                            and get back to you within 5-7 business days.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-white-50 small">
                        <i class="bi bi-lock"></i> &copy; <?php echo date('Y'); ?> HR Virtual Interview Portal - Powered by AI
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!$is_completed): ?>
    <script>
    const token = '<?php echo $token; ?>';
    let currentQuestion = 0;
    let totalQuestions = 5;
    
    // Initialize typing analyzer when interview starts
    let typingAnalyzer = null;
    
    // Start Interview
    document.getElementById('startBtn').addEventListener('click', async function() {
        document.getElementById('welcomeScreen').style.display = 'none';
        document.getElementById('interviewScreen').style.display = 'block';
        
        // Initialize typing analyzer for the answer textarea
        if (typeof TypingAnalyzer !== 'undefined') {
            typingAnalyzer = new TypingAnalyzer('answerText');
            window.typingAnalyzer = typingAnalyzer;
        }
        
        await loadNextQuestion();
    });
    
    // Submit Answer
    document.getElementById('submitBtn').addEventListener('click', async function() {
        const answer = document.getElementById('answerText').value.trim();
        
        if (!answer) {
            showStatus('Please provide an answer before submitting.', 'warning');
            return;
        }
        
        if (answer.length < 10) {
            showStatus('Please provide a more detailed answer (at least 10 characters).', 'warning');
            return;
        }
        
        await submitAnswer(answer);
    });
    
    // Load Next Question
    async function loadNextQuestion() {
        const statusDiv = document.getElementById('statusMessage');
        statusDiv.innerHTML = '<div class="alert alert-info border-0"><i class="bi bi-hourglass-split"></i> Loading next question...</div>';
        
        try {
            const response = await fetch(`../functions/actions.php?action=get_interview_question&token=${token}`);
            const data = await response.json();
            
            if (data.error) {
                showStatus(data.error, 'danger');
                return;
            }
            
            if (data.completed) {
                showCompletionScreen();
                return;
            }
            
            // Update UI
            currentQuestion = data.question_number;
            totalQuestions = data.total_questions;
            
            document.getElementById('questionNumber').textContent = currentQuestion;
            document.getElementById('questionText').textContent = data.question;
            document.getElementById('answerText').value = '';
            document.getElementById('progressBadge').textContent = `Question ${currentQuestion} of ${totalQuestions}`;
            
            updateProgress();
            statusDiv.innerHTML = '';
            
        } catch (error) {
            showStatus('Error loading question: ' + error.message, 'danger');
        }
    }
    
    // Submit Answer
    async function submitAnswer(answer) {
        const btn = document.getElementById('submitBtn');
        const originalHtml = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting your answer...';
        
        try {
            const formData = new FormData();
            formData.append('token', token);
            formData.append('answer', answer);
            
            // Add typing metadata from AI detection
            if (window.typingAnalyzer) {
                const metadata = window.typingAnalyzer.getMetadata();
                const suspicionScore = window.typingAnalyzer.getSuspicionScore();
                formData.append('typing_metadata', JSON.stringify(metadata));
                formData.append('suspicion_score', suspicionScore);
            }
            
            const response = await fetch('../functions/actions.php?action=submit_answer', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.error) {
                showStatus(data.error, 'danger');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                return;
            }
            
            if (data.success) {
                showStatus('✓ Answer submitted successfully!', 'success');
                
                // Load next question after a short delay
                setTimeout(async () => {
                    await loadNextQuestion();
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }, 1500);
            }
            
        } catch (error) {
            showStatus('Error submitting answer: ' + error.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
    
    // Update Progress Bar
    function updateProgress() {
        const percentage = (currentQuestion / totalQuestions) * 100;
        const progressBar = document.getElementById('progressBar');
        
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
    }
    
    // Show Status Message
    function showStatus(message, type) {
        const statusDiv = document.getElementById('statusMessage');
        const icon = type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle';
        statusDiv.innerHTML = `<div class="alert alert-${type} border-0"><i class="bi bi-${icon}"></i> ${message}</div>`;
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(() => {
                statusDiv.innerHTML = '';
            }, 3000);
        }
    }
    
    // Show Completion Screen
    function showCompletionScreen() {
        document.getElementById('interviewScreen').style.display = 'none';
        document.getElementById('completeScreen').style.display = 'block';
    }
    </script>
    <?php endif; ?>
</body>
</html>
