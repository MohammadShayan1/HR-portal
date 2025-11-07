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
            <button class="btn btn-danger me-2" id="bulkDeleteBtn" onclick="toggleBulkDelete()" style="display:none;">
                <i class="bi bi-trash"></i> Bulk Delete
            </button>
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
            <div id="bulkActionBar" class="alert alert-info" style="display:none;">
                <div class="d-flex justify-content-between align-items-center">
                    <span>
                        <strong><span id="selectedCount">0</span></strong> slot(s) selected
                    </span>
                    <div>
                        <button class="btn btn-sm btn-secondary" onclick="cancelBulkDelete()">
                            <i class="bi bi-x"></i> Cancel
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="confirmBulkDelete()">
                            <i class="bi bi-trash"></i> Delete Selected
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th id="checkboxHeader" style="display:none;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                            </th>
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
                                <td colspan="8" class="text-center py-4">
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
                                    <td class="checkbox-cell" style="display:none;">
                                        <?php if ($slot['status'] === 'available'): ?>
                                            <input type="checkbox" class="slot-checkbox" value="<?php echo $slot['id']; ?>" onchange="updateSelectedCount()">
                                        <?php endif; ?>
                                    </td>
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

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-info-circle"></i> Booking Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Interview Date</label>
                        <p class="fw-bold" id="bookingDate">-</p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="text-muted small">Time</label>
                        <p class="fw-bold" id="bookingTime">-</p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="text-muted small">Duration</label>
                        <p class="fw-bold" id="bookingDuration">-</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small"><i class="bi bi-person"></i> Candidate Name</label>
                        <p class="fw-bold" id="bookingCandidate">-</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small"><i class="bi bi-envelope"></i> Email</label>
                        <p><a href="" id="bookingEmail" class="text-decoration-none">-</a></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small"><i class="bi bi-telephone"></i> Phone</label>
                        <p class="fw-bold" id="bookingPhone">-</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small"><i class="bi bi-briefcase"></i> Position</label>
                        <p class="fw-bold" id="bookingJob">-</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small"><i class="bi bi-flag"></i> Status</label>
                        <p><span class="badge bg-warning" id="bookingStatus">-</span></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small"><i class="bi bi-clock-history"></i> Booked At</label>
                        <p class="fw-bold" id="bookingBookedAt">-</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="text-muted small"><i class="bi bi-camera-video"></i> Meeting Link</label>
                        <p id="bookingMeetingLink">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function createSlots() {
    const form = document.getElementById('createSlotsForm');
    const formData = new FormData(form);
    const createButton = document.querySelector('[onclick="createSlots()"]');
    const originalButtonText = createButton.innerHTML;
    
    // Disable button and show loading state
    createButton.disabled = true;
    createButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating Slots...';
    
    // Show loading modal
    Swal.fire({
        title: 'Creating Slots...',
        html: 'Please wait while we create your interview slots.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('functions/actions.php?action=create_interview_slots', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Re-enable button and restore text
        createButton.disabled = false;
        createButton.innerHTML = originalButtonText;
        
        if (data.success) {
            let message = `Successfully created ${data.count} interview slots!`;
            
            if (data.zoom_enabled && !data.warning) {
                message += '<br><br>✅ Zoom meetings created for each slot!';
            } else if (data.warning) {
                message += '<br><br>⚠️ ' + data.warning;
                if (data.zoom_error) {
                    message += '<br><br>Error: ' + data.zoom_error;
                }
            }
            
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                html: message,
                showConfirmButton: true,
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.error || 'Failed to create slots',
                showConfirmButton: true
            });
        }
    })
    .catch(error => {
        // Re-enable button on error
        createButton.disabled = false;
        createButton.innerHTML = originalButtonText;
        
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred while creating slots',
            showConfirmButton: true
        });
    });
}

function deleteSlot(slotId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to delete this slot?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`functions/actions.php?action=delete_interview_slot&slot_id=${slotId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Slot deleted successfully',
                        showConfirmButton: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.error || 'Failed to delete slot',
                        showConfirmButton: true
                    });
                }
            });
        }
    });
}

function editSlot(slotId) {
    // TODO: Implement edit functionality
    Swal.fire({
        icon: 'info',
        title: 'Coming Soon',
        text: 'Edit functionality will be available soon!',
        showConfirmButton: true
    });
}

function viewBooking(slotId) {
    // Fetch booking details and show in modal
    fetch(`functions/actions.php?action=get_booking_details&slot_id=${slotId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.error,
                    showConfirmButton: true
                });
                return;
            }
            
            // Populate modal with booking details
            document.getElementById('bookingDate').textContent = data.slot_date;
            document.getElementById('bookingTime').textContent = data.slot_time;
            document.getElementById('bookingDuration').textContent = data.duration + ' minutes';
            document.getElementById('bookingCandidate').textContent = data.candidate_name || 'N/A';
            document.getElementById('bookingEmail').textContent = data.candidate_email || 'N/A';
            document.getElementById('bookingEmail').href = 'mailto:' + (data.candidate_email || '');
            document.getElementById('bookingPhone').textContent = data.candidate_phone || 'N/A';
            document.getElementById('bookingJob').textContent = data.job_title || 'N/A';
            document.getElementById('bookingStatus').textContent = data.status || 'N/A';
            document.getElementById('bookingBookedAt').textContent = data.booked_at ? new Date(data.booked_at).toLocaleString() : 'N/A';
            
            // Meeting link
            const meetingLinkEl = document.getElementById('bookingMeetingLink');
            if (data.meeting_link) {
                meetingLinkEl.innerHTML = `<a href="${data.meeting_link}" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-box-arrow-up-right"></i> Join Meeting</a>`;
            } else {
                meetingLinkEl.textContent = 'No meeting link';
            }
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching booking details:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load booking details',
                showConfirmButton: true
            });
        });
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

// Bulk delete functionality
let bulkDeleteMode = false;

function toggleBulkDelete() {
    bulkDeleteMode = !bulkDeleteMode;
    
    const checkboxHeader = document.getElementById('checkboxHeader');
    const checkboxCells = document.querySelectorAll('.checkbox-cell');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const bulkBtn = document.getElementById('bulkDeleteBtn');
    
    if (bulkDeleteMode) {
        // Show checkboxes
        checkboxHeader.style.display = '';
        checkboxCells.forEach(cell => cell.style.display = '');
        bulkActionBar.style.display = 'block';
        bulkBtn.innerHTML = '<i class="bi bi-x"></i> Cancel Selection';
        bulkBtn.classList.remove('btn-danger');
        bulkBtn.classList.add('btn-secondary');
    } else {
        // Hide checkboxes
        checkboxHeader.style.display = 'none';
        checkboxCells.forEach(cell => cell.style.display = 'none');
        bulkActionBar.style.display = 'none';
        bulkBtn.innerHTML = '<i class="bi bi-trash"></i> Bulk Delete';
        bulkBtn.classList.remove('btn-secondary');
        bulkBtn.classList.add('btn-danger');
        
        // Uncheck all
        document.querySelectorAll('.slot-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAll').checked = false;
        updateSelectedCount();
    }
}

function toggleSelectAll(checkbox) {
    document.querySelectorAll('.slot-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const selected = document.querySelectorAll('.slot-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = selected;
}

function cancelBulkDelete() {
    toggleBulkDelete();
}

function confirmBulkDelete() {
    const selectedIds = Array.from(document.querySelectorAll('.slot-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select at least one slot to delete',
            showConfirmButton: true
        });
        return;
    }
    
    Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete <strong>${selectedIds.length}</strong> slot(s).<br><br>This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete them!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'Deleting Slots...',
            html: 'Please wait while we delete the selected slots.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Delete slots one by one
        let deleted = 0;
        let failed = 0;
        
        const deletePromises = selectedIds.map(slotId => 
            fetch(`functions/actions.php?action=delete_interview_slot&slot_id=${slotId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    deleted++;
                } else {
                    failed++;
                }
            })
            .catch(() => failed++)
        );
        
        Promise.all(deletePromises).then(() => {
            if (failed === 0) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: `Successfully deleted ${deleted} slot(s)!`,
                    showConfirmButton: true
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Partial Success',
                    html: `Deleted ${deleted} slot(s).<br>${failed} slot(s) failed to delete (may be booked).`,
                    showConfirmButton: true
                }).then(() => {
                    location.reload();
                });
            }
        });
    });
}

// Show bulk delete button if there are available slots
window.addEventListener('DOMContentLoaded', function() {
    const availableSlots = document.querySelectorAll('.slot-checkbox').length;
    if (availableSlots > 0) {
        document.getElementById('bulkDeleteBtn').style.display = 'inline-block';
    }
    
    // Restore previously selected days from localStorage
    const savedDays = localStorage.getItem('interview_slot_days');
    if (savedDays) {
        const days = JSON.parse(savedDays);
        // Uncheck all first
        document.querySelectorAll('[name="days[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        // Check saved days
        days.forEach(day => {
            const checkbox = document.querySelector(`[name="days[]"][value="${day}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }
    
    // Save selected days when they change
    document.querySelectorAll('[name="days[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectedDays = Array.from(document.querySelectorAll('[name="days[]"]:checked'))
                .map(cb => cb.value);
            localStorage.setItem('interview_slot_days', JSON.stringify(selectedDays));
        });
    });
});
</script>

<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
