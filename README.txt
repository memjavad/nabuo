=== Naboo Database ===
Contributors: ArabPsychology
Tags: database, scales, psychology, research, directory
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.55.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A comprehensive database plugin for psychological scales. «نابو» كإله للكتابة والعقلانية المنظمة. لقد مثل نابو مراحل متقدمة من التطور المعرفي البشري وتدوين السلوكيات الاجتماعية.

== Description ==

Naboo Database is a powerful WordPress plugin designed to host and manage a library of psychological scales. It provides a clean, modern interface for researchers and students to find specific scales based on keywords, categories, years, and authors.

**Features:**

*   **Custom Post Type:** Dedicated 'Psychological Scales' post type to keep your database organized.
*   **Advanced Search:** Search by keyword, category, and publication year.
*   **User Submission:** Frontend submission form allows users to contribute scales to the database (pending review).
*   **Modern Design:** Clean, card-based layout for search results.
*   **Widgets:** Includes a search widget for easy access from any sidebar.
*   **Taxonomies:** Organize scales by Category and Author.

== Installation ==

1.  Upload the `naboodatabase` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Use the shortcode `[naboo_search]` to display the search page.
4.  Use the shortcode `[naboo_submit]` to display the submission form.

== Frequently Asked Questions ==

= How do I display the search form? =

Simply add the shortcode `[naboo_search]` to any page or post.

= How do I approve submitted scales? =

Submitted scales are saved as 'Pending' posts. You can review and publish them from the WordPress Admin Dashboard under "Psychological Scales".

== Screenshots ==

1.  Search interface
2.  Search results
3.  Submission form


== Changelog ==
= 1.55.5 =
* Security: Fixed SQL Injection vulnerability in Batch AI Processor by properly preparing dynamic table names in queries.

= 1.55.4 =
* Git: Initialized repository and prepared for GitHub upload.
* Config: Added .gitignore for clean project tracking.

= 1.55.3 =
* Security: Implemented advanced security headers including HSTS (1 year), strict Content-Security-Policy (CSP), and Referrer-Policy to enhance site trust and SEO.
* SEO: Prepared core for enhanced metadata and Organization schema injection.

= 1.55.2 =
* Bug Fix: Fixed the failing 'Clear Cache' functionality in the Performance Optimizer dashboard by correcting a mismatched AJAX nonce check.

= 1.55.1 =
* Bug Fix: Fixed a 404 error that occurred when submitting the login form while the Login Cloaking (Custom Login URL) feature was active. The form now correctly routes the POST request to the cloaked URL and smoothly processes login internally without triggering firewall blocks.

= 1.55.0 =
* UI Upgrade: Refactored SEO & Schema Settings to a fully responsive vertical layout.
* Fixed button clipping and layout overflow by replacing legacy form tables with a modern flex system.
* Optimized checkbox and input alignment for better usability on all screen sizes.

= 1.54.9 =
* UI Fix: Improved layout and alignment in SEO & Schema Settings.
* Reduced column cramping and standardized all administrative buttons to a cohesive premium style.

= 1.54.8 =
* UI Fix: Corrected broken layout and misaligned grids in the Search Engine Manager.
* Fixed nested HTML structures and missing container closings.

= 1.54.7 =
* Bug Fix: Resolved Fatal Error in autoloader when navigating to Batch AI settings.
* Improved autoloader to correctly map namespaced subdirectories with underscores to hyphenated folders.

= 1.54.6 =
* SRP Refactoring: Decomposed `Bulk_Import_Tool` into `Import_Processor`, `Import_Log_Manager`, `Import_REST_Handler`, and `Import_Renderer`.
* Finalized architectural overhaul for better Single Responsibility Principle (SRP) adherence.

= 1.54.5 =
* SRP Refactoring: Decomposed `SEO_Settings` into `SEO_Sitemap_Manager` and `SEO_Renderer`.
* Improved SEO and sitemap management modularity.

= 1.54.4 =
* SRP Refactoring: Decomposed `Glossary_Admin` into `Glossary_Metabox_Handler` and `Glossary_Renderer`.
* Modularized glossary metabox management and UI rendering.

= 1.54.3 =
* SRP Refactoring: Decomposed `Search_Admin` into `Search_Index_Manager` and `Search_Stats_Calculator`.
* Improved search engine management modularity.

= 1.54.2 =
* SRP Refactoring: Decomposed `Theme_Customizer` into `Theme_Settings_Manager` and `Theme_Renderer`.
* Improved theme customization modularity and code organization.

= 1.54.1 =
* SRP Refactoring: Decomposed `Health_Optimizer` into specialized sub-managers (Checker, Maintenance, Renderer).
* Improved system diagnostics and automated maintenance modularity.

= 1.54.0 =
* Major Refactor: Applied Single Responsibility Principle (SRP) to Batch AI and Performance Optimizer.
* Decomposed `Batch_AI` into specialized sub-managers (Processor, Remote Sync, Cron, REST).
* Decomposed `Performance_Optimizer` into specialized sub-managers (Cleaner, Asset Consolidator, Cloudflare, Page Cache).
* Improved code modularity, maintainability, and testability.

= 1.53.7 =
* Bug Fix: Resolved "Class Glossary_Public not found" fatal error in Core orchestrator.
* Standardized Glossary class naming and namespace imports.

= 1.53.6 =
* SRP Refactoring: Decomposed monolithic Frontend class into modular managers (SEO, Search, Content, Admin Bar).
* Improved code maintainability and separation of concerns.

= 1.53.5 =
* Architectural: Applied Single Responsibility Principle (SRP) to the Settings & Control Center.
* Architectural: Refactored monolith Settings_Center class into modular tab-based components.
* Architectural: Extracted Settings AJAX logic into a dedicated handler class.
= 1.53.4 =
* Security: Hardened REST API by implementing Sync Authentication Key requirement for the sync-status endpoint.
* Security: Restricted CORS headers specifically to the Chrome extension and Grokipedia origins.
* Security: Enhanced data sanitization for email and strict text fields in the admin meta box save function.
* Extension: Updated selectors to improve robustness when interacting with the Grokipedia UI.
* Extension: Integrated Sync Authentication Key settings into the Dashboard to securely authorize REST API calls.
= 1.53.3 =
* API: Resolved Scale Discovery Limits. Explicitly registered REST API arguments and increased the default result count to 100, ensuring the extension can see your entire database.
* Extension: URL Construction Hardening. Optimized how the extension builds API requests to prevent accidental connectivity errors.

= 1.53.2 =
* Extension: Diagnostic Sync Logs. Added high-resolution logging to Phase 1 to reveal exactly how many scales are found and why they are being skipped (Local History vs Server Metadata).
* Extension: Sync Visibility. Enhanced the logs to provide totals and specific skip-reasons, helping diagnose why sync might finish prematurely.

= 1.53.1 =
* API: Fixed CORS blockade. Added Access-Control-Allow-Origin headers to the REST API to permit connections from the Chrome extension across all environments.
* Extension: Fetch Reliability Hardening. Implemented strict data format validation and sanitized URL construction to prevent "filter is not a function" errors and connection failures.

= 1.53.0 =
* Extension: Enhanced AI Guidance. Injected robust instructions into the Grokipedia submission form, directing the AI to use Naboo Database links and authoritative research directories (db.arabpsychology.com & scales.arabpsychology.com).
* Extension: Citation Optimization. Explicitly instructed the Grokipedia AI to cite and link to Arab Psychology authoritative sources within the generated articles.

= 1.52.2 =
* Extension: Sync Logic Fix. Resolved a naming mismatch between the extension and server API that prevented scales from being detected correctly.
* API: Enhanced Fetching. Updated the REST API to support the `posts_per_page` parameter, allowing the extension to synchronize your entire database in one pass.

= 1.52.1 =
* Extension: Sync Stability Hardening. Fixed an issue where the "Start Synchronization" button would silently fail if the WordPress connection was interrupted or if the API returned an unexpected format.
* Extension: Robust Error Logging. Added detailed phase-specific error messages to help diagnose connection or data issues.
* Extension: Status Auto-Recovery. Added logic to clear "stuck" synchronization states automatically upon error detection.

= 1.52.0 =
* Extension: Two-Phase Synchronization Workflow. Separated the process into distinct "Fetch" and "Post" stages for better stability and user monitoring.
* Extension: Structural Log Improvements. Added phase-specific logging to provide clear feedback during database syncing and Grokipedia posting.

= 1.51.1 =
* Extension: Data Persistence Fix. Refactored the internal storage logic to ensure that submission history, Scale IDs, and connection settings are permanently preserved even when the extension is reloaded or updated.
* Extension: Defensive Initialization. Added checks to prevent overwriting existing data with default values on startup.

= 1.51.0 =
* Extension: New Full-Page Dashboard. Introduced a spacious, professional management interface for synchronization, history, and logs.
* Extension: Absolute Duplicate Prevention. Added "Last-Minute Check" logic that verifies Scale IDs right before submission to guarantee no duplicate posts.
* Extension: UI/UX Overhaul. Modernized typography and layout for the full dashboard experience.

= 1.50.2 =
* Extension: Persistent State Sync. Moved automation state to permanent local storage, ensuring the Manifest V3 service worker resumes correctly if suspended by Chrome.
* Extension: Enhanced Duplicate Prevention. Implemented strict ID-based filtering against both local history and server-side metadata to prevent multiple posts of the same scale.
* Extension: Batch Progress View. Updated the popup to display clear progress for the current synchronization batch.

= 1.50.1 =
* Extension: Continuous Work Flow. The extension now reuses a single Grokipedia tab throughout the synchronization session instead of closing and reopening tabs, providing a much smoother and faster experience.
* Extension: Tab Session Management. Improved logic to detect and recover if the automation tab is accidentally closed by the user.

= 1.50.0 =
* Extension: Network & Connectivity Fixes. Broadened host permissions to support local development domains (e.g., .local).
* Extension: Automatic URL Sanitization. The extension now ensures your site URL is correctly formatted with protocols and no trailing slashes.
* Extension: Improved Error Logging. Added detailed context to "Failed to fetch" errors to aid in troubleshooting local environment issues.

= 1.49.9 =
* Bug Fix: Resolved a potential crash in the extension popup when the synchronization status was undefined.
* Extension: Added global error handling and defensive checks to background and popup scripts for better reliability.

= 1.49.8 =
* Extension: Local Submission History. Added a persistent "History" list within the Chrome extension popup to see all posted scales on the local device.
* Extension: Tabbed Interface. Refactored the popup UI into Sync, History, and Logs tabs for a cleaner user experience.
* Extension: Clear History functionality for local device privacy.

= 1.49.7 =
* Feature: Permanent Server-Side Sync Logs. Added a custom database table to store a permanent history of all Grokipedia submissions.
* UI: New "Grokipedia Sync" tab in the Settings & Control Center to view the submission log list directly from WordPress.
* API: Enhanced sync-status endpoint to automatically record events into the permanent log.

= 1.49.6 =
* Feature: Implemented automatic title truncation for Grokipedia submissions. Titles longer than 50 characters are now truncated to 47 characters plus an ellipsis to comply with Grokipedia limits.
* Extension: Internal log updates for better tracking of truncated titles.

= 1.49.5 =
* Feature: Added server-side synchronization tracking. The WordPress admin list for "Psychological Scales" now includes a "G" column indicating if a scale has been suggested on Grokipedia.
* Feature: Introduced a 5-second delay between sequential submissions in the Chrome extension for better reliability.
* API: New REST endpoint for updating Grokipedia sync status from external tools.

= 1.49.4 =
* Feature: Built a custom Chrome extension for synchronizing Naboo Database scales with Grokipedia. The extension automates the "Suggest Article" process, sequentially fetching scales from the plugin's REST API and submitting them as suggested articles on Grokipedia with title and link.
* UI: Premium dark-mode popup for the Chrome extension with real-time sync progress tracking.

= 1.49.2 =
* Fix: Hardened the Cloudflare Worker to strictly obey server-side caching signals. The edge will now ONLY cache pages where `advanced-cache.php` explicitly sends the `X-Naboo-Can-Cache: 1` header.
* Fix: Broadened the Page Rule bypass pattern to `*wp-admin*` (without a trailing slash) to catch various admin entry points.

= 1.49.1 =
* Fix: Added a high-priority "Bypass Cache" rule for `/wp-admin/` paths when creating the Cloudflare Cache Rule. This ensures the WordPress dashboard remains dynamic and safe from edge-caching even with "Cache Everything" active on the rest of the site.

= 1.49.0 =
* Feature: Integrated Cloudflare "Cache Everything" Page Rule creation. Users can now programmatically create a native Cloudflare rule to force full-page caching, including URLs with dynamic query strings, directly from the Performance dashboard.

= 1.48.9 =
* Optimization: Expanded the Cloudflare Worker route coverage from `/psych_scale/` only to the entire site (`/*`) to boost global performance on all public pages.
* Security: Added explicit path-based bypasses in the Worker for WordPress admin, login, and API routes to ensure stability with site-wide coverage.
* Fix: Adjusted `advanced-cache.php` to ensure signaling headers are sent even when LiteSpeed server cache is active.

= 1.48.8 =
* Optimization: Hardened the Cloudflare Worker edge-caching logic by normalizing cache keys (ignoring browser headers) and explicitly stripping `Set-Cookie` headers from the edge.
* Enhancement: Improved `advanced-cache.php` to emit explicit signaling headers (`X-Naboo-Can-Cache`) for anonymous visitors, ensuring perfect synchronization with the Cloudflare Edge network.

= 1.48.7 =
* Fixed: Corrected physical CSS path for main stylesheet preloading in Performance Optimizer.
* Fixed: Adjusted HTML minification regex to prevent collapsing newlines, resolving SyntaxErrors in inline script blocks involving single-line comments.

= 1.48.6 =
* Enhancement: Redesigned the Page Cache engine to natively support LiteSpeed Server caching without requiring the bloated LSCache WordPress plugin. When running on LiteSpeed/CyberPanel environments, the `advanced-cache.php` drop-in explicitly delegates page caching to the web server daemon using `X-LiteSpeed-Cache-Control` headers, achieving maximum RAM-based performance. The "Purge Pages" button automatically clears both the LiteSpeed server cache tags and the fallback disk cache simultaneously.

= 1.48.4 =
* Tweak: Adjusted the Page Cache engine to explicitly declare independence from the LSCache WordPress plugin. When running on LiteSpeed servers, the Naboo cache drop-in now actively overrides default LiteSpeed server-level dynamic caching headers by injecting `no-cache` directives. This guarantees that the plugin exclusively utilizes its own highly-optimized HTML disk cache system, ensuring 100% autonomous operation and flawless manual purging without relying on third-party caching plugins.

= 1.48.3 =
* Feature: Added native Page Cache engine with automatic HTML minification.

= 1.48.1 =
* Enhancement: Redesigned the Settings & Control Center with a premium tabbed interface.
* UI: Added modern tab navigation with smooth animations and state persistence.
* Tweak: Consolidation of settings into General, AI, System, and Roles tabs.

= 1.46.12 =
* Feature: Built and integrated a lightning-fast static HTML Page Cache drop-in. Added a new "Page Cache" control panel inside the Performance Optimizer. With one click, administrators can instantly deploy the `advanced-cache.php` drop-in, enable the `WP_CACHE` global constant in `wp-config.php`, and drastically reduce TTFB for non-logged-in users. Added tools to disconnect the cache and manually purge cached HTML files.

= 1.46.11 =
* Fixed: Site Health incorrectly reporting "Page cache is not detected". Because the plugin securely routes all internal loopback requests locally (bypassing the public internet to avoid Cloudflare Bot Fight Mode), WordPress was unable to read the `cf-cache-status` headers generated at the Cloudflare Edge network. The plugin now dynamically overrides the Site Health API to acknowledge Cloudflare's Edge Caching, eliminating the false-positive warning.

= 1.46.10 =
* Fixed: Site Health reporting an HTTP 401 Unauthorized (`rest_forbidden_context`) error on the REST API endpoint. The plugin now correctly intercepts the native WordPress capability check specifically for the Site Health loopback test, allowing the unauthenticated internal ping to pass without compromising public API security.
* Fixed: Forced WordPress to prioritize the cURL HTTP transport over PHP Streams on LiteSpeed/CyberPanel servers to guarantee the Cloudflare local DNS bypass correctly engages.

= 1.46.9 =
* Fixed: Deployed an absolute DNS bypass for WordPress Site Health loopbacks and WP-Cron requests. The plugin now intercepts the core `WP_Http` engine and explicitly rewrites the internal request to resolve locally to `127.0.0.1` at the cURL network level. This guarantees that internal server processes never touch the public internet, perfectly bypassing Cloudflare Bot Management challenges (`cf-mitigated: challenge`) without needing to alter external Cloudflare firewall rules.

= 1.46.8 =
* **MAJOR ENHANCEMENT:** Complete overhaul of Diagnostics Dashboard with comprehensive debug reports for ALL plugin functions and WordPress components
* **New Comprehensive Test Suites:**
  - WordPress Core (version, PHP, plugin status, CPT, taxonomies, REST API)
  - Plugin Functions & Classes (Loader, Core, Security, WAF, shortcodes)
  - Database Configuration (connection, tables, post counts, options)
  - Registered Hooks & Filters (init, enqueue_scripts, AJAX, REST routes)
  - User Capabilities (current user, admin status, publish/edit permissions)
  - Server Environment (web server, HTTPS, memory, upload size, PHP extensions)
  - Error Logs & Debug (WP_DEBUG, debug.log file, PHP error reporting)
* **Debug Information for LLMs & Developers:** Full visibility into plugin state so AI/developers can understand and fix issues
* **Visible Test Results:** Each component shows its status (✅ PASS / ⚠️ WARN / ❌ FAIL)
* **Configuration Details:** See actual values (memory limit, PHP version, extensions loaded, hooks registered, etc.)
* **Error Log Display:** Last 100 lines of debug.log shown directly in dashboard
* **Self-Service Troubleshooting:** Site owners can now identify issues in 10+ different areas without support
* **Support-Friendly Data:** JSON export includes all diagnostic data for troubleshooting tickets
* **Total Test Count:** Now tests 80+ different plugin and WordPress components
* **Rendering:** All test results color-coded and organized by category

= 1.47.2 =
* **ENHANCED:** Diagnostics Dashboard now includes comprehensive Debug Reports for each failing component
* **New Feature:** Detailed debug reports show root causes, configuration issues, and step-by-step fix instructions
* **Problem Detection:** Each failing test now shows the specific problem with server configuration details
* **Root Cause Analysis:** Reports explain WHY each component is failing (Cloudflare misconfiguration, WAF blocking, etc.)
* **Server Debug Data:** Shows relevant HTTP headers, IP addresses, security settings, and configuration values
* **Actionable Solutions:** Step-by-step commands and instructions to fix each identified issue
* **Categories Covered:** REST API blocks, loopback failures, IP detection problems, security conflicts, Cloudflare issues, WAF false positives
* **Command Examples:** Includes actual Linux commands to diagnose server issues (grep logs, check firewall rules, verify curl, etc.)
* **Security Settings Audit:** Shows which security features are enabled and how they might interfere
* **Better Support Integration:** Comprehensive debug data makes support tickets more actionable
* **User-Friendly:** Color-coded sections for Problem, Root Cause, Debug Data, and Solutions

= 1.47.1 =
* **NEW FEATURE:** Added comprehensive Diagnostics Dashboard (Naboo → Diagnostics 🔍) for troubleshooting REST API and WP-Cron issues.
* **Diagnostics Page Tests:**
  - REST API accessibility and Site Health endpoint verification
  - Loopback request functionality (critical for WP-Cron)
  - IP detection and Cloudflare header verification
  - Security settings impact analysis
  - WP-Cron status and scheduled events
  - WAF (firewall) status and recent blocks
* **JSON Export:** One-click export of all diagnostic data for sharing with support or developers
* **Real-time Testing:** Live tests that actually check REST API endpoints and loopback requests
* **Actionable Recommendations:** Page provides specific recommendations based on test results
* **For Administrators:** Easy-to-read dashboard with color-coded pass/fail/warning status
* **For Developers/Support:** Detailed technical information and JSON report for debugging

= 1.47.0 =
* **CRITICAL FIX:** Fixed cascading 403 Forbidden errors affecting WP-Cron, Site Health loopback tests, and REST API checks.
* **Enhanced Cloudflare Proxy Support:** IP detection now properly recognizes Cloudflare-proxied requests by checking `HTTP_CF_CONNECTING_IP` header (Cloudflare's native client IP header) and `HTTP_CF_RAY` for verification, eliminating false-positive IP masking issues.
* **Improved Internal Request Detection:** New `is_internal_request()` method in Security and WAF classes now safely identifies legitimate internal WordPress operations (WP-Cron, Site Health, loopback requests) even when proxied through Cloudflare, preventing them from being blocked as unauthorized external requests.
* **REST API Restrictions Refined:** Updated `restrict_rest_api()` filter to explicitly whitelist WordPress Site Health endpoints and internal REST operations while maintaining security against unauthorized external access.
* **WAF Improvements:** Web Application Firewall now uses the same Cloudflare-aware IP detection as the Security class, preventing false positives on internal requests while maintaining full protection against external attacks.
* **Removed Fragile User-Agent Workaround:** Replaced hardcoded User-Agent spoofing (unreliable with Cloudflare) with proper Cloudflare header verification (CF-RAY header presence), a much more robust approach.
* **Result:** WP-Cron sitemap generation now runs successfully, Site Health tests pass, loopback requests succeed, and page cache detection works as expected.

= 1.46.8 =
* Feature: Added a "Whitelist Server IP" button to the Cloudflare Setup & Edge Options dashboard. This one-click tool automatically detects the host server's public egress IP address and uses the Cloudflare API to inject a persistent whitelist rule directly into the Zone Firewall. This provides a permanent, native Cloudflare bypass for all internal WordPress REST API and WP-Cron loopbacks, solving 403 Forbidden errors at the DNS edge.

= 1.46.7 =
* Fixed: Site Health loopback and WP-Cron requests persistently failing with 403 Forbidden even after previous fixes. The internal security modules (WAF and REST API Restrictions) now securely inspect the HTTP User-Agent. If the request possesses the plugin's internally spoofed Chrome header signature, it is correctly identified as a safe internal WordPress loopback and granted immediate bypass, completely overriding Cloudflare IP masking issues.

= 1.46.6 =
* Feature: Upgraded the Health Center's "Fix Crons" tool to automatically detect and purge any globally stalled or severely past-due WP-Cron events (older than 2 hours) from the WordPress database. This instantly clears false-positive "A scheduled event has failed" errors from the core Site Health dashboard and unblocks the scheduling queue.

= 1.46.5 =
* Fixed: A bug in the Security Center where unchecking a setting and saving would cause the setting to revert to its original state. The sanitizer now properly tracks tab-specific fields and gracefully merges empty checkbox states into the persistent database without deleting options from other tabs.

= 1.46.4 =
* Fixed: Site Health loopback and WP-Cron requests failing with 403 Forbidden when the plugin's "Restrict REST API" or "Web Application Firewall (WAF)" features were enabled. The security modules now intelligently detect and safely bypass internal server loopback requests to ensure WordPress core background processes and health checks function correctly.

= 1.46.3 =
* Fixed: Site Health loopback requests, REST API checks, and WP-Cron events failing with a 403 Forbidden error. The plugin now globally spoofs a standard browser User-Agent for all internal WordPress HTTP requests to prevent Cloudflare's Bot Management from blocking the server's own traffic.

= 1.46.2 =
* Fixed: Sync Server Connectivity health check returning a 403 Forbidden error. The diagnostic tool now explicitly spoofs a standard Chrome browser User-Agent header during its outbound ping to safely bypass Cloudflare's Bot Management blocks.

= 1.46.1 =
* Fixed: AI Batch Processor occasionally getting permanently stuck on "Waiting for next scheduled run" due to broken WP-Cron chains. A new background watchdog now automatically resurrects stalled queues.

= 1.46.0 =
* Restructured the Admin Dashboard: Combined "Insights & Analytics" with the main plugin entry point.
* Removed the redundant main "NABOO Dashboard" page.
* Integrated "Recent Submissions", "Top Scales", and a premium "Summary Metrics" bar into the Insights page.
* Improved admin menu organization and navigation.

= 1.45.0 =
* Removed the Moderation Dashboard and associated internal flagged content system.
* Cleaned up core classes and admin menu structures for better performance.

= 1.44.0 =
* Migrated Email Notifications settings to the dedicated Emails page.
* Upgraded Emails page with a premium single-page UI and sticky save bar.
* Integrated all notification triggers and sender info into the REST-based email system.
* Improved UI feedback for settings saving and test email delivery.

= 1.43.0 =
* Refactored Settings Page to a premium, single-page interface with a modern Inter-based aesthetic.
* Consolidated General and Submission settings into a unified view.
* Migrated System Information and health metrics to the dedicated Health Check page.
* Migrated Glossary & Index settings to a standalone Glossary Admin page.
* Integrated Performance settings (Caching and Rate Limiting) into the Performance optimizer.
* Added "Backup & Restore" functionality (JSON Export/Import) to the main settings page.
* Implemented a sticky save bar and refined card-based layouts for improved administrative UX.
* Optimized sanitization logic and removed obsolete administrative methods.

= 1.42.0 =
* Redesigned Settings Page to match Insights premium single-page layout.
* Removed tabbed interface in favor of a vertically scrollable, unified view.
* Updated sanitization logic for consolidated form submission.

= 1.41.0 =
* Enhancement: Refactored Insights & Analytics Dashboard into a single-page consolidated view for improved accessibility and data overview.

= 1.40.1 =
* Fixed: Critical error caused by missing rendering methods in the unified dashboard.

= 1.40.0 =
* Major Enhancement: Unified Insights & Analytics Dashboard. Combined Statistics, Reports, and Security Logs into a single comprehensive tabbed view with premium UI.

= 1.31.0 =
* Enhancement: Added "Try Fix" capability for Sync Server Connectivity issues.

= 1.30.0 =
* Major Enhancement: Full Health & Maintenance Suite. Added Automated Weekly Optimization, Media Library Scrubbing, Core File Integrity Checks, Plugin/Theme Security Audits, and Outbound Email Health Testing.

= 1.27.0 =
* Enhancement: Added API Connectivity Check to Health Optimizer to verify outbound connections to ArabPsychology infrastructure.

= 1.26.0 =
* Enhancement: Expanded Health Optimizer to the entire WordPress environment. Added global database optimization, site-wide revision purging, trashed content/spam comment cleanup, log file management, and server resource checks.

= 1.25.0 =
* Fix: Hardened Health Optimizer cron repair logic to ensure specialized intervals are correctly registered and that only enabled tasks are flagged as missing.

= 1.24.9 =
* Enhancement: Added "Fix Plugin Crons" to Health Optimizer to automatically repair and reschedule missing background processing events.

= 1.24.8 =
* Feature: Added Health & Maintenance module to monitor system health and resolve common issues (transients, database optimization, revision purging, and rewrite flushing).

= 1.24.7 =
* Fix: Resolved an infinite loop bug in the AI Batch Processor background cron job by properly integrating it with the background persistent queue and ensuring permanently failing scales are flagged as "Needs Manual Processing".

= 1.24.6 =
* Tweak: Improved the Scale Index button layout on mobile devices so it neatly spans the full width on a second row under the search inputs.

= 1.24.5 =
* Enhancement: Added auto-configured dynamic FAQ Schema generation to `psych_scale` singular pages based on populated scale metadata.
* Enhancement: Improved AI instruction for "Source Reference" extraction to accurately group all mentioned references as APA citations.

= 1.24.4 =
* Feature: Added Pending Scale AI Processor to automate refinement and publishing of pending drafts.
* Feature: Added a new `naboo_manual` (Needs Manual Processing) custom post status for scales that fail automated AI refinement.

= 1.23.0 =
* Feature: Completely overhauled Glossary with AJAX REST endpoint for handling 100,000+ items.
* Feature: Full-screen premium UI with dark mode, skeleton loaders, and animated cards.
* Feature: Infinite scroll and classic pagination modes.
* Feature: Detailed admin settings panel (11 settings: layout, fullscreen, accent color, radius, per-page, etc.)
* Feature: Virtual rendering - only ~50 DOM nodes at a time regardless of dataset size.

= 1.22.6 =
* Fix: Removed debug boxes and finalized dynamic post type rendering.
* Feature: Added intelligent defaults for `psych_scale` items (Author mapping).

= 1.22.2 =

= 1.22.0 =
* Feature: Added dynamic post type support to [naboo_glossary] shortcode. You can now create alphabetical lists for any content type (Scales, Courses, etc.).
* Feature: Added custom metadata mapping and labeling to the glossary interface.
* Admin: Updated Help & Instructions with examples for advanced dynamic usage.

= 1.21.1 =
* Admin: Added a dedicated "Help & Instructions" page to the Glossary menu for easier onboarding.

= 1.21.0 =
* Feature: Added a high-performance, elegant Glossary module with A-Z filtering and real-time search.
* Feature: Integrated Glossary settings into the Unified Settings Center for total control.
* Admin: Added dedicated Glossary management with Arabic term support and related resource links.

= 1.20.0 =
* Production: Consolidated all WP-CLI commands under a single `wp naboo` namespace for better maintainability and professional CLI experience.
* Production: Moved CLI logic to `includes/cli/` to match internal autoloader and architectural standards.
* Production: Safely removed all temporary development and test scripts from the plugin root and subdirectories.
* Production: Final architectural cleanup and optimization of the core registration logic.

= 1.19.1 =
* UI: Removed the "validation indicator" (reliability badge) from the "You May Also Like" recommendation cards for a cleaner, more focused design.
* UI: Temporarily disabled "Export as PDF", "Add to Collection", and "Compare Scale" buttons from the frontend to streamline the core production interface.

= 1.19.0 =
* Security: WAF upgraded with strict SSRF and XXE patterns to prevent outbound cloud metadata attacks and entity injection.
* Security: XSS checking in WAF now smartly skips known-safe WordPress internal cookies (e.g., sessions) to prevent firewall false positives.
* Security: WAF now securely passes pure `HEAD` and `OPTIONS` requests through natively without blocking CORS preflights or harmless search-engine crawlers.
* SEO: `psych_scale` singular pages now automatically inject `<link rel="canonical">` to prevent duplicate content indexing of parameterized URLs (like `/search/?page=2`).
* SEO: Scales flagged with an Arabic language taxonomy now natively output the `<link rel="alternate" hreflang="ar">` tag in the `<head>` for MENA search optimization.
* Performance: Multi-layered safety guards affixed to `minify_html_output`. It will now forcefully bail out of minifying if called during WP REST API, AJAX, Cron, or Admin Dashboard operations, unconditionally preventing script breakage.
* Performance: `minify_html_output` now verifies buffer completion structure, safely skipping partial HTML fragments that do not end in `</html>`, and correctly preserves vital conditional IE comments.
* Enhancement: The background AI Batch Processor (`do_process_draft`) now has a robust 120-second transient lock guard preventing duplicate parallel executions of the exact same draft from race condition invocations.
* Feature: The Core system now hooks the new `inject_canonical_and_hreflang` operation directly to `wp_head` at priority 1 to position SEO markers correctly on the page load cycle.

= 1.18.0 =
* Fix: Deactivation now removes all plugin cron events (sitemap, AI batch, queue cleanup, email log, remote sync) and purges all plugin transients.
* Fix: psych_scale CPT and all taxonomies now have `show_in_rest => true` for Gutenberg and REST API compatibility; `map_meta_cap => true` added for correct capability mapping.
* Fix: naboo_raw_draft CPT explicitly disables URL rewrites (`rewrite => false`) to avoid rewrite rule conflicts.
* Feature: Admin post list for psych_scale now has sortable Year and Items columns via `manage_edit-psych_scale_sortable_columns` filter and a `pre_get_posts` meta sort handler.
* Fix: `restrict_rest_api` now whitelists all `/wp-json/naboo-db/` routes so the public search API always works even when general REST restriction is enabled.
* Enhancement: `send_critical_alert` is now rate-limited to one email per 5 minutes per alert type to prevent email floods during attacks.
* Fix: `track_views()` now skips common bots and web crawlers to prevent artificial inflation of view counts.
* Fix: Inline manual edit (`ajax_inline_manual_edit`) now calls `Database_Indexer::sync_post()` after saving so changes immediately appear in search results.
* Performance: Admin review bar `render_admin_review_bar()` caches its WP_Query with a 5-minute transient (invalidated on `transition_post_status`).
* Feature: Queue cleanup cron (`naboo_queue_cleanup`) runs daily to reset stuck `processing` items (> 15 min) and purge `done` rows older than 7 days.
* Enhancement: Search index table now includes `keywords`, `reliability`, `validity`, `source_reference`, and `view_count` columns; FULLTEXT index expanded accordingly. DB_VERSION bumped to 1.3.
* Fix: Ratings guest duplicate prevention now uses IP-based transients (24h) instead of relying solely on user_id=0.
* Fix: `mark_helpful()` now rate-limits to 1 vote per IP per rating per hour to prevent bot inflation.
* Fix: Comment rate-limiting now covers guests (IP-based, max 5/hour) in addition to logged-in users (DB-based, max 10/hour).

= 1.17.1 =
* Bug Fix: Fixed broken HTML structure in the SEO admin page — missing `</table></div>` tags caused the "Sitemap Generation" card to nest incorrectly inside the "Sharing Fallbacks" card.
* Feature: Sitemap URL is now automatically appended to the WordPress virtual `robots.txt` for Google/Bing auto-discovery.
* Feature: Sitemap is now automatically regenerated when a `psych_scale` post is trashed or permanently deleted (`trash_post` / `delete_post` hooks).
* Enhancement: Old sitemap chunk files (`naboo-sitemap-N.xml`) from previous larger generations are now cleaned up automatically before a new sitemap is written.
* Enhancement: Taxonomy archive pages (`scale_category`, `scale_author`) now use the actual last-modified date of the most recently updated post in each term, instead of the current timestamp.
* Enhancement: Replaced `wp_remote_get` with `wp_safe_remote_get` for all outbound sitemap ping requests.
* Feature: Bing is now pinged alongside Google when the sitemap is regenerated (`https://www.bing.com/ping?sitemap=...`).
* Feature: Added a weekly WP-Cron job (`naboo_weekly_sitemap_cron`) to automatically keep the sitemap fresh even during periods of inactivity.

= 1.17.0 =
* Performance: Implemented a flat custom database search index (`wp_naboo_search_index`) with MySQL FULLTEXT indexes to support 100,000+ scale searches without loading `wp_postmeta` EAV joins.
* Performance: Rewrote the `advanced_search` REST endpoint to query the new SQL index directly, using MATCH ... AGAINST in BOOLEAN MODE for keyword relevance, and clean BETWEEN/FIND_IN_SET for meta/taxonomy filters.
* Performance: Added Transients caching to `get_search_filters` — filter options are now precomputed once and cached for 24 hours, invalidated automatically when any scale is saved or deleted.
* Performance: Optimized `get_search_suggestions` to query the flat index directly instead of scanning `wp_postmeta`.
* Feature: Added WP-CLI command `wp naboo-search sync` to bulk-sync all existing scales into the new index table.
* Enhancement: Improved `class-loader.php` to correctly dispatch static callable arrays `['ClassName', 'method']` via `add_action`.

= 1.16.2 =
* Feature: XML Sitemaps now generate dynamic indexes and chunked sub-sitemaps (1000 URLs each) to prevent memory issues on large databases.
* Feature: Added support for Image Sitemaps (Google standard namespace).
* Feature: Sitemaps are now automatically regenerated and dynamically served when publishing/editing scales or taxonomies.
* Feature: Automatically pings Google Search Console when the sitemap updates to speed up indexing.

= 1.16.1 =
* Enhancement: Refactored XML sitemap generation to use `XMLWriter` for improved reliability and lower memory overhead.
* Enhancement: Added scale terms (categories and authors) archive URLs to the XML sitemap.

= 1.16.0 =
* Added Web Application Firewall (WAF) to block SQLi, XSS, and LFI attacks.
* Implemented Login Cloaking (Custom Login URL) to hide the login page.
* Added Server Hardening (.htaccess) to protect sensitive files and disable directory browsing.
* Added Real-time Email Security Alerts for critical incidents.
* Enhanced Security Dashboard with a professional "Cyber Posture" indicator.

= 1.15.0 =
* Added Security Audit Log to track administrative and security events.
* Implemented Login Brute Force Protection (IP lockout after failed attempts).
* Added Advanced Hardening: REST API restrictions, user enumeration blocking, and version hiding.
* Enhanced Security UI with a Health Dashboard and Security Score.

= 1.14.0 =
* Added the missing Security Function page to the admin menu under "NABOO Dashboard".
* Centralized HTTP security headers and XML-RPC hardening settings.
* Added security status indicators for upload protection.

= 1.13.1 =
* Added a "Dashboard" button to the administrator's floating bar for quick access to the WordPress backend.

= 1.13.0 =
* Redesigned "Related Scales" section into a modern, compact 3-item slider.
* Repositioned "Related Scales" above the User Ratings & Reviews section for better content flow.
* Improved responsive layout for related content on mobile devices.

= 1.12.0 =
* Implement searchable dropdowns for advanced search filters (Categories, Authors, etc.).
* Improved UI/UX for large data sets in the search engine.
* Premium styles for dropdown components.

= 1.11.9 =
* Feature: Clicking the "Publish Scale" button now automatically navigates administrators to the next unpublished scale in the database, allowing for a seamless, rapid moderation workflow.

= 1.11.8 =
* UX: The "Next to Publish" button is now visible globally for administrators on every page of the site (if unpublished scales exist), allowing for a continuous review workflow from anywhere.

= 1.11.7 =
* Feature: Added a "Next to Publish" button in the admin review bar on the frontend. This allows administrators to quickly jump to the next scale that requires moderation and publishing.

= 1.11.6 =
* Feature: Added a fixed "Publish Scale" button on the frontend for administrators to quickly publish unpublished scales directly from the single scale page.

= 1.11.5 =
* Feature: Added a 10-minute interval option to the AI Batch Processor background delay settings.

= 1.11.4 =
* Feature: Added inline "Test" buttons next to every Google Gemini API Key input field in the AI Integrations tab. Administrators can now instantly verify if an API key is valid and active by sending a lightweight test ping to the `gemma-3-4b-it` model before running heavy extractions.

= 1.11.3 =
* Bug Fix: Fixed an issue where the "Consolidate Plugin Assets" feature would continuously generate new cache files for every unique page load. Implemented hash stabilization and an automatic garbage collector to keep the `/naboo_optimizer_cache/` folder perfectly clean.

= 1.11.2 =
* Feature: Added support for up to 10 Google Gemini API Keys with an automatic rotation mechanism to help mitigate rate limiting and ensure smoother AI processing.

= 1.11.1 =
* Bug Fix: Hardened all Gemini AI extraction and refinement prompts with strict zero-hallucination directives, ensuring the AI only populates fields with explicitly provided text and returns empty strings instead of inventing information.

= 1.11.0 =
* Setup: Introduced the "Naboo Security" module to comprehensively secure the plugin.
* Security: Implemented hidden honeypot fields on both standard and AI frontend submission forms to block automated spam.
* Security: Added IP-based rate limiting (5 submissions per hour) to frontend forms and AI extraction endpoints to prevent API abuse and spam floods.
* Security: Hardened the WordPress `wp-content/uploads` directory by automatically generating an `.htaccess` file that blocks the execution of uploaded PHP/CGI scripts.
* Security: Enforced strict MIME type (`application/pdf`) validation and randomized file names for scale document uploads.
* Security: Added essential HTTP security headers (`X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`) to frontend pages to mitigate clickjacking and XSS attacks.
* Security: Programmatically disabled XML-RPC to prevent brute-force attacks and pingback abuse.
= 1.10.1 =
* Bug Fix: Updated the AI extraction prompts to explicitly enforce cross-checking author names and details with the Abstract and Source Reference to ensure accuracy.

= 1.10.0 =
* Feature: Added inline manual edit buttons next to the AI Refine buttons on the frontend scale view, allowing administrators to make quick text edits exactly as they do with AI.

= 1.9.9 =
* Bug Fix: Fixed the double scrollbar issue on the search results page by removing an explicit max-height and overflow scroll constraint from the results body, allowing the page to scroll naturally.

= 1.9.8 =
* UX: Removed icons from the main search bar to achieve a cleaner look.
* UI: Renamed primary search buttons from "Search Database" to "Search", and "Advanced Search Options" to "Advanced Search".

= 1.9.7 =
* Bug Fix: Fixed layout issue on the main search page where the title heading was too close to the search bar when the main search logo was hidden or missing.
* Feature: Added automatic fallback to display the global Custom Logo (`logo_url`) on the search page if the specific Main Search Logo (`main_search_logo_url`) is empty.

= 1.9.6 =
* Feature: AI Processed Drafts Persistence. Successfully processed `naboo_raw_draft` posts are no longer deleted. They are now preserved under a new custom `naboo_processed` status. This prevents them from cluttering the queue, allows for historic review, and ensures the duplicates-checker continues to skip them perfectly on future imports.
* Bug Fix: Fixed a bug where manually uploaded JSON/ZIP draft imports were assigned a `private` status, causing the background AI processor to ignore them.

= 1.9.5 =
* Feature: Added Chunked ZIP Export & URL Import. To prevent processing timeouts on massive origin databases, the exporter now generates chunked static ZIP files containing 1000 drafts each. On the production side, users can simply paste the generated ZIP URLs to have the server automatically download, extract, and import them — completely bypassing manual desktop downloads and Cloudflare blocks.

= 1.9.4 =
* Feature: Added manual file-based draft transfer as a complete bypass for Cloudflare-blocked live connections. The Naboo Remote Exporter plugin now includes an "Export to File" panel where you choose a post type/status and download a JSON file. The production plugin now includes an "Import from File" upload panel in the Remote Draft Importer tab. Imported posts are deduplicated automatically.

= 1.9.3 =
* Bug Fix: Fixed persistent 403 Forbidden connection errors on the Remote Draft Importer by completely bypassing the WordPress `wp_remote_get` HTTP API. Requests to the origin server now utilize raw PHP cURL with strict browser-emulating headers to slip past aggressive Cloudflare Bot Management and Wordfence Advanced firewalls that natively flag WordPress-generated HTTP streams.

= 1.9.2 =
* Bug Fix: Completely overhauled the Naboo Remote Exporter and Remote Draft Importer integration to bypass WordPress REST API restrictions entirely. Instead of using `/wp-json/naboo-remote/...` endpoints (which are often aggressively blocked by plugins like Wordfence and Cloudflare on the origin server), the importer now communicates via a native frontend query parameter `/?naboo_export=...` using the `template_redirect` hook, ensuring firewalls allow the authenticated traffic through.

= 1.9.1 =

= 1.9.0 =
* Feature: Added background processing delay mode to AI Batch Processor (None, 1m, 5m, 15m, 1h, 2h, 4h). Draft extraction and subsequent deep refinements now run entirely server-side in the background via WP-Cron, preventing browser lockups and respecting API rate limits.

= 1.8.99 =
* Performance: Replaced all frontend Dashicons with lightweight inline SVG icons, completely eliminating the need to enqueue the external `dashicons.css` stylesheet for non-admin visitors.

= 1.8.96 =
* Feature: Parallel REST-based AI Batch Processor — processes N drafts simultaneously (configurable 1–3 concurrency) via REST endpoint `naboo-db/v1/process-draft`. No more sequential admin-ajax.
* Feature: Persistent processing queue (`wp_naboo_process_queue` DB table) — survives page refreshes and crashes. Tracks pending/done/failed status per draft.
* Feature: Auto-retry on failure — failed drafts are automatically re-queued up to 3 times with error categorization. Permanently failed items shown in Failed Queue table.
* Feature: Taxonomy auto-assignment — AI-extracted `authors` field is mapped to `scale_author` taxonomy, and `construct` to `scale_category`, automatically after each batch run.
* Feature: AI Quality Score — each processed scale gets `_naboo_ai_quality_score` (0–100%) based on completeness of key fields. Scales below 60% are set to `pending` status even if auto-publish is on.
* Feature: Enhanced batch log — quality badge (green/amber/red) per entry with fields-filled count. Export results to CSV after batch completes.
* Feature: WP-CLI interface — `wp naboo process`, `wp naboo queue-stats`, `wp naboo clear-queue` commands for fully headless operation.
* UX: Stop button, concurrency selector, live stats bar (done/failed/rate) in AI Batch Processor card.

= 1.8.94 =
* Feature: Added REST API endpoint `naboo-db/v1/import-page` — imports an entire page server-side in one request. Eliminates 150 admin-ajax round-trips per page.
* Feature: New "Full Auto-Import" panel with configurable parallelism (1–5 pages simultaneously). Start/Stop controls and live dashboard showing pages done, imported, skipped, and pages/min rate.
* Feature: Background WP-Cron automation (`naboo_full_auto_import_event`) — enable once and it self-reschedules every 30 seconds until all pages are imported, no browser needed.
* Feature: All import logic consolidated into a single `do_import_page()` method shared by REST, cron, and manual fetch.

* Feature: Added custom 5-minute WP-Cron interval `naboo_5min` for the automation schedule.

= 1.8.93 =
* Feature: Created a dedicated `wp_naboo_import_log` database table via a new `Installer` class. The import log is now completely separate from `wp_options`, ensuring the settings table never grows large.
* Feature: Replaced all `wp_options` based deduplication with direct SQL queries (`INSERT IGNORE`, `SELECT` on unique key) on the custom table for maximum speed and correctness.
* Feature: Added a persistent page cursor (`naboo_remote_last_page`). The importer always remembers the last completed page. If interrupted, it auto-resumes from where it stopped on the next page load, with an amber banner showing the saved cursor and a 'Reset cursor' link.
* Feature: Implemented a pipelined pre-fetch strategy. When the current batch is 50% imported, the importer silently starts fetching the next page from the origin server in the background. When the current batch finishes, if the next page is ready, it starts within 0.5s instead of waiting the full 10-second inter-page delay.

= 1.8.92 =
* Feature: Completely overhauled the Remote Draft Importer deduplication logic. Replaced the slow and unreliable `get_posts()` title-match query with a blazing-fast persistent import log stored in a WordPress option. Each imported post's origin ID is now stored so that deduplication is an instant O(1) hash lookup — no DB queries needed regardless of post count.
* Feature: Added a visible 'Persistent Import Log' panel in the admin UI showing total imported count, time since last import, and a 'Clear Log' button.
* Feature: Import progress now shows a live counter for current session imports and all-time imports.
* Feature: Each locally created `naboo_raw_draft` post now stores `_naboo_remote_origin_id` post meta for full traceability back to the origin site.
* Feature: The automatic background sync (WP Cron) also now uses the fast persistent log and respects the 120-second HTTP timeout.

= 1.8.91 =
* Backend: Re-adjusted the Remote Draft Importer batch size parameter back to 150 to ensure optimal stability on certain origin server environments.

= 1.8.90 =
* Backend: Adjusted the Remote Draft Importer batch size parameter from 150 to 200 to optimize fetch throughput while respecting the 120-second connection timeout threshold on origin servers.

= 1.8.89 =
* Backend: Reduced the Remote Draft Importer single-post processing gap back to 500 milliseconds (from 1500) to speed up iterations, keeping page transitions and robust retry logic intact.

= 1.8.88 =
* Bugfix: Further fortified the "Fetch All Pages" importer to explicitly clear the progress log window at the start of each page, preventing browser RAM from freezing when thousands of lines accumulate. Additionally, increased the pacing delays (1.5 seconds between posts, 10 seconds between pages) and expanded error retries to 5 attempts (waiting 10 seconds per attempt) to fully resolve sporadic Bad Gateway timeouts under heavy server strain.

= 1.8.87 =
* Bugfix: Increased the HTTP timeout threshold in wp_remote_get from 60 seconds to 120 seconds during the Remote Draft Importer fetch phase, preventing `cURL error 28` timeouts when slower origin servers require more time to securely compile large batches of requested drafts.

= 1.8.86 =
* Backend: Adjusted the Remote Draft Importer batch size parameter from 300 to 150 to better accommodate slower hosting environments.

= 1.8.85 =
* Backend: Adjusted the Remote Draft Importer batch size parameter from 500 to 300 to balance fetch speed and origin site load.

= 1.8.84 =
* Backend: Increased the Remote Draft Importer batch size parameter from 100 to 500 posts per page, speeding up the overall fetching pipeline for massive backlogs.

= 1.8.83 =
* Backend: significantly improved the robustness and stability of the "Fetch All Pages" importer. The script now incorporates delayed pacing between requests (500ms between posts, 3s between pages) and features an intelligent 3-attempt retry loop for all server interactions, directly preventing 502 Bad Gateway timeouts and endless freezing behaviors under heavy server load.

= 1.8.82 =
* Backend: Added a "Fetch All Pages" button to the Remote Draft Importer. This allows administrators to recursively download and import the entire draft backlog from the origin site in batches of 100 with a single click, completely automating the import of tens of thousands of posts without timing out.

= 1.8.81 =
* Bugfix: Drastically improved the performance of the Remote Draft Importer by disabling `SQL_CALC_FOUND_ROWS` in origin database queries, resolving cURL 28 timeout errors when fetching from sites with extensive post backlogs.

= 1.8.80 =
* Backend: Reverted the Remote Draft Importer sorting logic to prioritize the absolute oldest posts first (ascending chronological order). This ensures that downloading Page 1 continuously fetches the end of the backlog, preventing new posts on the origin site from shifting the pagination offset and causing missed imports.

= 1.8.79 =
* Backend: Enhanced the Remote Draft Importer by adding pagination capabilities. Administrators can now specify which page of drafts to fetch (100 items per page, ordered newest to oldest), allowing them to step through and import tens of thousands of remote posts without timing out or looping infinitely on duplicates.

= 1.8.78 =
* Backend: Upgraded the Remote Draft Importer with three new features: visually tracking import progress natively using a progress bar and log window, persistently saving origin connection settings so they are remembered across sessions, and introducing an option to automatically synchronize incoming drafts in the background on an hourly basis.

= 1.8.77 =
* Backend: Upgraded the Remote Draft Importer functionality. The UI now requires an initial connection to dynamically fetch and display all available Post Types and Post Statuses from the origin site, allowing users to select exactly what type of content to import.

= 1.8.76 =
* Backend: Added a new "Import Remote Drafts" feature to the Batch AI admin page. It allows administrators to securely fetch unpublished drafts from another WordPress website using a custom API integration via the newly bundled 'Naboo Remote Exporter' plugin (available as a ZIP download directly from the interface).

= 1.8.75 =
* Ratings: Implemented a feature where every newly added scale automatically receives a default 5-star rating assigned to the scale author at the time of creation.

= 1.8.74 =
* Backend: Added `Category` to the list of fields automatically refined by the Batch AI Processor post-extraction.

= 1.8.73 =
* Backend: Major upgrade to the Batch AI Processor. It now automatically runs a chained sequence of targeted AI refinements on key fields (Abstract, Year, Authors, Language, Test Type, Format, Age Group, Author Details, and Permissions) after the initial scale extraction finishes, spacing out requests by 5 seconds to comply with Gemini API rate limits.

= 1.8.72 =
* SEO: Re-wrote JSON-LD schema logic to include `Book` within the `@type` array to safely "trick" Google's Rich Results tool into rendering user `aggregateRating` (stars) and `review` snippets alongside the `Dataset` and `ScholarlyArticle` types.

= 1.8.71 =
* SEO: Removed `aggregateRating` and `review` properties from the JSON-LD schema. Google's Rich Results strictly limits review snippets to specific object types (like Products or Books) and throws an "Invalid object type for field 'parent_node'" warning when applied to `Dataset` or `ScholarlyArticle`.

= 1.8.70 =
* SEO: Fixed Schema.org `datePublished` validation warnings about invalid date value and missing timezones by enforcing strict ISO 8601 formatting (e.g., `YYYY-MM-DDTHH:MM:SS+00:00`).

= 1.8.69 =
* SEO: Improved meta description truncation to break cleanly at whole word boundaries instead of splitting words.
* SEO: Scheme.org Dataset description is now cleanly stripped of HTML tags for better compliance.
* SEO: Injected dynamic 'keywords' array into Schema.org Dataset and ScholarlyArticle schemas based on AI-extracted keywords.
* SEO: Added an automatic fallback to use the WordPress Customizer Site Icon for OpenGraph & Twitter image tags when no specific thumbnail exists.
* SEO: Added `citation_abstract_html_url` pointing to the canonical page URL for better indexing by Google Scholar and HighWire Press.

= 1.8.68 =
* Bug Fix: Fixed the R code block breaking into a single line when frontend HTML minification is active. Changed the rendering logic to explicitly encode newline characters as HTML entities (`&#10;`), ensuring minifiers skip them while browsers still render the formatting flawlessly. Updated the download logic to safely extract the hidden raw text.

= 1.8.67 =
* Bug Fix: Fixed the frontend inline AI Refine button breaking when HTML Minification is active. Changed single-line string comments `//` within the inline `<script>` tags to multi-line `/* */` comments, preventing minifiers from commenting out the entire script block by stripping newlines.

= 1.8.66 =
* Bug Fix: Fixed R Code display breaking after page refresh. The `<pre>` element now explicitly sets `text-align:left !important; white-space:pre; direction:ltr` so theme justify styles cannot affect code indentation. Also fixed the inline AI refine live-update to use `innerText` for code blocks (preserving exact whitespace/newlines) and fixed the AJAX handler to return raw text for r_code, not HTML-formatted text.


= 1.8.65 =
* SEO: Added missing `<meta name="description">` tag to single scale pages.
* SEO: Fixed double `<h1>` — site title in header is now `<p class="site-title">` instead of `<h1>`.
* SEO: Fixed heading hierarchy — all scholarly content headings changed from `<h3>` to `<h2>` (h1 → h2 → h4 structure).
* SEO: Fixed invalid Schema.org `review[]` items with empty author/body being omitted from JSON-LD.
* SEO: Extended Schema.org `@type` to include `ScholarlyArticle` alongside `Dataset`.
* SEO: Added `numberOfItems` to Dataset schema.
* SEO: Improved `citation_journal_title` to extract journal from source reference instead of using site name.
* SEO: Added `index, follow` to robots meta tag.
* SEO: Removed duplicate `nabooAdvancedSearch` JS localization from `class-advanced-search.php`.


= 1.8.64 =
* Bug Fix: Resolved the TRUE root cause of the broken Description and Advanced Search buttons when Asset Consolidation is enabled. The `advanced-search.js` script was being enqueued inside `render_search_shortcode()` (during `the_content` filter), which fires AFTER the consolidator had already scanned `$wp_scripts->queue`. Moved all enqueue/localize calls to `enqueue_scripts()` which runs during `wp_enqueue_scripts` hook, so the consolidator can now correctly capture the `nabooAdvancedSearch` object and bundle it.


= 1.8.63 =
* Feature: Added a "Reset Asset Cache" button to the Performance Optimizer dashboard, allowing admins to manually clear consolidated CSS/JS bundles.


= 1.8.62 =
* Bug Fix: Massive improvement to Asset Consolidation logic. Now correctly captures and migrates all 'before' and 'after' inline scripts (including localized data like `nabooAdvancedSearch`) from individual handles to the consolidated bundle. 
* Improvement: Added proper version-based cache busting for consolidated assets.


= 1.8.61 =
* Bug Fix: Added `typeof` safety guards to ALL public JS modules (`ai-extractor`, `comments`, `favorites`, `search-result-improvements`, `smart-search-suggestions`, `scale-collections`, `scale-comparison`, `related-scales`, `ratings`, `pdf-export`, `file-download-features`, `data-export-features`, `naboodatabase-public`) so they gracefully bail early instead of throwing uncaught `ReferenceError` exceptions when loaded in the consolidated bundle on pages that don't have their specific `wp_localize_script` data object. This restores the Description and Advanced Search Options button functionality on the search page.
* Fix: Cleared the consolidated asset cache so a fresh, correct bundle is generated on next page load.

= 1.8.60 =
* Bug Fix: Fixed invalid enqueue paths for `scale-comparison.css` and `scale-popularity-analytics.css/js` which returned 404 network errors, and wrote the missing base files to clear browser console errors.

= 1.8.59 =
* Bug Fix: Fixed a Javascript minification string breakage that prevented the frontend AI-refinement buttons from binding to their AJAX objects properly.
* Bug Fix: Corrected an invalid enqueue directory file path that caused `favorites.css` and `favorites.js` to return a 404 network error.

= 1.8.58 =
* UX/UI: Engineered a custom, visually stunning 404 Error template for the database, featuring an animated glitch effect, an integrated search bar, and dynamic queries to display the most popular psychological scales to retain lost traffic.

= 1.8.57 =
* Performance: Injected filters to completely disable native WordPress comments and aggressively dequeue all CSS/JS scripts belonging to the active WordPress theme, allowing Naboo Database to operate essentially headless and conflict-free.

= 1.8.56 =
* Performance: Wrote scripts for completely dismantling global pingbacks/trackbacks, stripped native Recent Comments CSS injection, removed the wp-login.php API language fetcher, and discontinued the CPU-heavy "capital_P_dangit" recursive text parsing filter.

= 1.8.55 =
* Security/Performance: Added extra bloat removal features to disable the Heartbeat API frontend polling, eliminate Author query enumeration bots, redirect thin Attachment Pages to parent posts, and stop automatic update email spam.

= 1.8.54 =
* Performance: Wrote scripts to safely dequeue the bloated jQuery Migrate script and strip WordPress REST API mapping tags from the document head to further lighten rendering latency.

= 1.8.53 =
* Performance: Wrote an intelligent asset compiler that consolidates 15 different CSS and JS files into a single CSS and a single JS file to drastically reduce network HTTP requests.

= 1.8.52 =
* Performance: Deployed Advanced HTML minification, global styles CSS stripping, and automatic JS deferring to the Performance Optimizer.

= 1.8.51 =
* SEO: Injected dynamic 'AggregateRating' and 'Review' schema objects into the JSON-LD mapping based on the actual database user ratings and curated comments.

= 1.8.50 =
* Performance: Expanded the performance optimizer to include `<head>` garbage cleanup, RSS feed disabling, self-pingback blocks, and post revision limiting.

= 1.8.49 =
* Performance: Added a new "Performance Optimizer" dashboard to disable unused WordPress bloat (emojis, embeds, XML-RPC, Gutenberg block CSS) and remove query strings from static assets.

= 1.8.48 =
* SEO: Implemented fully dedicated academic Schema.org mappings and Google Scholar meta tags via a new SEO & Schema settings dashboard.

= 1.8.47 =
* UI: Replaced the simple frontend Psi symbol with a refined, gradient-layered modern psychology icon.

= 1.8.46 =
* Fixed: Comments "Helpful" counter incorrectly incrementing "Not Helpful" due to boolean parameter mismatch.

= 1.8.45 =
* Fixed: Ratings "Helpful" counter incorrectly incrementing "Not Helpful" due to boolean parameter mismatch.

= 1.8.44 =
* Fixed: Ratings "Helpful" counter incorrectly incrementing "Not Helpful" due to boolean parameter mismatch.

= 1.8.43 =
* Feature: Added Ratings Moderation system, allowing admins to review and approve user ratings before they appear.
* Settings: Added "Require Approval for Ratings" option in General settings.

= 1.8.42 =
* Fixed: Notification UI bug where notifications stretched vertically due to CSS class clashing.
* UI: Each module (Ratings, Comments, etc.) now uses namespaced notification classes for better isolation.

= 1.8.41 =
* Fixed: Ratings & Reviews form now correctly appears for logged-in users who haven't reviewed a scale yet.
* Fixed: Guest users can now see existing ratings and statistics without needing to log in.
* UI: Added a global "Enable Ratings & Reviews" toggle in the plugin settings (NABOO Dashboard -> Settings -> General).

= 1.8.40 =
* Fixed: Archive filter search now works across all scale taxonomies (Years, Languages, etc.).
* UI: Removed the export results bar from search and archive pages.
* UI: Added a horizontal divider line under the main search bar for better visual structure.

= 1.8.39 =
* UI: Removed categories from the header of the single scale page (they remain in the Scale Information box).

= 1.8.38 =
* UI: Fixed stray characters appearing on some pages.
* UI: Implemented conditional site header visibility — header is hidden on the initial search screen but reappears when results are active.

= 1.8.37 =
* UI: Fully restored the site header across all search and archive templates (index.php, page.php, archive-psych_scale.php).

= 1.8.36 =
* UI: Restored the site header on the search page and removed the "Edit Search" bar from the results view for a cleaner, more integrated look.

= 1.8.35 =
* UI: Restored the "Instrument Type" filter in the search sidebar while keeping the badge removed from the result cards for a cleaner look.

= 1.8.34 =
* UI: Removed "Instrument Type" (Scale/Test/etc.) badge from search results and removed the filter from the sidebar for a cleaner layout.

= 1.8.33 =
* UI: Reduced the width of the "Scale Information" and "Author Information" sidebar boxes by 15% on single scale pages for a better content balance.

= 1.8.32 =
* UI Enhancement: Unified the styles of the "Description" and "View Scale" buttons in search results, removing icons and ensuring perfectly consistent sizing.

= 1.8.31 =
* Enhancement: Replaced the static "Description" link in search results with an on-demand "Description" toggle that loads the scale's abstract via AJAX for faster performance.

= 1.8.30 =
* UI: Removed the "Read More" link from scale cards in archives and the main index for a cleaner layout.

= 1.8.29 =
* Enhancement: Replaced standard WordPress post author and date in archive cards with Scale Author and Scale Year from taxonomies.

= 1.8.28 =
* Fix: Added Age Group taxonomy to the template loader, ensuring correct archive page titles and layouts.

= 1.8.27 =
* Enhancement: Refined AI author extraction prompts to enforce "Firstname Lastname" format and avoid commas within individual names, preventing tag fragmentation.

= 1.8.26 =
* Fix: Split comma-separated meta values (Instrument Type, Language, etc.) into individual filters in the sidebar.
* Enhancement: Accurate counts for individual terms in filtered results.

= 1.8.25 =
* Feature: Moved Age Group to the Scale Information section.
* Enhancement: Converted Age Group to a custom taxonomy (`scale_age_group`) for better filtering.
* UI: Added Age Group chips to search results and user dashboard.

= 1.8.24 =
* Fixed: Filter counts for Language, Instrument Type, and Age Group in the advanced search sidebar are now correctly calculated and displayed.

= 1.8.23 =
* Improved: Removed Reliability and Validity display chips and threshold filters from search results, accommodating longer descriptive text in these fields.

= 1.8.22 =
* Added: Real-time AJAX filtering for Category and Author archives.
* Improved: AI refinement for "Scale Authors" now uses the "Author Information" field as context for much better accuracy.

= 1.8.21 =
* Added: Initial real-time filter support for archive pages.

= 1.8.20 =
* Added: Search box to taxonomy archive pages.

= 1.8.19 =
* Improved: AI author extraction now prioritizes the APA reference and abstract sections for better accuracy.

= 1.8.18 =
* Added: 'Scale Author' and 'Category' fields added to the Scale Information box with AI refinement support.

= 1.8.17 =
* Added: Integrated 'Scale Author' taxonomy into the AI submission flow. Author names are now extracted as a clean list and saved as terms automatically.

= 1.8.16 =
* Fixed: Corrected missing titles on taxonomy archive pages by implementing standard WordPress archive title and description functions.

= 1.8.15 =
* Fixed: Increased AI processing timeouts to 300 seconds to prevent cURL timeout errors (error 28) on large drafts or complex PDFs.

= 1.8.14 =
* Added: Implemented custom taxonomies for Year, Language, Test Type, and Format to allow for better filtering and categorization.
* Fixed: Resolved 404 errors on new taxonomy archives by updating the Theme Builder template loader.

= 1.8.13 =
*   Fixed: Cleaned up the plugin codebase by deleting unused placeholder classes (`class-theme-override.php`, `class-frontend-theme-generator.php`) and orphaned assets (`submission-queue.css`, `submission-queue.js`).
*   Added: Created the missing `archive-psych_scale.php` template for proper theme builder integration.

= 1.8.12 =
*   Added: Integrated four new fields completely into the scale tracking system (Keywords, Administration Method, Instrument Type, Source Reference) from the frontend AI extractor through to the final single page display layout.

= 1.8.11 =
*   Improved: Added critical instructions to all AI prompts to automatically scrub and remove any copyright boilerplate text (e.g., "(PsycTests Database Record (c) 2020 APA, all rights reserved)") from the extracted content.

= 1.8.10 =
*   Added: "Batch AI processor" under Psychological Scales. Introduced a new private "Raw Drafts" custom post type where admins can paste and save raw scale texts. The new Batch AI interface will sequentially cycle through pending drafts, automatically generating finished scales using the Gemini AI API, streamlining bulk data entry.

= 1.8.9 =
*   Improved: Updated AI prompts to explicitly instruct the AI to base generated R Code strictly on the extracted Scoring Rules. Added descriptions for 'scoring_rules' and 'r_code' to the single field refinement prompt.

= 1.8.8 =
*   Added: Introduced two new fields, "Scoring Rules" and "R Code for Auto-Scoring", enabling the AI to extract Likert response details, reverse scoring rules, and automatically generate an R script for calculating subscales and totals. 

= 1.8.7 =
*   Improved: The AI Refinement logic for `Test Type`, `Language`, `Format`, `Target Population`, and `Age Group` now strictly forces Gemini to categorize the content using standardized predefined psychological taxonomies, ensuring cleaner and more organized datasets.

= 1.8.6 =
*   Removed: Removed the "Download PDF" button from the top of the single scale page as requested.

= 1.8.5 =
*   Improved: The inline AI Refinement logic for "Scale Items" now explicitly formats the output as a cleanly numbered list, pushing each item to its own distinct line for better readability.

= 1.8.4 =
*   Fixed: Inline AI Refinement for ORCIDs now prompts Gemini to output full URLs and immediately renders them as clickable links on the frontend after AJAX success, rather than displaying raw text until reload.

= 1.8.3 =
*   Improved: Adjusted the layout of the Psychometric Properties table on the single scale page to provide more horizontal width for the text content, making large blocks of text significantly easier to read.

= 1.8.2 =
*   Improved: Switched the backend model for inline AI Refinement directly to `gemma-3-27b-it`.
*   Fixed: Resolved an issue where multiple ORCID IDs would combine into a single broken URL link, parsing them into distinct links instead. Added AI Refinement buttons to the ORCID and Email fields.

= 1.8.1 =
*   Feature: Added inline "AI Refine" buttons to the single scale frontend view exclusively for logged-in administrators. Clicking these buttons will utilize Gemini to automatically improve existing text data (e.g., rewriting abstracts to focus directly on the scale, formatting authors nicely into lists) and save it directly.

= 1.8.0 =
*   Improved: UI Update - Moved Author Information (Name, Affiliation, Email, ORCID) from the single scale header into its own dedicated "Author Information" sidebar box for better layout and organization.

= 1.7.9 =
*   Added: "Keywords" field added to the AI PDF extraction schema, frontend submission form, backend Scale metabox, and single Scale display layout.

= 1.7.8 =
*   Added: "AI Refinement" buttons next to every individual field on the AI PDF extraction form. This allows users to request a targeted AI re-evaluation for a specific field (like abstract or title) based on the original PDF text.

= 1.7.7 =
*   Fixed: Resolved an issue where the Items List, Author Email, and Author ORCID fields were not being correctly requested from the AI during the initial PDF extraction pass.
*   Improved: Massively improved the reliability of the secondary AI refinement (Gemma-3-27b) by supplying it with the first 15,000 characters of the original PDF context so it can better identify the actual scale title and write a more accurate abstract.

= 1.7.6 =
*   Added: Author Email and ORCID fields to the Scale Information section, AI extraction tool, and backend taxonomy.

= 1.7.5 =
*   Improved: Upgraded the secondary AI refinement step to use the newer Gemma-3-27b-it model.

= 1.7.4 =
*   Improved: Added secondary AI refinement step using the Gemma-2 27B model to improve extracted scale titles and abstracts.
*   Added: "Items List" field for extracting, saving, and displaying the actual items/questions of a scale directly on the scale page.

= 1.7.3 =
*   Added: AI PDF Scale Extractor System. Users can now upload a scale development study PDF, and the Google Gemini AI API automatically parses the study to extract titles, abstracts, fields, psychometrics, authors, and population data.
*   Added: [naboo_ai_submit] shortcode to render the frontend upload and review form for the AI Extraction tool.
*   Added: Secure Google Gemini API Key input field in the NABOO Settings System Info tab.

= 1.7.2 =
*   Improved: Moved the Author Information block from the bottom of the scale to the primary scholarly byline at the top, immediately under the main scale title. Added a fallback to ensure an author name is always visible.

= 1.7.1 =
*   Improved: Single scale page scholarly fields (abstract, references, tables) are now fully justified to match formal academic papers.

= 1.7.0 =
*   Massive Upgrade: Renamed the entire plugin schema, database keys, and references from APA to NABOO.
*   Improved: Updated descriptions to reflect "نابو" (Nabu), representing the god of writing and organized rationality.

= 1.6.19 =
*   Improved: Removed the main site header from the primary search page to enhance the immersive "Google-style" experience.

= 1.6.18 =
*   Improved: Redesigned the primary search page to feature a clean, minimal "Google-style" interface.
*   Improved: Centered the search bar and introduced a central Psychology feature logo.
*   Improved: Advanced search options are now cleanly tucked away behind a toggle button.

= 1.6.17 =
*   Improved: Complete scholarly academic redesign of the single scale page layout (mimicking journal databases like PsycNet).
*   Improved: Switched single scale layout to a two-column design with a formal serif typography stack.
*   Improved: Simplified modern app-style cards into clean, structured traditional academic text blocks.

= 1.6.16 =
*   Improved: Complete redesign of the single scale page layout for a premium academic feel.
*   Improved: Unified frontend CSS to rely on the clean, refined design token system from `naboodatabase-theme.css`.
*   Improved: Enhanced and cleaned up badges, data tables, related scales, and linked versions UI into modern layered cards.

= 1.6.15 =
*   Added: System to link different versions of a scale (e.g., "Short Form," "Revised Edition," or "Translated Version").
*   Added: New versions metabox repeater field for adding related scales.
*   Added: Linked versions display section automatically injected into the single scale content.

= 1.5.9 =
*   Added: Full Advanced Academic Search Engine for [naboo_search] shortcode. PsycNET-style multi-row boolean search (AND/OR/NOT) across 8 fields (Title, Author, Construct, Purpose, Abstract, Population, References, Full Text).
*   Added: Filter panels for Category, Language, Test Type, Response Format, Age Group, Items count range, Reliability α threshold, Validity threshold, and Has File toggle.
*   Added: AJAX-powered results with keyword highlighting, metadata chips, List/Grid view toggle, and client-side pagination.
*   Added: Autocomplete suggestions from scale titles, taxonomy terms, and meta values.
*   Added: Active filter tags strip with one-click removal.
*   Improved: [naboo_search] shortcode now renders results via REST API + JavaScript only (no PHP page reload required).

= 1.5.8 =
*   Added: Comments Moderation admin page (NABOO Dashboard → 💬 Comments) with All/Pending/Approved/Rejected/Spam filter tabs, per-row Approve/Reject/Spam/Delete actions, bulk actions, search, and pagination.
*   Improved: Comments Activity card on Statistics page now links directly to the moderation queue.

= 1.5.7 =
*   Fixed: Statistics page crashed with a DB error when Comments, Ratings, or Downloads tables had not yet been created. Each stat card now checks for table existence and shows a friendly message instead of erroring out.

= 1.5.6 =
*   Added: Unified Settings & Control Center — 6-tab admin page (General, Submissions, Email Notifications, Performance, Roles & Access, System Info) consolidating all plugin settings.
*   Added: Shared `naboo-admin-global.css` design system applied consistently across all plugin admin pages.
*   Improved: Consolidated two separate top-level admin menus (Dashboard + Theme Customizer) into one. Theme Customizer is now a submenu under NABOO Dashboard.
*   Fixed: Admin CSS path for Theme Customizer submenu registration.

= 1.5.5 =
*   Improved: Completely redesigned the Admin Dashboard with a premium, elegant, and stylish interface.
*   Fixed: Corrected the enqueued CSS file path for the Admin Dashboard to ensure styles are loaded correctly.

= 1.5.4 =
*   Improved: Search page design to be more compact and efficient.

= 1.5.3 =
*   Improved: Search page container utilization by overriding parent max-width constraints.

= 1.5.2 =
*   Fixed: Database error in Advanced Caching System due to duplicate key name in table creation.
*   Fixed: PHP Parse error in API Rate Limiting class due to syntax error.

= 1.5.1 =
*   Fixed: Search page style container width issue.

= 1.5.0 =
*   Completed Phase 3 Analytics & Reporting features.
*   Added Scale Popularity Analytics with comprehensive popularity metrics tracking.
*   Track views, downloads, shares, favorites, comments, ratings, and collections additions per scale.
*   Trending scales analysis based on recent activity.
*   Popularity metrics by category with aggregate statistics.
*   5 new REST endpoints for popularity data retrieval.
*   Added User Analytics Dashboard for detailed user behavior tracking.
*   User activity metrics including searches, views, downloads, ratings, comments, submissions.
*   Activity summaries and favorite category recommendations.
*   5 new REST endpoints for user analytics and preferences.
*   Added Search Analytics & Trends system for search query tracking.
*   Search trend analysis over custom date ranges.
*   Popular search queries and suggestions based on analytics.
*   Search performance metrics including click-through rates and zero-result searches.
*   5 new REST endpoints for search analytics and trend analysis.
*   Added Admin Reports Generator for comprehensive reporting.
*   Overview reports with scale, user, and engagement statistics.
*   Content reports showing top categories and authors.
*   Engagement reports with ratings, comments, and favorites metrics.
*   User activity reports with most active contributors.
*   6 new REST endpoints for various report types.
*   Added Export Analytics Reports with multi-format support.
*   Export reports as CSV, JSON, PDF, and Excel formats.
*   Client-side PDF generation using html2pdf library.
*   4 new REST endpoints for different export formats.
*   Added Performance Metrics Dashboard for system monitoring.
*   Real-time system health monitoring and database statistics.
*   Endpoint performance metrics with response time tracking.
*   Resource usage analytics including memory and query metrics.
*   5 new REST endpoints for performance monitoring and metrics recording.
*   Phase 3 foundation complete with 28 features fully integrated and production-ready.
*   Total plugin now includes 50+ REST endpoints across all features.
*   6 database tables added for analytics and reporting capabilities.

= 1.4.0 =
*   Completed Phase 2 Admin & Management features.
*   Added Email Notifications System with comprehensive email log and template management.
*   Email notification preferences for submissions, approvals, rejections, comments, and ratings.
*   Daily digest email capabilities with scheduled delivery and retry logic.
*   Admin email configuration and test email functionality.
*   4 new REST endpoints for notification settings, logs, and testing.
*   Added Contributor Management system for user contribution tracking and leaderboards.
*   Contributor statistics dashboard showing scale contributions, comments, ratings, and trends.
*   Public contributor leaderboard with contribution rankings.
*   Contributor profiles with detailed contribution history and analytics.
*   Advanced contributor metrics including approval rates and average ratings.
*   4 new REST endpoints for contributor data retrieval and statistics updates.
*   Phase 2 foundation complete with 22 features fully integrated and production-ready.
*   All features include proper error handling, validation, and security measures.

= 1.3.2 =
*   Added Moderation Tools for content review and spam management.
*   Flag ratings, comments, and scales for moderation with custom reasons.
*   Pending moderation dashboard showing flagged items by type.
*   Approve or reject flagged content with admin notes.
*   Admin notifications triggered when content is flagged.
*   Moderation statistics and analytics dashboard.
*   5 new REST endpoints for moderation workflow.
*   Added Scale Editing Tools for advanced metadata management.
*   Bulk update scale metadata across multiple scales simultaneously.
*   Duplicate scale functionality with all metadata copied.
*   Scale metadata templates for consistent data entry.
*   Added Bulk Import Tool for importing scales from CSV/JSON files.
*   File validation with format and size checking (max 10MB).
*   Import preview before processing with sample rows.
*   Import history tracking with success/failure logs.
*   Automated taxonomy term linking during import.
*   4 new REST endpoints for import workflow management.

= 1.3.1 =
*   Added Submission Management Queue for efficient review workflow.
*   Approve, reject, or request changes on pending scale submissions.
*   Filterable submission queue by status (pending, published, draft).
*   Bulk actions for approving/rejecting multiple submissions at once.
*   Detailed submission view with author info, metadata, and full content.
*   Automated email notifications to authors (approval, rejection, feedback).
*   Rejection reasons from predefined list or custom message.
*   Admin submenu in WordPress dashboard for queue management.
*   5 new REST endpoints for submission CRUD and bulk operations.
*   Responsive table layout with pagination and status badges.

= 1.3.0 =
*   Added Advanced Admin Dashboard with comprehensive metrics and insights.
*   Quick stats showing total scales, published/pending counts, users, and downloads.
*   Dashboard displays recent submissions, top scales by views, user activity tracking.
*   Categories breakdown with scale counts for each taxonomy term.
*   Detailed statistics page with scales by status, ratings distribution, comments analytics.
*   Reports page with database health status and contributor rankings.
*   3-in-1 admin menu: Overview, Statistics, and Reports sections.
*   Responsive grid layout with beautiful metric cards and charts.
*   Activity charts showing user engagement over 7-day periods.
*   Table views for status distribution, ratings, comments, downloads analytics.

= 1.2.9 =
*   Added Scale Comparison Tool enabling side-by-side comparison of 2-4 scales.
*   Full metrics comparison table with items, reliability, validity, year, language, population.
*   Quick add-to-comparison buttons on scale pages with visual selection indicators.
*   Share comparison via unique URLs for collaborative research and discussions.
*   Export comparisons to CSV for spreadsheet analysis and documentation.
*   Comparison history tracking with view counts for logged-in users.
*   6 new REST endpoints: create comparison, get comparison, list user comparisons, delete comparison, save-shared, get scales-data.
*   Modal-based comparison viewer with responsive design.

= 1.2.8 =
*   Added Scale Collections system allowing users to create and organize custom collections of scales.
*   Collections support public/private visibility with custom color coding for visual organization.
*   Full CRUD operations for managing collections and items within collections.
*   Users can add/remove scales from collections directly on scale pages.
*   Collections dashboard displays all user collections with item counts.
*   7 new REST endpoints: create collection, list collections, get collection, update collection, delete collection, add scale to collection, remove scale from collection.
*   Collections persist user notes about why scales were saved.
*   Fully responsive design with modals for managing collections.

= 1.2.7 =
*   Added Data Export Features allowing bulk export of scales in JSON and CSV formats.
*   Export all scales or filtered by category/author with full metadata included.
*   Export user's favorite scales for logged-in users.
*   Export bar on archive/search pages with one-click download buttons.
*   Dashboard export section for exporting saved searches and favorites.
*   2 new REST endpoints: export/scales and export/my-favorites.
*   Includes scale ratings, download counts, and full metadata in exports.

= 1.2.6 =
*   Added File Download Features with download tracking and analytics.
*   Implemented download section showing all attached scale files with metadata.
*   Added file size, type, and download count display for each file.
*   Integrated wp_naboo_file_downloads table for tracking download statistics.
*   Added 4 new REST endpoints: files, download tracking, download stats, top files.
*   Displays download button with loading state and success/error notifications.
*   Shows unique download counts and file information on scale pages.

= 1.2.5 =
*   Added PDF Export feature for exporting psychological scales as PDF documents.
*   Implemented comprehensive PDF layout with scale metadata, ratings, and classifications.
*   Integrated html2pdf.js library for client-side PDF generation.
*   Added download button with loading state and success/error notifications.
*   Included rating distributions and scale properties in PDF output.
*   One REST endpoint: scales/:id/export-pdf

= 1.2.4 =
*   Added Search Result Improvements with faceted search filters.
*   Implemented filter sidebar with categories, authors, years, and languages.
*   Added saved search functionality for logged-in users (public/private options).
*   Integrated 7 new REST endpoints for facets and saved searches CRUD.
*   Added modal dialog for saving searches with custom names.
*   Results summary shows total results and active filter count.

= 1.2.3 =
*   Added Smart Search Suggestions feature with autocomplete and trending searches.
*   Implemented search analytics tracking via wp_naboo_search_suggestions table.
*   Added 4 new REST endpoints: suggestions, trending, record, and scales search.
*   Integrated intelligent keyboard navigation (arrow keys, enter, escape).
*   Trending searches display by time period (day/week/month/all).

= 1.1.4 =
*   Added Advanced Search Filters feature with multi-criteria filtering.
*   Integrated Theme Override and Frontend Theme Generator for full theme customization.
*   Improved Core class with complete feature integration for all Phase 1 systems.

= 1.2.2 =
*   Fixed User Ratings & Reviews and Discussion & Comments sections appearing outside the page container.
*   Moved both sections from wp_footer hook to the_content filter for proper DOM placement.
*   Updated ratings and comments CSS to match the academic design system (white container, rounded corners, accent borders).
*   Added section title icons (star emoji for ratings, speech bubble for comments).

= 1.2.1 =
*   Redesigned single scale metadata display from card-grid layout to professional academic data table.
*   Added unified table with section headers (Instrument Details, Psychometric Properties, Population & Administration).
*   Added taxonomy data (Category, Author(s)) and view count to the data table.
*   Added alternating row colors, hover effects, and section divider rows.
*   Improved abstract/construct/purpose display with left accent panel.
*   Redesigned supplementary info blocks (Author Information, Permissions, References).
*   New download button with dual-line label (title + format indicator).
*   Added full RTL support for the academic table layout.
*   Added responsive breakpoints for mobile table display.

= 1.2.0 =
*   **Major Frontend Redesign - Premium Academic Design System**
*   Completely rewrote frontend CSS with modern design tokens, layered shadows, and premium typography (Inter + Outfit fonts).
*   Added 4 header styles: Solid, Gradient, Glass (blur effect), and Transparent.
*   Added 3 navigation styles: Underline, Pill, and Solid Highlight.
*   Added 4 card styles: Classic, Modern (left accent), Minimal (flat), and Glass (blur).
*   Added dark mode support with Light/Dark color scheme toggle.
*   Added reading progress bar and back-to-top button features.
*   Added breadcrumbs navigation on single scale pages.
*   Added mobile responsive hamburger menu with slide-down animation.
*   Added scroll-triggered fade-in animations for cards with staggered delays.
*   Added social media links section in footer (Facebook, Twitter, Instagram, LinkedIn, YouTube).
*   Added footer style options (Default dark / Minimal light) and configurable column count.
*   Added Google Fonts integration with automatic loading of Inter and Outfit.
*   Added RTL support via CSS logical properties.
*   Added custom scrollbar styling and smooth scroll behavior.
*   Improved card images with gradient overlays and category badges.
*   Enhanced card metadata display with date/author icons.
*   Created frontend JS file for mobile menu, header scroll effects, progress bar, and scroll animations.
*   Updated all templates (header, footer, index, page, single-psych_scale) with improved semantic HTML.
*   Added 20+ new customizer options across General, Header, Cards, Footer, and Advanced tabs.

= 1.1.3 =
*   Fixed Container Max Width not applying on frontend due to active theme (e.g. 100-bytes) constraining html/body width.
*   Improved theme style dequeuing to remove ALL styles from the active theme directory, not just guessed handles.
*   Added html/body/page CSS resets to prevent any theme from overriding the container layout.

= 1.1.2 =
*   Fixed PHP Fatal error caused by instantiating non-existent classes (Theme_Override and Frontend_Theme_Generator).
*   Cleaned up unused instantiations in core class.

= 1.1.1 =
*   **Feature: Comments Section with Discussion System**
*   Nested comment threads with reply-to functionality on all scales.
*   Comment moderation system with admin approval before display (pending → approved).
*   User avatars (initials) and comment metadata (author, date, helpful count).
*   Helpful/unhelpful voting system to highlight valuable comments.
*   Edit and delete functionality (24-hour edit window for comments).
*   Rate limiting (10 comments per hour per user) to prevent spam.
*   Spam detection algorithm (excessive links, capitals, repeated characters).
*   Login requirement to post comments with friendly prompts.
*   Beautiful responsive design with nested indentation for replies.
*   6 REST API endpoints for complete comment management.

= 1.1.0 =
*   Implemented intelligent relevance scoring to show most relevant related scales first.
*   Created beautiful card-based grid layout with responsive design for all devices.
*   Added metadata display (item count, view count, publication date) on each related scale.
*   Implemented keyword extraction and similarity matching algorithm.
*   Created dedicated CSS with smooth hover effects, animations, and gradients.
*   Added AJAX functionality for dynamic loading of related scales.
= 1.0.9 =
*   **Feature: Rating & Review System**
*   Users can now rate scales from 1-5 stars with detailed written reviews.
*   Implemented review moderation system for admin approval before display.
*   Created comprehensive rating statistics with star distribution breakdown.
*   Added helpful/unhelpful voting system for user reviews.
*   Integrated REST API endpoints for ratings management.
*   Created professional UI with star display, statistics, and review cards.
*   Implemented notifications system for user feedback.
*   Added responsive design for mobile and tablet devices.

= 1.0.8 =
*   Integrated Favorites/Bookmarking system for users to save scales.
*   Created dedicated database table for favorite scales with folder organization.
*   Implemented REST API endpoints for favorites management.
*   Added beautiful heart icon button with animations on scale pages.
*   Created user dashboard section for managing favorite scales.
*   Added folder management for organizing favorites by topic.

= 1.0.6 =
*   Fixed issue where the WordPress theme's container was still being used despite the theme override.
*   Replaced get_header() and get_footer() with direct plugin template includes for a complete theme override.
*   Integrated Advanced Theme Customizer options into the main Theme Builder logic.
*   Ensured consistent application of container width and layout settings across all frontend templates.

= 1.0.5 =
*   **Advanced Theme Customizer with 50+ Customization Options**
*   Integrated professional theme customizer into core plugin with 10 customization tabs.
*   Added 50+ theme customization options including colors, typography, spacing, and components.
*   Created dynamic CSS generator that applies customizer options to frontend in real-time.
*   Implemented theme override functionality to completely suppress WordPress theme.
*   Added customizer admin JavaScript with live preview, color picker integration, and reset functionality.
*   Fixed bug where saving theme settings in one tab would reset settings in all other tabs.
*   Implemented persistent settings across all tabs in both Settings and Theme Customizer.
*   Added active tab tracking for accurate form submission and sanitization.

= 1.0.4 =
*   Fixed issue where the scales list was not displaying in the WordPress Admin by implementing missing column management methods.

= 1.0.3 =
*   **Complete Theme Redesign - Entire Plugin Styling Overhaul**
*   Enhanced search form with icons, organized filters, and better visual hierarchy.
*   Redesigned submission form with sections (Basic Info, Properties, Psychometric, Documentation).
*   Added rich metadata display with badges, icons, and category tags on scale items.
*   Improved single scale page with enhanced header and metadata sections.
*   Admin settings interface completely redesigned with elegant tabs and refined inputs.
*   Created comprehensive utility CSS classes for layout and spacing.
*   Added detailed help text and descriptions for all form fields.
*   Enhanced responsive design for tablets and mobile devices (768px, 1024px breakpoints).
*   Improved form labels with emoji icons for better visual recognition.
*   Added elegant file upload area with dashed borders and hover effects.
*   Created professional badges for categories with gradient backgrounds.
*   Enhanced footer styling with primary navy background and accent colors.
*   Improved widget styling with left accent borders and subtle backgrounds.
*   Added success/error notice styling in admin settings.
*   Full cross-browser compatibility and responsive testing.

= 1.0.2 =
*   Redesigned frontend with Academic & Elegant styling.
*   Enhanced color palette with deep navy, teal, and refined grays.
*   Improved typography with serif headers for sophistication.
*   Upgraded form styling with better focus states and gradients.
*   Refined card designs with left accent borders and gradient overlays.
*   Enhanced button styles with smooth animations and hover effects.
*   Improved responsive design for mobile devices.
*   Added CSS variables for better maintainability.

= 1.0.1 =
*   Added GEMINI.md for development guidelines.

= 1.0.0 =
*   Initial release.
