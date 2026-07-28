<?php
require_once 'config/database.php';
$new_hash = password_hash('password123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'teacher_demo'");
$stmt->execute([$new_hash]);
echo "Password updated successfully to 'password123'. Hash: " . $new_hash;
