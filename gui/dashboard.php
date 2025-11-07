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

// Get upcoming meetings for current user
$stmt = $pdo->prepare("
    SELECT m.*, c.name as candidate_name, c.email as candidate_email, j.title as job_title
    FROM meetings m
    JOIN candidates c ON m.candidate_id = c.id
    JOIN jobs j ON c.job_id = j.id
    WHERE m.user_id = ?
    ORDER BY m.meeting_date ASC, m.meeting_time ASC
");
$stmt->execute([$user_id]);
$meetings = $stmt->fetchAll();

// Convert meetings to calendar events format
$calendar_events = [];
foreach ($meetings as $meeting) {
    $calendar_events[] = [
        'id' => $meeting['id'],
        'title' => $meeting['title'] . ' - ' . $meeting['candidate_name'],
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
            'candidate_name' => $meeting['candidate_name'],
            'candidate_email' => $meeting['candidate_email'],
            'job_title' => $meeting['job_title'],
            'zoom_join_url' => $meeting['zoom_join_url'],
            'zoom_start_url' => $meeting['zoom_start_url'],
            'status' => $meeting['status']
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
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-calendar3"></i> Meeting Calendar</h5>
                    <span class="badge bg-primary"><?php echo count($meetings); ?> scheduled</span>
                </div>
            </div>
            <div class="card-body">
                <div id="calendar"></div>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-event"></i> Meeting Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="meetingDetails">
                <!-- Meeting details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: <?php echo json_encode($calendar_events); ?>,
        eventClick: function(info) {
            // Show meeting details in modal
            var event = info.event;
            var props = event.extendedProps;
            
            var html = '';
            
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
            
            document.getElementById('meetingDetails').innerHTML = html;
            new bootstrap.Modal(document.getElementById('meetingModal')).show();
        },
        eventColor: '<?php echo $theme_primary; ?>',
        height: 'auto',
        aspectRatio: 2
    });
    
    calendar.render();
});
</script>
