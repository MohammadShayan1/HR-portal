<?php
/**
 * Settings Page
 */
$page_title = 'Settings - HR Portal';

$show_success = isset($_GET['success']);
$current_user = get_logged_in_user();
$current_api_key = get_setting('gemini_key');
$logo_exists = file_exists(__DIR__ . '/../assets/uploads/logo.png');

// Get theme colors
$theme_primary = get_setting('theme_primary') ?? '#0d6efd';
$theme_secondary = get_setting('theme_secondary') ?? '#6c757d';
$theme_accent = get_setting('theme_accent') ?? '#0dcaf0';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="bi bi-gear"></i> Settings</h1>
    </div>
</div>

<?php if ($show_success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> Settings saved successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-person-circle"></i> Account Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="functions/actions.php?action=update_profile">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" value="<?php echo sanitize($current_user['email']); ?>" disabled>
                        <small class="form-text text-muted">Email cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" 
                               value="<?php echo sanitize($current_user['company_name'] ?? ''); ?>" 
                               placeholder="Your Company Name">
                        <small class="form-text text-muted">This name will appear in your portal</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-key"></i> AI Configuration</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="functions/actions.php?action=save_settings" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="gemini_key" class="form-label">Google Gemini API Key</label>
                        <input type="text" class="form-control" id="gemini_key" name="gemini_key" 
                               value="<?php echo sanitize($current_api_key ?? ''); ?>" 
                               placeholder="AIza...">
                        <small class="form-text text-muted">
                            Get your free API key from 
                            <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>
                        </small>
                        <?php if ($current_api_key): ?>
                            <div class="mt-2">
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> API Key Configured
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="mt-2">
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-exclamation-triangle"></i> API Key Required
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-4">
                        <label for="logo" class="form-label">Company Logo</label>
                        <?php if ($logo_exists): ?>
                            <div class="mb-2">
                                <img src="assets/uploads/logo.png?<?php echo time(); ?>" 
                                     alt="Current Logo" 
                                     style="max-height: 100px; border: 1px solid #ddd; padding: 10px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        <small class="form-text text-muted">
                            Upload a PNG, JPG, or GIF image. Recommended size: 200x50px. Theme colors will be extracted automatically.
                        </small>
                    </div>
                    
                    <?php if ($logo_exists): ?>
                    <hr>
                    
                    <div class="mb-4">
                        <label class="form-label">Current Theme Colors</label>
                        <div class="d-flex gap-3">
                            <div class="text-center">
                                <div class="rounded" style="width: 60px; height: 60px; background-color: <?php echo $theme_primary; ?>; border: 2px solid #ddd;"></div>
                                <small class="text-muted d-block mt-1">Primary</small>
                                <code class="small"><?php echo $theme_primary; ?></code>
                            </div>
                            <div class="text-center">
                                <div class="rounded" style="width: 60px; height: 60px; background-color: <?php echo $theme_secondary; ?>; border: 2px solid #ddd;"></div>
                                <small class="text-muted d-block mt-1">Secondary</small>
                                <code class="small"><?php echo $theme_secondary; ?></code>
                            </div>
                            <div class="text-center">
                                <div class="rounded" style="width: 60px; height: 60px; background-color: <?php echo $theme_accent; ?>; border: 2px solid #ddd;"></div>
                                <small class="text-muted d-block mt-1">Accent</small>
                                <code class="small"><?php echo $theme_accent; ?></code>
                            </div>
                        </div>
                        <small class="form-text text-muted mt-2 d-block">
                            These colors are automatically extracted from your logo and applied to the dashboard theme.
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-palette"></i> Theme Preview</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">Current dashboard theme colors:</p>
                <div class="p-3 rounded mb-2" style="background-color: <?php echo $theme_primary; ?>;">
                    <span class="text-white fw-bold">Primary Color</span>
                </div>
                <div class="p-3 rounded mb-2" style="background-color: <?php echo $theme_secondary; ?>;">
                    <span class="text-white fw-bold">Secondary Color</span>
                </div>
                <div class="p-3 rounded" style="background-color: <?php echo $theme_accent; ?>;">
                    <span class="text-white fw-bold">Accent Color</span>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> About</h5>
            </div>
            <div class="card-body">
                <h6>HR Virtual Interview Portal</h6>
                <p class="text-muted small">
                    An AI-powered recruitment platform that automates the interview process using 
                    Google's Gemini AI.
                </p>
                
                <hr>
                
                <h6>Features</h6>
                <ul class="small mb-0">
                    <li>AI-powered job description generation</li>
                    <li>Automated text-based interviews</li>
                    <li>Intelligent candidate evaluation</li>
                    <li>Self-hosted and secure</li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-database"></i> System Info</h5>
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-6">PHP Version:</dt>
                    <dd class="col-6"><?php echo phpversion(); ?></dd>
                    
                    <dt class="col-6">Database:</dt>
                    <dd class="col-6">SQLite</dd>
                    
                    <dt class="col-6">Admin Email:</dt>
                    <dd class="col-6"><?php echo sanitize($_SESSION['user_email'] ?? 'N/A'); ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
