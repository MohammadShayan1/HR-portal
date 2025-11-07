<?php
require_once 'functions/db.php';

echo "Making first user a super admin...\n\n";

$pdo = get_db();

// Get first user
$stmt = $pdo->query("SELECT id, email FROM users ORDER BY id ASC LIMIT 1");
$user = $stmt->fetch();

if ($user) {
    // Make them super admin
    $stmt = $pdo->prepare("UPDATE users SET is_super_admin = 1 WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    echo "✅ User '{$user['email']}' (ID: {$user['id']}) is now a Super Admin!\n\n";
    echo "Login and access: http://localhost/HR-portal/index.php?page=super_admin\n";
} else {
    echo "❌ No users found. Please create a user account first.\n";
}
