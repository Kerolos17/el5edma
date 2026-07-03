---
name: Ministry System
description: Arabic-first church ministry management — calm, professional, modern
colors:
  # Web App surface
  warm-cloud: "#f6f8fb"
  silver-mist: "#dbe3ee"
  stone-grey: "#64748b"
  deep-ink: "#0f172a"
  calm-blue: "#2563eb"
  calm-blue-dark: "#1d4ed8"
  pure-white: "#ffffff"
  soft-frost: "#f8fafc"
  # Servant surface
  deep-teal: "#006D77"
  teal-soft: "#4D9BA3"
  teal-light: "#C7E5E8"
  warm-gold: "#F4A261"
  gold-soft: "#F7BB86"
  gold-light: "#FDEBD0"
  warm-cream: "#FFF8F0"
  soft-ivory: "#FFFBF7"
  soft-gray: "#F5F3F0"
  deep-navy: "#2C3E50"
  # Semantic
  semantic-success: "#06A77D"
  semantic-warning: "#F77F00"
  semantic-error: "#E63946"
  semantic-info: "#457B9D"
typography:
  display:
    fontFamily: "Amiri, serif"
    fontSize: "clamp(1.5rem, 3vw, 2rem)"
    fontWeight: 700
    lineHeight: 1.2
  headline:
    fontFamily: "Cairo, sans-serif"
    fontSize: "clamp(1.1rem, 2.5vw, 1.8rem)"
    fontWeight: 800
    lineHeight: 1.25
  title:
    fontFamily: "Cairo, sans-serif"
    fontSize: "clamp(0.95rem, 2vw, 1.15rem)"
    fontWeight: 800
    lineHeight: 1.35
  body:
    fontFamily: "Cairo, sans-serif"
    fontSize: "0.92rem"
    fontWeight: 500
    lineHeight: 1.7
  label:
    fontFamily: "Cairo, sans-serif"
    fontSize: "0.78rem"
    fontWeight: 700
    letterSpacing: "0.01em"
  mono:
    fontFamily: "JetBrains Mono, monospace"
rounded:
  sm: "6px"
  md: "8px"
  lg: "12px"
  xl: "20px"
  pill: "999px"
  card-servant: "24px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
components:
  button-primary:
    backgroundColor: "{colors.calm-blue}"
    textColor: "#ffffff"
    rounded: "{rounded.lg}"
    padding: "0 0.95rem"
    size: "min-height 2.75rem"
  button-primary-hover:
    backgroundColor: "{colors.calm-blue-dark}"
  button-secondary:
    backgroundColor: "{colors.soft-frost}"
    textColor: "#1e293b"
    rounded: "{rounded.lg}"
    border: "1px solid {colors.silver-mist}"
    padding: "0 0.95rem"
  nav-item-default:
    backgroundColor: "transparent"
    textColor: "#475569"
    rounded: "{rounded.lg}"
    padding: "0 0.85rem"
  nav-item-active:
    backgroundColor: "#eff6ff"
    textColor: "{colors.calm-blue-dark}"
  stat-card:
    backgroundColor: "{colors.pure-white}"
    rounded: "{rounded.xl}"
    padding: "1rem"
    shadow: "0 16px 45px rgba(15, 23, 42, 0.06)"
  s-card:
    backgroundColor: "{colors.pure-white}"
    rounded: "{rounded.card-servant}"
    shadow: "0 2px 8px rgba(0,61,66,0.06), 0 8px 24px rgba(0,61,66,0.08)"
  filter-chip-default:
    backgroundColor: "{colors.pure-white}"
    textColor: "#475569"
    rounded: "{rounded.pill}"
    border: "1px solid {colors.silver-mist}"
  filter-chip-active:
    backgroundColor: "#eff6ff"
    textColor: "{colors.calm-blue-dark}"
    borderColor: "#bfdbfe"
---

# Design System: Ministry System

## 1. Overview

**Creative North Star: "البيت الدافئ" (The Warm Home)**

Calm, professional, and modern. A ministry management system that feels like coming home — warm without being sentimental, organized without feeling cold. The aesthetic is restrained and intentional: every surface breathes, every interaction has purpose, and the user is never overwhelmed.

The system explicitly rejects the enterprise-clinical look (slate-grey overload, aggressive blues, sterile data tables) and the dark-heavy aesthetic (default dark themes, deep shadows, neon accents). Instead, it defaults to light warmth (cream and ivory bases with subtle teal or blue accents) and uses darkness only as the secondary, optional mode.

**Key Characteristics:**
- Restrained color strategy: warm neutral base + one muted accent
- Shadow-based elevation: cards float on soft, atmospheric shadows
- Two typographic personalities: Amiri serif for display warmth (servant panel), Cairo sans for professional clarity (both panels)
- Generous whitespace and vertical rhythm — the interface is never dense
- RTL-first but bilingual-ready
- Accessibility is structure, not overlay: high contrast, large targets, clear labels

## 2. Colors: The Warm Home Palette

A restrained palette rooted in warm neutrals. The servant surface leans warmer (teal + gold), the web app cleaner (calm blue + warm cloud). Both share the same philosophy: warm, not clinical.

### Primary

- **Calm Blue** (`#2563eb` / oklch(0.55 0.15 250)): Primary accent for the Web App. Used for primary actions, active navigation, and interactive states. Rare enough to be meaningful.
- **Deep Teal** (`#006D77` / oklch(0.45 0.1 200)): Primary accent for the Servant panel. Warm, natural, calming. Used for the sidebar, primary buttons, and the wizard progress connector.

### Secondary

- **Warm Gold** (`#F4A261` / oklch(0.75 0.12 75)): Accent for the Servant panel. Used sparingly: badge highlights, avatar rings, stat card icon backgrounds. A golden thread of warmth.

### Neutral

- **Warm Cloud** (`#f6f8fb`): Base background for the Web App. Light, airy, slightly warm.
- **Warm Cream** (`#FFF8F0`): Base background for the Servant panel. Warmer and more textured.
- **Soft Ivory** (`#FFFBF7`): Cards, modals, elevated surfaces in the Servant panel.
- **Pure White** (`#ffffff`): Web App cards, panels, stat cards.
- **Soft Frost** (`#f8fafc`): Web App secondary surfaces, table rows, input backgrounds.
- **Silver Mist** (`#dbe3ee`): Borders, dividers, subtle container edges.
- **Stone Grey** (`#64748b`): Muted text, secondary labels, metadata, helper text.
- **Deep Ink** (`#0f172a`): Primary body text — high contrast, never pure black.
- **Deep Navy** (`#2C3E50`): Primary body text on the Servant panel.

### Semantic

- **Success** (`#06A77D`): Confirmed, completed, active.
- **Warning** (`#F77F00`): Attention required, pending.
- **Error** (`#E63946`): Critical, failed, requires intervention.
- **Info** (`#457B9D`): Informational, neutral.

### Tone Classes
Five tone modifiers for status pills and stat icons: `tone-slate` (neutral), `tone-blue` (info), `tone-emerald` (success), `tone-amber` (warning), `tone-rose` (critical). Each has light background + saturated text in the same hue.

### Named Rules

**The One Voice Rule.** The primary accent is the only saturated color on any given surface. Accents never compete. The secondary accent (Gold) appears only on the Servant panel, and only as a complementary accent ≤10% of the surface.

## 3. Typography

**Display Font:** Amiri (with fallback serif)
**Body Font:** Cairo (primary), Inter (English fallback)
**Label/Mono Font:** JetBrains Mono (Filament admin code inputs)

**Character:** A deliberate two-face system. Amiri provides warmth and dignity for display headings in the Servant panel — the face of the church, not the office. Cairo brings professional clarity for body text, labels, and data in both panels. English falls back to Inter for native system feel.

### Hierarchy

- **Display** (Amiri 700, clamp(1.5rem, 3vw, 2rem), 1.2): Hero headings, servant panel welcome cards, key metric reveals. Warmth and presence.
- **Headline** (Cairo 800, clamp(1.1rem, 2.5vw, 1.8rem), 1.25): Page titles, stat card numbers. Bold and commanding.
- **Title** (Cairo 800, clamp(0.95rem, 2vw, 1.15rem), 1.35): Section headers, card titles, modal headings.
- **Body** (Cairo 500, 0.92rem, 1.7): Paragraphs, descriptions, table cell content. Prose max-width 65-75ch.
- **Label** (Cairo 700, 0.78rem, tracked 0.01em): Table headers, filter chips, status pills, form labels, metadata. Compact and legible.

### Named Rules

**The One Family Rule.** Cairo carries all body, title, headline, and label roles across both surfaces. Amiri is reserved for display-only moments in the Servant panel. No mixing of display and body fonts on the same surface for the same purpose.

## 4. Elevation

Shadow-based, with a default flat resting state. Elevation is conveyed through atmospheric box-shadows, not gradient depth or tonal layering. Shadows are soft and diffused — the interface floats rather than stacks.

### Shadow Vocabulary

- **Card rest** (`0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04)`): Default state for panels, stat cards, sections.
- **Card hover** (`0 20px 50px rgba(15, 23, 42, 0.09)`): Raised state on hover. Cards lift gently by -2px.
- **Dropdown** (`0 12px 36px rgba(15, 23, 42, 0.12)`): Dropdown menus, notification panels. Floating above all content.
- **Modal** (`0 24px 70px rgba(15, 23, 42, 0.18)`): Modal panels. Highest elevation, focused content only.
- **Servant card rest** (`0 2px 8px rgba(0,61,66,0.06), 0 8px 24px rgba(0,61,66,0.08)`): Softer, warmer shadows for servant panels.
- **Servant card hover** (`0 8px 16px rgba(0,61,66,0.10), 0 16px 48px rgba(0,61,66,0.15)`): Warmer raised state.

### Named Rules

**The Flat-By-Default Rule.** Surfaces are flat at rest. Shadows appear only as a response to state (hover, elevation, focus). No surface has a shadow in its default resting state unless it's a floating element (dropdown, modal, toast).

## 5. Components

### Buttons

- **Shape:** Gently rounded (12px / 0.75rem).
- **Primary:** Calm Blue or Deep Teal background, white text, weight 800. Full height 2.75rem. Hover: darkens by one shade. Active: scale(0.97). Disabled: opacity 0.6.
- **Secondary:** Soft Frost background, slate text, 1px Silver Mist border. Same height and radius as primary. Hover: background darkens slightly.
- **Icon button:** 2.75rem square, Pure White background, Silver Mist border. Hover: Soft Frost background.
- **Ghost (Filament):** Transparent, primary color text. Hover: opacity increases.

### Chips / Filter Chips

- **Shape:** Pill (999px border-radius), 2.75rem min-height.
- **Default:** White background, 1px Silver Mist border, slate text, weight 800.
- **Active:** Light blue background (eff6ff), Calm Blue Dark text, blue border (bfdbfe).
- Each chip is a touch target ≥48px.

### Stat Cards

- **Shape:** 16px / 1rem border-radius, generous padding (1rem).
- **Background:** Pure White (Web App), raised below content.
- **Shadow:** Soft atmospheric at rest, lifts on hover.
- **Content:** Icon (2.75rem toned square), value (Headline weight 800), label (muted Label weight 700).
- **Animation:** Fade-in with scale on page enter, staggered by card index (0-0.18s delays).

### Navigation

- **Web App sidebar:** 17rem sticky sidebar, translucent white with backdrop-blur, Silver Mist border-right. Nav items: 2.75rem min-height, 12px border-radius. Active/hover: light blue background + Calm Blue Dark text. Hover: translateX(-2px).
- **Servant sidebar:** Fixed 16rem dark teal gradient sidebar on desktop. Nav items: white text at 0.7 opacity, hover → full opacity with subtle glow. Active: teal highlight bar.
- **Mobile nav:** 4-item bottom bar (Web App), 3-item + FAB (Servant). Backdrop-blur, 4.5rem min-height.
- **Drawer:** Slide-in from right, dark slate gradient, 18rem wide. Backdrop with blur.

### Modals

- **Structure:** Fixed backdrop → centered sheet → white panel. Backdrop click and ESC dismiss.
- **Shape:** 1.1rem border-radius, white background, 24px + 70px shadow. Wide variant: up to 58rem.
- **Header:** Close button (left in RTL), title (weight 800), optional description.
- **Form body:** 2-column responsive grid (1fr → 2fr at 640px). Fields: 0.9rem radius, Soft Frost background, 1px Silver Mist border. Error: deep rose text below field.
- **Mobile:** Full-width, bottom-anchored, rounded top corners only.

### Inputs / Fields

- **Shape:** 0.9rem border-radius, 3rem min-height.
- **Default:** Soft Frost background, 1px Silver Mist border, Deep Ink text, Cairo weight 500.
- **Focus:** border-shift to Calm Blue, no glow. Optional: 3px blue focus ring for keyboard navigation.
- **Error:** Red border, error text below (deep rose, 0.76rem, weight 700).
- **Disabled:** 0.6 opacity, not-allowed cursor.
- **Servant search:** 16px radius, 2px border, soft shadow, white background. Focus: teal border + teal glow ring.

### Cards / Containers

- **Web App panels:** 1rem border-radius, Pure White, Silver Mist border, soft shadow. Header with title + optional action link.
- **Servant cards:** 24px border-radius, white, servant-specific warm shadow. Card-lift hover effect: -4px translateY + scale(1.01).
- **Mobile cards:** 0.95rem radius, Soft Frost background, 1px border. Stacked grid on mobile, 2-column on desktop.

### Badges / Status Pills

- **Shape:** Pill (999px), 1.75rem min-height, with leading dot indicator.
- **Five tones:** slate (neutral), blue (info), emerald (success), amber (warning), rose (critical).
- **Servant badges:** Gradient-filled pills (success/warning/critical/info), white text, weight 600.

## 6. Do's and Don'ts

### Do:
- **Do** use the Warm Cloud or Warm Cream base for all backgrounds. Cool greys are prohibited outside the dark theme.
- **Do** keep the primary accent to ≤10% of any screen. Rarity is the point.
- **Do** use Amiri for servant display headings only. Never use it for body text, buttons, labels, or data.
- **Do** maintain 48px minimum touch targets on all interactive elements.
- **Do** use shadow-based elevation. Flat surfaces at rest, shadow on hover/state change.
- **Do** show empty states with an icon, helpful message, and a CTA. Never show a blank table.
- **Do** use skeleton shimmer on loading, not spinners in content areas.
- **Do** support `prefers-reduced-motion` on all animations: skeleton → static, reveals → instant, blobs → static gradient.
- **Do** keep form fields at 0.9rem border-radius with Soft Frost background. Consistent across both surfaces.

### Don't:
- **Don't** use enterprise-clinical palettes: no slate-grey overload, no aggressive blue gradients, no sterile data tables. The anti-reference is a hospital dashboard.
- **Don't** use dark theme as default. Light is the default; dark is optional.
- **Don't** use glassmorphism decoratively. Backdrop-blur is functional (topbar, sidebar), never decorative.
- **Don't** use side-stripe borders (border-left >1px as a colored accent). Use full borders, background tints, or nothing.
- **Don't** use gradient text (background-clip: text). Solid colors only.
- **Don't** use display fonts (Amiri) in UI labels, buttons, or data cells.
- **Don't** nest cards. A card inside a card is always wrong.
- **Don't** use modals as the first thought. Exhaust inline expandable forms and slide-over panels first.
- **Don't** reinvent standard affordances: no custom scrollbars that hide content, no non-standard form controls.
- **Don't** use decorative motion that doesn't convey state. Every animation must communicate: loading, transition, feedback, or reveal.
- **Don't** repeat the same card grid pattern everywhere. Vary layout rhythm — not everything is an icon-title-description card.
