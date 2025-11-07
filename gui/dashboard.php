<?php
/**
 * Dashboard Page
 */
$page_title = 'Dashboard - HR Portal';

// Get statistics for current user
$pdo = get_db();
$user_id = get_current_user_id();

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jobs WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_jobs = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidates c JOIN jobs j ON c.job_id = j.id WHERE j.user_id = ?");
$stmt->execute([$user_id]);
$total_candidates = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidates c JOIN jobs j ON c.job_id = j.id WHERE j.user_id = ? AND c.status = 'Applied'");
$stmt->execute([$user_id]);
$pending_interviews = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidates c JOIN jobs j ON c.job_id = j.id WHERE j.user_id = ? AND c.status = 'Interview Completed'");
$stmt->execute([$user_id]);
$completed_interviews = $stmt->fetch()['count'];

// Recent candidates for current user
$stmt = $pdo->prepare("
    SELECT c.*, j.title as job_title 
    FROM candidates c 
    JOIN jobs j ON c.job_id = j.id 
    WHERE j.user_id = ?
    ORDER BY c.applied_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_candidates = $stmt->fetchAll();

// Get upcoming meetings for current user (including generic events)
$stmt = $pdo->prepare("
    SELECT m.*, 
           c.name as candidate_name, 
           c.email as candidate_email, 
           j.title as job_title,
           COALESCE(m.event_type, 'meeting') as event_type,
           COALESCE(m.location, '') as location
    FROM meetings m
    LEFT JOIN candidates c ON m.candidate_id = c.id
    LEFT JOIN jobs j ON c.job_id = j.id
    WHERE m.user_id = ?
    ORDER BY m.meeting_date ASC, m.meeting_time ASC
");
$stmt->execute([$user_id]);
$meetings = $stmt->fetchAll();

// Convert meetings to calendar events format
$calendar_events = [];
foreach ($meetings as $meeting) {
    // Handle both candidate-linked meetings and generic events
    $event_title = $meeting['title'];
    if ($meeting['candidate_name']) {
        $event_title .= ' - ' . $meeting['candidate_name'];
    }
    
    $calendar_events[] = [
        'id' => $meeting['id'],
        'title' => $event_title,
        'start' => $meeting['meeting_date'] . 'T' . $meeting['meeting_time'],
        'end' => date('Y-m-d\TH:i:s', strtotime($meeting['meeting_date'] . ' ' . $meeting['meeting_time'] . ' +' . $meeting['duration'] . ' minutes')),
        'backgroundColor' => match($meeting['status']) {
            'scheduled' => '#667eea',
            'completed' => '#10b981',
            'cancelled' => '#ef4444',
            default => '#6b7280'
        },
        'extendedProps' => [
            'source' => 'internal',
            'event_type' => $meeting['event_type'],
            'candidate_name' => $meeting['candidate_name'],
            'candidate_email' => $meeting['candidate_email'],
            'job_title' => $meeting['job_title'],
            'description' => $meeting['description'] ?? '',
            'location' => $meeting['location'],
            'duration' => $meeting['duration'],
            'zoom_join_url' => $meeting['zoom_join_url'],
            'zoom_start_url' => $meeting['zoom_start_url'],
            'status' => $meeting['status'],
            'sync_google' => $meeting['sync_google'] ?? 0,
            'sync_outlook' => $meeting['sync_outlook'] ?? 0
        ]
    ];
}

// Fetch Google Calendar events if connected
require_once __DIR__ . '/../functions/calendar_sync.php';
$google_sync_enabled = get_setting('google_calendar_sync', $user_id) === '1';
if ($google_sync_enabled) {
    $google_events = fetch_google_calendar_events($user_id);
    if (isset($google_events['items']) && is_array($google_events['items'])) {
        foreach ($google_events['items'] as $event) {
            // Skip events without start time
            if (!isset($event['start'])) continue;
            
            $calendar_events[] = [
                'id' => 'google_' . $event['id'],
                'title' => '📅 ' . ($event['summary'] ?? 'Untitled'),
                'start' => $event['start']['dateTime'] ?? $event['start']['date'],
                'end' => $event['end']['dateTime'] ?? $event['end']['date'],
                'backgroundColor' => '#34a853',
                'borderColor' => '#34a853',
                'extendedProps' => [
                    'source' => 'google',
                    'description' => $event['description'] ?? '',
                    'location' => $event['location'] ?? '',
                    'html_link' => $event['htmlLink'] ?? ''
                ]
            ];
        }
    }
}

// Fetch Outlook Calendar events if connected
$outlook_sync_enabled = get_setting('outlook_calendar_sync', $user_id) === '1';
if ($outlook_sync_enabled) {
    $outlook_events = fetch_outlook_calendar_events($user_id);
    if (isset($outlook_events['value']) && is_array($outlook_events['value'])) {
        foreach ($outlook_events['value'] as $event) {
            // Skip events without start time
            if (!isset($event['start'])) continue;
            
            $calendar_events[] = [
                'id' => 'outlook_' . $event['id'],
                'title' => '📧 ' . ($event['subject'] ?? 'Untitled'),
                'start' => $event['start']['dateTime'],
                'end' => $event['end']['dateTime'],
                'backgroundColor' => '#0078d4',
                'borderColor' => '#0078d4',
                'extendedProps' => [
                    'source' => 'outlook',
                    'description' => strip_tags($event['body']['content'] ?? ''),
                    'location' => $event['location']['displayName'] ?? '',
                    'web_link' => $event['webLink'] ?? ''
                ]
            ];
        }
    }
}

// Get theme colors
$theme_primary = get_setting('theme_primary') ?? '#667eea';
$theme_secondary = get_setting('theme_secondary') ?? '#764ba2';
$theme_accent = get_setting('theme_accent') ?? '#f093fb';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1"><i class="bi bi-speedometer2"></i> Dashboard</h1>
                <p class="text-muted mb-0">Welcome back! Here's your recruitment overview.</p>
            </div>
            <a href="index.php?page=jobs" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New Job
            </a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%); color: white;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title opacity-75 mb-2">Total Jobs</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $total_jobs; ?></h2>
                    </div>
                    <div class="icon-bg">
                        <i class="bi bi-briefcase" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 opacity-75">
                <small><i class="bi bi-arrow-up-right"></i> Active positions</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title opacity-75 mb-2">Total Candidates</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $total_candidates; ?></h2>
                    </div>
                    <div class="icon-bg">
                        <i class="bi bi-people" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 opacity-75">
                <small><i class="bi bi-person-check"></i> Applications received</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title opacity-75 mb-2">Pending Interviews</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $pending_interviews; ?></h2>
                    </div>
                    <div class="icon-bg">
                        <i class="bi bi-clock-history" style="font-size: 3rem; opacity: 0.2;"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 opacity-75">
                <small><i class="bi bi-hourglass-split"></i> Awaiting response</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title opacity-75 mb-2">Completed</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $completed_interviews; ?></h2>
                    </div>
                    <div class="icon-bg">
                        <i class="bi bi-check-circle" style="font-size: 3rem; opacity: 0.2;"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 opacity-75">
                <small><i class="bi bi-clipboard-check"></i> Ready for review</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> Recent Candidates</h5>
                    <a href="index.php?page=candidates" class="btn btn-sm btn-outline-primary">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_candidates)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ddd;"></i>
                        <p class="text-muted mt-3 mb-0">No candidates yet. Create a job to start receiving applications!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th class="border-0">Name</th>
                                    <th class="border-0">Email</th>
                                    <th class="border-0">Job Position</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Applied</th>
                                    <th class="border-0 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_candidates as $candidate): ?>
                                    <tr>
                                        <td class="align-middle">
                                            <strong><?php echo sanitize($candidate['name']); ?></strong>
                                        </td>
                                        <td class="align-middle">
                                            <small class="text-muted"><?php echo sanitize($candidate['email']); ?></small>
                                        </td>
                                        <td class="align-middle">
                                            <?php echo sanitize($candidate['job_title']); ?>
                                        </td>
                                        <td class="align-middle">
                                            <?php
                                            $badge_class = match($candidate['status']) {
                                                'Applied' => 'bg-warning text-dark',
                                                'Interview Completed' => 'bg-info',
                                                'Report Ready' => 'bg-success',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo sanitize($candidate['status']); ?>
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <small><?php echo format_date($candidate['applied_at']); ?></small>
                                        </td>
                                        <td class="align-middle text-end">
                                            <a href="index.php?page=candidates&job_id=<?php echo $candidate['job_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="calendar-wrapper">
            <!-- Left Panel - Today's Info -->
            <div class="calendar-sidebar">
                <div class="today-display">
                    <div class="current-date-large"><?php echo date('d'); ?></div>
                    <div class="current-day"><?php echo strtoupper(date('l')); ?></div>
                    <div class="current-month-year"><?php echo date('F Y'); ?></div>
                </div>
                
                <div class="today-events">
                    <h6 class="text-white mb-3">Today's Events</h6>
                    <?php 
                    $today = date('Y-m-d');
                    $today_meetings = array_filter($meetings, function($m) use ($today) {
                        return $m['meeting_date'] === $today;
                    });
                    ?>
                    <?php if (count($today_meetings) > 0): ?>
                        <ul class="events-list">
                            <?php foreach (array_slice($today_meetings, 0, 3) as $meeting): ?>
                                <li>
                                    <i class="bi bi-circle-fill me-2" style="font-size: 0.5rem;"></i>
                                    <?php echo htmlspecialchars($meeting['title']); ?>
                                    <small class="d-block ms-3 mt-1 opacity-75">
                                        <?php echo date('g:i A', strtotime($meeting['meeting_time'])); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-white-50 mb-0">No events scheduled for today</p>
                    <?php endif; ?>
                    <a href="#" class="view-all-link" onclick="window.calendarInstance.changeView('listWeek'); return false;">
                        View all events →
                    </a>
                </div>
                
                <div class="quick-actions mt-auto">
                    <button class="btn btn-light w-100 mb-2" onclick="openEventForm()">
                        <i class="bi bi-plus-lg me-2"></i>Create Event
                    </button>
                    <button class="btn btn-outline-light w-100" onclick="refreshCalendar()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                    </button>
                </div>
            </div>
            
            <!-- Right Panel - Calendar -->
            <div class="calendar-main">
                <div class="calendar-header-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Meeting Calendar</h5>
                            <small class="text-muted">Manage your interviews and events</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary px-3 py-2">
                                <i class="bi bi-calendar-check me-1"></i>
                                <?php echo count($meetings); ?> scheduled
                            </span>
                        </div>
                    </div>
                </div>
                
                <?php if (count($calendar_events) == 0 && !$google_sync_enabled && !$outlook_sync_enabled): ?>
                    <div class="alert alert-info mx-3 mt-3">
                        <i class="bi bi-info-circle"></i> Your calendar is empty. Click <strong>"Create Event"</strong> to get started, or <a href="index.php?page=settings" class="alert-link">connect your Google/Outlook calendar</a> to sync events.
                    </div>
                <?php endif; ?>
                
                <div id="calendar" class="p-3"></div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-rocket-takeoff"></i> Quick Start Guide</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex align-items-start">
                            <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">1</span>
                            <div>
                                <strong>Configure Gemini API</strong>
                                <p class="mb-0 text-muted small">Set up your API key in <a href="index.php?page=settings">Settings</a> to enable AI features</p>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex align-items-start">
                            <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">2</span>
                            <div>
                                <strong>Create Job Postings</strong>
                                <p class="mb-0 text-muted small">Use AI to generate professional <a href="index.php?page=jobs">job descriptions</a></p>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex align-items-start">
                            <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">3</span>
                            <div>
                                <strong>Share Application Links</strong>
                                <p class="mb-0 text-muted small">Send job links to candidates for easy application</p>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex align-items-start">
                            <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">4</span>
                            <div>
                                <strong>Review AI Reports</strong>
                                <p class="mb-0 text-muted small">Get intelligent evaluations and make informed decisions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-stars"></i> AI Features</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 rounded" style="background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);">
                            <i class="bi bi-file-text text-primary" style="font-size: 2rem;"></i>
                            <h6 class="mt-2 mb-1">Smart Descriptions</h6>
                            <small class="text-muted">AI-powered job descriptions</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
                            <i class="bi bi-chat-dots text-warning" style="font-size: 2rem;"></i>
                            <h6 class="mt-2 mb-1">Auto Interviews</h6>
                            <small class="text-muted">Tailored interview questions</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                            <i class="bi bi-graph-up text-info" style="font-size: 2rem;"></i>
                            <h6 class="mt-2 mb-1">Smart Analysis</h6>
                            <small class="text-muted">Objective candidate scoring</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded" style="background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%);">
                            <i class="bi bi-shield-check text-success" style="font-size: 2rem;"></i>
                            <h6 class="mt-2 mb-1">Self-Hosted</h6>
                            <small class="text-muted">Complete data privacy</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Meeting Details Modal -->
<div class="modal fade" id="meetingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-calendar-event"></i> Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="meetingDetails">
                <!-- Event details will be loaded here -->
            </div>
            <div class="modal-footer" id="modalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Event Modal -->
<div class="modal fade" id="eventFormModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventFormTitle"><i class="bi bi-plus-circle"></i> Add Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <input type="hidden" id="eventId" name="event_id">
                    <input type="hidden" id="eventSource" name="event_source" value="internal">
                    
                    <div class="mb-3">
                        <label for="eventType" class="form-label">Event Type</label>
                        <select class="form-select" id="eventType" name="event_type" required>
                            <option value="meeting">Meeting</option>
                            <option value="reminder">Reminder</option>
                            <option value="task">Task</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="eventTitle" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="eventTitle" name="event_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="eventDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="eventDescription" name="event_description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="eventDate" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="eventDate" name="event_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="eventTime" class="form-label">Time *</label>
                            <input type="time" class="form-control" id="eventTime" name="event_time" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="eventDuration" class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" id="eventDuration" name="event_duration" value="60" min="15" step="15">
                    </div>
                    
                    <div class="mb-3">
                        <label for="eventLocation" class="form-label">Location / Link</label>
                        <input type="text" class="form-control" id="eventLocation" name="event_location" placeholder="Meeting room or video link">
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="syncToGoogle" name="sync_google">
                        <label class="form-check-label" for="syncToGoogle">
                            Sync to Google Calendar
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="syncToOutlook" name="sync_outlook">
                        <label class="form-check-label" for="syncToOutlook">
                            Sync to Outlook Calendar
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEvent()">
                    <i class="bi bi-save"></i> Save Event
                </button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">

<style>
/* Calendar Wrapper - Split Design */
.calendar-wrapper {
    display: flex;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    min-height: 650px;
}

/* Left Sidebar - Colored Panel */
.calendar-sidebar {
    width: 320px;
    background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
    color: white;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    position: relative;
}

.calendar-sidebar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.05);
    pointer-events: none;
}

/* Today's Date Display */
.today-display {
    text-align: center;
    margin: 2rem 0;
    z-index: 1;
}

.current-date-large {
    font-size: 140px;
    font-weight: 700;
    line-height: 1;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.current-day {
    font-size: 28px;
    font-weight: 600;
    letter-spacing: 2px;
    margin-top: -20px;
}

.current-month-year {
    font-size: 16px;
    opacity: 0.9;
    margin-top: 0.5rem;
}

/* Today's Events */
.today-events {
    z-index: 1;
    margin-top: 1.5rem;
}

.today-events h6 {
    font-weight: 600;
    letter-spacing: 1px;
}

.events-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.events-list li {
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 0.9rem;
}

.events-list li:last-child {
    border-bottom: none;
}

.view-all-link {
    color: white;
    text-decoration: none;
    font-size: 0.9rem;
    display: inline-block;
    margin-top: 1rem;
    opacity: 0.9;
    transition: all 0.3s;
}

.view-all-link:hover {
    opacity: 1;
    color: white;
    transform: translateX(5px);
}

/* Quick Actions */
.quick-actions {
    margin-top: 2rem;
    z-index: 1;
}

.quick-actions .btn {
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-weight: 500;
    transition: all 0.3s;
}

.quick-actions .btn-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.quick-actions .btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Right Calendar Panel */
.calendar-main {
    flex: 1;
    background: white;
    display: flex;
    flex-direction: column;
}

.calendar-header-custom {
    padding: 1.5rem 2rem;
    border-bottom: 2px solid #f1f5f9;
}

.calendar-header-custom h5 {
    color: #1e293b;
    font-weight: 600;
    margin: 0;
}

/* Calendar Styling */
.fc {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.fc .fc-toolbar {
    padding: 1rem;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.fc .fc-toolbar-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Navigation Buttons */
.fc .fc-button {
    background: white;
    border: 2px solid #e2e8f0;
    color: #64748b;
    font-weight: 600;
    text-transform: capitalize;
    padding: 0.5rem 1.25rem;
    border-radius: 10px;
    transition: all 0.3s;
}

.fc .fc-button:hover {
    background: #f8fafc;
    border-color: <?php echo $theme_primary; ?>;
    color: <?php echo $theme_primary; ?>;
    transform: translateY(-2px);
}

.fc .fc-button:focus {
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.fc .fc-button-primary:not(:disabled):active,
.fc .fc-button-primary:not(:disabled).fc-button-active {
    background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
    border-color: <?php echo $theme_primary; ?>;
    color: white;
    transform: translateY(0);
}

.fc .fc-button-group > .fc-button {
    margin: 0;
}

/* Today Button - Special Style */
.fc .fc-today-button {
    background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
    border: none;
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.fc .fc-today-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

/* Calendar Cells */
.fc .fc-daygrid-day {
    transition: all 0.2s;
}

.fc .fc-daygrid-day:hover {
    background-color: #f8fafc;
    cursor: pointer;
}

.fc .fc-day-today {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
}

.fc .fc-day-today .fc-daygrid-day-number {
    background: linear-gradient(135deg, <?php echo $theme_primary; ?> 0%, <?php echo $theme_secondary; ?> 100%);
    color: white;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Events */
.fc-event {
    border: none;
    padding: 4px 8px;
    margin: 2px 0;
    border-radius: 6px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s;
    border-left: 3px solid rgba(0, 0, 0, 0.2);
}

.fc-event:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.fc-event-title {
    font-weight: 600;
}

/* Day Headers */
.fc .fc-col-header-cell {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    color: #64748b;
    padding: 1rem 0;
    border: none;
}

/* Grid Lines */
.fc .fc-scrollgrid {
    border-color: #e2e8f0;
    border-width: 2px;
}

.fc .fc-scrollgrid td,
.fc .fc-scrollgrid th {
    border-color: #e2e8f0;
}

/* More Link */
.fc .fc-more-link {
    color: <?php echo $theme_primary; ?>;
    font-weight: 600;
    font-size: 0.8rem;
}

.fc .fc-more-link:hover {
    color: <?php echo $theme_secondary; ?>;
}

/* Time Grid */
.fc .fc-timegrid-slot {
    height: 3rem;
}

.fc .fc-timegrid-slot-label {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

/* List View */
.fc-list-event:hover {
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, transparent 100%);
}

.fc-list-event-title {
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 992px) {
    .calendar-wrapper {
        flex-direction: column;
        border-radius: 15px;
    }
    
    .calendar-sidebar {
        width: 100%;
        min-height: auto;
        padding: 1.5rem;
    }
    
    .current-date-large {
        font-size: 100px;
    }
    
    .current-day {
        font-size: 24px;
    }
    
    .today-display {
        margin: 1rem 0;
    }
    
    .quick-actions {
        margin-top: 1rem;
        display: flex;
        gap: 0.5rem;
    }
    
    .quick-actions .btn {
        flex: 1;
    }
}

@media (max-width: 768px) {
    .fc .fc-toolbar {
        flex-direction: column;
    }
    
    .fc .fc-toolbar-title {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .fc .fc-button {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .calendar-header-custom {
        padding: 1rem;
    }
}
</style>

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    
    if (!calendarEl) {
        console.error('Calendar element not found!');
        return;
    }
    
    console.log('Initializing calendar...');
    console.log('Events:', <?php echo json_encode($calendar_events); ?>);
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: {
            today: 'Today',
            month: 'Month',
            week: 'Week',
            day: 'Day',
            list: 'List'
        },
        events: <?php echo json_encode($calendar_events); ?>,
        editable: true,
        droppable: true,
        eventDrop: function(info) {
            // Update event when dragged to new date
            updateEventDateTime(info.event);
        },
        eventResize: function(info) {
            // Update event when resized
            updateEventDateTime(info.event);
        },
        dateClick: function(info) {
            // Quick add event on date click
            openEventForm(info.dateStr);
        },
        eventClick: function(info) {
            // Show event details with edit/delete options
            showEventDetails(info.event);
        },
        eventColor: '<?php echo $theme_primary; ?>',
        height: 'auto',
        nowIndicator: true,
        navLinks: true,
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5],
            startTime: '09:00',
            endTime: '18:00',
        },
        dayMaxEvents: 3,
        slotMinTime: '08:00:00',
        slotMaxTime: '20:00:00',
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        }
    });
    
    calendar.render();
    console.log('Calendar rendered successfully');
    
    // Make calendar globally accessible
    window.calendarInstance = calendar;
    
    // Show event details function
    function showEventDetails(event) {
        var props = event.extendedProps;
        var html = '';
        var canEdit = props.source === 'internal' || !props.source;
        
        // Handle internal meetings (from HR Portal)
        if (props.source === 'internal') {
            html = `
                <div class="mb-3">
                    <span class="badge bg-primary">HR Portal Meeting</span>
                </div>
                <div class="mb-3">
                    <strong>Title:</strong> ${event.title}
                </div>
                <div class="mb-3">
                    <strong>Candidate:</strong> ${props.candidate_name}<br>
                    <strong>Email:</strong> ${props.candidate_email}
                </div>
                <div class="mb-3">
                    <strong>Position:</strong> ${props.job_title}
                </div>
                <div class="mb-3">
                    <strong>Date & Time:</strong> ${event.start.toLocaleString()}
                </div>
                <div class="mb-3">
                    <strong>Status:</strong> 
                    <span class="badge bg-${props.status === 'scheduled' ? 'primary' : props.status === 'completed' ? 'success' : 'danger'}">
                        ${props.status.charAt(0).toUpperCase() + props.status.slice(1)}
                    </span>
                </div>
            `;
            
            if (props.zoom_join_url) {
                html += `
                    <div class="mb-3">
                        <strong>Join Meeting:</strong><br>
                        <a href="${props.zoom_join_url}" target="_blank" class="btn btn-sm btn-primary mt-2">
                            <i class="bi bi-camera-video"></i> Join Zoom Meeting
                        </a>
                    </div>
                `;
            }
            
            if (props.zoom_start_url) {
                html += `
                    <div class="mb-3">
                        <strong>Start Meeting (Host):</strong><br>
                        <a href="${props.zoom_start_url}" target="_blank" class="btn btn-sm btn-success mt-2">
                            <i class="bi bi-play-circle"></i> Start as Host
                        </a>
                    </div>
                `;
            }
        }
        // Handle Google Calendar events
        else if (props.source === 'google') {
            html = `
                <div class="mb-3">
                    <span class="badge" style="background-color: #34a853;"><i class="bi bi-google"></i> Google Calendar</span>
                </div>
                <div class="mb-3">
                    <strong>Title:</strong> ${event.title.replace('📅 ', '')}
                </div>
                <div class="mb-3">
                    <strong>Date & Time:</strong> ${event.start.toLocaleString()}
                </div>
            `;
            
            if (props.description) {
                html += `
                    <div class="mb-3">
                        <strong>Description:</strong><br>
                        ${props.description}
                    </div>
                `;
            }
            
            if (props.location) {
                html += `
                    <div class="mb-3">
                        <strong>Location:</strong> ${props.location}
                    </div>
                `;
            }
            
            if (props.html_link) {
                html += `
                    <div class="mb-3">
                        <a href="${props.html_link}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right"></i> View in Google Calendar
                        </a>
                    </div>
                `;
            }
        }
        // Handle Outlook Calendar events
        else if (props.source === 'outlook') {
            html = `
                <div class="mb-3">
                    <span class="badge" style="background-color: #0078d4;"><i class="bi bi-microsoft"></i> Outlook Calendar</span>
                </div>
                <div class="mb-3">
                    <strong>Title:</strong> ${event.title.replace('📧 ', '')}
                </div>
                <div class="mb-3">
                    <strong>Date & Time:</strong> ${event.start.toLocaleString()}
                </div>
            `;
            
            if (props.description) {
                html += `
                    <div class="mb-3">
                        <strong>Description:</strong><br>
                        ${props.description.substring(0, 200)}${props.description.length > 200 ? '...' : ''}
                    </div>
                `;
            }
            
            if (props.location) {
                html += `
                    <div class="mb-3">
                        <strong>Location:</strong> ${props.location}
                    </div>
                `;
            }
            
            if (props.web_link) {
                html += `
                    <div class="mb-3">
                        <a href="${props.web_link}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right"></i> View in Outlook
                        </a>
                    </div>
                `;
            }
        }
        // Handle custom events
        else {
            html = `
                <div class="mb-3">
                    <span class="badge bg-secondary">Custom Event</span>
                </div>
                <div class="mb-3">
                    <strong>Title:</strong> ${event.title}
                </div>
                <div class="mb-3">
                    <strong>Type:</strong> ${props.event_type || 'Event'}
                </div>
                <div class="mb-3">
                    <strong>Date & Time:</strong> ${event.start.toLocaleString()}
                </div>
            `;
            
            if (props.description) {
                html += `<div class="mb-3"><strong>Description:</strong><br>${props.description}</div>`;
            }
            
            if (props.location) {
                html += `<div class="mb-3"><strong>Location:</strong> ${props.location}</div>`;
            }
        }
        
        document.getElementById('meetingDetails').innerHTML = html;
        
        // Update footer with edit/delete buttons for editable events
        var footer = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
        if (canEdit) {
            footer = `
                <button type="button" class="btn btn-danger" onclick="deleteEvent('${event.id}')">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <button type="button" class="btn btn-primary" onclick="editEvent('${event.id}')">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            `;
        }
        document.getElementById('modalFooter').innerHTML = footer;
        
        new bootstrap.Modal(document.getElementById('meetingModal')).show();
    }
    
    // Export to global scope
    window.showEventDetails = showEventDetails;
    
    // Open event form for add or edit
    window.openEventForm = function(dateStr = null, eventObj = null) {
        var form = document.getElementById('eventForm');
        form.reset();
        
        if (eventObj) {
            // Edit mode
            document.getElementById('eventFormTitle').textContent = '✏️ Edit Event';
            document.getElementById('eventId').value = eventObj.id;
            document.getElementById('eventSource').value = eventObj.extendedProps.source || 'internal';
            document.getElementById('eventType').value = eventObj.extendedProps.event_type || 'meeting';
            document.getElementById('eventTitle').value = eventObj.title;
            document.getElementById('eventDescription').value = eventObj.extendedProps.description || '';
            
            var startDate = new Date(eventObj.start);
            document.getElementById('eventDate').value = startDate.toISOString().split('T')[0];
            document.getElementById('eventTime').value = startDate.toTimeString().slice(0, 5);
            document.getElementById('eventDuration').value = eventObj.extendedProps.duration || 60;
            document.getElementById('eventLocation').value = eventObj.extendedProps.location || '';
            
            document.getElementById('syncToGoogle').checked = eventObj.extendedProps.sync_google || false;
            document.getElementById('syncToOutlook').checked = eventObj.extendedProps.sync_outlook || false;
        } else {
            // Add mode
            document.getElementById('eventFormTitle').textContent = '➕ Add Event';
            document.getElementById('eventId').value = '';
            document.getElementById('eventSource').value = 'internal';
            
            if (dateStr) {
                document.getElementById('eventDate').value = dateStr;
            }
        }
        
        new bootstrap.Modal(document.getElementById('eventFormModal')).show();
    };
    
    // Edit existing event
    window.editEvent = function(eventId) {
        var event = window.calendarInstance.getEventById(eventId);
        if (event) {
            // Close details modal
            bootstrap.Modal.getInstance(document.getElementById('meetingModal')).hide();
            // Open form modal
            openEventForm(null, event);
        }
    };
    
    // Save event (add or update)
    window.saveEvent = function() {
        var form = document.getElementById('eventForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        var formData = new FormData(form);
        formData.append('action', 'save_calendar_event');
        
        fetch('functions/actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Event saved successfully!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('eventFormModal')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || 'Failed to save event', 'danger');
            }
        })
        .catch(error => {
            showToast('Error saving event', 'danger');
            console.error('Error:', error);
        });
    };
    
    // Update event date/time after drag or resize
    window.updateEventDateTime = function(event) {
        if (event.extendedProps.source !== 'internal' && event.extendedProps.source) {
            showToast('Cannot edit external calendar events', 'warning');
            event.revert();
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'update_event_datetime');
        formData.append('event_id', event.id);
        formData.append('start', event.start.toISOString());
        if (event.end) {
            formData.append('end', event.end.toISOString());
        }
        
        fetch('functions/actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Event updated successfully!', 'success');
            } else {
                showToast(data.error || 'Failed to update event', 'danger');
                event.revert();
            }
        })
        .catch(error => {
            showToast('Error updating event', 'danger');
            console.error('Error:', event);
            event.revert();
        });
    };
    
    // Delete event
    window.deleteEvent = function(eventId) {
        var event = window.calendarInstance.getEventById(eventId);
        if (!event) return;
        
        if (event.extendedProps.source !== 'internal' && event.extendedProps.source) {
            showToast('Cannot delete external calendar events', 'warning');
            return;
        }
        
        if (!confirm('Are you sure you want to delete this event?')) {
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'delete_calendar_event');
        formData.append('event_id', eventId);
        
        fetch('functions/actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Event deleted successfully!', 'success');
                event.remove();
                bootstrap.Modal.getInstance(document.getElementById('meetingModal')).hide();
            } else {
                showToast(data.error || 'Failed to delete event', 'danger');
            }
        })
        .catch(error => {
            showToast('Error deleting event', 'danger');
            console.error('Error:', error);
        });
    };
    
    // Refresh calendar
    window.refreshCalendar = function() {
        showToast('Refreshing calendar...', 'info');
        setTimeout(() => location.reload(), 500);
    };
    
    // Toast notification helper
    function showToast(message, type = 'info') {
        var toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        var toastId = 'toast-' + Date.now();
        var bgClass = type === 'success' ? 'bg-success' : type === 'danger' ? 'bg-danger' : type === 'warning' ? 'bg-warning' : 'bg-info';
        
        var toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        var toastEl = document.getElementById(toastId);
        var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
        
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    }
});
</script>
