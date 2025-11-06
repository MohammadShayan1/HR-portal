<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Check - HR Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="bi bi-check2-circle"></i> HR Portal - System Check</h3>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-4">Installation Verification</h5>
                        
                        <?php
                        $checks = [];
                        $all_passed = true;
                        
                        // PHP Version
                        $php_version = phpversion();
                        $php_check = version_compare($php_version, '7.4.0', '>=');
                        $checks[] = [
                            'name' => 'PHP Version',
                            'status' => $php_check,
                            'message' => "PHP $php_version " . ($php_check ? '(OK)' : '(Requires 7.4+)')
                        ];
                        $all_passed = $all_passed && $php_check;
                        
                        // PDO SQLite
                        $pdo_check = extension_loaded('pdo_sqlite');
                        $checks[] = [
                            'name' => 'PDO SQLite Extension',
                            'status' => $pdo_check,
                            'message' => $pdo_check ? 'Installed' : 'NOT INSTALLED (Required)'
                        ];
                        $all_passed = $all_passed && $pdo_check;
                        
                        // cURL
                        $curl_check = extension_loaded('curl');
                        $checks[] = [
                            'name' => 'cURL Extension',
                            'status' => $curl_check,
                            'message' => $curl_check ? 'Installed' : 'NOT INSTALLED (Required for AI)'
                        ];
                        $all_passed = $all_passed && $curl_check;
                        
                        // Fileinfo
                        $fileinfo_check = extension_loaded('fileinfo');
                        $checks[] = [
                            'name' => 'Fileinfo Extension',
                            'status' => $fileinfo_check,
                            'message' => $fileinfo_check ? 'Installed' : 'NOT INSTALLED (Required for uploads)'
                        ];
                        $all_passed = $all_passed && $fileinfo_check;
                        
                        // Write permissions
                        $write_check = is_writable(__DIR__);
                        $checks[] = [
                            'name' => 'Directory Write Permission',
                            'status' => $write_check,
                            'message' => $write_check ? 'Writable' : 'NOT WRITABLE (Required for database)'
                        ];
                        $all_passed = $all_passed && $write_check;
                        
                        // Uploads directory
                        $uploads_dir = __DIR__ . '/assets/uploads';
                        $uploads_check = is_dir($uploads_dir) && is_writable($uploads_dir);
                        $checks[] = [
                            'name' => 'Uploads Directory',
                            'status' => $uploads_check,
                            'message' => $uploads_check ? 'Exists and writable' : 'NOT WRITABLE (Required for files)'
                        ];
                        $all_passed = $all_passed && $uploads_check;
                        
                        // Check if files exist
                        $required_files = [
                            'index.php',
                            'functions/db.php',
                            'functions/auth.php',
                            'functions/core.php',
                            'functions/actions.php',
                            'gui/header.php',
                            'gui/login.php',
                            'public/apply.php',
                            'public/interview.php'
                        ];
                        
                        $files_exist = true;
                        $missing_files = [];
                        foreach ($required_files as $file) {
                            if (!file_exists(__DIR__ . '/' . $file)) {
                                $files_exist = false;
                                $missing_files[] = $file;
                            }
                        }
                        
                        $checks[] = [
                            'name' => 'Required Files',
                            'status' => $files_exist,
                            'message' => $files_exist ? 'All files present' : 'Missing: ' . implode(', ', $missing_files)
                        ];
                        $all_passed = $all_passed && $files_exist;
                        
                        // Display results
                        ?>
                        
                        <div class="list-group mb-4">
                            <?php foreach ($checks as $check): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo $check['name']; ?></strong><br>
                                        <small class="text-muted"><?php echo $check['message']; ?></small>
                                    </div>
                                    <?php if ($check['status']): ?>
                                        <span class="badge bg-success rounded-pill">
                                            <i class="bi bi-check-lg"></i> PASS
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill">
                                            <i class="bi bi-x-lg"></i> FAIL
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($all_passed): ?>
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle"></i> All Checks Passed!</h5>
                                <p class="mb-0">Your system is ready to run the HR Portal. You can now proceed with registration.</p>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="gui/register.php" class="btn btn-primary btn-lg">
                                    <i class="bi bi-arrow-right-circle"></i> Continue to Registration
                                </a>
                                <a href="gui/login.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-box-arrow-in-right"></i> Already have an account? Login
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h5><i class="bi bi-exclamation-triangle"></i> System Requirements Not Met</h5>
                                <p class="mb-0">Please fix the failed checks above before proceeding. Refer to README.md for assistance.</p>
                            </div>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <h6>System Information</h6>
                        <dl class="row small mb-0">
                            <dt class="col-sm-4">PHP Version:</dt>
                            <dd class="col-sm-8"><?php echo phpversion(); ?></dd>
                            
                            <dt class="col-sm-4">Server Software:</dt>
                            <dd class="col-sm-8"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></dd>
                            
                            <dt class="col-sm-4">Document Root:</dt>
                            <dd class="col-sm-8"><code><?php echo __DIR__; ?></code></dd>
                            
                            <dt class="col-sm-4">PHP Extensions:</dt>
                            <dd class="col-sm-8">
                                <?php
                                $extensions = get_loaded_extensions();
                                echo count($extensions) . ' loaded';
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <p class="text-muted small">
                        HR Virtual Interview Portal v1.0 | 
                        <a href="README.md" target="_blank">Documentation</a> | 
                        <a href="QUICKSTART.md" target="_blank">Quick Start</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
