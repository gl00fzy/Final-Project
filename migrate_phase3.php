<?php
require_once 'config/database.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        student_id TEXT PRIMARY KEY,
        name TEXT NOT NULL
    )");
    echo "Created students table.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE student_scores ADD COLUMN exam_set TEXT DEFAULT 'A'");
    echo "Added exam_set.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_shares (
        share_id INTEGER PRIMARY KEY AUTOINCREMENT,
        exam_id INTEGER NOT NULL,
        shared_to_user_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(exam_id),
        FOREIGN KEY (shared_to_user_id) REFERENCES users(user_id)
    )");
    echo "Created exam_shares table.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }
