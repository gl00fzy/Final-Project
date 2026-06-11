---
name: OMR System
description: Web-based multiple-choice exam grading tool for MSU teachers
colors:
  yellow-primary: "#EAB308"
  yellow-hover: "#CA8A04"
  yellow-surface: "#FEF9C3"
  yellow-text: "#854D0E"
  gray-900: "#111827"
  gray-800: "#1F2937"
  gray-700: "#374151"
  gray-600: "#4B5563"
  gray-500: "#6B7280"
  gray-400: "#9CA3AF"
  gray-300: "#D1D5DB"
  gray-200: "#E5E7EB"
  gray-100: "#F3F4F6"
  gray-50: "#F9FAFB"
  white: "#FFFFFF"
  red-600: "#DC2626"
  red-surface: "#FEF2F2"
  emerald-500: "#10B981"
  emerald-surface: "#ECFDF5"
  indigo-600: "#4F46E5"
  indigo-surface: "#EEF2FF"
typography:
  display:
    fontFamily: "Inter, system-ui, sans-serif"
    fontSize: "1.875rem"
    fontWeight: 800
    lineHeight: 1.2
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "Inter, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.3
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Inter, system-ui, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 700
    lineHeight: 1.4
  body:
    fontFamily: "Inter, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Inter, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "0.025em"
rounded:
  sm: "8px"
  md: "12px"
  lg: "16px"
  full: "9999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
  section: "40px"
components:
  button-primary:
    backgroundColor: "{colors.yellow-primary}"
    textColor: "{colors.gray-900}"
    rounded: "{rounded.md}"
    padding: "12px 24px"
  button-primary-hover:
    backgroundColor: "{colors.yellow-hover}"
  button-secondary:
    backgroundColor: "{colors.gray-100}"
    textColor: "{colors.gray-700}"
    rounded: "{rounded.md}"
    padding: "12px 24px"
  button-danger:
    backgroundColor: "{colors.red-600}"
    textColor: "{colors.white}"
    rounded: "{rounded.md}"
    padding: "12px 24px"
  input-default:
    backgroundColor: "{colors.white}"
    textColor: "{colors.gray-900}"
    rounded: "{rounded.sm}"
    padding: "10px 16px"
  card-default:
    backgroundColor: "{colors.white}"
    rounded: "{rounded.lg}"
    padding: "24px"
  navbar:
    backgroundColor: "{colors.gray-800}"
    textColor: "{colors.white}"
    height: "64px"
---

# Design System: OMR System

## 1. Overview

**Creative North Star: "The Exam Control Room"**

A no-nonsense command center where teachers execute grading workflows with precision and confidence. The interface is structured, task-focused, and respects the teacher's time above all else. Every surface serves the workflow: scan answer sheets, manage answer keys, review results, administer users. There is no marketing, no onboarding whimsy, no decoration for its own sake.

The system is built on a single sans-serif family (Inter) at a tight scale ratio, with yellow (#EAB308) as the sole brand accent. The palette is restrained: gray neutrals carry 90% of the surface, and yellow marks only primary actions and active states. This restraint is deliberate—in a data-rich grading tool, color discipline prevents cognitive overload.

The system explicitly rejects overly complex dashboards, outdated '90s web portal looks, or overly playful/childish interfaces (per PRODUCT.md). It also rejects gratuitous animation, display fonts in UI labels, and decorative motion that doesn't convey state.

**Key Characteristics:**
- Single-family typography (Inter) across all surfaces, no pairing
- Yellow accent used exclusively for primary actions and active state indicators
- Flat-by-default elevation with hover-only shadows
- Rounded corners (8–16px) that soften without becoming playful
- Consistent component vocabulary across all screens

## 2. Colors

A restrained palette. Yellow is the single accent; gray neutrals carry the structure. Semantic colors (red, emerald, indigo) appear only in their designated roles and never as decoration.

### Primary
- **MSU Yellow** (#EAB308): Primary CTA buttons, active tab indicators, selected answer key bubbles, score highlights, brand icon accents. Used on ≤10% of any given screen.
- **Yellow Hover** (#CA8A04): Hover state for primary buttons.
- **Yellow Surface** (#FEF9C3): Background tint for yellow-accented callouts and admin stat cards.
- **Yellow Text** (#854D0E): Text on yellow surface backgrounds for contrast compliance.

### Neutral
- **Ink** (#111827 / gray-900): Primary headings, bold data values.
- **Body** (#374151 / gray-700): Default body text, secondary labels.
- **Muted** (#6B7280 / gray-500): Tertiary text, timestamps, helper copy.
- **Placeholder** (#9CA3AF / gray-400): Input placeholders, disabled text.
- **Border** (#E5E7EB / gray-200): Card borders, table dividers, input outlines.
- **Surface** (#F3F4F6 / gray-100): Secondary button backgrounds, table headers, alternate rows.
- **Canvas** (#F9FAFB / gray-50): Page body background.
- **Card** (#FFFFFF / white): Primary card and modal surfaces.
- **Nav** (#1F2937 / gray-800): Top navigation bar.

### Semantic
- **Error** (#DC2626 / red-600): Delete confirmations, error alerts, destructive buttons.
- **Error Surface** (#FEF2F2): Error alert background tint.
- **Success** (#10B981 / emerald-500): Scan success feedback, correct-answer indicators in item analysis, print/PDF actions.
- **Success Surface** (#ECFDF5): Success alert background tint.
- **Admin/Share** (#4F46E5 / indigo-600): Admin badge button in nav, share-exam actions. Secondary accent for admin-only surfaces.
- **Admin Surface** (#EEF2FF): Indigo-tinted backgrounds for share/admin elements.

### Named Rules
**The One Accent Rule.** Yellow is the sole brand color. Red, emerald, and indigo are semantic only—they mark state, not identity. If a new feature needs a color, it must be a tint of an existing semantic or a shade of the neutral ramp, never a new accent.

## 3. Typography

**Display Font:** Inter (with system-ui, sans-serif fallback)
**Body Font:** Inter (with system-ui, sans-serif fallback)
**Mono Font:** System monospace (used for student IDs and email addresses)

**Character:** One family, many weights. Inter carries everything from page headings to bubble labels. The system feels consistent and workmanlike; typography variety comes from weight and size, never from font pairing. This is a tool, not a magazine.

### Hierarchy
- **Display** (800, 1.875rem / 30px, 1.2 line-height): Page-level headings. "OMR System" logo text, "ภาพรวมระบบ" admin hero.
- **Headline** (700, 1.5rem / 24px, 1.3 line-height): Section headings. "จัดการข้อสอบ", exam card titles.
- **Title** (700, 1.25rem / 20px, 1.4 line-height): Modal titles, tab section headers, card sub-headings.
- **Body** (400, 0.875rem / 14px, 1.5 line-height): Default reading text, form labels, activity feed entries. Max line-length 65–75ch for prose blocks.
- **Label** (600, 0.75rem / 12px, 0.025em tracking): Stat card category labels, badge text, table column headers. Often uppercase with wider tracking.

### Named Rules
**The Inter-Only Rule.** No secondary typeface. Weight and size provide all the hierarchy this tool needs. Display fonts in UI labels, buttons, or data are prohibited.

## 4. Elevation

Flat-by-default. Surfaces are flat at rest. Shadows appear only as a response to state: hover lifts cards with `0 12px 28px rgba(234,179,8,0.18)` (yellow-tinted shadow on stat cards), modals use `shadow-xl` for clear layering, and `backdrop-blur-sm` behind modal backdrops adds depth without heavy shadow. Depth is primarily conveyed through background-color differentiation: gray-50 canvas → white cards → gray-800 nav.

### Shadow Vocabulary
- **Card hover** (`0 12px 28px rgba(234,179,8,0.18)`): Stat cards on admin dashboard hover. Yellow-tinted to reinforce the accent.
- **Card ambient** (`0 1px 2px 0 rgba(0,0,0,0.05)`): Default subtle shadow on white cards over gray-50 canvas (Tailwind `shadow-sm`).
- **Modal** (`shadow-xl` / `shadow-2xl`): Modal dialogs and scanner result cards. Clear separation from the page.
- **Scanner HUD** (`shadow-lg` with `backdrop-blur-md`): Floating controls on the camera scanner view. Glass-like backdrop on dark transparent backgrounds.

### Named Rules
**The Flat-By-Default Rule.** Surfaces are flat at rest. Shadows appear only as a response to state (hover, elevation, focus). Static shadows on resting cards are wrong—use border + background instead.

## 5. Components

### Buttons
- **Shape:** Gently rounded (12px radius / `rounded-xl`). Not pill-shaped, not sharp.
- **Primary:** Yellow (#EAB308) background, gray-900 text, 600–700 weight, 12px 24px padding. The single most recognizable interactive element.
- **Hover / Focus:** Yellow darkens to #CA8A04. Focus ring: 2px yellow-500 with 2px offset. Transition: `background-color 0.2s ease`.
- **Secondary:** Gray-100 background, gray-700 text. Cancel buttons, back actions.
- **Danger:** Red-600 background, white text. Delete confirmations only. Never for primary actions.
- **Ghost:** Transparent background, colored text with hover-visible border (rose-600 text for delete links, indigo-700 text for share actions).

### Cards / Containers
- **Corner Style:** Generously rounded (16px / `rounded-2xl`).
- **Background:** White (#FFFFFF) on gray-50 canvas.
- **Shadow Strategy:** `shadow-sm` at rest (barely visible), `shadow-md` on hover via `transition-shadow`. Reference Elevation section.
- **Border:** 1px solid gray-100. Subtle; the border-radius does the visual work.
- **Internal Padding:** 24px (`p-6`). Consistent across exam cards, modals, admin panels.

### Inputs / Fields
- **Style:** White background, 1px gray-300 border, 8px radius (`rounded-lg`). 10px 16px padding.
- **Focus:** 2px yellow-500 ring (`focus:ring-2 focus:ring-yellow-500`), border shifts to yellow-500. Transition: `border-color 0.2s`.
- **Error:** Red-50 background with red-200 border and red-600 text for inline error messages.
- **Disabled:** Gray-100 background, gray-400 text. Cursor not-allowed.

### Navigation
- **Style:** Gray-800 background, white text, 64px height, full-width sticky top.
- **Logo:** "OMR System" or "Admin Panel" with checkmark SVG icon, bold tracking-wider text.
- **Links:** Gray-700 background pills on hover becoming gray-600. 8px radius.
- **Active indicator:** Indigo-600 for admin button (admin-only surfaces).
- **Mobile:** Items stack or collapse to icon-only at `sm` breakpoint.

### Tabs
- **Style:** Text buttons in a horizontal row, separated by gaps, inside a white card.
- **Default:** Gray-500 text, no border.
- **Active:** Gray-900 text, 3px yellow-500 bottom border, 700 weight.

### Answer Key Bubbles (Signature Component)
- **Shape:** 32×32px circles (`rounded-full`), 1px gray-300 border.
- **Default:** White background, gray-600 text.
- **Selected:** Yellow-400 background, gray-900 text, yellow-500 border. The signature interaction of this product.
- **Transition:** `background-color 0.2s ease, border-color 0.2s ease`.

### Stat Cards (Admin Dashboard)
- **Shape:** White, 16px radius, 1px gray-100 border, 20px padding.
- **Hover:** translateY(-3px) lift + yellow-tinted shadow `0 12px 28px rgba(234,179,8,0.18)`. Transition 200ms ease.
- **Value typography:** 2.25rem / 800 weight. Color varies by semantic role (gray-800 default, yellow-500 for exams, emerald-500 for scans, sky-500 for daily).

### Scanner HUD (Camera Overlay)
- **Background:** Black/50–80% opacity with `backdrop-blur-md`. Glass-like floating panels.
- **Corners:** Extra-large radius (12–16px / `rounded-xl` to `rounded-full`).
- **Borders:** 1px white/10–20% opacity. Subtle containment on dark glass.
- **Typography:** White text, bold, small (xs to sm). High contrast on dark surfaces.
- **Viewfinder corners:** 3px yellow-400 bracket lines. The signature visual of the scanner.

## 6. Do's and Don'ts

### Do:
- **Do** use yellow (#EAB308) exclusively for primary CTAs, active states, and the brand icon. Rarity is the point.
- **Do** use Inter at 400/500/600/700/800 weights for all hierarchy. One family is enough.
- **Do** use `transition-colors` on every interactive element. 200ms, ease.
- **Do** use gray-50 canvas → white cards → gray-800 nav as the three-tier depth model.
- **Do** use the same button shape (12px radius, same padding scale) across every surface.
- **Do** use semantic colors (red, emerald, indigo) only in their designated roles (error, success, admin).
- **Do** maintain consistent component vocabulary across all screens—same button, same input, same card.

### Don't:
- **Don't** introduce a second accent color. Yellow is the identity. Red, emerald, and indigo are semantic only.
- **Don't** use display fonts, decorative typefaces, or font pairings. Inter only.
- **Don't** create overly complex dashboards with too many stat cards or nested data panels (per PRODUCT.md anti-reference).
- **Don't** use outdated '90s web portal patterns: heavy beveled borders, dark table headers, fixed-width layouts (per PRODUCT.md anti-reference).
- **Don't** use overly playful or childish interface elements: emoji as UI icons, rounded-full buttons with bouncy animations, bright multicolor palettes (per PRODUCT.md anti-reference).
- **Don't** use border-left or border-right greater than 1px as a colored accent stripe on cards or list items.
- **Don't** apply gradient text (`background-clip: text` with gradient).
- **Don't** use glassmorphism as a default surface treatment. The scanner HUD is the sole exception (functional glass on camera overlay).
- **Don't** animate `<img>` elements on hover—ever.
- **Don't** add decorative motion that doesn't convey state. No orchestrated page-load sequences. Product loads into a task; users don't want to watch it load.
- **Don't** use modals as a first thought. Exhaust inline and progressive alternatives first. (The scanner page's manual-entry modal and dashboard modals are exceptions where the action genuinely interrupts flow.)
