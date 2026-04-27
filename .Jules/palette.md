## 2024-05-24 - Form Inputs Accessibility
**Learning:** Form inputs in the extension (popup and dashboard) lacked `for` attributes on labels connecting them to inputs, reducing screen reader support. Link icons like "↗" were also read aloud without `aria-hidden`.
**Action:** Ensure all `<label>` tags use a `for` attribute that matches the `id` of the `<input>`, and wrap decorative text icons in `<span aria-hidden="true">` while providing an `aria-label` for context.
## 2026-04-27 - Added ARIA labels to scale comparison buttons
**Learning:** In the `includes/public/class-scale-comparison.php` file, icon-only buttons like the toggle comparison bar and close modal buttons were missing `aria-label` attributes. This is a common pattern to look out for in dynamic UI components.
**Action:** When adding new icon-only buttons to UI components, always ensure an `aria-label` is included to provide context for screen readers. Check both server-rendered PHP templates and dynamic JS components for this pattern.
