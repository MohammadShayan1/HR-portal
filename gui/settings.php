<?php
/**
 * Settings Page
 */
$page_title = 'Settings - HR Portal';

$show_success = isset($_GET['success']);
$current_user = get_logged_in_user();
$current_api_key = get_setting('gemini_key');
$user_id = get_current_user_id();
$logo_path = get_setting('logo_path', $user_id);
$logo_exists = $logo_path && file_exists(__DIR__ . '/../' . $logo_path);

// Get LinkedIn settings
$linkedin_access_token = get_setting('linkedin_access_token');
$linkedin_org_id = get_setting('linkedin_org_id');
$linkedin_auto_post = get_setting('linkedin_auto_post') === '1';

// Get Zoom settings
$zoom_account_id = get_setting('zoom_account_id');
$zoom_client_id = get_setting('zoom_client_id');
$zoom_client_secret = get_setting('zoom_client_secret');

// Get calendar sync settings
$google_calendar_token = get_setting('google_calendar_token');
$google_calendar_sync = get_setting('google_calendar_sync') === '1';
$outlook_calendar_token = get_setting('outlook_calendar_token');
$outlook_calendar_sync = get_setting('outlook_calendar_sync') === '1';
$timezone = get_setting('timezone') ?: 'UTC';

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
                                <img src="<?php echo htmlspecialchars($logo_path); ?>?<?php echo time(); ?>" 
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
        
        <div class="card mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-linkedin"></i> LinkedIn Integration</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="functions/actions.php?action=save_linkedin_settings">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <strong>How to get LinkedIn credentials:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Go to <a href="https://www.linkedin.com/developers/apps" target="_blank">LinkedIn Developers</a></li>
                            <li>Create an app and request access to the "Share on LinkedIn" and "Sign In with LinkedIn" products</li>
                            <li>Generate an Access Token from the Auth tab</li>
                            <li>Get your Organization ID from your LinkedIn company page URL</li>
                        </ol>
                    </div>
                    
                    <div class="mb-3">
                        <label for="linkedin_access_token" class="form-label">LinkedIn Access Token</label>
                        <input type="text" class="form-control" id="linkedin_access_token" name="linkedin_access_token" 
                               value="<?php echo sanitize($linkedin_access_token ?? ''); ?>" 
                               placeholder="AQV...">
                        <small class="form-text text-muted">
                            Your LinkedIn OAuth 2.0 access token
                        </small>
                        <?php if ($linkedin_access_token): ?>
                            <div class="mt-2">
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Token Configured
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="linkedin_org_id" class="form-label">LinkedIn Organization ID</label>
                        <input type="text" class="form-control" id="linkedin_org_id" name="linkedin_org_id" 
                               value="<?php echo sanitize($linkedin_org_id ?? ''); ?>" 
                               placeholder="12345678">
                        <small class="form-text text-muted">
                            Your company's LinkedIn organization ID (found in your company page URL)
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="linkedin_auto_post" 
                                   name="linkedin_auto_post" value="1" <?php echo $linkedin_auto_post ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="linkedin_auto_post">
                                Automatically post jobs to LinkedIn when created
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            When enabled, new job postings will be automatically shared on your LinkedIn company page
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-linkedin"></i> Save LinkedIn Settings
                    </button>
                    
                    <?php if ($linkedin_access_token && $linkedin_org_id): ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="testLinkedInConnection()">
                            <i class="bi bi-wifi"></i> Test Connection
                        </button>
                    <?php endif; ?>
                </form>
                
                <div id="linkedinTestResult" class="mt-3"></div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-camera-video"></i> Zoom Integration</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="functions/actions.php?action=save_zoom_settings">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <strong>How to get Zoom Server-to-Server OAuth credentials:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Go to <a href="https://marketplace.zoom.us/" target="_blank">Zoom App Marketplace</a></li>
                            <li>Click "Develop" → "Build App"</li>
                            <li>Choose "Server-to-Server OAuth" app type</li>
                            <li>Fill in app details and activate the app</li>
                            <li><strong>Add these scopes:</strong> <code>meeting:write:admin</code>, <code>meeting:read:admin</code></li>
                            <li>Copy Account ID, Client ID, and Client Secret</li>
                        </ol>
                    </div>
                    
                    <div class="mb-3">
                        <label for="zoom_account_id" class="form-label">Account ID</label>
                        <input type="text" class="form-control" id="zoom_account_id" name="zoom_account_id" 
                               value="<?php echo sanitize($zoom_account_id ?? ''); ?>" 
                               placeholder="Your Zoom Account ID">
                        <?php if ($zoom_account_id): ?>
                            <div class="mt-2">
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Account ID Configured
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="zoom_client_id" class="form-label">Client ID</label>
                        <input type="text" class="form-control" id="zoom_client_id" name="zoom_client_id" 
                               value="<?php echo sanitize($zoom_client_id ?? ''); ?>" 
                               placeholder="Your Client ID">
                    </div>
                    
                    <div class="mb-3">
                        <label for="zoom_client_secret" class="form-label">Client Secret</label>
                        <input type="password" class="form-control" id="zoom_client_secret" name="zoom_client_secret" 
                               value="<?php echo sanitize($zoom_client_secret ?? ''); ?>" 
                               placeholder="Your Client Secret">
                        <small class="form-text text-muted">
                            Used to create Zoom meetings for high-scoring candidates (60+ score)
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-camera-video"></i> Save Zoom Settings
                    </button>
                    
                    <?php if ($zoom_account_id && $zoom_client_id && $zoom_client_secret): ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="testZoomConnection()">
                            <i class="bi bi-wifi"></i> Test Connection
                        </button>
                    <?php endif; ?>
                </form>
                
                <div id="zoomTestResult" class="mt-3"></div>
            </div>
        </div>
        
        <!-- Calendar Sync Integration -->
        <div class="card mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-calendar-sync"></i> Calendar Synchronization</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">Sync your meetings with Google Calendar and Microsoft Outlook. Events will be automatically synced both ways.</p>
                
                <!-- Timezone Setting -->
                <div class="mb-4">
                    <label class="form-label"><strong>Default Timezone</strong></label>
                    <select class="form-control" id="timezone" name="timezone">
                        <option value="UTC" <?php echo $timezone === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                        <option value="America/New_York" <?php echo $timezone === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time (US & Canada)</option>
                        <option value="America/Chicago" <?php echo $timezone === 'America/Chicago' ? 'selected' : ''; ?>>Central Time (US & Canada)</option>
                        <option value="America/Denver" <?php echo $timezone === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time (US & Canada)</option>
                        <option value="America/Los_Angeles" <?php echo $timezone === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time (US & Canada)</option>
                        <option value="Europe/London" <?php echo $timezone === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                        <option value="Europe/Paris" <?php echo $timezone === 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                        <option value="Asia/Dubai" <?php echo $timezone === 'Asia/Dubai' ? 'selected' : ''; ?>>Dubai</option>
                        <option value="Asia/Karachi" <?php echo $timezone === 'Asia/Karachi' ? 'selected' : ''; ?>>Pakistan</option>
                        <option value="Asia/Kolkata" <?php echo $timezone === 'Asia/Kolkata' ? 'selected' : ''; ?>>India</option>
                        <option value="Asia/Tokyo" <?php echo $timezone === 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo</option>
                        <option value="Australia/Sydney" <?php echo $timezone === 'Australia/Sydney' ? 'selected' : ''; ?>>Sydney</option>
                    </select>
                    <small class="text-muted">This timezone will be used for all calendar events</small>
                </div>
                
                <hr>
                
                <!-- Google Calendar -->
                <div class="mb-4">
                    <h6 class="mb-3"><i class="bi bi-google"></i> Google Calendar</h6>
                    <?php if (!empty($google_calendar_token)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Connected to Google Calendar
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="googleCalendarSync" 
                                   onchange="saveCalendarSettings()"
                                   <?php echo $google_calendar_sync ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="googleCalendarSync">
                                Automatically sync meetings to Google Calendar
                            </label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="testGoogleCalendar()">
                            <i class="bi bi-arrow-clockwise"></i> Test Connection
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="disconnectGoogleCalendar()">
                            <i class="bi bi-x-circle"></i> Disconnect
                        </button>
                    <?php else: ?>
                        <p class="text-muted small mb-3">Connect your Google Calendar to automatically sync meetings.</p>
                        <button type="button" class="btn btn-primary" onclick="connectGoogleCalendar()">
                            <i class="bi bi-google"></i> Connect Google Calendar
                        </button>
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>How to get Google Calendar API credentials:</strong><br>
                                1. Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a><br>
                                2. Create a new project or select existing one<br>
                                3. Enable Google Calendar API<br>
                                4. Create OAuth 2.0 credentials<br>
                                5. Add authorized redirect URI: <code><?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/functions/oauth_callback.php?provider=google'; ?></code>
                            </small>
                        </div>
                    <?php endif; ?>
                    <div id="googleCalendarTestResult" class="mt-3"></div>
                </div>
                
                <hr>
                
                <!-- Microsoft Outlook -->
                <div class="mb-4">
                    <h6 class="mb-3"><i class="bi bi-microsoft"></i> Microsoft Outlook</h6>
                    <?php if (!empty($outlook_calendar_token)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Connected to Outlook Calendar
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="outlookCalendarSync" 
                                   onchange="saveCalendarSettings()"
                                   <?php echo $outlook_calendar_sync ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="outlookCalendarSync">
                                Automatically sync meetings to Outlook Calendar
                            </label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="testOutlookCalendar()">
                            <i class="bi bi-arrow-clockwise"></i> Test Connection
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="disconnectOutlookCalendar()">
                            <i class="bi bi-x-circle"></i> Disconnect
                        </button>
                    <?php else: ?>
                        <p class="text-muted small mb-3">Connect your Microsoft Outlook to automatically sync meetings.</p>
                        <button type="button" class="btn btn-primary" onclick="connectOutlookCalendar()">
                            <i class="bi bi-microsoft"></i> Connect Outlook Calendar
                        </button>
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>How to get Microsoft Graph API credentials:</strong><br>
                                1. Go to <a href="https://portal.azure.com/" target="_blank">Azure Portal</a><br>
                                2. Register a new application<br>
                                3. Add Calendar.ReadWrite permissions<br>
                                4. Create client secret<br>
                                5. Add redirect URI: <code><?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/functions/oauth_callback.php?provider=outlook'; ?></code>
                            </small>
                        </div>
                    <?php endif; ?>
                    <div id="outlookCalendarTestResult" class="mt-3"></div>
                </div>
                
                <button type="button" class="btn btn-success" onclick="saveCalendarSettings()">
                    <i class="bi bi-save"></i> Save Calendar Settings
                </button>
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

<script>
function testLinkedInConnection() {
    const resultDiv = document.getElementById('linkedinTestResult');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Testing connection...</div>';
    
    fetch('functions/actions.php?action=test_linkedin')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> ${data.message}
                </div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i> ${data.error}
                </div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> Connection test failed: ${error.message}
            </div>`;
        });
}

function testZoomConnection() {
    const resultDiv = document.getElementById('zoomTestResult');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Testing connection...</div>';
    
    fetch('functions/actions.php?action=test_zoom')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> ${data.message}
                </div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i> ${data.error}
                </div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> Connection test failed: ${error.message}
            </div>`;
        });
}

function connectGoogleCalendar() {
    window.location.href = 'functions/oauth_callback.php?provider=google&action=connect';
}

function testGoogleCalendar() {
    const resultDiv = document.getElementById('googleCalendarTestResult');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Testing connection...</div>';
    
    fetch('functions/actions.php?action=test_google_calendar')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> ${data.message}
                </div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i> ${data.error}
                </div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> Connection test failed: ${error.message}
            </div>`;
        });
}

function disconnectGoogleCalendar() {
    if (confirm('Are you sure you want to disconnect Google Calendar?')) {
        fetch('functions/actions.php?action=disconnect_google_calendar', {
            method: 'POST'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
    }
}

function connectOutlookCalendar() {
    window.location.href = 'functions/oauth_callback.php?provider=outlook&action=connect';
}

function testOutlookCalendar() {
    const resultDiv = document.getElementById('outlookCalendarTestResult');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Testing connection...</div>';
    
    fetch('functions/actions.php?action=test_outlook_calendar')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> ${data.message}
                </div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i> ${data.error}
                </div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> Connection test failed: ${error.message}
            </div>`;
        });
}

function disconnectOutlookCalendar() {
    if (confirm('Are you sure you want to disconnect Outlook Calendar?')) {
        fetch('functions/actions.php?action=disconnect_outlook_calendar', {
            method: 'POST'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
    }
}

function saveCalendarSettings() {
    const formData = new FormData();
    formData.append('timezone', document.getElementById('timezone').value);
    formData.append('google_calendar_sync', document.getElementById('googleCalendarSync')?.checked ? '1' : '0');
    formData.append('outlook_calendar_sync', document.getElementById('outlookCalendarSync')?.checked ? '1' : '0');
    
    fetch('functions/actions.php?action=save_calendar_settings', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show subtle success notification
                const notification = document.createElement('div');
                notification.className = 'alert alert-success position-fixed';
                notification.style = 'top: 20px; right: 20px; z-index: 9999; animation: fadeIn 0.3s;';
                notification.innerHTML = '<i class="bi bi-check-circle"></i> Calendar settings saved';
                document.body.appendChild(notification);
                
                // Auto-remove after 2 seconds
                setTimeout(() => {
                    notification.style.animation = 'fadeOut 0.3s';
                    setTimeout(() => notification.remove(), 300);
                }, 2000);
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error saving settings:', error);
        });
}
</script>

