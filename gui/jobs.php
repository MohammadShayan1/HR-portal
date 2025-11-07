<?php
/**
 * Jobs Management Page
 */
$page_title = 'Jobs - HR Portal';

// Get all jobs for current user
$pdo = get_db();
$user_id = get_current_user_id();

$stmt = $pdo->prepare("
    SELECT j.*, 
           COUNT(DISTINCT c.id) as candidate_count 
    FROM jobs j 
    LEFT JOIN candidates c ON j.id = c.job_id 
    WHERE j.user_id = ?
    GROUP BY j.id 
    ORDER BY j.created_at DESC
");
$stmt->execute([$user_id]);
$jobs = $stmt->fetchAll();

$show_success = isset($_GET['success']);
$show_deleted = isset($_GET['deleted']);
$show_error = isset($_GET['error']);
$linkedin_posted = isset($_GET['linkedin']) && $_GET['linkedin'] === 'posted';
$linkedin_failed = isset($_GET['linkedin']) && $_GET['linkedin'] === 'failed';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="bi bi-briefcase"></i> Job Postings</h1>
    </div>
</div>

<?php if ($show_success && $linkedin_posted): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> Job created successfully and posted to LinkedIn!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($show_success && $linkedin_failed): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> Job created successfully, but LinkedIn posting failed. Check your LinkedIn settings.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($show_success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> Job created successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($show_deleted): ?>
    <div class="alert alert-info alert-dismissible fade show">
        Job deleted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($show_error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        Error: Please fill in all required fields.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Job</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="functions/actions.php?action=create_job" id="createJobForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Job Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required 
                                   placeholder="e.g., Senior Software Engineer">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="brief" class="form-label">Brief Description *</label>
                            <input type="text" class="form-control" id="brief" name="brief" 
                                   placeholder="e.g., Full-stack developer for web applications" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Full Job Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="10" required></textarea>
                        <small class="form-text text-muted">
                            Use the AI button below to generate a professional description, or write your own.
                        </small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-info" id="generateBtn">
                            <i class="bi bi-stars"></i> Generate with AI
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Job
                        </button>
                    </div>
                    
                    <div id="aiStatus" class="mt-2"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Job Postings</h5>
            </div>
            <div class="card-body">
                <?php if (empty($jobs)): ?>
                    <p class="text-muted">No jobs posted yet. Create your first job above!</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Created</th>
                                    <th>Candidates</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jobs as $job): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo sanitize($job['title']); ?></strong>
                                        </td>
                                        <td><?php echo format_date($job['created_at']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo $job['candidate_count']; ?> applicants
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="index.php?page=candidates&job_id=<?php echo $job['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-people"></i> View Candidates
                                                </a>
                                                <button type="button" class="btn btn-sm btn-secondary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#linkModal<?php echo $job['id']; ?>">
                                                    <i class="bi bi-link-45deg"></i> Get Link
                                                </button>
                                                <a href="functions/actions.php?action=delete_job&job_id=<?php echo $job['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this job?')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Link Modal -->
                                    <div class="modal fade" id="linkModal<?php echo $job['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Application Link</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Share this link with candidates to apply for this job:</p>
                                                    <?php
                                                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                                                    $host = $_SERVER['HTTP_HOST'];
                                                    $dir = dirname($_SERVER['PHP_SELF']);
                                                    $base_url = $protocol . '://' . $host . $dir;
                                                    $apply_link = $base_url . '/public/apply.php?job_id=' . $job['id'];
                                                    ?>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" 
                                                               value="<?php echo sanitize($apply_link); ?>" 
                                                               id="link<?php echo $job['id']; ?>" readonly>
                                                        <button class="btn btn-outline-secondary" type="button"
                                                                onclick="copyLink(<?php echo $job['id']; ?>)">
                                                            <i class="bi bi-clipboard"></i> Copy
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize TinyMCE Rich Text Editor
tinymce.init({
    selector: '#description',
    height: 400,
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
        'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | formatselect | ' +
        'bold italic underline | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | code | help',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; }',
    valid_elements: '*[*]',
    extended_valid_elements: '*[*]',
    valid_children: '+body[style]',
    setup: function(editor) {
        // When editor content changes, update the textarea
        editor.on('change keyup', function() {
            editor.save();
        });
    }
});

// Form submission - ensure TinyMCE content is saved
document.getElementById('createJobForm').addEventListener('submit', function(e) {
    // Trigger TinyMCE to save content to textarea
    if (tinymce.get('description')) {
        tinymce.get('description').save();
    }
    
    // Validate that description is not empty
    const description = document.getElementById('description').value.trim();
    if (!description || description === '') {
        e.preventDefault();
        alert('Please enter or generate a job description before creating the job.');
        return false;
    }
});

// AI Job Description Generator
document.getElementById('generateBtn').addEventListener('click', async function() {
    const title = document.getElementById('title').value;
    const brief = document.getElementById('brief').value;
    const statusDiv = document.getElementById('aiStatus');
    const btn = this;
    
    if (!title || !brief) {
        statusDiv.innerHTML = '<div class="alert alert-warning">Please fill in Job Title and Brief Description first.</div>';
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';
    statusDiv.innerHTML = '<div class="alert alert-info">AI is generating your job description...</div>';
    
    try {
        const formData = new FormData();
        formData.append('title', title);
        formData.append('brief', brief);
        
        const response = await fetch('functions/actions.php?action=gen_job_desc', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.error) {
            statusDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        } else {
            // Wait for TinyMCE to be ready, then set content
            if (tinymce.get('description')) {
                tinymce.get('description').setContent(data.description);
                // Manually save to textarea
                tinymce.get('description').save();
            } else {
                // Fallback if TinyMCE not ready
                document.getElementById('description').value = data.description;
            }
            statusDiv.innerHTML = '<div class="alert alert-success">Job description generated successfully! You can now create the job.</div>';
        }
    } catch (error) {
        statusDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-stars"></i> Generate with AI';
    }
});

// Copy link function
function copyLink(jobId) {
    const input = document.getElementById('link' + jobId);
    input.select();
    document.execCommand('copy');
    
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
    
    setTimeout(() => {
        btn.innerHTML = originalHtml;
    }, 2000);
}
</script>
