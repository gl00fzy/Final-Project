# Feature Request: Professor Registration System

**Role:** Senior Full-Stack Developer

**Context:** We need a way for new professors to create their own accounts to use the Multiple Choice Grading System. However, to prevent students or unauthorized users from registering, we will implement a "Registration with Invite Code" pattern.

**Task:**
Please create a Registration flow matching our existing **Tailwind CSS Emerald Green** theme.

---

## 🎯 Requirements & Logic

### 1. Database (`users` table check)
* Ensure the `users` table exists with columns: `user_id` (PK, auto-increment), `username` (UNIQUE), `password` (VARCHAR 255 for hashes), and `name` (VARCHAR).

### 2. Frontend (`register.php`)
* Create a clean, centered card UI similar to `index.php` (Login).
* **Fields:** * Full Name
  * Username
  * Password
  * Confirm Password
  * **Invite Code** (Required field)
* Add a link at the bottom: "Already have an account? Login here" pointing back to `index.php`.

### 3. Backend API (`api/register_action.php`)
* Handle the POST request securely.
* **Validation:**
  1. Check if the "Invite Code" matches a hardcoded secret in the PHP file (e.g., `$SECRET_INVITE_CODE = "OMR-PRO-2026";`). If not, return an error.
  2. Check if the passwords match.
  3. Query the DB to ensure the `username` is not already taken.
* **Security:** Use `password_hash()` to securely hash the password before inserting it into the database. NEVER store plain-text passwords.
* Use **PDO Prepared Statements** for the `INSERT` query.
* Return JSON response (`success` or `error` with message).

### 4. Client-side JS
* Add an event listener to the form to handle the `fetch` request to the API, displaying Tailwind-styled error messages or redirecting to `index.php` upon success.

**Execution:**
Please generate `register.php` and `api/register_action.php` applying all these rules.
