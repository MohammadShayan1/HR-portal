<?php
/**
 * Candidate Report Page
 */
$page_title = 'Candidate Report - HR Portal';

$candidate_id = $_GET['candidate_id'] ?? 0;

if ($candidate_id <= 0) {
    header('Location: index.php?page=jobs');
    exit;
}

$pdo = get_db();
$user_id = get_current_user_id();

// Get candidate and job details - ensure job belongs to current user
$stmt = $pdo->prepare("
    SELECT c.*, j.title as job_title, j.id as job_id 
    FROM candidates c 
    JOIN jobs j ON c.job_id = j.id 
    WHERE c.id = ? AND j.user_id = ?
");
$stmt->execute([$candidate_id, $user_id]);
$candidate = $stmt->fetch();

if (!$candidate) {
    header('Location: index.php?page=jobs');
    exit;
}

// Get report
$stmt = $pdo->prepare("SELECT * FROM reports WHERE candidate_id = ? ORDER BY generated_at DESC LIMIT 1");
$stmt->execute([$candidate_id]);
$report = $stmt->fetch();

if (!$report) {
    header('Location: index.php?page=candidates&job_id=' . $candidate['job_id']);
    exit;
}

// Check regeneration limit
$regeneration_count = $report['regeneration_count'] ?? 0;
$regenerations_left = 5 - $regeneration_count;
$can_regenerate = $regeneration_count < 5;

// Get interview Q&A
$stmt = $pdo->prepare("SELECT * FROM interview_answers WHERE candidate_id = ? ORDER BY id");
$stmt->execute([$candidate_id]);
$qa_pairs = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=jobs">Jobs</a></li>
                <li class="breadcrumb-item"><a href="index.php?page=candidates&job_id=<?php echo $candidate['job_id']; ?>">Candidates</a></li>
                <li class="breadcrumb-item active"><?php echo sanitize($candidate['name']); ?></li>
            </ol>
        </nav>
        
        <h1 class="mb-4">
            <i class="bi bi-file-earmark-text"></i> Evaluation Report
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person"></i> Candidate Information</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name:</dt>
                    <dd class="col-sm-8"><?php echo sanitize($candidate['name']); ?></dd>
                    
                    <dt class="col-sm-4">Email:</dt>
                    <dd class="col-sm-8"><?php echo sanitize($candidate['email']); ?></dd>
                    
                    <dt class="col-sm-4">Job:</dt>
                    <dd class="col-sm-8"><?php echo sanitize($candidate['job_title']); ?></dd>
                    
                    <dt class="col-sm-4">Applied:</dt>
                    <dd class="col-sm-8"><?php echo format_date($candidate['applied_at']); ?></dd>
                    
                    <dt class="col-sm-4">Resume:</dt>
                    <dd class="col-sm-8">
                        <?php if ($candidate['resume_path']): ?>
                            <a href="<?php echo sanitize($candidate['resume_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-file-earmark-pdf"></i> View Resume
                            </a>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-star"></i> AI Score</h5>
            </div>
            <div class="card-body text-center">
                <?php 
                $has_error = strpos($report['report_content'], 'Error generating report') !== false || 
                             strpos($report['report_content'], 'Unable to generate') !== false;
                
                if ($has_error): ?>
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle"></i> Report generation encountered an issue
                    </div>
                    <?php if ($can_regenerate): ?>
                        <button id="regenerateBtn" class="btn btn-primary" onclick="regenerateReport()">
                            <i class="bi bi-arrow-clockwise"></i> Regenerate Report
                        </button>
                        <small class="d-block mt-2 text-muted"><?php echo $regenerations_left; ?> regenerations left</small>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>
                            <i class="bi bi-x-circle"></i> Regeneration Limit Reached
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="display-1 fw-bold 
                        <?php 
                        if ($report['score'] >= 80) echo 'text-success';
                        elseif ($report['score'] >= 60) echo 'text-warning';
                        else echo 'text-danger';
                        ?>">
                        <?php echo $report['score']; ?>
                    </div>
                    <p class="text-muted mb-0">out of 100</p>
                    <small class="text-muted">Generated: <?php echo format_date($report['generated_at']); ?></small>
                    <div class="mt-3">
                        <?php if ($can_regenerate): ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="regenerateReport()">
                                <i class="bi bi-arrow-clockwise"></i> Regenerate (<?php echo $regenerations_left; ?> left)
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-secondary" disabled title="Maximum 5 regenerations reached">
                                <i class="bi bi-x-circle"></i> Limit Reached
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($report['score'] >= 60): ?>
                            <button class="btn btn-sm btn-success ms-2" onclick="sendSchedulingInvitation(<?php echo $candidate['id']; ?>, '<?php echo htmlspecialchars($candidate['name']); ?>')">
                                <i class="bi bi-calendar-check"></i> Send Scheduling Link
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> AI Evaluation Report</h5>
                <?php if ($can_regenerate): ?>
                    <button class="btn btn-sm btn-light" onclick="regenerateReport()">
                        <i class="bi bi-arrow-clockwise"></i> Regenerate
                    </button>
                <?php else: ?>
                    <button class="btn btn-sm btn-secondary" disabled title="Maximum 5 regenerations reached">
                        <i class="bi bi-x-circle"></i> Limit Reached
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="report-content">
                    <?php echo markdown_to_html($report['report_content']); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Interview Transcript</h5>
            </div>
            <div class="card-body">
                <?php if (empty($qa_pairs)): ?>
                    <p class="text-muted">No interview data available.</p>
                <?php else: ?>
                    <?php foreach ($qa_pairs as $i => $qa): ?>
                        <div class="mb-4">
                            <h6 class="text-primary">
                                <i class="bi bi-question-circle"></i> Question <?php echo $i + 1; ?>
                            </h6>
                            <p class="ps-4"><?php echo sanitize($qa['question']); ?></p>
                            
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h6 class="text-success mb-0">
                                    <i class="bi bi-chat-left-dots"></i> Answer
                                </h6>
                                
                                <?php 
                                $ai_score = isset($qa['ai_detection_score']) ? (int)$qa['ai_detection_score'] : 0;
                                $ai_flags = isset($qa['ai_detection_flags']) ? json_decode($qa['ai_detection_flags'], true) : [];
                                
                                if ($ai_score > 0):
                                    // Determine badge color based on score
                                    if ($ai_score < 30) {
                                        $badge_class = 'bg-success';
                                        $badge_text = 'Low Risk';
                                        $icon = 'check-circle';
                                    } elseif ($ai_score < 60) {
                                        $badge_class = 'bg-warning text-dark';
                                        $badge_text = 'Moderate Risk';
                                        $icon = 'exclamation-triangle';
                                    } else {
                                        $badge_class = 'bg-danger';
                                        $badge_text = 'High Risk';
                                        $icon = 'x-circle';
                                    }
                                ?>
                                    <span class="badge <?php echo $badge_class; ?>" title="AI Detection Score: <?php echo $ai_score; ?>%">
                                        <i class="bi bi-<?php echo $icon; ?>"></i> 
                                        AI Detection: <?php echo $ai_score; ?>% - <?php echo $badge_text; ?>
                                    </span>
                                    
                                    <?php if (!empty($ai_flags)): ?>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#ai-details-<?php echo $i; ?>" 
                                                aria-expanded="false">
                                            <i class="bi bi-info-circle"></i> Details
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <p class="ps-4 border-start border-3 border-success">
                                <?php echo nl2br(sanitize($qa['answer'])); ?>
                            </p>
                            
                            <?php if (!empty($ai_flags)): ?>
                                <div class="collapse mt-2" id="ai-details-<?php echo $i; ?>">
                                    <div class="card card-body bg-light">
                                        <h6 class="mb-2"><i class="bi bi-flag"></i> Detection Flags:</h6>
                                        <ul class="mb-2">
                                            <?php foreach ($ai_flags as $flag): ?>
                                                <li class="text-muted small"><?php echo sanitize($flag); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <?php if (isset($qa['typing_metadata']) && !empty($qa['typing_metadata'])): 
                                            $metadata = json_decode($qa['typing_metadata'], true);
                                            if ($metadata):
                                        ?>
                                            <h6 class="mb-2 mt-2"><i class="bi bi-graph-up"></i> Typing Analysis:</h6>
                                            <div class="row small text-muted">
                                                <div class="col-md-3">
                                                    <strong>Total Keystrokes:</strong> <?php echo $metadata['total_keystrokes'] ?? 'N/A'; ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Paste Count:</strong> <?php echo $metadata['paste_count'] ?? 0; ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Typing Speed:</strong> <?php echo isset($metadata['typing_speed']) ? round($metadata['typing_speed'], 1) : 'N/A'; ?> chars/sec
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Response Time:</strong> <?php echo isset($metadata['response_time']) ? round($metadata['response_time']) : 'N/A'; ?>s
                                                </div>
                                            </div>
                                        <?php endif; endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($i < count($qa_pairs) - 1): ?>
                            <hr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <a href="index.php?page=candidates&job_id=<?php echo $candidate['job_id']; ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Candidates
        </a>
        <button onclick="window.print()" class="btn btn-outline-primary">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <?php if ($can_regenerate): ?>
            <button class="btn btn-outline-secondary" onclick="regenerateReport()">
                <i class="bi bi-arrow-clockwise"></i> Regenerate Report (<?php echo $regenerations_left; ?> left)
            </button>
        <?php else: ?>
            <button class="btn btn-outline-secondary" disabled title="Maximum 5 regenerations reached">
                <i class="bi bi-x-circle"></i> Regeneration Limit Reached
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
function sendSchedulingInvitation(candidateId, candidateName) {
    if (!confirm(`Send scheduling invitation to ${candidateName}?\n\nThis will email them a link to choose their preferred interview time from available slots.`)) {
        return;
    }
    
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
    
    const formData = new FormData();
    formData.append('candidate_id', candidateId);
    
    fetch('functions/actions.php?action=send_scheduling_invitation', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        if (data.success) {
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Sent!';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
            alert(`✅ Scheduling invitation sent to ${candidateName}!\n\nThey will receive an email with a link to select their preferred interview time.`);
        } else {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            alert('Error: ' + (data.error || 'Failed to send invitation'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        alert('Error: ' + error.message);
    });
}

function regenerateReport() {
    if (!confirm('Are you sure you want to regenerate this report? This will replace the existing evaluation.')) {
        return;
    }
    
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';
    
    fetch('functions/actions.php?action=generate_report&candidate_id=<?php echo $candidate_id; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            } else if (data.success) {
                // Reload the page to show the new report
                window.location.reload();
            }
        })
        .catch(error => {
            alert('Error regenerating report: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
}
</script>

<style>
@media print {
    .navbar, .breadcrumb, .btn, footer { display: none !important; }
    .card { border: 1px solid #000 !important; }
}

.report-content h1, .report-content h2, .report-content h3 {
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.report-content ul {
    padding-left: 0;
}

.report-content li {
    list-style-position: inside;
}
</style>
