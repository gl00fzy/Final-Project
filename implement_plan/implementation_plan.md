# Implementation Plan: Phase 4 Advanced Key Editor UI

This phase focuses on completely revamping the Key Editor UI to allow professors to visually configure the new advanced JSON parameters (Multiple answers, Points, Penalties, Logic, and Ignore).

## Proposed Changes

### [MODIFY] [key_editor.php](file:///c:/Final%20Project/key_editor.php)

#### 1. Data Structure Migration & Initialization
When loading the key from the database, the PHP script will need to adapt to the new structure.
- Old structure: `{"A": {"1": "B"}}`
- New structure: `{"A": {"1": {"answers": ["B"], "logic": "OR", "points": 1, "penalty": 0, "ignore": false}}}`
- The script will initialize missing configurations with defaults `(points: 1, logic: 'OR')` to prevent JS errors.

#### 2. UI Layout Overhaul
- **Multi-select Bubbles:** Modify the A/B/C/D/E buttons to act as toggles (checkboxes) instead of radio buttons, allowing multiple correct answers.
- **Settings Collapse/Modal per Question:** Add a "gear" settings button next to each question. Clicking it will reveal a Tailwind dropdown or inline form below the bubbles containing:
  - `Points` (Number input, default 1)
  - `Penalty` (Number input, default 0)
  - `Logic` (Dropdown: `OR (Any match)` / `AND (Exact match)`)
  - `Ignore` (Toggle switch to ignore the question)

#### 3. JavaScript Logic Rewrite
- Rewrite `renderKey()` to parse the new object format. It will highlight multiple bubbles and populate the settings dropdown correctly.
- Update click listeners so clicking a bubble toggles it in the `answerKey[currentSet][q].answers` array rather than replacing it.
- Bind `change` events on the settings inputs to update their respective keys in the `answerKey` object instantly.
- The "Save" button will `JSON.stringify` the new complex object and send it to `api/exams.php`, which will then naturally trigger the Auto-Regrade hook we built in Phase 3.

## User Review Required
> [!IMPORTANT]
> The UI for per-question settings can get cluttered. I plan to use an **inline collapsible section** (hidden by default) that expands when the user clicks a "Settings" gear icon for that specific question. This keeps the initial view clean. Do you approve this inline collapsible design?

## Verification Plan
1. Open the Key Editor for an exam.
2. Select multiple bubbles for a question.
3. Open the gear menu, change Points to 2 and Logic to AND.
4. Hit Save.
5. Reload the page and verify the state persists correctly.
