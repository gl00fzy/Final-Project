<?php
$db_file = __DIR__ . '/database.sqlite';
$schema_file = dirname(__DIR__) . '/schema.sql';
$is_new_db = !file_exists($db_file);

try {
    $pdo = new PDO("sqlite:" . $db_file);
    // Set errormode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Initialize database if it's new
    if ($is_new_db && file_exists($schema_file)) {
        $schema = file_get_contents($schema_file);
        $pdo->exec($schema);
    }

} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
