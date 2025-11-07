<?php
/**
 * Email Logs Viewer
 * View all email communications sent from the system
 */

require_once __DIR__ . '/../functions/db.php';
require_once __DIR__ . '/../functions/auth.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = get_db();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$limit = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_clauses = ["c.user_id = :user_id"];
$params = [':user_id' => $user_id];

if ($status_filter !== 'all') {
    $where_clauses[] = "el.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search_query)) {
    $where_clauses[] = "(c.name LIKE :search OR el.recipient LIKE :search OR el.subject LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$where_sql = implode(' AND ', $where_clauses);

// Get total count
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM email_logs el
    LEFT JOIN candidates c ON el.candidate_id = c.id
    WHERE $where_sql
");
$count_stmt->execute($params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $limit);

// Get email logs with candidate information
$stmt = $pdo->prepare("
    SELECT 
        el.*,
        c.name as candidate_name,
        c.email as candidate_email,
        j.job_title
    FROM email_logs el
    LEFT JOIN candidates c ON el.candidate_id = c.id
    LEFT JOIN jobs j ON c.job_id = j.id
    WHERE $where_sql
    ORDER BY el.sent_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_emails,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
        SUM(CASE WHEN status LIKE 'failed%' THEN 1 ELSE 0 END) as failed_count
    FROM email_logs el
    LEFT JOIN candidates c ON el.candidate_id = c.id
    WHERE c.user_id = :user_id
");
$stats_stmt->execute([':user_id' => $user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Logs - HR Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .stats-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stats-card.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stats-card.sent {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .stats-card.failed {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .log-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-sent {
            background-color: #d4edda;
            color: #155724;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-subject {
            font-weight: 500;
            color: #333;
        }
        .email-details {
            font-size: 0.9rem;
            color: #666;
        }
        .pagination-wrapper {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-envelope-paper"></i> Email Logs</h2>
                <p class="text-muted mb-0">Track all email communications sent from the system</p>
            </div>
            <a href="?page=dashboard" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card total">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="stats-number"><?php echo number_format($stats['total_emails']); ?></p>
                            <p class="stats-label mb-0">Total Emails</p>
                        </div>
                        <i class="bi bi-envelope-fill" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card sent">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="stats-number"><?php echo number_format($stats['sent_count']); ?></p>
                            <p class="stats-label mb-0">Successfully Sent</p>
                        </div>
                        <i class="bi bi-check-circle-fill" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card failed">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="stats-number"><?php echo number_format($stats['failed_count']); ?></p>
                            <p class="stats-label mb-0">Failed to Send</p>
                        </div>
                        <i class="bi bi-x-circle-fill" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="email_logs">
                
                <div class="col-md-4">
                    <label class="form-label"><i class="bi bi-funnel"></i> Status Filter</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="sent" <?php echo $status_filter === 'sent' ? 'selected' : ''; ?>>Sent Only</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed Only</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-search"></i> Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search candidate name, email, or subject..." 
                           value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Email Logs Table -->
        <div class="log-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>Candidate</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="text-muted mt-3 mb-0">No email logs found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <div class="email-details">
                                            <i class="bi bi-calendar3"></i>
                                            <?php echo date('M d, Y', strtotime($log['sent_at'])); ?>
                                            <br>
                                            <i class="bi bi-clock"></i>
                                            <?php echo date('h:i A', strtotime($log['sent_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($log['candidate_name'] ?? 'N/A'); ?></strong>
                                            <?php if ($log['job_title']): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="bi bi-briefcase"></i>
                                                    <?php echo htmlspecialchars($log['job_title']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($log['recipient']); ?>" class="text-decoration-none">
                                            <i class="bi bi-envelope"></i>
                                            <?php echo htmlspecialchars($log['recipient']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="email-subject">
                                            <?php echo htmlspecialchars($log['subject']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($log['status'] === 'sent'): ?>
                                            <span class="status-badge status-sent">
                                                <i class="bi bi-check-circle"></i> Sent
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-failed">
                                                <i class="bi bi-x-circle"></i> Failed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detailsModal<?php echo $log['id']; ?>">
                                            <i class="bi bi-info-circle"></i> View
                                        </button>
                                    </td>
                                </tr>

                                <!-- Details Modal -->
                                <div class="modal fade" id="detailsModal<?php echo $log['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Email Log Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th width="30%">Candidate ID</th>
                                                        <td><?php echo $log['candidate_id']; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Candidate Name</th>
                                                        <td><?php echo htmlspecialchars($log['candidate_name'] ?? 'N/A'); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Recipient Email</th>
                                                        <td><?php echo htmlspecialchars($log['recipient']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Subject</th>
                                                        <td><?php echo htmlspecialchars($log['subject']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Status</th>
                                                        <td>
                                                            <?php if ($log['status'] === 'sent'): ?>
                                                                <span class="status-badge status-sent">
                                                                    <i class="bi bi-check-circle"></i> Successfully Sent
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="status-badge status-failed">
                                                                    <i class="bi bi-x-circle"></i> <?php echo htmlspecialchars($log['status']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Sent At</th>
                                                        <td><?php echo date('F j, Y g:i A', strtotime($log['sent_at'])); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>User Agent</th>
                                                        <td><small><?php echo htmlspecialchars($log['user_agent'] ?? 'N/A'); ?></small></td>
                                                    </tr>
                                                    <tr>
                                                        <th>IP Address</th>
                                                        <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper">
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=email_logs&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $page - 1; ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=email_logs&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=email_logs&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $page + 1; ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

        <!-- Summary Footer -->
        <div class="text-center mt-4 text-muted">
            <small>
                Showing <?php echo count($logs); ?> of <?php echo number_format($total_records); ?> email logs
                <?php if ($success_rate = ($stats['total_emails'] > 0 ? round(($stats['sent_count'] / $stats['total_emails']) * 100, 1) : 0)): ?>
                    | Success Rate: <strong><?php echo $success_rate; ?>%</strong>
                <?php endif; ?>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
