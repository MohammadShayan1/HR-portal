<?php
/**
 * Candidate Interview Scheduling Page
 * Public page for candidates to select interview slots
 */

require_once __DIR__ . '/functions/db.php';
require_once __DIR__ . '/functions/interview_slots.php';

$token = $_GET['token'] ?? '';
$pdo = get_db();

if (empty($token)) {
    die('Invalid or missing scheduling token');
}

// Get candidate and available slots
$stmt = $pdo->prepare("
    SELECT c.*, j.title as job_title, j.user_id, u.company_name, u.email as company_email
    FROM candidates c
    JOIN jobs j ON c.job_id = j.id
    JOIN users u ON j.user_id = u.id
    WHERE c.scheduling_token = ?
");
$stmt->execute([$token]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidate) {
    die('Invalid scheduling token');
}

// Check if already scheduled
if ($candidate['slot_id']) {
    $stmt = $pdo->prepare("SELECT * FROM interview_slots WHERE id = ?");
    $stmt->execute([$candidate['slot_id']]);
    $booked_slot = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get available slots
$stmt = $pdo->prepare("
    SELECT * FROM interview_slots 
    WHERE user_id = ? 
    AND status = 'available'
    AND slot_date >= date('now')
    AND slot_date <= date('now', '+30 days')
    ORDER BY slot_date, slot_time
");
$stmt->execute([$candidate['user_id']]);
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group slots by date
$grouped_slots = [];
foreach ($slots as $slot) {
    $date = $slot['slot_date'];
    if (!isset($grouped_slots[$date])) {
        $grouped_slots[$date] = [];
    }
    $grouped_slots[$date][] = $slot;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Your Interview - <?php echo htmlspecialchars($candidate['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .scheduling-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .content-section {
            padding: 40px;
        }
        .slot-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .slot-card:hover {
            border-color: #0d6efd;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        .slot-card.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .date-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0 15px 0;
            border-left: 4px solid #0d6efd;
        }
        .time-badge {
            background-color: #0d6efd;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
        }
        .success-message {
            background-color: #d4edda;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="scheduling-container">
        <div class="header-section">
            <h1><i class="bi bi-calendar-check"></i> Schedule Your Interview</h1>
            <p class="mb-0 mt-3" style="font-size: 1.1rem;">
                <?php echo htmlspecialchars($candidate['company_name']); ?>
            </p>
        </div>

        <div class="content-section">
            <?php if (isset($booked_slot)): ?>
                <!-- Already Scheduled -->
                <div class="success-message">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h2 class="mt-3 text-success">Interview Already Scheduled!</h2>
                    <p class="lead">Hello <?php echo htmlspecialchars($candidate['name']); ?>,</p>
                    <p>Your interview for <strong><?php echo htmlspecialchars($candidate['job_title']); ?></strong> has been confirmed.</p>
                    
                    <div class="alert alert-info mt-4">
                        <h5><i class="bi bi-calendar3"></i> Interview Details</h5>
                        <p class="mb-1">
                            <strong>Date:</strong> <?php echo date('l, F j, Y', strtotime($booked_slot['slot_date'])); ?>
                        </p>
                        <p class="mb-1">
                            <strong>Time:</strong> <?php echo date('g:i A', strtotime($booked_slot['slot_time'])); ?>
                        </p>
                        <p class="mb-0">
                            <strong>Duration:</strong> <?php echo $booked_slot['duration']; ?> minutes
                        </p>
                        <?php if ($booked_slot['meeting_link']): ?>
                            <hr>
                            <a href="<?php echo htmlspecialchars($booked_slot['meeting_link']); ?>" class="btn btn-primary" target="_blank">
                                <i class="bi bi-box-arrow-up-right"></i> Join Meeting (on interview day)
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-muted">
                        <small>A confirmation email has been sent to <?php echo htmlspecialchars($candidate['email']); ?></small>
                    </p>
                </div>
            
            <?php elseif (empty($grouped_slots)): ?>
                <!-- No Slots Available -->
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <h4>No Available Slots</h4>
                    <p>Unfortunately, there are no available interview slots at this time. Please contact us directly to schedule your interview.</p>
                    <p class="mb-0">
                        <strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($candidate['company_email']); ?>">
                            <?php echo htmlspecialchars($candidate['company_email']); ?>
                        </a>
                    </p>
                </div>
            
            <?php else: ?>
                <!-- Slot Selection -->
                <div class="mb-4">
                    <h3>Hello <?php echo htmlspecialchars($candidate['name']); ?>! 👋</h3>
                    <p class="text-muted">
                        Please select a convenient time slot for your interview for the position of 
                        <strong><?php echo htmlspecialchars($candidate['job_title']); ?></strong>
                    </p>
                </div>

                <div id="slotsContainer">
                    <?php foreach ($grouped_slots as $date => $date_slots): ?>
                        <div class="date-header">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar3"></i>
                                <?php echo date('l, F j, Y', strtotime($date)); ?>
                            </h5>
                        </div>
                        
                        <div class="row">
                            <?php foreach ($date_slots as $slot): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="slot-card" onclick="selectSlot(<?php echo $slot['id']; ?>, this)">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="time-badge">
                                                <i class="bi bi-clock"></i>
                                                <?php echo date('g:i A', strtotime($slot['slot_time'])); ?>
                                            </span>
                                            <span class="text-muted">
                                                <?php echo $slot['duration']; ?> min
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-4">
                    <button id="confirmBtn" class="btn btn-success btn-lg" onclick="confirmBooking()" disabled>
                        <i class="bi bi-check-circle"></i> Confirm Interview
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSlotId = null;

        function selectSlot(slotId, element) {
            // Remove previous selection
            document.querySelectorAll('.slot-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Select new slot
            element.classList.add('selected');
            selectedSlotId = slotId;
            
            // Enable confirm button
            document.getElementById('confirmBtn').disabled = false;
        }

        function confirmBooking() {
            if (!selectedSlotId) {
                alert('Please select a time slot');
                return;
            }

            if (!confirm('Are you sure you want to book this interview slot?')) {
                return;
            }

            const formData = new FormData();
            formData.append('slot_id', selectedSlotId);
            formData.append('token', '<?php echo htmlspecialchars($token); ?>');

            fetch('functions/actions.php?action=book_interview_slot', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Interview scheduled successfully! Check your email for confirmation.');
                    location.reload();
                } else {
                    alert('❌ Error: ' + (data.error || 'Failed to book slot'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>
</html>
