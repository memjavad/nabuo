# Naboo Database - Development Guide (GEMINI.md)

This document provides foundational mandates and architectural guidance for the Naboo Database plugin. It takes precedence over general workflows.

## 1. Core Mandates

### Versioning
- **CRITICAL:** The plugin version MUST be incremented after every code modification. 
- Update the version in:
  1. `naboodatabase.php` (Plugin header and `NABOODATABASE_VERSION` constant).
  2. `README.txt` (`Stable tag` and `Changelog`).
- Use Semantic Versioning (e.g., `1.0.0` -> `1.0.1`).

### Coding Standards
- **Namespace:** `ArabPsychology\NabooDatabase`.
- **File Naming:** Follow WordPress Coding Standards for file names (`class-*.php`), even within namespaced directories.
- **Autoloader:** The autoloader in `naboodatabase.php` maps the namespace to the `includes/` directory. Ensure new classes follow the sub-directory structure (e.g., `ArabPsychology\NabooDatabase\Admin\My_Class` -> `includes/admin/class-my-class.php`).

## 2. Architecture & Components

### Core Lifecycle
- **`naboodatabase.php`:** Entry point. Initializes the autoloader and runs the `Core` class.
- **`Core` Class:** Orchestrates dependencies and registers hooks via the `Loader`.
- **`Loader` Class:** Central registry for actions, filters, and shortcodes.

### Data Model (CPT & Taxonomies)
- **CPT:** `psych_scale`.
- **Taxonomies:** `scale_category` (hierarchical), `scale_author` (non-hierarchical).
- **Meta Keys:**
  - `_naboo_scale_items`: Number of items.
  - `_naboo_scale_reliability`: Reliability coefficient.
  - `_naboo_scale_validity`: Validity coefficient.
  - `_naboo_scale_year`: Publication year.
  - `_naboo_scale_language`: Language of the scale.
  - `_naboo_scale_population`: Target population.
  - `_naboo_scale_file`: Attachment ID for the scale file.
  - `_naboo_view_count`: Internal view counter.

### Frontend Features
- **Shortcodes:**
  - `[naboo_search]`: Renders the search interface and results.
  - `[naboo_submit]`: Renders the frontend submission form.
  - `[naboo_dashboard]`: Renders the user dashboard for managing submissions.

## 3. Development Workflow

1. **Research:** Use `grep_search` to find existing hook implementations before adding new ones.
2. **Implementation:**
   - Add logic to the appropriate class in `includes/admin/`, `includes/public/`, or `includes/core/`.
   - Register the hook in `includes/class-core.php`.
3. **Verification:**
   - Verify that the CPT and taxonomies are correctly registered.
   - Test shortcode output and form submissions.
   - Ensure SEO tags (Schema.org and OpenGraph) are present on single scale pages.
4. **Finalize:** Increment the version number in all required files.

## 4. SEO & Metadata
- The plugin automatically injects Schema.org `Dataset` JSON-LD and OpenGraph tags into the `<head>` of `psych_scale` singular pages. Ensure any new metadata is also reflected in these SEO hooks.
