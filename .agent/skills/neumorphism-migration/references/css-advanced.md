# Advanced Neumorphism CSS Techniques

## Convex vs Concave Surfaces

### Convex (raised/protruding) — default raised state
```css
/* Element protrudes FROM the surface */
.convex {
  background: linear-gradient(145deg, #f0f5fc, #d4dae4);
  box-shadow:
    6px 6px 12px #b8bec7,
    -6px -6px 12px #ffffff;
}
```

### Concave (sunken/carved-in) — input fields, pressed state
```css
/* Element is carved INTO the surface */
.concave {
  background: linear-gradient(145deg, #d4dae4, #f0f5fc); /* reversed gradient */
  box-shadow:
    inset 6px 6px 12px #b8bec7,
    inset -6px -6px 12px #ffffff;
}
```

### Flat (no depth) — disabled/neutral state
```css
.flat-neu {
  background: #e0e5ec;
  box-shadow: none;
}
```

---

## Gradient Tint for Colored Elements

To add color while keeping the neumorphic feel:

```css
.colored-card {
  background: linear-gradient(135deg, #e0e5ec 0%, #dde4f0 100%);
  box-shadow:
    6px 6px 12px #b8bec7,
    -6px -6px 12px #ffffff;
}

/* Active/selected state with color accent */
.card--active {
  background: linear-gradient(135deg, #e8eeff 0%, #dde4f0 100%);
  box-shadow:
    6px 6px 12px #b8bec7,
    -6px -6px 12px #ffffff,
    inset 0 0 0 2px rgba(77, 124, 254, 0.3); /* subtle accent border */
}
```

---

## Progress Bars

```css
.progress-track {
  background: #e0e5ec;
  border-radius: 50px;
  height: 10px;
  box-shadow:
    inset 3px 3px 6px #b8bec7,
    inset -3px -3px 6px #ffffff;
}

.progress-fill {
  height: 100%;
  border-radius: 50px;
  background: linear-gradient(90deg, #4d7cfe, #6e9bff);
  box-shadow: 0 2px 6px rgba(77, 124, 254, 0.5);
}
```

---

## Navigation / Tab Bar

```css
.nav-bar {
  background: #e0e5ec;
  border-radius: 20px;
  padding: 8px;
  display: flex;
  gap: 4px;
  box-shadow:
    6px 6px 12px #b8bec7,
    -6px -6px 12px #ffffff;
}

.nav-item {
  border-radius: 14px;
  padding: 10px 20px;
  background: transparent;
  transition: all 0.2s ease;
}

.nav-item--active {
  background: #e0e5ec;
  box-shadow:
    inset 3px 3px 6px #b8bec7,
    inset -3px -3px 6px #ffffff;
}
```

---

## CSS Custom Property System for Theming

```css
:root {
  /* Base color — change this ONE variable to re-theme */
  --neu-base-hue: 220;
  --neu-base-sat: 20%;
  --neu-base-light: 88%;

  /* Derived values (no need to change) */
  --neu-bg: hsl(var(--neu-base-hue), var(--neu-base-sat), var(--neu-base-light));
  --neu-dark: hsl(var(--neu-base-hue), calc(var(--neu-base-sat) + 5%), calc(var(--neu-base-light) - 15%));
  --neu-light: hsl(var(--neu-base-hue), 0%, 100%);
  
  --neu-shadow-sm:
    4px 4px 8px var(--neu-dark),
    -4px -4px 8px var(--neu-light);
  --neu-shadow-md:
    6px 6px 12px var(--neu-dark),
    -6px -6px 12px var(--neu-light);
  --neu-shadow-lg:
    10px 10px 20px var(--neu-dark),
    -10px -10px 20px var(--neu-light);
  
  --neu-inset-sm:
    inset 4px 4px 8px var(--neu-dark),
    inset -4px -4px 8px var(--neu-light);
  --neu-inset-md:
    inset 6px 6px 12px var(--neu-dark),
    inset -6px -6px 12px var(--neu-light);

  /* Typography */
  --neu-text-primary: hsl(var(--neu-base-hue), 30%, 20%);
  --neu-text-secondary: hsl(var(--neu-base-hue), 15%, 55%);
  --neu-accent: #4d7cfe;
  
  /* Radius scale */
  --neu-radius-sm: 8px;
  --neu-radius-md: 16px;
  --neu-radius-lg: 24px;
  --neu-radius-xl: 32px;
}

/* Usage */
.card  { box-shadow: var(--neu-shadow-md); border-radius: var(--neu-radius-lg); }
.input { box-shadow: var(--neu-inset-sm); border-radius: var(--neu-radius-sm); }
.btn   { box-shadow: var(--neu-shadow-sm); border-radius: var(--neu-radius-sm); }
```

---

## SVG Filter Alternative (broader browser support)

For browsers with limited `box-shadow` on SVG elements:

```html
<svg style="display:none">
  <defs>
    <filter id="neu-raise">
      <feDropShadow dx="5" dy="5" stdDeviation="8" flood-color="#b8bec7" />
      <feDropShadow dx="-5" dy="-5" stdDeviation="8" flood-color="#ffffff" />
    </filter>
  </defs>
</svg>

<div style="filter: url(#neu-raise);">...</div>
```

---

## Animation Patterns

### Smooth press animation (for buttons)
```css
@keyframes neu-press {
  0%   { box-shadow: var(--neu-shadow-md); transform: scale(1); }
  50%  { box-shadow: var(--neu-inset-sm); transform: scale(0.97); }
  100% { box-shadow: var(--neu-inset-sm); transform: scale(0.97); }
}

.btn:active {
  animation: neu-press 0.1s ease forwards;
}
```

### Hover "lift" effect
```css
.card {
  transition: box-shadow 0.3s ease, transform 0.3s ease;
}
.card:hover {
  box-shadow: var(--neu-shadow-lg);
  transform: translateY(-2px);
}
```

---

## Checklist Before Delivery

- [ ] All backgrounds use the same base color (`--neu-bg`)
- [ ] All raised elements have dual box-shadow (dark + light)
- [ ] All inputs/pressed states use inset shadows
- [ ] Border-radius is consistently soft (≥8px)
- [ ] Text contrast passes WCAG AA (use browser DevTools or https://webaim.org/resources/contrastchecker/)
- [ ] `:hover` states show visual change
- [ ] `:active` states switch from raised to inset
- [ ] `:focus-visible` has a visible outline (accessibility)
- [ ] No hard `border` declarations on neumorphic surfaces
- [ ] Dark mode variant provided (or noted as optional)
