<?php
/**
 * Zoom Integration Functions
 * Create and manage Zoom meetings
 */

/**
 * Create a Zoom meeting
 * @param string $topic Meeting topic
 * @param string $start_time Start time in ISO 8601 format
 * @param int $duration Duration in minutes
 * @param int $user_id User ID for getting Zoom credentials
 * @return array Meeting details or error
 */
function create_zoom_meeting($topic, $start_time, $duration, $user_id) {
    $zoom_api_key = get_setting('zoom_api_key', $user_id);
    $zoom_api_secret = get_setting('zoom_api_secret', $user_id);
    
    if (empty($zoom_api_key) || empty($zoom_api_secret)) {
        return [
            'success' => false,
            'error' => 'Zoom credentials not configured'
        ];
    }
    
    // Generate JWT token for Zoom API
    $token = generate_zoom_jwt($zoom_api_key, $zoom_api_secret);
    
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
        'Authorization: Bearer ' . $token
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
 * Generate JWT token for Zoom API
 * @param string $api_key Zoom API Key
 * @param string $api_secret Zoom API Secret
 * @return string JWT token
 */
function generate_zoom_jwt($api_key, $api_secret) {
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    
    $payload = [
        'iss' => $api_key,
        'exp' => time() + 3600 // Token valid for 1 hour
    ];
    
    $base64_header = base64_url_encode(json_encode($header));
    $base64_payload = base64_url_encode(json_encode($payload));
    
    $signature = hash_hmac('sha256', "$base64_header.$base64_payload", $api_secret, true);
    $base64_signature = base64_url_encode($signature);
    
    return "$base64_header.$base64_payload.$base64_signature";
}

/**
 * Base64 URL encode
 */
function base64_url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Delete a Zoom meeting
 * @param string $meeting_id Zoom meeting ID
 * @param int $user_id User ID for getting Zoom credentials
 * @return array Success status
 */
function delete_zoom_meeting($meeting_id, $user_id) {
    $zoom_api_key = get_setting('zoom_api_key', $user_id);
    $zoom_api_secret = get_setting('zoom_api_secret', $user_id);
    
    if (empty($zoom_api_key) || empty($zoom_api_secret)) {
        return ['success' => false, 'error' => 'Zoom credentials not configured'];
    }
    
    $token = generate_zoom_jwt($zoom_api_key, $zoom_api_secret);
    $url = "https://api.zoom.us/v2/meetings/$meeting_id";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
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
    $zoom_api_key = get_setting('zoom_api_key', $user_id);
    $zoom_api_secret = get_setting('zoom_api_secret', $user_id);
    
    if (empty($zoom_api_key) || empty($zoom_api_secret)) {
        return [
            'success' => false,
            'error' => 'Zoom credentials not configured'
        ];
    }
    
    $token = generate_zoom_jwt($zoom_api_key, $zoom_api_secret);
    $url = "https://api.zoom.us/v2/users/me";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
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
