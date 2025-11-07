<?php
/**
 * Public Job Application Form
 */
session_start();
require_once __DIR__ . '/../functions/db.php';
require_once __DIR__ . '/../functions/core.php';

$job_id = $_GET['job_id'] ?? 0;

if ($job_id <= 0) {
    die('Invalid job ID');
}

// Get job details
$pdo = get_db();
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    die('Job not found');
}

$show_success = isset($_GET['success']);
$show_error = isset($_GET['error']);
$token = $_GET['token'] ?? '';
$error_type = $_GET['error'] ?? '';

// Get logo and theme colors from job owner's settings
$logo_filename = 'logo_user_' . $job['user_id'] . '.png';
$logo_path = __DIR__ . '/../assets/uploads/' . $logo_filename;
// Check for different extensions
if (!file_exists($logo_path)) {
    $logo_filename = 'logo_user_' . $job['user_id'] . '.jpg';
    $logo_path = __DIR__ . '/../assets/uploads/' . $logo_filename;
}
if (!file_exists($logo_path)) {
    $logo_filename = 'logo_user_' . $job['user_id'] . '.gif';
    $logo_path = __DIR__ . '/../assets/uploads/' . $logo_filename;
}
$has_logo = file_exists($logo_path);

$pdo = get_db();
$theme_primary = get_setting('theme_primary', $job['user_id']) ?? '#667eea';
$theme_secondary = get_setting('theme_secondary', $job['user_id']) ?? '#764ba2';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply - <?php echo sanitize($job['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
            min-height: 100vh;
        }
        .application-card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .form-control:focus, .form-select:focus {
            border-color: <?php echo $theme_primary; ?>;
            box-shadow: 0 0 0 0.25rem <?php echo $theme_primary; ?>40;
        }
        .job-description {
            background: #f8f9fa;
            border-left: 4px solid <?php echo $theme_primary; ?>;
        }
        .job-content {
            max-height: 400px;
            overflow-y: auto;
        }
        .job-content::-webkit-scrollbar {
            width: 8px;
        }
        .job-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .job-content::-webkit-scrollbar-thumb {
            background: <?php echo $theme_primary; ?>;
            border-radius: 10px;
        }
        .job-content h3 {
            color: <?php echo $theme_primary; ?>;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }
        .job-content ul {
            padding-left: 1.5rem;
        }
        .job-content li {
            margin-bottom: 0.5rem;
        }
        .form-control-lg {
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 0.5rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control-lg:focus {
            border-color: <?php echo $theme_primary; ?>;
            box-shadow: 0 0 0 0.25rem <?php echo $theme_primary; ?>40;
        }
        .btn-apply {
            background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
        }
        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px <?php echo $theme_primary; ?>66;
        }
        .btn-apply:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="text-center mb-4">
                    <div class="company-header">
                        <?php if ($has_logo): ?>
                            <img src="../assets/uploads/<?php echo $logo_filename; ?>?<?php echo time(); ?>" alt="Company Logo" style="max-height: 60px;">
                        <?php else: ?>
                            <h2 class="mb-0" style="color: <?php echo $theme_primary; ?>;">
                                <i class="bi bi-building"></i> Company Name
                            </h2>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($show_success): ?>
                    <div class="card application-card border-0">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-check-circle text-success success-icon" style="font-size: 5rem;"></i>
                            <h2 class="mt-4 mb-3 fw-bold">Application Submitted Successfully!</h2>
                            <p class="lead mb-4">Thank you for applying. Your unique interview link is:</p>
                            
                            <?php
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                            $host = $_SERVER['HTTP_HOST'];
                            $dir = dirname($_SERVER['PHP_SELF']);
                            $base_url = $protocol . '://' . $host . $dir;
                            $interview_link = $base_url . '/interview.php?token=' . urlencode($token);
                            ?>
                            
                            <div class="input-group mb-4">
                                <input type="text" class="form-control" value="<?php echo sanitize($interview_link); ?>" id="interviewLink" readonly>
                                <button class="btn btn-primary" type="button" onclick="copyLink()">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                <strong>Next Step:</strong> Please save this link and complete your interview at your convenience.
                            </div>
                            
                            <a href="<?php echo sanitize($interview_link); ?>" class="btn btn-success btn-lg">
                                <i class="bi bi-play-circle"></i> Start Interview Now
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card application-card border-0">
                        <div class="card-body p-5">
                            <div class="mb-4">
                                <h1 class="card-title mb-2 fw-bold">
                                    Apply for: <?php echo sanitize($job['title']); ?>
                                </h1>
                                <p class="text-muted mb-4">Fill out the form below to submit your application</p>
                                
                                <div class="job-description mb-4 p-4 rounded">
                                    <h5 class="fw-bold mb-3">Job Description</h5>
                                    <div class="job-content">
                                        <?php echo $job['description']; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <?php if ($show_error): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?php
                                    $error_message = match($error_type) {
                                        'missing_fields' => 'Please fill in all required fields.',
                                        'invalid_email' => 'Please enter a valid email address.',
                                        'resume_required' => 'Please upload your resume.',
                                        'file_too_large' => 'Resume file size must be less than 5MB.',
                                        'invalid_file_type' => 'Invalid file type. Only PDF, DOC, and DOCX files are allowed.',
                                        default => 'An error occurred. Please try again.'
                                    };
                                    echo sanitize($error_message);
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="../functions/actions.php?action=submit_application" enctype="multipart/form-data" id="applicationForm">
                                <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label fw-semibold">
                                            <i class="bi bi-person"></i> Full Name *
                                        </label>
                                        <input type="text" class="form-control form-control-lg" id="name" name="name" 
                                               placeholder="John Doe" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-semibold">
                                            <i class="bi bi-envelope"></i> Email Address *
                                        </label>
                                        <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                               placeholder="john@example.com" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-semibold">
                                            <i class="bi bi-telephone"></i> Phone Number *
                                        </label>
                                        <input type="tel" class="form-control form-control-lg" id="phone" name="phone" 
                                               placeholder="+1 (555) 000-0000" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="experience" class="form-label fw-semibold">
                                            <i class="bi bi-briefcase"></i> Years of Experience *
                                        </label>
                                        <input type="number" class="form-control form-control-lg" id="experience" name="experience" 
                                               placeholder="5" min="0" required>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="resume" class="form-label fw-semibold">
                                            <i class="bi bi-file-earmark-text"></i> Resume * (PDF, DOC, DOCX - Max 5MB)
                                        </label>
                                        <input type="file" class="form-control form-control-lg" id="resume" name="resume" 
                                               accept=".pdf,.doc,.docx" required>
                                        <div id="fileInfo" class="form-text mt-2"></div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 mt-5">
                                    <button type="submit" class="btn btn-apply btn-lg" id="submitBtn">
                                        <i class="bi bi-send-fill"></i> Submit Application
                                    </button>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="bi bi-shield-check"></i> Your information is secure and will be used only for recruitment purposes
                                    </small>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <p class="text-white-50 small">
                        <i class="bi bi-lock"></i> &copy; <?php echo date('Y'); ?> HR Virtual Interview Portal - Powered by AI
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function copyLink() {
        const input = document.getElementById('interviewLink');
        input.select();
        document.execCommand('copy');
        
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
        
        setTimeout(() => {
            btn.innerHTML = originalHtml;
        }, 2000);
    }
    
    // File input change handler - show file info
    document.getElementById('resume')?.addEventListener('change', function(e) {
        const fileInfo = document.getElementById('fileInfo');
        const file = e.target.files[0];
        
        if (file) {
            const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
            fileInfo.innerHTML = `<i class="bi bi-check-circle text-success"></i> Selected: ${file.name} (${fileSizeMB} MB)`;
        } else {
            fileInfo.innerHTML = '';
        }
    });
    
    // Form validation and file size check
    document.getElementById('applicationForm')?.addEventListener('submit', function(e) {
        const fileInput = document.getElementById('resume');
        const submitBtn = document.getElementById('submitBtn');
        
        if (fileInput.files.length > 0) {
            const fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
            if (fileSize > 5) {
                e.preventDefault();
                alert('Resume file size must be less than 5MB');
                return false;
            }
        } else {
            e.preventDefault();
            alert('Please upload your resume');
            return false;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Submitting...';
    });
    </script>
</body>
</html>
