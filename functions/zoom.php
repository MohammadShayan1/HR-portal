<?php
/**
 * Zoom Integration Functions
 * Create and manage Zoom meetings using Server-to-Server OAuth
 */

/**
 * Get Zoom OAuth access token
 * @param int $user_id User ID for getting Zoom credentials
 * @return array|false Access token or false on failure
 */
function get_zoom_access_token($user_id) {
    $zoom_account_id = get_setting('zoom_account_id', $user_id);
    $zoom_client_id = get_setting('zoom_client_id', $user_id);
    $zoom_client_secret = get_setting('zoom_client_secret', $user_id);
    
    if (empty($zoom_account_id) || empty($zoom_client_id) || empty($zoom_client_secret)) {
        return false;
    }
    
    // Check if we have a cached token
    $cached_token = get_setting('zoom_access_token', $user_id);
    $token_expires = get_setting('zoom_token_expires', $user_id);
    
    if ($cached_token && $token_expires && time() < $token_expires - 300) {
        // Token is still valid (with 5-minute buffer)
        return $cached_token;
    }
    
    // Get new token
    $url = "https://zoom.us/oauth/token";
    $auth = base64_encode("$zoom_client_id:$zoom_client_secret");
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'account_credentials',
        'account_id' => $zoom_account_id
    ]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        $access_token = $data['access_token'];
        $expires_in = $data['expires_in'];
        
        // Cache the token
        set_setting('zoom_access_token', $access_token, $user_id);
        set_setting('zoom_token_expires', time() + $expires_in, $user_id);
        
        return $access_token;
    }
    
    return false;
}

/**
 * Create a Zoom meeting
 * @param string $topic Meeting topic
 * @param string $start_time Start time in ISO 8601 format
 * @param int $duration Duration in minutes
 * @param int $user_id User ID for getting Zoom credentials
 * @return array Meeting details or error
 */
function create_zoom_meeting($topic, $start_time, $duration, $user_id) {
    $access_token = get_zoom_access_token($user_id);
    
    if (!$access_token) {
        return [
            'success' => false,
            'error' => 'Zoom credentials not configured or invalid'
        ];
    }
    
    // Zoom API endpoint
    $url = "https://api.zoom.us/v2/users/me/meetings";
    
    // Meeting data
    $data = [
        'topic' => $topic,
        'type' => 2, // Scheduled meeting
        'start_time' => $start_time,
        'duration' => $duration,
        'timezone' => 'UTC',
        'settings' => [
            'host_video' => true,
            'participant_video' => true,
            'join_before_host' => false,
            'mute_upon_entry' => true,
            'watermark' => false,
            'audio' => 'both',
            'auto_recording' => 'none'
        ]
    ];
    
    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        return [
            'success' => false,
            'error' => 'cURL error: ' . $curl_error
        ];
    }
    
    if ($http_code >= 200 && $http_code < 300) {
        $meeting_data = json_decode($response, true);
        
        return [
            'success' => true,
            'meeting_id' => $meeting_data['id'],
            'join_url' => $meeting_data['join_url'],
            'start_url' => $meeting_data['start_url'],
            'password' => $meeting_data['password'] ?? null
        ];
    } else {
        $error_data = json_decode($response, true);
        $error_message = $error_data['message'] ?? 'Unknown error';
        
        return [
            'success' => false,
            'error' => "Zoom API error (HTTP $http_code): $error_message"
        ];
    }
}

/**
 * Delete a Zoom meeting
 * @param string $meeting_id Zoom meeting ID
 * @param int $user_id User ID for getting Zoom credentials
 * @return array Success status
 */
function delete_zoom_meeting($meeting_id, $user_id) {
    $access_token = get_zoom_access_token($user_id);
    
    if (!$access_token) {
        return ['success' => false, 'error' => 'Zoom credentials not configured'];
    }
    
    $url = "https://api.zoom.us/v2/meetings/$meeting_id";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 204) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Failed to delete meeting'];
    }
}

/**
 * Test Zoom connection
 * @param int $user_id User ID
 * @return array Success status and message
 */
function test_zoom_connection($user_id) {
    $access_token = get_zoom_access_token($user_id);
    
    if (!$access_token) {
        return [
            'success' => false,
            'error' => 'Zoom credentials not configured or invalid'
        ];
    }
    
    $url = "https://api.zoom.us/v2/users/me";
    
    $ch = curl_init($url);
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
        return ['success' => false, 'error' => 'Connection failed: ' . $curl_error];
    }
    
    if ($http_code === 200) {
        $user_data = json_decode($response, true);
        $email = $user_data['email'] ?? 'Unknown';
        
        return [
            'success' => true,
            'message' => "✓ Connected successfully to Zoom account: $email"
        ];
    } else {
        $error_data = json_decode($response, true);
        $error_message = $error_data['message'] ?? 'Authentication failed';
        
        return [
            'success' => false,
            'error' => "Connection failed (HTTP $http_code): $error_message"
        ];
    }
}