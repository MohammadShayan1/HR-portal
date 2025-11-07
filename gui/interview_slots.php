<?php
/**
 * Interview Slots Management
 * Create and manage interview time slots
 */

require_once __DIR__ . '/../functions/db.php';
require_once __DIR__ . '/../functions/auth.php';

$user_id = get_current_user_id();
$pdo = get_db();

// Get all slots for this user
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        c.name as candidate_name,
        c.email as candidate_email,
        j.title as job_title
    FROM interview_slots s
    LEFT JOIN candidates c ON s.candidate_id = c.id
    LEFT JOIN jobs j ON c.job_id = j.id
    WHERE s.user_id = ?
    ORDER BY s.slot_date DESC, s.slot_time DESC
");
$stmt->execute([$user_id]);
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => count($slots),
    'available' => count(array_filter($slots, fn($s) => $s['status'] === 'available')),
    'booked' => count(array_filter($slots, fn($s) => $s['status'] === 'booked')),
];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-calendar-check"></i> Interview Slots Management</h2>
            <p class="text-muted">Create and manage interview time slots for candidates</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSlotModal">
                <i class="bi bi-plus-circle"></i> Create Slots
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body">
                    <h3 class="text-primary"><?php echo $stats['total']; ?></h3>
                    <p class="mb-0">Total Slots</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <h3 class="text-success"><?php echo $stats['available']; ?></h3>
                    <p class="mb-0">Available Slots</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body">
                    <h3 class="text-warning"><?php echo $stats['booked']; ?></h3>
                    <p class="mb-0">Booked Slots</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Slots Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Candidate</th>
                            <th>Meeting Link</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($slots)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="text-muted mt-2">No interview slots created yet</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSlotModal">
                                        Create Your First Slot
                                    </button>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($slots as $slot): ?>
                                <tr>
                                    <td>
                                        <i class="bi bi-calendar3"></i>
                                        <?php echo date('M d, Y', strtotime($slot['slot_date'])); ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-clock"></i>
                                        <?php echo date('h:i A', strtotime($slot['slot_time'])); ?>
                                    </td>
                                    <td><?php echo $slot['duration']; ?> mins</td>
                                    <td>
                                        <?php if ($slot['status'] === 'available'): ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php elseif ($slot['status'] === 'booked'): ?>
                                            <span class="badge bg-warning">Booked</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Expired</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($slot['candidate_name']): ?>
                                            <strong><?php echo htmlspecialchars($slot['candidate_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($slot['job_title'] ?? ''); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($slot['meeting_link']): ?>
                                            <a href="<?php echo htmlspecialchars($slot['meeting_link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-box-arrow-up-right"></i> Join
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($slot['status'] === 'available'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="editSlot(<?php echo $slot['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteSlot(<?php echo $slot['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-info" onclick="viewBooking(<?php echo $slot['id']; ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Slot Modal -->
<div class="modal fade" id="createSlotModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Interview Slots</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createSlotsForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date Range</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" required>
                            <small class="text-muted">Start date for slots</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date (Optional)</label>
                            <input type="date" class="form-control" id="endDate" name="end_date">
                            <small class="text-muted">Leave empty for single day</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" name="start_time" value="09:00" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" class="form-control" name="end_time" value="17:00" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Duration (minutes)</label>
                            <select class="form-select" name="duration" required>
                                <option value="15">15 minutes</option>
                                <option value="30" selected>30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">60 minutes</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="createZoom" name="create_zoom" value="yes" checked>
                            <label class="form-check-label" for="createZoom">
                                <strong>Automatically create Zoom meetings for each slot</strong>
                            </label>
                        </div>
                        <small class="text-muted">Each time slot will have its own unique Zoom meeting link</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Days of Week</label>
                        <div class="btn-group w-100" role="group">
                            <input type="checkbox" class="btn-check" id="day1" name="days[]" value="1" checked>
                            <label class="btn btn-outline-primary" for="day1">Mon</label>
                            
                            <input type="checkbox" class="btn-check" id="day2" name="days[]" value="2" checked>
                            <label class="btn btn-outline-primary" for="day2">Tue</label>
                            
                            <input type="checkbox" class="btn-check" id="day3" name="days[]" value="3" checked>
                            <label class="btn btn-outline-primary" for="day3">Wed</label>
                            
                            <input type="checkbox" class="btn-check" id="day4" name="days[]" value="4" checked>
                            <label class="btn btn-outline-primary" for="day4">Thu</label>
                            
                            <input type="checkbox" class="btn-check" id="day5" name="days[]" value="5" checked>
                            <label class="btn btn-outline-primary" for="day5">Fri</label>
                            
                            <input type="checkbox" class="btn-check" id="day6" name="days[]" value="6">
                            <label class="btn btn-outline-primary" for="day6">Sat</label>
                            
                            <input type="checkbox" class="btn-check" id="day0" name="days[]" value="0">
                            <label class="btn btn-outline-primary" for="day0">Sun</label>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Preview:</strong> This will create slots from <span id="previewText">selected dates and times</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createSlots()">
                    <i class="bi bi-plus-circle"></i> Create Slots
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function createSlots() {
    const form = document.getElementById('createSlotsForm');
    const formData = new FormData(form);
    
    fetch('functions/actions.php?action=create_interview_slots', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const zoomMsg = data.zoom_enabled ? '\n\n✅ Zoom meetings created for each slot!' : '';
            alert(`Successfully created ${data.count} interview slots!${zoomMsg}`);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to create slots'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating slots');
    });
}

function deleteSlot(slotId) {
    if (!confirm('Are you sure you want to delete this slot?')) return;
    
    fetch(`functions/actions.php?action=delete_interview_slot&slot_id=${slotId}`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Slot deleted successfully');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to delete slot'));
        }
    });
}

function editSlot(slotId) {
    // TODO: Implement edit functionality
    alert('Edit functionality coming soon!');
}

function viewBooking(slotId) {
    // TODO: Implement view booking details
    alert('View booking details coming soon!');
}

// Update preview text
document.querySelectorAll('#createSlotsForm input').forEach(input => {
    input.addEventListener('change', updatePreview);
});

function updatePreview() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const startTime = document.querySelector('[name="start_time"]').value;
    const endTime = document.querySelector('[name="end_time"]').value;
    const duration = document.querySelector('[name="duration"]').value;
    
    if (startDate && startTime && endTime) {
        const dateText = endDate ? `${startDate} to ${endDate}` : startDate;
        const slotsPerDay = Math.floor((parseTimeToMinutes(endTime) - parseTimeToMinutes(startTime)) / duration);
        document.getElementById('previewText').textContent = `${dateText}, ${startTime} to ${endTime}, ~${slotsPerDay} slots per day`;
    }
}

function parseTimeToMinutes(time) {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
}
</script>

<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
