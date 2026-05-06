## 2024-05-24 - Form Accessibility Improvements
**Learning:** Found multiple instances where interactive elements like inputs (min/max range, download toggle, row search term) and icon-only buttons (list/grid view toggles, refine with AI buttons, add row/date buttons) lacked `aria-label` attributes. Without these, screen readers struggle to convey the purpose of these UI components.
**Action:** Always ensure that form inputs have either a connected `<label>` via `for` attribute or a descriptive `aria-label`. Similarly, verify that icon-only buttons include `aria-label` to provide context for assistive technologies.

## 2024-05-24 - Form Inputs Accessibility
**Learning:** Form inputs in the extension (popup and dashboard) lacked `for` attributes on labels connecting them to inputs, reducing screen reader support. Link icons like "↗" were also read aloud without `aria-hidden`.
**Action:** Ensure all `<label>` tags use a `for` attribute that matches the `id` of the `<input>`, and wrap decorative text icons in `<span aria-hidden="true">` while providing an `aria-label` for context.
