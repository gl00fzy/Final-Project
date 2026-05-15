<?php
require_once 'config/database.php';
try {
    $pdo->exec("ALTER TABLE student_scores ADD COLUMN image_path TEXT;");
    echo "Added image_path.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE student_scores ADD COLUMN raw_answers TEXT;");
    echo "Added raw_answers.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }
