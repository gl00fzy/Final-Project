<?php
require_once 'config/database.php';

// MySQL error for duplicate column: "Duplicate column name 'xxx'"
// ใช้ stripos เพื่อ case-insensitive matching
function isDuplicateColumn(string $msg): bool {
    return stripos($msg, 'Duplicate column name') !== false;
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL;");
    echo "Added email column.<br>";
} catch (PDOException $e) {
    if (!isDuplicateColumn($e->getMessage())) {
        echo "Error adding email: " . $e->getMessage() . "<br>";
    } else {
        echo "email column already exists — skipped.<br>";
    }
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL;");
    echo "Added google_id column.<br>";
} catch (PDOException $e) {
    if (!isDuplicateColumn($e->getMessage())) {
        echo "Error adding google_id: " . $e->getMessage() . "<br>";
    } else {
        echo "google_id column already exists — skipped.<br>";
    }
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN auth_provider VARCHAR(20) DEFAULT 'local';");
    echo "Added auth_provider column.<br>";
} catch (PDOException $e) {
    if (!isDuplicateColumn($e->getMessage())) {
        echo "Error adding auth_provider: " . $e->getMessage() . "<br>";
    } else {
        echo "auth_provider column already exists — skipped.<br>";
    }
}

// Fix existing users
$pdo->exec("UPDATE users SET auth_provider = 'local' WHERE auth_provider IS NULL;");

echo "Migration completed.";
