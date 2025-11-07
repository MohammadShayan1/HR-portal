<?php
/**
 * Super Admin Panel
 * Centralized configuration for all users
 */

// Check if user is super admin
$user_id = get_current_user_id();
$pdo = get_db();
$stmt = $pdo->prepare("SELECT is_super_admin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['is_super_admin'] != 1) {
    header('Location: index.php?page=dashboard');
    exit;
}

$page_title = 'Super Admin Panel';

// Get system settings
function get_system_setting($key, $default = '') {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

// Get all system OAuth settings
$google_client_id = get_system_setting('google_client_id');
$google_client_secret = get_system_setting('google_client_secret');
$google_redirect_uri = get_system_setting('google_redirect_uri');

$outlook_client_id = get_system_setting('outlook_client_id');
$outlook_client_secret = get_system_setting('outlook_client_secret');
$outlook_redirect_uri = get_system_setting('outlook_redirect_uri');

$zoom_account_id = get_system_setting('zoom_account_id');
$zoom_client_id = get_system_setting('zoom_client_id');
$zoom_client_secret = get_system_setting('zoom_client_secret');

// Get gemini API key from first user (for reference)
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'gemini_api_key' LIMIT 1");
$gemini_sample = $stmt->fetch();

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$total_users = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_super_admin = 1");
$super_admins = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM jobs");
$total_jobs = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM candidates");
$total_candidates = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM meetings");
$total_meetings = $stmt->fetch()['count'];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-shield-lock-fill text-danger"></i> Super Admin Panel</h2>
                    <p class="text-muted">Centralized configuration for all users</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Settings saved successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- System Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?php echo $total_users; ?></h3>
                    <small class="text-muted">Total Users</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?php echo $super_admins; ?></h3>
                    <small class="text-muted">Super Admins</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?php echo $total_jobs; ?></h3>
                    <small class="text-muted">Total Jobs</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?php echo $total_candidates; ?></h3>
                    <small class="text-muted">Total Candidates</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0"><?php echo $total_meetings; ?></h3>
                    <small class="text-muted">Total Meetings</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Tabs -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#google">
                        <i class="bi bi-google"></i> Google Calendar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#outlook">
                        <i class="bi bi-microsoft"></i> Outlook Calendar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#zoom">
                        <i class="bi bi-camera-video"></i> Zoom
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#users">
                        <i class="bi bi-people"></i> Manage Users
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Google Calendar Tab -->
                <div class="tab-pane fade show active" id="google">
                    <h5 class="mb-3">Google Calendar OAuth Configuration</h5>
                    <p class="text-muted">Configure Google OAuth once here, and all users can connect their Google Calendar</p>
                    
                    <form action="functions/actions.php?action=save_system_oauth" method="POST">
                        <input type="hidden" name="provider" value="google">
                        
                        <div class="mb-3">
                            <label class="form-label">Google Client ID *</label>
                            <input type="text" class="form-control" name="client_id" value="<?php echo htmlspecialchars($google_client_id); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Google Client Secret *</label>
                            <input type="text" class="form-control" name="client_secret" value="<?php echo htmlspecialchars($google_client_secret); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Redirect URI</label>
                            <input type="text" class="form-control" name="redirect_uri" value="<?php echo htmlspecialchars($google_redirect_uri ?: (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/functions/oauth_callback.php?provider=google'); ?>" required>
                            <small class="text-muted">Add this exact URL to your Google Cloud Console OAuth credentials</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>Setup Instructions:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                                <li>Create/select project → APIs & Services → Credentials</li>
                                <li>Create OAuth 2.0 Client ID (Web application)</li>
                                <li>Add the Redirect URI shown above</li>
                                <li>Enable Google Calendar API in APIs & Services → Library</li>
                                <li>Copy Client ID and Secret here</li>
                            </ol>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Google OAuth Settings
                        </button>
                    </form>
                </div>

                <!-- Outlook Calendar Tab -->
                <div class="tab-pane fade" id="outlook">
                    <h5 class="mb-3">Microsoft Outlook OAuth Configuration</h5>
                    <p class="text-muted">Configure Microsoft OAuth once here, and all users can connect their Outlook Calendar</p>
                    
                    <form action="functions/actions.php?action=save_system_oauth" method="POST">
                        <input type="hidden" name="provider" value="outlook">
                        
                        <div class="mb-3">
                            <label class="form-label">Microsoft Client ID (Application ID) *</label>
                            <input type="text" class="form-control" name="client_id" value="<?php echo htmlspecialchars($outlook_client_id); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Microsoft Client Secret *</label>
                            <input type="text" class="form-control" name="client_secret" value="<?php echo htmlspecialchars($outlook_client_secret); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Redirect URI</label>
                            <input type="text" class="form-control" name="redirect_uri" value="<?php echo htmlspecialchars($outlook_redirect_uri ?: (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/functions/oauth_callback.php?provider=outlook'); ?>" required>
                            <small class="text-muted">Add this exact URL to your Azure App registrations</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>Setup Instructions:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Go to <a href="https://portal.azure.com/" target="_blank">Azure Portal</a></li>
                                <li>Azure Active Directory → App registrations → New registration</li>
                                <li>Add the Redirect URI shown above (Web platform)</li>
                                <li>Certificates & secrets → New client secret</li>
                                <li>API permissions → Add Microsoft Graph → Delegated → Calendars.ReadWrite</li>
                                <li>Copy Application (client) ID and Secret here</li>
                            </ol>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Outlook OAuth Settings
                        </button>
                    </form>
                </div>

                <!-- Zoom Tab -->
                <div class="tab-pane fade" id="zoom">
                    <h5 class="mb-3">Zoom OAuth Configuration</h5>
                    <p class="text-muted">Configure Zoom Server-to-Server OAuth once, and all users can create Zoom meetings</p>
                    
                    <form action="functions/actions.php?action=save_system_oauth" method="POST">
                        <input type="hidden" name="provider" value="zoom">
                        
                        <div class="mb-3">
                            <label class="form-label">Zoom Account ID *</label>
                            <input type="text" class="form-control" name="account_id" value="<?php echo htmlspecialchars($zoom_account_id); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Zoom Client ID *</label>
                            <input type="text" class="form-control" name="client_id" value="<?php echo htmlspecialchars($zoom_client_id); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Zoom Client Secret *</label>
                            <input type="text" class="form-control" name="client_secret" value="<?php echo htmlspecialchars($zoom_client_secret); ?>" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>Setup Instructions:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Go to <a href="https://marketplace.zoom.us/" target="_blank">Zoom App Marketplace</a></li>
                                <li>Develop → Build App → Server-to-Server OAuth</li>
                                <li>Add scopes: meeting:write:admin, meeting:read:admin</li>
                                <li>Copy Account ID, Client ID, and Client Secret here</li>
                            </ol>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Zoom OAuth Settings
                        </button>
                    </form>
                </div>

                <!-- Users Management Tab -->
                <div class="tab-pane fade" id="users">
                    <h5 class="mb-3">User Management</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Company</th>
                                    <th>Created</th>
                                    <th>Super Admin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT id, email, company_name, created_at, is_super_admin FROM users ORDER BY created_at DESC");
                                $users = $stmt->fetchAll();
                                foreach ($users as $u):
                                ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['company_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <?php if ($u['is_super_admin']): ?>
                                            <span class="badge bg-danger">Super Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['id'] != $user_id): ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="toggleSuperAdmin(<?php echo $u['id']; ?>, <?php echo $u['is_super_admin'] ? 'false' : 'true'; ?>)">
                                                <?php echo $u['is_super_admin'] ? 'Remove' : 'Make'; ?> Super Admin
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSuperAdmin(userId, makeAdmin) {
    if (!confirm('Are you sure you want to ' + (makeAdmin ? 'grant' : 'revoke') + ' super admin privileges?')) {
        return;
    }
    
    fetch('functions/actions.php?action=toggle_super_admin', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'user_id=' + userId + '&is_super_admin=' + (makeAdmin ? '1' : '0')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to update user'));
        }
    });
}
</script>
