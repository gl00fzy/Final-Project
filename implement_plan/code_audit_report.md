# 📋 Project Audit: Comprehensive Feature List
**OMR Grading System — Codebase Audit Report**
*Generated: 2026-05-18*

---

## 1. 👤 User & Exam Management

### Authentication
- **Login system** (`index.php` + `api/auth.php`) — Username/password login with session management
- **Session security** — `session_regenerate_id(true)` to prevent session fixation attacks
- **Logout** — Clears session via `api/auth.php?logout=1`
- **Access control** — All protected pages redirect unauthenticated users to `index.php`

### Professor Registration
- **Self-registration page** (`register.php`) — New professor accounts via web form
- **Invite code gate** — Hardcoded code `OMR-PRO-2026` required to register (prevents unauthorized signups)
- **BCRYPT password hashing** (`api/register_action.php`) — Passwords stored securely with `password_hash()`
- **Client-side password confirmation** — JS validates that password and confirm-password match before submitting
- **Duplicate username detection** — Backend returns an error if username already exists

### Exam Management (`dashboard.php` + `api/exams.php`)
- **Create exam** — Modal form with exam title, subject code, and selectable question count (50 / 100 / 150)
- **List exams** — AJAX-loaded card grid showing all owned exams, sorted newest-first
- **Share exam** — Share an exam with another professor by username (`api/share_manager.php`)
  - Ownership verification before sharing
  - Cannot share to self
  - Duplicate share prevention
- **Delete exam** — Atomic transactional delete (`api/delete_exam.php`) including:
  - Ownership verification
  - Cascade deletion of `student_scores` and `exam_shares`
  - Physical deletion of scanned image files from `/uploads/`

---

## 2. ⚙️ Advanced Grading Engine

### Core Engine (`api/grading_engine.php`)
- **`calculate_score()` function** — Central, reusable grading function used across multiple APIs
- **Backward compatibility** — Handles both old flat string key format (`"A"`) and new advanced JSON format
- **Set-based key support** — Evaluates student answers against the correct set (A, B, or C) from the multi-set JSON key structure

### Advanced Scoring Logic
- **OR Logic (Default)** — A question is correct if any of the student's selected answers intersect with the correct answers
- **AND Logic** — A question is correct only if the student's selection *exactly* matches all correct answers (order-insensitive sort comparison)
- **Custom point weighting** — Each question can have its own `points` value (not limited to 1)
- **Negative penalty** — Each question can have a configurable `penalty` deducted for wrong answers
- **Ignore flag** — Questions marked `"ignore": true` are skipped entirely during grading
- **Score floor** — Final calculated score is floored at 0 (cannot go negative)

### Multi-Set Answer Key
- **3 exam sets (A, B, C)** — Each exam can hold independent answer keys for three parallel versions
- **JSON key structure** — Each question stored as `{ answers: [], logic, points, penalty, ignore }`
- **Legacy key migration** — Old flat-format keys are automatically normalized to the new structure on load

### Auto-Regrade
- **Triggered on key save** (`api/exams.php` → `save_key` action) — Whenever an answer key is updated via the Key Editor, the system automatically recalculates scores for *all* previously scanned students who have `raw_answers` stored
- **Triggered on scan-to-key** (`api/scan_key.php`) — When a new key is set via camera scan, the same bulk regrade runs immediately
- **Preserves historical raw answers** — `raw_answers` column in `student_scores` stores the original bubble data, enabling recalculation at any time

---

## 3. 📷 Scanner & OMR Capabilities

### Camera Engine (`js/scanner.js` + `scanner.php`)
- **OpenCV.js integration** — Loads `opencv.js 4.8.0` asynchronously for real-time computer vision in-browser
- **Back camera preference** — Uses `facingMode: 'environment'` to default to the rear camera on mobile devices
- **Real-time video processing** — `processVideo()` loop runs via `requestAnimationFrame` for smooth processing
- **Grayscale + Gaussian Blur + Canny Edge Detection** — Standard OMR preprocessing pipeline
- **Contour detection** — `findContours()` to locate square corner markers on the OMR sheet
- **Perspective transformation** — 4-point marker detection triggers `getPerspectiveTransform` + `warpPerspective` to correct skewed sheet images
- **Bubble detection** — Scans bubble regions within the warped image to determine filled vs. empty bubbles
- **Duplicate submission prevention** — `scannedStudentIds` Set prevents the same student from being submitted twice in one session
- **Beep on successful scan** — Audio feedback via Web Audio API

### Scanner UI (`scanner.php`)
- **Full-screen immersive layout** — Camera feed fills `100dvh`, UI floats as HUD overlays (4-Layer Architecture)
- **Dual scan modes**:
  - **Student Mode** — Scans student answer sheet, grades it, and saves score
  - **Key Mode** — Scans a master answer sheet to extract and set the exam's answer key
- **Mode Toggle** — Segmented pill button UI (`สแกนนิสิต` / `สแกนเฉลย`) with active state using MSU Yellow
- **Exam Set Selector** — Dropdown to select which set (A, B, C) the current scan belongs to
- **Viewfinder / Reticle** — 4-corner bracket overlay (MSU Yellow `border-yellow-400`) with A4 aspect ratio (1:1.414) to guide document alignment
- **Guidance text** — "เล็งกรอบให้อยู่ในหน้าจอ" floating below the reticle
- **Status Indicator** — Floating status pill showing current state (loading, scanning, mode, errors)
- **Success Result Card** — Full-screen overlay showing student ID and score on successful scan
- **Dynamic border feedback** — `video-wrapper` border turns green on success, red on error

### Scan-to-Key (`api/scan_key.php`)
- **Camera-based key extraction** — Scanned bubbles are normalized into the Advanced JSON key structure
- **Set-safe merge** — Only the scanned set (A, B, or C) is updated; other sets remain intact
- **Legacy key migration** — If existing key is in old flat format, it's migrated to multi-set format before merging
- **Immediate auto-regrade** — After saving the new key, all existing `raw_answers` are regraded

### Manual Score Entry
- **Manual entry modal** — Floating pill button opens a modal for manual student ID + score input
- **Fallback for failed scans** — Used when lighting or paper quality prevents camera detection

---

## 4. 🗄️ Data Management & Recovery

### Student Roster (`roster.php` + `api/upload_roster.php`)
- **CSV roster upload** — Accepts a `.csv` file (columns: `student_id`, `name`)
- **Auto header detection** — Skips header row if it contains `student_id` or `รหัสนิสิต`
- **Upsert logic** — `INSERT OR REPLACE` updates existing students rather than duplicating
- **Transactional import** — Wrapped in `beginTransaction()` / `commit()` / `rollBack()` for data integrity
- **Roster view table** — Displays all students with ID and name

### Results & Analytics (`view_results.php` + `api/analytics.php`)
- **Score summary statistics** — Average, Maximum, Minimum, and Standard Deviation
- **Score histogram** — Distribution chart in bins of 10 (rendered with Chart.js)
- **Item analysis** — Per-question breakdown of how many students selected each option (A–E)
- **Hard question flagging** — Questions where fewer than 50% of students answered correctly are highlighted in red
- **Student score table** — Full list of all scanned students with their scores and timestamps
- **Scanned image thumbnails** — If an image was saved during scanning, it's viewable in results

### CSV Export (`api/export_csv.php`)
- **Download scores as CSV** — Exports `student_id`, `score`, `scanned_at` for all students in an exam
- **UTF-8 BOM** — Prepends BOM byte sequence for correct Thai character display in Microsoft Excel

### Key Editor (`key_editor.php`)
- **Per-question bubble grid** — Interactive A–E bubble selector for each question, supporting multi-select
- **Set tabs (A, B, C)** — Switch between and independently edit each exam set's key
- **Collapsible gear settings** — Per-question collapsible panel to configure `Logic (AND/OR)`, `Points`, `Penalty`, and `Ignore` flag
- **Auto-regrade on save** — Saving the key immediately triggers bulk regrading of all historical raw_answers
- **Legacy key normalization** — Old single-answer keys are automatically upgraded to Advanced JSON format on load

### Database Integrity
- **PDO Prepared Statements** — All SQL queries use parameterized statements to prevent SQL injection
- **SQLite `busy_timeout = 5000`** — Prevents database lock errors under concurrent writes
- **`UNIQUE(exam_id, student_id)`** — Database-level constraint prevents duplicate score entries
- **Duplicate score detection** — API returns a `"duplicate"` status code (not an error) for graceful UI handling

---

## 5. 🎨 UI/UX & Theming

### MSU Yellow & Gray Theme
- **Primary color** — `yellow-500` / `yellow-600` for all CTAs, active states, and brand accents
- **Secondary color** — `gray-800` for navbars, and `gray-900` for dark HUD elements on scanner
- **Accessibility** — Yellow backgrounds use `text-gray-900` (dark text) for WCAG contrast compliance
- **Global font** — `Inter` (Google Fonts) across all pages

### Favicon
- **Custom favicon** — `favicon_pic/favicon_for_web.png` linked in the `<head>` of all 7 UI pages

### Responsive Design
- **Mobile-first breakpoints** — `sm:`, `md:` Tailwind prefixes used throughout for adaptive layouts
- **`100dvh` viewport** — Scanner uses dynamic viewport height to correctly handle mobile browser URL bars
- **`whitespace-nowrap`** — Toggle buttons and action buttons prevent awkward text wrapping on small screens
- **Compact mobile sizing** — Scanner HUD uses `text-xs md:text-sm` and reduced padding on mobile

### Scanner UX
- **ZipGrade-style full-screen camera** — Immersive 4-layer architecture (Background → Camera → Reticle → HUD)
- **`object-contain` camera feed** — Ensures no cropping of the document corners, critical for OpenCV accuracy
- **Gradient HUD overlays** — `bg-gradient-to-b from-black/80 to-transparent` for readable controls over camera
- **Floating pill buttons** — Modern, minimal control style that maximizes camera viewport real estate
- **Micro-interaction** — Hover scale effect (`hover:scale-105`) on Manual Entry button

### Dashboard & General Pages
- **Exam card grid** — Responsive `grid-cols-1 md:grid-cols-2 lg:grid-cols-3` layout
- **Modal dialogs** — Create exam and share exam use `backdrop-blur-sm` modals
- **Loading spinners** — Animated spinner shown while AJAX loads exam list
- **AJAX forms** — All major actions (create, share, delete, scan) use `fetch()` without page reloads
- **Toast/alert feedback** — Inline alert boxes with contextual colors (yellow for success, red for errors)

---

## 📁 File Map Summary

| File | Role |
|------|------|
| `index.php` | Login page |
| `register.php` | Professor registration |
| `dashboard.php` | Exam management dashboard |
| `scanner.php` | Full-screen OMR scanner UI |
| `key_editor.php` | Advanced answer key editor |
| `view_results.php` | Results, analytics, export |
| `roster.php` | Student roster management |
| `api/auth.php` | Login/logout handler |
| `api/register_action.php` | Registration backend |
| `api/exams.php` | Create/list/save_key + auto-regrade |
| `api/scores.php` | Save scanned student score |
| `api/scan_key.php` | Camera-scanned key extraction + auto-regrade |
| `api/grading_engine.php` | Core `calculate_score()` function |
| `api/analytics.php` | Stats, histogram, item analysis |
| `api/share_manager.php` | Exam sharing between professors |
| `api/delete_exam.php` | Atomic exam deletion |
| `api/upload_roster.php` | CSV roster import |
| `api/export_csv.php` | CSV score export |
| `js/scanner.js` | OpenCV pipeline + submission logic |
| `schema.sql` | SQLite database schema |
