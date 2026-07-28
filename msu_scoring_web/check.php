<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query('SELECT * FROM users');
    $users = $stmt->fetchAll();
    if (empty($users)) {
        echo "No users found. Attempting to insert...\n";
        $schema = file_get_contents('schema.sql');
        $pdo->exec($schema);
        echo "Schema executed again.\n";
        $stmt = $pdo->query('SELECT * FROM users');
        $users = $stmt->fetchAll();
    }
    print_r($users);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
