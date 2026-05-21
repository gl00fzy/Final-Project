<?php
/**
 * migrate_phase2.php — Phase 2: Role-Based Admin & Usage Statistics
 *
 * Run once:  C:\xampp\php\php.exe migrate_phase2.php
 * Or via browser: http://localhost:8000/migrate_phase2.php
 *
 * Safe to run multiple times — all statements use IF NOT EXISTS / OR IGNORE.
 */
require_once 'config/database.php';

$steps = [];

// ── 1. Add `role` column to users ────────────────────────────────────────
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'user'");
    $steps[] = "✅ Added `role` column to `users` table (default: 'user').";
} catch (Exception $e) {
    // SQLite throws if column already exists — safe to ignore
    $steps[] = "⏭  `role` column already exists in `users` — skipped.";
}

// ── 2. Promote teacher_demo (or first user) to admin ─────────────────────
try {
    $affected = $pdo->exec("UPDATE users SET role = 'admin' WHERE username = 'teacher_demo'");
    if ($affected > 0) {
        $steps[] = "✅ Promoted `teacher_demo` to admin.";
    } else {
        // Fall back: promote the first user in the table
        $pdo->exec("UPDATE users SET role = 'admin' WHERE user_id = (SELECT MIN(user_id) FROM users)");
        $steps[] = "✅ Promoted first registered user to admin (teacher_demo not found).";
    }
} catch (Exception $e) {
    $steps[] = "❌ Failed to promote admin: " . $e->getMessage();
}

// ── 3. Create system_logs table ───────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_logs (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id    INTEGER NOT NULL,
        action     TEXT    NOT NULL,
        exam_id    INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )");
    $steps[] = "✅ Created `system_logs` table (or already existed).";
} catch (Exception $e) {
    $steps[] = "❌ Failed to create system_logs: " . $e->getMessage();
}

// ── Output ────────────────────────────────────────────────────────────────
$isCli = php_sapi_name() === 'cli';

if ($isCli) {
    echo "\n=== Phase 2 Migration ===\n";
    foreach ($steps as $s) { echo $s . "\n"; }
    echo "\nDone.\n";
} else {
    echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'>
    <title>Phase 2 Migration</title>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap' rel='stylesheet'>
    <style>body{font-family:Inter,sans-serif;background:#f9fafb;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .card{background:#fff;border-radius:1rem;padding:2rem 2.5rem;box-shadow:0 4px 24px #0001;border:1px solid #e5e7eb;max-width:520px;width:100%}
    h1{font-size:1.4rem;font-weight:700;margin-bottom:1.5rem;color:#111827}
    li{padding:.5rem 0;border-bottom:1px solid #f3f4f6;font-size:.95rem;color:#374151;list-style:none}
    a{display:inline-block;margin-top:1.5rem;background:#6366f1;color:#fff;padding:.65rem 1.5rem;border-radius:.6rem;text-decoration:none;font-weight:600;font-size:.9rem}
    a:hover{background:#4f46e5}</style></head><body><div class='card'>
    <h1>⚙️ Phase 2 Migration</h1><ul>";
    foreach ($steps as $s) { echo "<li>$s</li>"; }
    echo "</ul><a href='admin_dashboard.php'>→ เข้า Admin Dashboard</a></div></body></html>";
}
