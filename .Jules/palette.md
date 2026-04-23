## 2024-05-24 - Form Inputs Accessibility
**Learning:** Form inputs in the extension (popup and dashboard) lacked `for` attributes on labels connecting them to inputs, reducing screen reader support. Link icons like "↗" were also read aloud without `aria-hidden`.
**Action:** Ensure all `<label>` tags use a `for` attribute that matches the `id` of the `<input>`, and wrap decorative text icons in `<span aria-hidden="true">` while providing an `aria-label` for context.
