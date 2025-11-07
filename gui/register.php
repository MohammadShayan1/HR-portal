<?php
/**
 * Registration Page
 * Only accessible when no users exist
 */
session_start();
require_once __DIR__ . '/../functions/db.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/core.php';

// Redirect if already logged in
if (is_authenticated()) {
    header('Location: ../index.php');
    exit;
}

// Handle registration
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $company_name = $_POST['company_name'] ?? '';
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $result = register_user($email, $password, $company_name);
        if ($result === true) {
            // Get the newly created user ID
            $pdo = get_db();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            $new_user_id = $user['id'];
            
            // Handle logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Create user-specific logo filename
                $logo_path = $upload_dir . 'logo_user_' . $new_user_id . '.png';
                
                // Validate image
                $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
                $file_type = mime_content_type($_FILES['logo']['tmp_name']);
                
                if (in_array($file_type, $allowed_types)) {
                    move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
                    
                    // Extract colors from logo
                    require_once __DIR__ . '/../functions/theme.php';
                    $colors = extract_colors_from_image($logo_path, 3);
                    set_setting('theme_primary', $colors[0], $new_user_id);
                    set_setting('theme_secondary', $colors[1] ?? $colors[0], $new_user_id);
                    set_setting('theme_accent', $colors[2] ?? $colors[0], $new_user_id);
                    set_setting('logo_path', 'assets/uploads/logo_user_' . $new_user_id . '.png', $new_user_id);
                }
            }
            
            $success = 'Registration successful! You can now login.';
            header('refresh:2;url=login.php');
        } else {
            $error = $result;
        }
    }
}

$page_title = 'Register - HR Portal';

// Use default neutral colors for registration page
$theme_primary = '#667eea';
$theme_secondary = '#764ba2';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
        }
        .register-card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.6s ease-out;
            border: none;
            overflow: hidden;
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
        .register-header {
            background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: <?php echo $theme_primary; ?>;
            box-shadow: 0 0 0 0.25rem <?php echo $theme_primary; ?>40;
            transform: translateY(-2px);
        }
        .btn-register {
            background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px <?php echo $theme_primary; ?>66;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-link a:hover {
            color: #fff;
            text-shadow: 0 0 10px rgba(255,255,255,0.5);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card register-card">
            <div class="register-header">
                <div class="mb-3">
                    <i class="bi bi-clipboard-check" style="font-size: 3.5rem;"></i>
                </div>
                <h2 class="mb-0">Interview Portal</h2>
                <p class="mb-0 opacity-75">Create Your Account</p>
            </div>
            
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-circle"></i> <?php echo sanitize($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo sanitize($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="company_name" class="form-label fw-semibold">
                            <i class="bi bi-building"></i> Company Name
                        </label>
                        <input type="text" class="form-control" id="company_name" name="company_name" 
                               placeholder="Your Company Name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="logo" class="form-label fw-semibold">
                            <i class="bi bi-image"></i> Company Logo (Optional)
                        </label>
                        <input type="file" class="form-control" id="logo" name="logo" 
                               accept="image/*">
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Upload your logo to customize your portal colors
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">
                            <i class="bi bi-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="you@company.com" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Create a strong password" required minlength="6">
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> At least 6 characters
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold">
                            <i class="bi bi-lock-fill"></i> Confirm Password
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Re-enter your password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-register">
                            <i class="bi bi-check-circle-fill"></i> Create Account
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Already have an account? <a href="login.php" style="color: <?php echo $theme_primary; ?>;">Login here</a>
                        </small>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="back-link">
            <a href="../home.php">
                <i class="bi bi-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
