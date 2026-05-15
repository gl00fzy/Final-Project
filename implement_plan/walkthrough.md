# Advanced Grading Engine - Epic Completed 🏆

The final piece of the ZipGrade-like Advanced Engine is now online.

## Phase 5: Scan to Set Key (`scanner.php`)
Professors can now use their mobile device's camera to instantly build Answer Keys.

### 1. Dual-Mode UI
- Added a segmented toggle switch at the top center of the scanner screen: **[ สแกนนิสิต ]** (Scan Student) and **[ สแกนเฉลย ]** (Scan Key).
- The UI dynamically changes colors based on the mode: Green/Dark for grading students, and a distinct **Blue** theme for scanning answer keys to prevent accidental mis-scans.

### 2. Intelligent Data Parsing (`api/scan_key.php`)
- When in "Scan Key" mode, the camera bypasses student grading entirely.
- It detects the filled bubbles and sends them to the new `scan_key.php` endpoint.
- The API automatically translates these raw bubbles into the complex Advanced JSON configuration, defaulting to:
  - `Points`: 1
  - `Penalty`: 0
  - `Logic`: OR
  - `Ignore`: False
- It merges this newly scanned key precisely into the selected Exam Set (A, B, or C) without deleting other sets.

### 3. The Ultimate Synergy
- Because we built a unified `grading_engine.php` in Phase 3, the exact moment the camera scans and saves the new Answer Key, the backend triggers the Auto-Regrade script.
- **Result:** You can point your camera at an Answer Key sheet, wait for the *beep*, and instantly, your entire classroom's grades are recalculated against that new physical key.

---

> [!SUCCESS]
> **Epic Feature Request Completed!** 
> The system has successfully transformed from a basic OMR string-matcher into a full-fledged, commercial-grade evaluation engine supporting Negative Scoring, Multi-Select Bubbles, Ignore Rules, Automated Regrading, and Camera-Based Key Capture.
