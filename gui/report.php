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
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> AI Evaluation Report</h5>
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
                            
                            <h6 class="text-success">
                                <i class="bi bi-chat-left-dots"></i> Answer
                            </h6>
                            <p class="ps-4 border-start border-3 border-success">
                                <?php echo nl2br(sanitize($qa['answer'])); ?>
                            </p>
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
    </div>
</div>

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
