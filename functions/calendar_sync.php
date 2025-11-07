<?php
/**
 * Calendar Synchronization Functions
 * Sync meetings with Google Calendar and Microsoft Outlook
 */

/**
 * Add event to Google Calendar
 */
function add_to_google_calendar($meeting_data, $user_id) {
    $encrypted_token = get_setting('google_calendar_token', $user_id);
    
    if (empty($encrypted_token)) {
        return ['error' => 'Google Calendar not connected'];
    }
    
    // Decrypt the access token (SECURITY ENHANCEMENT)
    require_once __DIR__ . '/security.php';
    $access_token = decrypt_data($encrypted_token);
    
    // Prepare event data
    $start_datetime = $meeting_data['meeting_date'] . 'T' . $meeting_data['meeting_time'] . ':00';
    $end_datetime = date('Y-m-d\TH:i:s', strtotime($start_datetime . ' +' . $meeting_data['duration'] . ' minutes'));
    
    $event = [
        'summary' => $meeting_data['title'],
        'description' => $meeting_data['description'] . "\n\nZoom Link: " . ($meeting_data['zoom_join_url'] ?? ''),
        'start' => [
            'dateTime' => $start_datetime,
            'timeZone' => get_setting('timezone', $user_id) ?: 'UTC'
        ],
        'end' => [
            'dateTime' => $end_datetime,
            'timeZone' => get_setting('timezone', $user_id) ?: 'UTC'
        ],
        'attendees' => [
            ['email' => $meeting_data['candidate_email']]
        ],
        'conferenceData' => [
            'createRequest' => [
                'requestId' => uniqid(),
                'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
            ]
        ],
        'reminders' => [
            'useDefault' => false,
            'overrides' => [
                ['method' => 'email', 'minutes' => 24 * 60],
                ['method' => 'popup', 'minutes' => 30]
            ]
        ]
    ];
    
    // Add Zoom link to location if available
    if (!empty($meeting_data['zoom_join_url'])) {
        $event['location'] = $meeting_data['zoom_join_url'];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        return [
            'success' => true,
            'event_id' => $result['id'],
            'html_link' => $result['htmlLink']
        ];
    } else {
        return ['error' => 'Failed to create Google Calendar event: ' . $response];
    }
}

/**
 * Add event to Microsoft Outlook Calendar
 */
function add_to_outlook_calendar($meeting_data, $user_id) {
    $encrypted_token = get_setting('outlook_calendar_token', $user_id);
    
    if (empty($encrypted_token)) {
        return ['error' => 'Microsoft Outlook not connected'];
    }
    
    // Decrypt the access token (SECURITY ENHANCEMENT)
    require_once __DIR__ . '/security.php';
    $access_token = decrypt_data($encrypted_token);
    
    // Prepare event data
    $start_datetime = $meeting_data['meeting_date'] . 'T' . $meeting_data['meeting_time'] . ':00';
    $end_datetime = date('Y-m-d\TH:i:s', strtotime($start_datetime . ' +' . $meeting_data['duration'] . ' minutes'));
    
    $event = [
        'subject' => $meeting_data['title'],
        'body' => [
            'contentType' => 'HTML',
            'content' => nl2br($meeting_data['description']) . '<br><br><strong>Zoom Link:</strong> <a href="' . ($meeting_data['zoom_join_url'] ?? '') . '">' . ($meeting_data['zoom_join_url'] ?? '') . '</a>'
        ],
        'start' => [
            'dateTime' => $start_datetime,
            'timeZone' => get_setting('timezone', $user_id) ?: 'UTC'
        ],
        'end' => [
            'dateTime' => $end_datetime,
            'timeZone' => get_setting('timezone', $user_id) ?: 'UTC'
        ],
        'location' => [
            'displayName' => 'Zoom Meeting'
        ],
        'attendees' => [
            [
                'emailAddress' => [
                    'address' => $meeting_data['candidate_email'],
                    'name' => $meeting_data['candidate_name']
                ],
                'type' => 'required'
            ]
        ],
        'isOnlineMeeting' => true,
        'onlineMeetingProvider' => 'unknown',
        'onlineMeetingUrl' => $meeting_data['zoom_join_url'] ?? null,
        'reminderMinutesBeforeStart' => 30,
        'isReminderOn' => true
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://graph.microsoft.com/v1.0/me/events');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 201) {
        $result = json_decode($response, true);
        return [
            'success' => true,
            'event_id' => $result['id'],
            'web_link' => $result['webLink']
        ];
    } else {
        return ['error' => 'Failed to create Outlook Calendar event: ' . $response];
    }
}

/**
 * Fetch events from Google Calendar
 */
function fetch_google_calendar_events($user_id, $time_min = null, $time_max = null) {
    $access_token = get_setting('google_calendar_token', $user_id);
    
    if (empty($access_token)) {
        return ['error' => 'Google Calendar not connected'];
    }
    
    $time_min = $time_min ?: date('c', strtotime('-30 days'));
    $time_max = $time_max ?: date('c', strtotime('+90 days'));
    
    $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events?' . http_build_query([
        'timeMin' => $time_min,
        'timeMax' => $time_max,
        'singleEvents' => 'true',
        'orderBy' => 'startTime'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    } else {
        return ['error' => 'Failed to fetch Google Calendar events: ' . $response];
    }
}

/**
 * Fetch events from Microsoft Outlook Calendar
 */
function fetch_outlook_calendar_events($user_id, $time_min = null, $time_max = null) {
    $access_token = get_setting('outlook_calendar_token', $user_id);
    
    if (empty($access_token)) {
        return ['error' => 'Microsoft Outlook not connected'];
    }
    
    $time_min = $time_min ?: date('c', strtotime('-30 days'));
    $time_max = $time_max ?: date('c', strtotime('+90 days'));
    
    $url = 'https://graph.microsoft.com/v1.0/me/calendarview?' . http_build_query([
        'startDateTime' => $time_min,
        'endDateTime' => $time_max,
        '$orderby' => 'start/dateTime'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    } else {
        return ['error' => 'Failed to fetch Outlook Calendar events: ' . $response];
    }
}

/**
 * Test Google Calendar connection
 */
function test_google_calendar_connection($user_id) {
    $encrypted_token = get_setting('google_calendar_token', $user_id);
    
    if (empty($encrypted_token)) {
        return ['error' => 'Google Calendar access token not configured'];
    }
    
    // Decrypt the token
    require_once __DIR__ . '/security.php';
    $access_token = decrypt_data($encrypted_token);
    
    if (!$access_token) {
        return ['error' => 'Failed to decrypt access token. Please reconnect your account.'];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/calendar/v3/users/me/calendarList/primary');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        return ['error' => 'Connection failed: ' . $curl_error];
    }
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        return [
            'success' => true,
            'message' => 'Connected to Google Calendar: ' . ($result['summary'] ?? 'Primary Calendar')
        ];
    } else {
        return ['error' => 'Google Calendar connection failed. Please reconnect your account.'];
    }
}

/**
 * Test Microsoft Outlook connection
 */
function test_outlook_calendar_connection($user_id) {
    $encrypted_token = get_setting('outlook_calendar_token', $user_id);
    
    if (empty($encrypted_token)) {
        return ['error' => 'Microsoft Outlook access token not configured'];
    }
    
    // Decrypt the token
    require_once __DIR__ . '/security.php';
    $access_token = decrypt_data($encrypted_token);
    
    if (!$access_token) {
        return ['error' => 'Failed to decrypt access token. Please reconnect your account.'];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://graph.microsoft.com/v1.0/me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        return ['error' => 'Connection failed: ' . $curl_error];
    }
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        return [
            'success' => true,
            'message' => 'Connected to Outlook Calendar: ' . ($result['userPrincipalName'] ?? 'Your Account')
        ];
    } else {
        return ['error' => 'Outlook Calendar connection failed. Please reconnect your account.'];
    }
}

/**
 * Sync meeting to enabled calendars
 */
function sync_meeting_to_calendars($meeting_data, $user_id) {
    $results = [];
    
    // Check if Google Calendar sync is enabled
    $google_sync_enabled = get_setting('google_calendar_sync', $user_id) === '1';
    if ($google_sync_enabled) {
        $google_result = add_to_google_calendar($meeting_data, $user_id);
        $results['google'] = $google_result;
        
        // Save Google event ID to database if successful
        if (isset($google_result['event_id']) && isset($meeting_data['meeting_id'])) {
            $pdo = get_db();
            $stmt = $pdo->prepare("UPDATE meetings SET google_event_id = ? WHERE id = ?");
            $stmt->execute([$google_result['event_id'], $meeting_data['meeting_id']]);
        }
    }
    
    // Check if Outlook Calendar sync is enabled
    $outlook_sync_enabled = get_setting('outlook_calendar_sync', $user_id) === '1';
    if ($outlook_sync_enabled) {
        $outlook_result = add_to_outlook_calendar($meeting_data, $user_id);
        $results['outlook'] = $outlook_result;
        
        // Save Outlook event ID to database if successful
        if (isset($outlook_result['event_id']) && isset($meeting_data['meeting_id'])) {
            $pdo = get_db();
            $stmt = $pdo->prepare("UPDATE meetings SET outlook_event_id = ? WHERE id = ?");
            $stmt->execute([$outlook_result['event_id'], $meeting_data['meeting_id']]);
        }
    }
    
    return $results;
}
