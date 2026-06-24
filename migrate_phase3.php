<?php
require_once 'config/database.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        student_id VARCHAR(50)  NOT NULL,
        name       VARCHAR(255) NOT NULL,
        PRIMARY KEY (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created students table.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE student_scores ADD COLUMN exam_set TEXT DEFAULT 'A'");
    echo "Added exam_set.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_shares (
        share_id          INT NOT NULL AUTO_INCREMENT,
        exam_id           INT NOT NULL,
        shared_to_user_id INT NOT NULL,
        created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (share_id),
        CONSTRAINT fk_shares_exam2 FOREIGN KEY (exam_id)           REFERENCES exams(exam_id),
        CONSTRAINT fk_shares_user2 FOREIGN KEY (shared_to_user_id) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created exam_shares table.\n";
} catch(Exception $e) { echo $e->getMessage() . "\n"; }
