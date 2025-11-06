<?php
/**
 * AI Response Detection Functions
 * Helps identify if candidate answers are AI-generated
 */

/**
 * Analyze answer for AI-generated patterns
 * @param string $answer The candidate's answer
 * @param array $metadata Additional metadata (typing speed, paste events, etc.)
 * @return array Detection results with score and flags
 */
function detect_ai_response($answer, $metadata = []) {
    $flags = [];
    $ai_probability = 0;
    
    // 1. Check for overly formal/perfect structure
    if (is_overly_structured($answer)) {
        $flags[] = 'Highly structured answer';
        $ai_probability += 15;
    }
    
    // 2. Check for common AI phrases
    $ai_phrases = [
        'as an AI', 'I don\'t have personal', 'I cannot', 'I\'m not able to',
        'delve into', 'it\'s worth noting', 'moreover', 'furthermore',
        'in conclusion', 'to summarize', 'it is important to note',
        'comprehensive approach', 'multifaceted', 'nuanced perspective'
    ];
    
    $phrase_count = 0;
    foreach ($ai_phrases as $phrase) {
        if (stripos($answer, $phrase) !== false) {
            $phrase_count++;
        }
    }
    
    if ($phrase_count >= 2) {
        $flags[] = 'Contains typical AI phrases';
        $ai_probability += 20;
    }
    
    // 3. Check response time (too fast for long answers = copy-paste)
    if (isset($metadata['response_time']) && isset($metadata['answer_length'])) {
        $expected_time = ($metadata['answer_length'] / 40) * 1000; // ~40 chars per second typing
        if ($metadata['response_time'] < ($expected_time * 0.3)) {
            $flags[] = 'Response time too fast for answer length';
            $ai_probability += 25;
        }
    }
    
    // 4. Check if answer was pasted (client-side detection)
    if (isset($metadata['was_pasted']) && $metadata['was_pasted']) {
        $flags[] = 'Answer was pasted, not typed';
        $ai_probability += 30;
    }
    
    // 5. Check typing pattern consistency
    if (isset($metadata['typing_intervals'])) {
        $variance = calculate_variance($metadata['typing_intervals']);
        if ($variance < 10) { // Too consistent = likely pasted
            $flags[] = 'Typing pattern too consistent';
            $ai_probability += 20;
        }
    }
    
    // 6. Check for perfect grammar (unusual for real-time typing)
    if (has_perfect_grammar($answer)) {
        $flags[] = 'Perfect grammar with no typos';
        $ai_probability += 10;
    }
    
    // 7. Check answer length (AI tends to be verbose)
    $word_count = str_word_count($answer);
    if ($word_count > 200) {
        $flags[] = 'Unusually long answer';
        $ai_probability += 10;
    }
    
    // 8. Check for markdown formatting (AI often uses it)
    if (preg_match('/(\*\*|\#\#|```|\n\-\s)/', $answer)) {
        $flags[] = 'Contains markdown formatting';
        $ai_probability += 15;
    }
    
    // Cap at 100%
    $ai_probability = min($ai_probability, 100);
    
    return [
        'ai_probability' => $ai_probability,
        'is_likely_ai' => $ai_probability > 50,
        'flags' => $flags,
        'recommendation' => get_recommendation($ai_probability)
    ];
}

/**
 * Check if answer is overly structured
 */
function is_overly_structured($answer) {
    // Check for numbered lists, bullet points, multiple paragraphs with headers
    $structure_count = 0;
    
    if (preg_match('/^\d+\.\s/m', $answer)) $structure_count++;
    if (preg_match('/^[\-\*]\s/m', $answer)) $structure_count++;
    if (preg_match('/\n\n.*\n\n/s', $answer)) $structure_count++;
    if (preg_match('/^[A-Z][^.!?]*:$/m', $answer)) $structure_count++;
    
    return $structure_count >= 2;
}

/**
 * Check for perfect grammar (simplified)
 */
function has_perfect_grammar($answer) {
    // Perfect capitalization, no common typos, perfect punctuation
    $sentences = preg_split('/[.!?]+/', $answer);
    $perfect_sentences = 0;
    
    foreach ($sentences as $sentence) {
        $sentence = trim($sentence);
        if (empty($sentence)) continue;
        
        // Check if starts with capital letter
        if (ctype_upper(substr($sentence, 0, 1))) {
            $perfect_sentences++;
        }
    }
    
    $total_sentences = count(array_filter($sentences, 'strlen'));
    if ($total_sentences == 0) return false;
    
    return ($perfect_sentences / $total_sentences) > 0.95;
}

/**
 * Calculate variance in typing intervals
 */
function calculate_variance($intervals) {
    if (empty($intervals)) return 0;
    
    $mean = array_sum($intervals) / count($intervals);
    $variance = 0;
    
    foreach ($intervals as $interval) {
        $variance += pow($interval - $mean, 2);
    }
    
    return sqrt($variance / count($intervals));
}

/**
 * Get recommendation based on AI probability
 */
function get_recommendation($probability) {
    if ($probability < 30) {
        return 'Low risk - Answer appears genuine';
    } elseif ($probability < 60) {
        return 'Medium risk - Some suspicious patterns detected';
    } else {
        return 'High risk - Strong indicators of AI-generated response';
    }
}

/**
 * Enhanced version using Gemini API to analyze
 */
function ai_deep_analysis($question, $answer, $user_id = null) {
    $prompt = "You are an expert at detecting AI-generated text. Analyze this interview answer and determine if it was likely written by AI or a human.\n\n" .
              "Question: {$question}\n\n" .
              "Answer: {$answer}\n\n" .
              "Analyze for:\n" .
              "1. Overly formal or perfect language\n" .
              "2. Typical AI phrases and patterns\n" .
              "3. Lack of personal anecdotes or specific details\n" .
              "4. Generic corporate-speak\n" .
              "5. Unusual verbosity\n\n" .
              "Return JSON with:\n" .
              "{\n" .
              '  "is_ai_generated": true/false,'."\n" .
              '  "confidence": 0-100,'."\n" .
              '  "reasoning": "brief explanation",'."\n" .
              '  "red_flags": ["flag1", "flag2"]'."\n" .
              "}";
    
    $result = call_gemini_api($prompt, true, $user_id);
    
    if (isset($result['error'])) {
        return null;
    }
    
    return $result;
}
