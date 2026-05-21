# Implementation Plan — Phase 2: Role-Based Admin & Usage Statistics

We are adding a two-tier role system (`user` / `admin`) and a centralized Admin Dashboard that shows system-wide usage statistics and allows granting admin privileges to other users.

---

## Open Questions

> [!IMPORTANT]
> **Auth strategy:** Currently `auth.php` stores only `user_id` and `name` in the session. Should the `role` also be stored in the session at login time (fast, cached) rather than queried from the DB on every page load? **Recommended: yes**, store `$_SESSION['role']` at login. This approach is used in the plan below.

> [!IMPORTANT]
> **Log granularity:** Should `system_logs` track exam *creation* events too, or just *scans*? The plan below tracks scans only (as specified), but flagging this in case broader audit trails are needed later.

---

## Proposed Changes

### 1. Database Migration

#### [NEW] `migrate_phase2.php`
A one-shot migration script (following the existing `migrate_phase3.php` pattern) that runs via the command line or browser to upgrade the live database:

- `ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'user'` — adds role column, existing accounts default to `'user'`
- Promote the default demo account (`teacher_demo`) to `'admin'` automatically so the system has at least one admin from the start
- `CREATE TABLE IF NOT EXISTS system_logs (...)` — new audit table

**`system_logs` schema:**
```sql
CREATE TABLE IF NOT EXISTS system_logs (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL,
    action     TEXT    NOT NULL,   -- e.g. 'scan_success'
    exam_id    INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

#### [MODIFY] `schema.sql`
Append the two new schema blocks so fresh database installs include the role column and `system_logs` from the start.

---

### 2. Auth — Store Role in Session

#### [MODIFY] `api/auth.php`
- After successful `password_verify`, store `$_SESSION['role'] = $user['role']` alongside `user_id` and `name`.
- This avoids a DB round-trip for role checks on every admin page.

---

### 3. Usage Tracking

#### [MODIFY] `api/scores.php`
- After a **successful** `INSERT INTO student_scores`, insert a row into `system_logs`:
  ```php
  $pdo->prepare("INSERT INTO system_logs (user_id, action, exam_id) VALUES (?, 'scan_success', ?)")
      ->execute([$user_id, $exam_id]);
  ```
- This is inside the existing `try` block, so it only fires on actual successful saves (not duplicates or errors).

---

### 4. Admin Dashboard

#### [NEW] `admin_dashboard.php`
A standalone PHP page with a **purple/indigo admin colour theme** to visually distinguish it from the regular yellow teacher dashboard. It includes:

**Access Control:**
- Session check: `$_SESSION['role'] !== 'admin'` → redirect to `dashboard.php` with an error flash.

**Stats Cards (via direct DB queries at page load):**
| Stat | Query |
|---|---|
| Total Users | `SELECT COUNT(*) FROM users` |
| Total Exams | `SELECT COUNT(*) FROM exams` |
| Total Scans (all-time) | `SELECT COUNT(*) FROM system_logs WHERE action = 'scan_success'` |
| Scans Today | `SELECT COUNT(*) FROM system_logs WHERE action='scan_success' AND DATE(created_at) = DATE('now')` |

**Recent Activity Feed:**
- Last 10 log entries joined with `users.name` and `exams.exam_title` — shown as a timeline list.

**Grant Admin Interface:**
- A small form: input `@msu.ac.th` email → POST to `api/admin_action.php?action=grant_admin`.
- Shows inline success/error feedback.

#### [NEW] `api/admin_action.php`
Handles admin-only actions. Phase 2 supports one action:
- `grant_admin`: validates the requester is admin, finds the user by email, updates `role = 'admin'`. Returns JSON.

---

### 5. Dashboard Navigation Link

#### [MODIFY] `dashboard.php`
- Add a conditional link in the navbar: if `$_SESSION['role'] === 'admin'`, render an "🛡 Admin" link pointing to `admin_dashboard.php`.
- This keeps the admin section invisible to regular users.

---

## Verification Plan

### Automated
- Run `C:\xampp\php\php.exe migrate_phase2.php` to apply the migration — check output for success messages.
- Confirm no errors appear on `dashboard.php` and `admin_dashboard.php`.

### Manual
1. Log in as `teacher_demo` → verify "🛡 Admin" link appears in navbar.
2. Navigate to `admin_dashboard.php` → verify stats cards render correct counts.
3. Try accessing `admin_dashboard.php` while logged in as a regular user → verify redirect.
4. Scan a sheet via the scanner → verify a new row appears in `system_logs` and the "Scans Today" counter increments.
5. Use the Grant Admin form to promote another user → verify their role changes in the DB.
