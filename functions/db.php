<?php
/**
 * Database Connection and Setup
 * Uses SQLite for a portable, single-file database
 */

// Database file path
define('DB_FILE', __DIR__ . '/../db.sqlite');

/**
 * Get database connection
 * @return PDO
 */
function get_db() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Initialize database tables if they don't exist
            init_database($pdo);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Initialize database tables
 * @param PDO $pdo
 */
function init_database($pdo) {
    // Users table - each user gets their own portal
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        company_name TEXT,
        created_at TEXT NOT NULL
    )");
    
    // Jobs table - each job belongs to a user
    $pdo->exec("CREATE TABLE IF NOT EXISTS jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        created_at TEXT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    // Candidates table
    $pdo->exec("CREATE TABLE IF NOT EXISTS candidates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        job_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT,
        experience TEXT,
        resume_path TEXT,
        status TEXT DEFAULT 'Applied',
        interview_token TEXT UNIQUE,
        applied_at TEXT NOT NULL,
        FOREIGN KEY (job_id) REFERENCES jobs(id)
    )");
    
    // Interview answers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS interview_answers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        candidate_id INTEGER NOT NULL,
        question TEXT NOT NULL,
        answer TEXT NOT NULL,
        FOREIGN KEY (candidate_id) REFERENCES candidates(id)
    )");
    
    // Reports table
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        candidate_id INTEGER NOT NULL,
        report_content TEXT NOT NULL,
        score INTEGER,
        generated_at TEXT NOT NULL,
        FOREIGN KEY (candidate_id) REFERENCES candidates(id)
    )");
    
    // Settings table - each user has their own settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        key TEXT NOT NULL,
        value TEXT,
        UNIQUE(user_id, key),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    // Interview questions cache table (for storing generated questions per candidate)
    $pdo->exec("CREATE TABLE IF NOT EXISTS interview_questions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        candidate_id INTEGER NOT NULL,
        question TEXT NOT NULL,
        question_order INTEGER NOT NULL,
        FOREIGN KEY (candidate_id) REFERENCES candidates(id)
    )");
}

/**
 * Get a setting value for current user
 * @param string $key
 * @param int|null $user_id Optional user ID, defaults to current user
 * @return string|null
 */
function get_setting($key, $user_id = null) {
    if ($user_id === null) {
        // Try to get current user ID if function exists
        if (function_exists('get_current_user_id')) {
            $user_id = get_current_user_id();
        }
    }
    
    $pdo = get_db();
    
    // If no user_id, try to get the first matching setting (for public pages)
    if (!$user_id) {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : null;
    }
    
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE user_id = ? AND key = ?");
    $stmt->execute([$user_id, $key]);
    $result = $stmt->fetch();
    return $result ? $result['value'] : null;
}

/**
 * Set a setting value for current user
 * @param string $key
 * @param string $value
 * @param int|null $user_id Optional user ID, defaults to current user
 */
function set_setting($key, $value, $user_id = null) {
    if ($user_id === null) {
        // Try to get current user ID if function exists
        if (function_exists('get_current_user_id')) {
            $user_id = get_current_user_id();
        }
    }
    
    if (!$user_id) {
        return;
    }
    
    $pdo = get_db();
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (user_id, key, value) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $key, $value]);
}
