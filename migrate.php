<?php
/**
 * Database Migration Script
 * Run this once to update the database schema for multi-tenant support
 */

require_once __DIR__ . '/functions/db.php';

echo "Starting database migration...\n";

try {
    $pdo = get_db();
    
    // Add company_name and created_at to users table
    echo "Adding company_name column to users table...\n";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN company_name TEXT");
        echo "✓ Added company_name column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ company_name column already exists\n";
        } else {
            throw $e;
        }
    }
    
    echo "Adding created_at column to users table...\n";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN created_at TEXT NOT NULL DEFAULT ''");
        echo "✓ Added created_at column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ created_at column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Update existing users with created_at if empty
    echo "Updating existing users with created_at...\n";
    $pdo->exec("UPDATE users SET created_at = '" . date('Y-m-d H:i:s') . "' WHERE created_at = ''");
    echo "✓ Updated existing users\n";
    
    // Add user_id to jobs table
    echo "Adding user_id column to jobs table...\n";
    try {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN user_id INTEGER NOT NULL DEFAULT 1");
        echo "✓ Added user_id column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ user_id column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Migrate settings table to include user_id
    echo "Checking settings table structure...\n";
    $stmt = $pdo->query("PRAGMA table_info(settings)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    
    if (!in_array('user_id', $columns)) {
        echo "Migrating settings table...\n";
        
        // Create new settings table with user_id
        $pdo->exec("CREATE TABLE settings_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            key TEXT NOT NULL,
            value TEXT,
            UNIQUE(user_id, key),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");
        
        // Copy existing settings to all users
        $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $old_settings = $pdo->query("SELECT key, value FROM settings")->fetchAll();
        
        foreach ($users as $user_id) {
            foreach ($old_settings as $setting) {
                $stmt = $pdo->prepare("INSERT INTO settings_new (user_id, key, value) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $setting['key'], $setting['value']]);
            }
        }
        
        // Drop old table and rename new one
        $pdo->exec("DROP TABLE settings");
        $pdo->exec("ALTER TABLE settings_new RENAME TO settings");
        
        echo "✓ Migrated settings table\n";
    } else {
        echo "✓ Settings table already has user_id column\n";
    }
    
    // Add phone and experience to candidates table
    echo "Adding phone column to candidates table...\n";
    try {
        $pdo->exec("ALTER TABLE candidates ADD COLUMN phone TEXT");
        echo "✓ Added phone column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ phone column already exists\n";
        } else {
            throw $e;
        }
    }
    
    echo "Adding experience column to candidates table...\n";
    try {
        $pdo->exec("ALTER TABLE candidates ADD COLUMN experience TEXT");
        echo "✓ Added experience column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ experience column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Add AI detection fields to interview_answers table
    echo "Adding AI detection fields to interview_answers table...\n";
    try {
        $pdo->exec("ALTER TABLE interview_answers ADD COLUMN typing_metadata TEXT");
        echo "✓ Added typing_metadata column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ typing_metadata column already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE interview_answers ADD COLUMN ai_detection_score INTEGER DEFAULT 0");
        echo "✓ Added ai_detection_score column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ ai_detection_score column already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE interview_answers ADD COLUMN ai_detection_flags TEXT");
        echo "✓ Added ai_detection_flags column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ ai_detection_flags column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Add regeneration_count to reports table
    echo "Adding regeneration_count to reports table...\n";
    try {
        $pdo->exec("ALTER TABLE reports ADD COLUMN regeneration_count INTEGER DEFAULT 0");
        echo "✓ Added regeneration_count column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ regeneration_count column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Create meetings table
    echo "Creating meetings table...\n";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS meetings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            candidate_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            meeting_date TEXT NOT NULL,
            meeting_time TEXT NOT NULL,
            duration INTEGER DEFAULT 60,
            zoom_meeting_id TEXT,
            zoom_join_url TEXT,
            zoom_start_url TEXT,
            google_event_id TEXT,
            outlook_event_id TEXT,
            status TEXT DEFAULT 'scheduled',
            created_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (candidate_id) REFERENCES candidates(id)
        )");
        echo "✓ Created meetings table\n";
    } catch (PDOException $e) {
        echo "✓ Meetings table already exists\n";
    }
    
    // Add Google and Outlook event ID columns if they don't exist
    echo "Adding calendar sync columns to meetings table...\n";
    try {
        $pdo->exec("ALTER TABLE meetings ADD COLUMN google_event_id TEXT");
        echo "✓ Added google_event_id column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ google_event_id column already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE meetings ADD COLUMN outlook_event_id TEXT");
        echo "✓ Added outlook_event_id column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ outlook_event_id column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Add event_type column for generic events (meeting, reminder, task, other)
    echo "Adding event_type column to meetings table...\n";
    try {
        $pdo->exec("ALTER TABLE meetings ADD COLUMN event_type TEXT DEFAULT 'meeting'");
        echo "✓ Added event_type column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ event_type column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Add location column for event location/link
    echo "Adding location column to meetings table...\n";
    try {
        $pdo->exec("ALTER TABLE meetings ADD COLUMN location TEXT");
        echo "✓ Added location column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ location column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Add sync flags columns
    echo "Adding calendar sync flags to meetings table...\n";
    try {
        $pdo->exec("ALTER TABLE meetings ADD COLUMN sync_google INTEGER DEFAULT 0");
        echo "✓ Added sync_google column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ sync_google column already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE meetings ADD COLUMN sync_outlook INTEGER DEFAULT 0");
        echo "✓ Added sync_outlook column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ sync_outlook column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Make candidate_id nullable for generic events
    echo "Note: candidate_id in meetings table should be nullable for generic events.\n";
    echo "  SQLite doesn't support modifying column constraints easily.\n";
    echo "  For generic events (not linked to candidates), use NULL or 0 for candidate_id.\n";
    
    // Add is_super_admin column to users table
    echo "\nAdding is_super_admin column to users table...\n";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_super_admin INTEGER DEFAULT 0");
        echo "✓ Added is_super_admin column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "✓ is_super_admin column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Create system_settings table for centralized OAuth credentials
    echo "\nCreating system_settings table for centralized configuration...\n";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        echo "✓ Created system_settings table\n";
    } catch (PDOException $e) {
        echo "✓ system_settings table already exists\n";
    }
    
    echo "\n✅ Database migration completed successfully!\n";
    echo "\nYou can now delete this migrate.php file.\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

