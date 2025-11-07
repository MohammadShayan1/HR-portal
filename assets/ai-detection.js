/**
 * AI Detection - Client-side Typing Pattern Analysis
 * Captures metadata to detect copy-paste and AI-generated answers
 */

class TypingAnalyzer {
    constructor(textareaId) {
        this.textarea = document.getElementById(textareaId);
        this.startTime = null;
        this.typingIntervals = [];
        this.lastKeyTime = null;
        this.wasPasted = false;
        this.totalKeystrokes = 0;
        this.backspaceCount = 0;
        this.pasteCount = 0;
        
        this.init();
    }
    
    init() {
        if (!this.textarea) return;
        
        // Track when user starts typing
        this.textarea.addEventListener('focus', () => {
            this.startTime = Date.now();
            this.lastKeyTime = Date.now();
        });
        
        // Track keystrokes
        this.textarea.addEventListener('keydown', (e) => {
            const now = Date.now();
            
            if (this.lastKeyTime) {
                const interval = now - this.lastKeyTime;
                this.typingIntervals.push(interval);
            }
            
            this.lastKeyTime = now;
            this.totalKeystrokes++;
            
            if (e.key === 'Backspace' || e.key === 'Delete') {
                this.backspaceCount++;
            }
        });
        
        // Detect paste events
        this.textarea.addEventListener('paste', (e) => {
            this.wasPasted = true;
            this.pasteCount++;
            
            // Just track paste events, no warnings shown to user
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        });
        
        // Track copy events
        this.textarea.addEventListener('copy', () => {
            console.log('Copy detected - tracking for analysis');
        });
    }
    
    showWarning() {
        // Warning disabled - let candidates work freely
        // Data is still tracked for backend analysis
    }
    
    getMetadata() {
        const endTime = Date.now();
        const responseTime = this.startTime ? endTime - this.startTime : 0;
        const answerLength = this.textarea.value.length;
        
        // Calculate typing speed (chars per second)
        const typingSpeed = responseTime > 0 ? (answerLength / (responseTime / 1000)) : 0;
        
        // Calculate average interval between keystrokes
        const avgInterval = this.typingIntervals.length > 0 
            ? this.typingIntervals.reduce((a, b) => a + b, 0) / this.typingIntervals.length 
            : 0;
        
        return {
            response_time: responseTime,
            answer_length: answerLength,
            was_pasted: this.wasPasted,
            paste_count: this.pasteCount,
            typing_intervals: this.typingIntervals,
            avg_typing_interval: Math.round(avgInterval),
            typing_speed: Math.round(typingSpeed),
            total_keystrokes: this.totalKeystrokes,
            backspace_count: this.backspaceCount,
            // Backspace ratio helps detect corrections (humans make more)
            correction_ratio: this.totalKeystrokes > 0 ? (this.backspaceCount / this.totalKeystrokes) : 0
        };
    }
    
    getSuspicionScore() {
        const meta = this.getMetadata();
        let score = 0;
        
        // Pasted content is highly suspicious
        if (meta.was_pasted) score += 40;
        
        // Typing speed > 120 chars/sec is suspicious (average is 40-60)
        if (meta.typing_speed > 120) score += 30;
        
        // Too few corrections suggests copy-paste
        if (meta.correction_ratio < 0.05 && meta.answer_length > 100) score += 20;
        
        // Very consistent typing intervals (variance < 50ms) is suspicious
        const variance = this.calculateVariance(meta.typing_intervals);
        if (variance < 50 && meta.typing_intervals.length > 10) score += 20;
        
        return Math.min(score, 100);
    }
    
    calculateVariance(intervals) {
        if (intervals.length === 0) return 0;
        
        const mean = intervals.reduce((a, b) => a + b, 0) / intervals.length;
        const squaredDiffs = intervals.map(x => Math.pow(x - mean, 2));
        const avgSquaredDiff = squaredDiffs.reduce((a, b) => a + b, 0) / intervals.length;
        
        return Math.sqrt(avgSquaredDiff);
    }
}

// Auto-initialize for interview page
document.addEventListener('DOMContentLoaded', function() {
    const answerTextarea = document.getElementById('answer');
    if (answerTextarea) {
        window.typingAnalyzer = new TypingAnalyzer('answer');
        
        // Attach metadata to form submission
        const form = answerTextarea.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const metadata = window.typingAnalyzer.getMetadata();
                const suspicionScore = window.typingAnalyzer.getSuspicionScore();
                
                // Add hidden fields with metadata
                const metadataInput = document.createElement('input');
                metadataInput.type = 'hidden';
                metadataInput.name = 'typing_metadata';
                metadataInput.value = JSON.stringify(metadata);
                form.appendChild(metadataInput);
                
                const scoreInput = document.createElement('input');
                scoreInput.type = 'hidden';
                scoreInput.name = 'suspicion_score';
                scoreInput.value = suspicionScore;
                form.appendChild(scoreInput);
                
                // No confirmation prompts - let candidates submit freely
                // Suspicion scores are tracked for backend analysis only
            });
        }
    }
});
