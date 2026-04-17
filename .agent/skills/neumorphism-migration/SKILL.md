---
name: neumorphism-migration
description: >
  Migrates existing web page designs to the Neumorphism (Soft UI) style pioneered by Alexander Plyuto.
  Use this skill whenever the user asks to: convert a website to neumorphism or soft UI, apply the
  neumorphic design trend, redesign UI components with soft shadows and extrusion effects, migrate
  HTML/CSS to neumorphic style, make buttons/cards/inputs "pop out" from the background, or apply
  the "new skeuomorphism" aesthetic. Also trigger when the user mentions terms like "soft UI",
  "extruded elements", "pillowy buttons", "neumorphic calculator/dashboard/card", or references
  Alexander Plyuto's banking app design. Even if the user simply says "make it look softer" or
  "give it depth without being flat", apply this skill.
---

# Neumorphism Migration Skill

## What is Neumorphism?

Neumorphism (also called "Soft UI" or "New Skeuomorphism") was popularized by designer **Alexander Plyuto** whose banking app concept went viral on Dribbble in 2019. It combines:
- The **depth and tactility** of Skeuomorphism (elements feel physically touchable)
- The **cleanliness and minimalism** of Flat Design

The term was coined by Michał Malewicz (CEO of Hype4) in 2019. Elements appear to **extrude from or embed into** the background — as if they could be physically pressed.

---

## Core Visual Principles

### 1. Unified Surface Color
- Background and UI elements share the **same base color** (or very close shades)
- No dramatic color changes between container and element
- Typical base: light gray (`#e0e5ec`), off-white (`#f0f0f3`), or soft pastels

### 2. Dual Shadow System (THE KEY TECHNIQUE)
Every raised element needs **two box-shadows simultaneously**:
- **Dark shadow**: bottom-right, slightly darker than base color → simulates gravity/shade
- **Light shadow**: top-left, slightly lighter than base color → simulates a light source above-left

```css
/* RAISED element (button at rest, card) */
box-shadow:
  6px 6px 12px #b8bec7,   /* dark shadow: bottom-right */
  -6px -6px 12px #ffffff;  /* light shadow: top-left */
```

```css
/* PRESSED / INSET element (active button, input field) */
box-shadow:
  inset 4px 4px 8px #b8bec7,   /* dark inset: top-left inside */
  inset -4px -4px 8px #ffffff;  /* light inset: bottom-right inside */
```

### 3. Muted Monochromatic Color Palette
- Use ONE base color and work with ±10–20% lightness/darkness
- Low saturation — avoid vivid/bright colors for backgrounds
- Accent colors (for active states, highlights) should be used sparingly

### 4. Soft Blur Radius
- Shadow blur values: typically `10px–20px` (never sharp/crisp)
- Spread: `0` or very small (avoid large spread which looks CSS-boxed)
- Border-radius: large (`12px–24px` for cards, `50%` for circles, `8px–16px` for buttons)

### 5. No Hard Borders
- Avoid `border: 1px solid` on neumorphic surfaces
- If borders are needed: use a very subtle `1px solid rgba(255,255,255,0.2)` for inner glow
- Let shadows define the edges instead

---

## Migration Checklist

When migrating a page, process these elements in order:

### Step 1 — Extract the base color
1. Identify the page's dominant background color
2. If it's white (`#ffffff`), shift to `#e0e5ec` or similar soft gray
3. Calculate the dark shadow color: darken base by ~15–20% (use HSL)
4. Calculate the light shadow color: lighten base by ~10% (or pure white)

### Step 2 — Rewrite the CSS Custom Properties
```css
:root {
  --neu-bg: #e0e5ec;
  --neu-shadow-dark: #b8bec7;
  --neu-shadow-light: #ffffff;
  --neu-radius: 16px;
  --neu-blur: 12px;
  --neu-distance: 6px;
  --neu-accent: #7b61ff; /* use sparingly */
}
```

### Step 3 — Apply to each component type

#### Cards / Containers
```css
.card {
  background: var(--neu-bg);
  border-radius: var(--neu-radius);
  box-shadow:
    var(--neu-distance) var(--neu-distance) var(--neu-blur) var(--neu-shadow-dark),
    calc(-1 * var(--neu-distance)) calc(-1 * var(--neu-distance)) var(--neu-blur) var(--neu-shadow-light);
  padding: 24px;
}
```

#### Buttons (Raised at rest, Pressed on :active)
```css
.btn {
  background: var(--neu-bg);
  border: none;
  border-radius: 12px;
  cursor: pointer;
  box-shadow:
    var(--neu-distance) var(--neu-distance) var(--neu-blur) var(--neu-shadow-dark),
    calc(-1 * var(--neu-distance)) calc(-1 * var(--neu-distance)) var(--neu-blur) var(--neu-shadow-light);
  transition: box-shadow 0.15s ease, transform 0.1s ease;
}

.btn:active,
.btn.pressed {
  box-shadow:
    inset var(--neu-distance) var(--neu-distance) var(--neu-blur) var(--neu-shadow-dark),
    inset calc(-1 * var(--neu-distance)) calc(-1 * var(--neu-distance)) var(--neu-blur) var(--neu-shadow-light);
  transform: scale(0.98);
}
```

#### Input Fields (Always inset — "carved into" the surface)
```css
input, textarea, select {
  background: var(--neu-bg);
  border: none;
  border-radius: 8px;
  outline: none;
  box-shadow:
    inset 4px 4px 8px var(--neu-shadow-dark),
    inset -4px -4px 8px var(--neu-shadow-light);
  padding: 12px 16px;
}
```

#### Toggle / Switches
```css
.toggle-track {
  background: var(--neu-bg);
  border-radius: 50px;
  box-shadow:
    inset 3px 3px 6px var(--neu-shadow-dark),
    inset -3px -3px 6px var(--neu-shadow-light);
}

.toggle-thumb {
  background: var(--neu-bg);
  border-radius: 50%;
  box-shadow:
    2px 2px 5px var(--neu-shadow-dark),
    -2px -2px 5px var(--neu-shadow-light);
}
```

#### Sliders (Alexander Plyuto's signature element)
```css
.slider-track {
  background: var(--neu-bg);
  border-radius: 50px;
  height: 8px;
  box-shadow:
    inset 2px 2px 5px var(--neu-shadow-dark),
    inset -2px -2px 5px var(--neu-shadow-light);
}

.slider-thumb {
  background: var(--neu-bg);
  border-radius: 50%;
  width: 24px; height: 24px;
  box-shadow:
    3px 3px 6px var(--neu-shadow-dark),
    -3px -3px 6px var(--neu-shadow-light);
}
```

#### Circular Icon Buttons / Avatars
```css
.icon-circle {
  border-radius: 50%;
  background: var(--neu-bg);
  box-shadow:
    5px 5px 10px var(--neu-shadow-dark),
    -5px -5px 10px var(--neu-shadow-light);
}
```

### Step 4 — Typography adjustments
- Body text: keep dark for readability (`#333` or `#444`) — contrast is critical
- Labels: medium gray (`#888`–`#aaa`)
- Headings: near-black (`#1a1a2e`) or dark accent
- **NEVER** use very light gray text on the neumorphic background — it disappears
- Use font-weight `400`–`600` (avoid ultra-thin weights)

### Step 5 — Dark Mode variant
Dark neumorphism uses a dark base and lighter/darker shadow pair:
```css
/* Dark mode overrides */
@media (prefers-color-scheme: dark) {
  :root {
    --neu-bg: #1e2027;
    --neu-shadow-dark: #15171d;
    --neu-shadow-light: #272a33;
  }
}
```

---

## Accessibility Warnings

Neumorphism is known for potential accessibility issues. Always:

1. **Contrast ratio**: Text must meet WCAG AA (4.5:1 for normal text). Test with a contrast checker.
2. **Interactive element indicators**: Add clear `:focus` styles since borders are removed.
   ```css
   .btn:focus-visible {
     outline: 2px solid var(--neu-accent);
     outline-offset: 3px;
   }
   ```
3. **Don't rely only on shadows** to show interactivity — use labels, icons, or subtle color tints on hover.
4. **Hover states**: Add a slight color shift or shadow intensification so clickable elements are obvious.
   ```css
   .btn:hover {
     box-shadow:
       8px 8px 16px var(--neu-shadow-dark),
       -8px -8px 16px var(--neu-shadow-light);
   }
   ```

---

## Common Pitfalls to Avoid

| ❌ Wrong | ✅ Correct |
|----------|-----------|
| White background (`#fff`) with neumorphic elements | Soft gray background (`#e0e5ec`) that matches elements |
| Only one box-shadow | Always TWO shadows (dark + light) |
| Sharp blur (small px) | Soft blur (10–20px) |
| `border: 1px solid #ccc` on elements | No borders, or use very subtle inner border |
| High-saturation accent everywhere | Accents only on active/selected states |
| Flat shadows (same direction as flat design drop shadow) | Dual opposing shadows |
| Black text at 100% — wait, this is actually fine | Keep text high contrast |
| Raising AND pressing shadows together | Raised = outset shadows, Pressed = inset shadows (never both) |

---

## Quick Reference: Shadow Formulas

Given base color `H S L%`:

| Element state | Dark shadow | Light shadow |
|---|---|---|
| Raised | `hsl(H, S, L-15%)` at bottom-right | `#fff` or `hsl(H, S, L+10%)` at top-left |
| Pressed (inset) | `hsl(H, S, L-15%)` inset top-left | `#fff` inset bottom-right |
| Flat (no effect) | Remove both shadows | — |

---

## Reference: Alexander Plyuto's Original Palette

His viral banking app used approximately:
- Background: `#e0e5ec`
- Dark shadow: `#a3b1c6`  
- Light shadow: `#ffffff`
- Accent blue: `#4d7cfe`
- Text primary: `#2d3748`
- Text secondary: `#8896ab`

---

## Read More (Advanced)

See `references/css-advanced.md` for:
- Gradient overlays to add color tints to neumorphic surfaces
- Convex vs concave surface variants
- Neumorphism with CSS variables for theming
- SVG filter alternative technique for older browser support
