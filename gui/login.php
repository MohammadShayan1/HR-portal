<?php
/**
 * Login Page
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

// Handle login
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = login_user($email, $password);
    if ($result === true) {
        header('Location: ../index.php');
        exit;
    } else {
        $error = $result;
    }
}

$page_title = 'Login - HR Portal';

// Use default neutral colors for login page
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
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        .login-card {
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
        .login-header {
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
        .btn-login {
            background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
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
    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <div class="mb-3">
                    <i class="bi bi-clipboard-check" style="font-size: 3.5rem;"></i>
                </div>
                <h2 class="mb-0">Interview Portal</h2>
                <p class="mb-0 opacity-75">Admin Login</p>
            </div>
            
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-circle"></i> <?php echo sanitize($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label for="email" class="form-label fw-semibold">
                            <i class="bi bi-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="admin@example.com" required autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="bi bi-box-arrow-in-right"></i> Login to Dashboard
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Don't have an account? <a href="register.php" style="color: <?php echo $theme_primary; ?>;">Sign up here</a>
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
