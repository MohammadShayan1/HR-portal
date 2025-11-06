<?php
/**
 * Core Helper Functions
 * Includes AI integration with Gemini API and utility functions
 */

require_once __DIR__ . '/db.php';

/**
 * Call Gemini API
 * @param string $prompt The prompt to send to Gemini
 * @param bool $json_response Whether to expect JSON response
 * @param int|null $user_id Optional user ID to get API key for specific user
 * @return string|array Response from Gemini (string or parsed JSON array)
 */
function call_gemini_api($prompt, $json_response = false, $user_id = null) {
    $api_key = get_setting('gemini_key', $user_id);
    
    if (empty($api_key)) {
        return $json_response ? ['error' => 'Gemini API key not configured'] : 'Error: Gemini API key not configured. Please set it in Settings.';
    }
    
    // Gemini API endpoint - Using Gemini 2.0 Flash model
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";
    
    // Prepare request body with generation config
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 2048
        ]
    ];
    
    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    // SSL options for Windows/WAMP
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code !== 200) {
        $error_msg = 'API request failed';
        if (!empty($curl_error)) {
            $error_msg .= ': ' . $curl_error;
        }
        if (!empty($response)) {
            $error_data = json_decode($response, true);
            if (isset($error_data['error']['message'])) {
                $error_msg .= ' - ' . $error_data['error']['message'];
            }
        }
        return $json_response ? ['error' => $error_msg] : 'Error: Failed to connect to Gemini API. ' . $error_msg;
    }
    
    // Parse response
    $result = json_decode($response, true);
    
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return $json_response ? ['error' => 'Invalid API response'] : 'Error: Invalid response from API.';
    }
    
    $text = $result['candidates'][0]['content']['parts'][0]['text'];
    
    // If expecting JSON, try to parse it
    if ($json_response) {
        // Extract JSON from markdown code blocks if present
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
            $text = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $text, $matches)) {
            $text = $matches[1];
        }
        
        // Try to extract JSON object from text
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $text = $matches[0];
        }
        
        $text = trim($text);
        
        $json = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        } else {
            // If JSON parsing failed, return error with raw text for debugging
            return ['error' => 'Failed to parse JSON response: ' . json_last_error_msg(), 'raw' => $text];
        }
    }
    
    return $text;
}

/**
 * Generate job description using AI
 * @param string $title Job title
 * @param string $brief Brief description
 * @return string Generated job description (HTML formatted)
 */
function generate_job_description($title, $brief) {
    $prompt = "Create a professional, detailed job description for a {$title} position. " .
              "Brief requirements: {$brief}\n\n" .
              "Please include:\n" .
              "- Job Overview\n" .
              "- Key Responsibilities\n" .
              "- Required Qualifications\n" .
              "- Preferred Skills\n" .
              "- What We Offer\n\n" .
              "Format the response in HTML with proper headings (<h3>), paragraphs (<p>), and bullet lists (<ul><li>). " .
              "Make it well-structured and visually appealing for a job posting.";
    
    $text = call_gemini_api($prompt);
    
    // Clean up the response and ensure proper HTML formatting
    $text = trim($text);
    
    // Remove markdown code blocks if present
    $text = preg_replace('/```html\s*/s', '', $text);
    $text = preg_replace('/```\s*/s', '', $text);
    
    // Convert markdown-style headers to HTML if not already HTML
    if (strpos($text, '<h') === false) {
        // Convert ### Header to <h3>Header</h3>
        $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^# (.+)$/m', '<h3>$1</h3>', $text);
        
        // Convert **bold** to <strong>
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        
        // Convert markdown lists to HTML
        $text = preg_replace_callback('/^[\-\*] (.+)$/m', function($matches) {
            static $in_list = false;
            $item = '<li>' . $matches[1] . '</li>';
            if (!$in_list) {
                $in_list = true;
                return '<ul>' . $item;
            }
            return $item;
        }, $text);
        
        // Close any open lists
        if (strpos($text, '<ul>') !== false && strpos($text, '</ul>') === false) {
            $text .= '</ul>';
        }
        
        // Wrap plain paragraphs
        $lines = explode("\n", $text);
        $formatted = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if (strpos($line, '<') !== 0) {
                $line = '<p>' . $line . '</p>';
            }
            $formatted[] = $line;
        }
        $text = implode("\n", $formatted);
    }
    
    return $text;
}

/**
 * Generate interview questions based on job description
 * @param string $job_description
 * @param int $num_questions Number of questions to generate
 * @return array Array of questions
 */
function generate_interview_questions($job_description, $num_questions = 5, $user_id = null) {
    $prompt = "You are an expert HR interviewer. Based on the following job description, " .
              "generate exactly {$num_questions} insightful interview questions.\n\n" .
              "Job Description:\n{$job_description}\n\n" .
              "Return ONLY a JSON array of questions in this exact format:\n" .
              '["Question 1?", "Question 2?", "Question 3?", "Question 4?", "Question 5?"]' . "\n\n" .
              "Make the questions relevant, professional, and designed to assess the candidate's " .
              "skills, experience, and fit for this role.";
    
    $result = call_gemini_api($prompt, true, $user_id);
    
    // If we got an error or invalid format, return default questions
    if (isset($result['error']) || !is_array($result) || empty($result)) {
        return [
            "Tell us about your relevant experience for this position.",
            "What are your key strengths that make you suitable for this role?",
            "Describe a challenging project you worked on and how you handled it.",
            "Where do you see yourself professionally in the next 3-5 years?",
            "Why are you interested in this position and our company?"
        ];
    }
    
    // If the result is an associative array with questions, extract them
    if (!isset($result[0]) && is_array($result)) {
        $result = array_values($result);
    }
    
    return array_slice($result, 0, $num_questions);
}

/**
 * Generate evaluation report for a candidate
 * @param int $candidate_id
 * @return array|null Array with 'score' and 'report_content', or null on error
 */
function generate_evaluation_report($candidate_id) {
    $pdo = get_db();
    
    // Get candidate and job info with user_id
    $stmt = $pdo->prepare("
        SELECT c.*, j.title, j.description, j.user_id 
        FROM candidates c 
        JOIN jobs j ON c.job_id = j.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        return null;
    }
    
    // Get interview Q&A
    $stmt = $pdo->prepare("
        SELECT question, answer 
        FROM interview_answers 
        WHERE candidate_id = ? 
        ORDER BY id
    ");
    $stmt->execute([$candidate_id]);
    $qa_pairs = $stmt->fetchAll();
    
    if (empty($qa_pairs)) {
        return null;
    }
    
    // Build interview transcript
    $transcript = "Job Title: {$candidate['title']}\n\n";
    $transcript .= "Job Description:\n{$candidate['description']}\n\n";
    $transcript .= "Candidate: {$candidate['name']} ({$candidate['email']})\n\n";
    $transcript .= "Interview Transcript:\n";
    
    foreach ($qa_pairs as $i => $qa) {
        $num = $i + 1;
        $transcript .= "Q{$num}: {$qa['question']}\n";
        $transcript .= "A{$num}: {$qa['answer']}\n\n";
    }
    
    // Create evaluation prompt
    $prompt = "You are an expert HR analyst. Based on the job description and the candidate's " .
              "interview answers, please provide a comprehensive evaluation.\n\n" .
              $transcript . "\n\n" .
              "IMPORTANT: Respond with ONLY a valid JSON object, no additional text or explanation.\n\n" .
              "Use this exact format:\n" .
              "{\n" .
              '  "score": 75,'."\n" .
              '  "report_content": "# Evaluation Report\n\n## Overall Assessment\n\nYour detailed assessment here..."'."\n" .
              "}\n\n" .
              "The report_content should be markdown-formatted and include:\n" .
              "- **Overall Assessment**: Brief summary\n" .
              "- **Strengths**: Key positive points\n" .
              "- **Areas of Concern**: Any weaknesses or gaps\n" .
              "- **Communication Skills**: Quality of responses\n" .
              "- **Technical/Professional Fit**: Match to job requirements\n" .
              "- **Recommendation**: Hire, Maybe, or Pass with reasoning\n\n" .
              "Score should be 0-100. Be objective, professional, and thorough.\n\n" .
              "Remember: Return ONLY the JSON object, nothing else.";
    
    $result = call_gemini_api($prompt, true, $candidate['user_id']);
    
    if (isset($result['error'])) {
        return [
            'score' => 0,
            'report_content' => "Error generating report: " . $result['error']
        ];
    }
    
    // Validate and return result
    if (!isset($result['score']) || !isset($result['report_content'])) {
        return [
            'score' => 50,
            'report_content' => "# Evaluation Report\n\nUnable to generate structured report. Raw response:\n\n" . 
                              (isset($result['raw']) ? $result['raw'] : json_encode($result))
        ];
    }
    
    return $result;
}

/**
 * Sanitize output for HTML display
 * @param string $text
 * @return string
 */
function sanitize($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 * @param string $datetime
 * @return string
 */
function format_date($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

/**
 * Generate a random token
 * @param int $length
 * @return string
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get base URL of the application
 * @return string
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove trailing slash if exists
    $script_path = rtrim($script_path, '/');
    
    return $protocol . '://' . $host . $script_path . '/';
}

/**
 * Simple markdown to HTML converter
 * @param string $text
 * @return string
 */
function markdown_to_html($text) {
    // Headers
    $text = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $text);
    
    // Bold
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    
    // Italic
    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
    
    // Lists
    $text = preg_replace('/^\- (.*?)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $text);
    
    // Line breaks
    $text = nl2br($text);
    
    return $text;
}
