<?php
/**
 * LinkedIn Integration Functions
 * Post jobs to LinkedIn company page
 */

/**
 * Post a job to LinkedIn
 * @param string $title Job title
 * @param string $description Job description (will be truncated to 1300 chars for LinkedIn)
 * @param string $apply_url Application URL
 * @param int $user_id User ID for getting LinkedIn credentials
 * @return array Success status and message/error
 */
function post_job_to_linkedin($title, $description, $apply_url, $user_id) {
    $access_token = get_setting('linkedin_access_token', $user_id);
    $org_id = get_setting('linkedin_org_id', $user_id);
    
    if (empty($access_token) || empty($org_id)) {
        return [
            'success' => false,
            'error' => 'LinkedIn credentials not configured'
        ];
    }
    
    // Strip HTML tags and limit description length for LinkedIn
    $clean_description = strip_tags($description);
    $clean_description = mb_substr($clean_description, 0, 1300);
    
    // Build post text
    $post_text = "🚀 We're Hiring!\n\n";
    $post_text .= "📋 Position: $title\n\n";
    $post_text .= "$clean_description\n\n";
    $post_text .= "🔗 Apply now: $apply_url\n\n";
    $post_text .= "#hiring #job #career #opportunity";
    
    // Prepare LinkedIn API request
    $url = "https://api.linkedin.com/v2/ugcPosts";
    
    $data = [
        'author' => "urn:li:organization:$org_id",
        'lifecycleState' => 'PUBLISHED',
        'specificContent' => [
            'com.linkedin.ugc.ShareContent' => [
                'shareCommentary' => [
                    'text' => $post_text
                ],
                'shareMediaCategory' => 'NONE'
            ]
        ],
        'visibility' => [
            'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
        ]
    ];
    
    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token,
        'X-Restli-Protocol-Version: 2.0.0'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    // SSL options
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
        return [
            'success' => true,
            'message' => 'Job posted to LinkedIn successfully!',
            'response' => json_decode($response, true)
        ];
    } else {
        $error_data = json_decode($response, true);
        $error_message = $error_data['message'] ?? 'Unknown error';
        
        return [
            'success' => false,
            'error' => "LinkedIn API error (HTTP $http_code): $error_message",
            'response' => $error_data
        ];
    }
}

/**
 * Test LinkedIn connection
 * @param int $user_id User ID
 * @return array Success status and message/error
 */
function test_linkedin_connection($user_id) {
    $access_token = get_setting('linkedin_access_token', $user_id);
    $org_id = get_setting('linkedin_org_id', $user_id);
    
    if (empty($access_token) || empty($org_id)) {
        return [
            'success' => false,
            'error' => 'LinkedIn credentials not configured'
        ];
    }
    
    // Test by fetching organization info
    $url = "https://api.linkedin.com/v2/organizations/$org_id";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'X-Restli-Protocol-Version: 2.0.0'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        return [
            'success' => false,
            'error' => 'Connection failed: ' . $curl_error
        ];
    }
    
    if ($http_code === 200) {
        $org_data = json_decode($response, true);
        $org_name = $org_data['localizedName'] ?? 'Unknown';
        
        return [
            'success' => true,
            'message' => "✓ Connected successfully to: $org_name"
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
