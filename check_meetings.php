<?php
require_once 'functions/db.php';

$pdo = get_db();

echo "=== Database Check ===\n\n";

// Check meetings table
$stmt = $pdo->query("SELECT COUNT(*) as count FROM meetings");
$result = $stmt->fetch();
echo "Total meetings: " . $result['count'] . "\n\n";

// Show recent meetings
$stmt = $pdo->query("SELECT id, user_id, title, meeting_date, meeting_time, event_type FROM meetings ORDER BY meeting_date DESC LIMIT 5");
$meetings = $stmt->fetchAll();

if (count($meetings) > 0) {
    echo "Recent meetings:\n";
    foreach ($meetings as $meeting) {
        echo "- ID: {$meeting['id']}, User: {$meeting['user_id']}, Title: {$meeting['title']}, Date: {$meeting['meeting_date']}, Type: " . ($meeting['event_type'] ?? 'N/A') . "\n";
    }
} else {
    echo "No meetings found in database.\n";
}

echo "\n=== Done ===\n";
