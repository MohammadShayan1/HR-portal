<?php
/**
 * Candidates Page
 */
$page_title = 'Candidates - HR Portal';

$job_id = $_GET['job_id'] ?? 0;

if ($job_id <= 0) {
    header('Location: index.php?page=jobs');
    exit;
}

$pdo = get_db();
$user_id = get_current_user_id();

// Get job details - ensure it belongs to current user
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND user_id = ?");
$stmt->execute([$job_id, $user_id]);
$job = $stmt->fetch();

if (!$job) {
    header('Location: index.php?page=jobs');
    exit;
}

// Get all candidates for this job
$stmt = $pdo->prepare("
    SELECT * FROM candidates 
    WHERE job_id = ? 
    ORDER BY applied_at DESC
");
$stmt->execute([$job_id]);
$candidates = $stmt->fetchAll();

$show_report_generated = isset($_GET['report_generated']);
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=jobs">Jobs</a></li>
                <li class="breadcrumb-item active"><?php echo sanitize($job['title']); ?></li>
            </ol>
        </nav>
        
        <h1 class="mb-4">
            <i class="bi bi-people"></i> Candidates for: <?php echo sanitize($job['title']); ?>
        </h1>
    </div>
</div>

<?php if ($show_report_generated): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> Report generated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> All Candidates</h5>
            </div>
            <div class="card-body">
                <?php if (empty($candidates)): ?>
                    <p class="text-muted">No candidates have applied yet.</p>
                    <p>
                        Share the application link to receive candidates:
                        <?php
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'];
                        $dir = dirname($_SERVER['PHP_SELF']);
                        $base_url = $protocol . '://' . $host . $dir;
                        $apply_link = $base_url . '/public/apply.php?job_id=' . $job_id;
                        ?>
                    </p>
                    <div class="input-group" style="max-width: 600px;">
                        <input type="text" class="form-control" value="<?php echo sanitize($apply_link); ?>" id="applyLink" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyApplyLink()">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Applied</th>
                                    <th>Status</th>
                                    <th>Resume</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr>
                                        <td><?php echo sanitize($candidate['name']); ?></td>
                                        <td><?php echo sanitize($candidate['email']); ?></td>
                                        <td><?php echo format_date($candidate['applied_at']); ?></td>
                                        <td>
                                            <?php
                                            $badge_class = match($candidate['status']) {
                                                'Applied' => 'bg-secondary',
                                                'Interview Completed' => 'bg-warning text-dark',
                                                'Report Ready' => 'bg-success',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo sanitize($candidate['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($candidate['resume_path']): ?>
                                                <a href="<?php echo sanitize($candidate['resume_path']); ?>" 
                                                   target="_blank" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-file-earmark-pdf"></i> View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($candidate['status'] === 'Applied'): ?>
                                                <button class="btn btn-sm btn-secondary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#interviewModal<?php echo $candidate['id']; ?>">
                                                    <i class="bi bi-link-45deg"></i> Interview Link
                                                </button>
                                            <?php elseif ($candidate['status'] === 'Interview Completed'): ?>
                                                <a href="functions/actions.php?action=gen_report&candidate_id=<?php echo $candidate['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="bi bi-file-earmark-text"></i> Generate Report
                                                </a>
                                            <?php elseif ($candidate['status'] === 'Report Ready'): ?>
                                                <a href="index.php?page=report&candidate_id=<?php echo $candidate['id']; ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="bi bi-eye"></i> View Report
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Interview Link Modal -->
                                    <div class="modal fade" id="interviewModal<?php echo $candidate['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Interview Link</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Send this link to <?php echo sanitize($candidate['name']); ?> to start the interview:</p>
                                                    <?php
                                                    $interview_link = get_base_url() . 'public/interview.php?token=' . $candidate['interview_token'];
                                                    ?>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" 
                                                               value="<?php echo sanitize($interview_link); ?>" 
                                                               id="interviewLink<?php echo $candidate['id']; ?>" readonly>
                                                        <button class="btn btn-outline-secondary" type="button"
                                                                onclick="copyInterviewLink(<?php echo $candidate['id']; ?>)">
                                                            <i class="bi bi-clipboard"></i> Copy
                                                        </button>
                                                    </div>
                                                    <small class="text-muted mt-2 d-block">
                                                        This unique link allows the candidate to take the automated interview.
                                                    </small>
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
function copyApplyLink() {
    const input = document.getElementById('applyLink');
    input.select();
    document.execCommand('copy');
    
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
    
    setTimeout(() => {
        btn.innerHTML = originalHtml;
    }, 2000);
}

function copyInterviewLink(candidateId) {
    const input = document.getElementById('interviewLink' + candidateId);
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
