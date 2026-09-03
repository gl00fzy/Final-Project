<?php
require_once 'config/database.php';
echo "<pre>";
try {
    // 1. Add status column to users
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('pending','active','suspended') NOT NULL DEFAULT 'active'");
    echo "✅ Added status column to users\n";

    // 2. Set all existing users to active
    $pdo->exec("UPDATE users SET status = 'active' WHERE status IS NULL OR status = ''");
    echo "✅ Set all existing users to active\n";

    // 3. Create invite_codes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invite_codes (
            code_id    INT NOT NULL AUTO_INCREMENT,
            code       VARCHAR(32) NOT NULL UNIQUE,
            label      VARCHAR(100) DEFAULT NULL,
            role_grant ENUM('user','admin') DEFAULT 'user',
            max_uses   INT DEFAULT NULL,
            used_count INT DEFAULT 0,
            expires_at DATETIME DEFAULT NULL,
            is_active  TINYINT(1) DEFAULT 1,
            created_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (code_id),
            CONSTRAINT fk_invcode_creator FOREIGN KEY (created_by) REFERENCES users (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created invite_codes table\n";
    echo "\n🎉 Migration complete!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
