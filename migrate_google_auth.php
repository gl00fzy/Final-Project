<?php
require_once 'config/database.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN email TEXT;");
    echo "Added email column.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') === false) {
        echo "Error adding email: " . $e->getMessage() . "<br>";
    }
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN google_id TEXT;");
    echo "Added google_id column.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') === false) {
        echo "Error adding google_id: " . $e->getMessage() . "<br>";
    }
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN auth_provider TEXT DEFAULT 'local';");
    echo "Added auth_provider column.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') === false) {
        echo "Error adding auth_provider: " . $e->getMessage() . "<br>";
    }
}

// Modify existing test user just in case
$pdo->exec("UPDATE users SET auth_provider = 'local' WHERE auth_provider IS NULL;");

echo "Migration completed.";
?>
