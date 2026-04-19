# Naboo Database Plugin Wiki

Welcome to the official documentation for the **Naboo Database** WordPress plugin. This wiki serves as a comprehensive guide for users, administrators, and developers.

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Installation & Setup](#2-installation--setup)
3. [User Guide](#3-user-guide)
    - [Searching & Browsing](#searching--browsing)
    - [Viewing Scales](#viewing-scales)
    - [User Features](#user-features)
    - [Submitting a Scale](#submitting-a-scale)
4. [Administrator Guide](#4-administrator-guide)
    - [Dashboards & Analytics](#dashboards--analytics)
    - [Managing Scales](#managing-scales)
    - [Submission Workflow](#submission-workflow)
    - [Theme Customizer](#theme-customizer)
    - [Tools & Reports](#tools--reports)
5. [Developer Documentation](#5-developer-documentation)
    - [Architecture](#architecture)
    - [Data Model](#data-model)
    - [REST API](#rest-api)
    - [Hooks & Filters](#hooks--filters)

---

## 1. Introduction

**Naboo Database** is a robust WordPress plugin designed to create, manage, and display a repository of psychological scales and measures. It transforms a WordPress site into a professional academic database with advanced search capabilities, peer-review submission workflows, and detailed analytics.

**Key Features:**
*   **Academic Data Structure:** Specialized fields for psychometrics (validity, reliability), construction, and administration.
*   **Advanced Search:** Faceted search by category, author, year, and dynamic smart suggestions.
*   **Community Driven:** Frontend submission system with moderation queues.
*   **Engagement:** User favorites, collections, comparisons, ratings, and comments.
*   **Analytics:** Detailed tracking of views, downloads, search trends, and scale popularity.

---

## 2. Installation & Setup

### Requirements
*   WordPress 5.0 or higher.
*   PHP 7.4 or higher recommended.

### Installation Steps
1.  Download the `naboodatabase` plugin folder.
2.  Upload it to your WordPress `wp-content/plugins/` directory.
3.  Activate the plugin via the **Plugins** menu in WordPress.
4.  Upon activation, the plugin will create:
    *   Custom Post Type: `psych_scale`
    *   Taxonomies: `scale_category`, `scale_author`
    *   Database tables for favorites, ratings, and analytics.

### Configuration
1.  Navigate to **Naboo Database** in the admin sidebar.
2.  **Permalinks:** Go to *Settings > Permalinks* and click "Save Changes" to flush rewrite rules (ensures scale pages load correctly).
3.  **Pages:** Create two pages and add the following shortcodes:
    *   **Search Page:** `[naboo_search]`
    *   **Submission Page:** `[naboo_submit]`
    *   *(Optional)* **User Dashboard:** `[naboo_dashboard]`

---

## 3. User Guide

### 🔍 Searching & Browsing
The search interface is your primary tool for discovery.
1.  **Basic Search:** Enter keywords (e.g., "Depression", "Beck") into the search bar. Use the **Smart Suggestions** that appear as you type for faster results.
2.  **Using Filters:** 
    *   Navigate to the sidebar to find **Faceted Filters**.
    *   Click on a **Category** (e.g., *Clinical Psychology*) to narrow results.
    *   Select an **Author** or a **Publication Year** range.
    *   Filters are cumulative; you can select multiple criteria to pinpoint specific scales.
3.  **Saving a Search:** 
    *   If you are logged in, perform a search with your desired filters.
    *   Click the **"Save Search"** button (usually a bookmark icon).
    *   Give your search a name and choose if it should be *Public* or *Private*.
    *   Access saved searches later from your **User Dashboard**.

### 📄 Viewing & Interacting with Scales
Each scale page is a comprehensive data sheet.
1.  **Reading Data:** Use the **Academic Table** to review technical specifications like *Cronbach's Alpha* (Reliability) or *Factor Analysis* results.
2.  **Downloading:** Click the **"Download Scale Document"** button. If you are not logged in, you may be prompted to do so depending on site settings.
3.  **PDF Export:** Click **"Export to PDF"** to generate a clean, printable summary of the scale's metadata for your research notes.
4.  **Comparing Scales:**
    *   On a search result or single scale page, click **"Add to Compare"**.
    *   A comparison bar will appear at the bottom. Select 2-4 scales.
    *   Click **"Compare Now"** to see a side-by-side table of all psychometric properties.

### ❤️ Favorites & Collections
1.  **Adding Favorites:** Click the **Heart icon** on any scale.
2.  **Creating Folders:**
    *   Go to your **User Dashboard > Favorites**.
    *   Click **"Create New Folder"** and name it (e.g., "Thesis Research").
    *   Move favorited scales into specific folders for organization.
3.  **Building Collections:**
    *   Go to **User Dashboard > My Collections**.
    *   Click **"New Collection"**, add a title, description, and set visibility.
    *   Browse scales and use the **"Add to Collection"** menu to populate your list.

### 📝 Submitting a New Scale
1.  Navigate to the **Submission Page** (`[naboo_submit]`).
2.  **Step 1: Core Info:** Provide the full title, the specific construct measured (e.g., "Social Anxiety"), and a detailed abstract.
3.  **Step 2: Technical Data:** Enter the number of items, publication year, and select the **Test Type** (e.g., *Self-Report*).
4.  **Step 3: Psychometrics:** Paste your reliability and validity coefficients. Be specific about the population used for validation.
5.  **Step 4: File Upload:** Drag and drop the scale manual or instrument file (PDF/DOC).
6.  **Submit:** Click **"Submit for Review"**. You will receive an email once an admin reviews your submission.

---

## 4. Administrator Guide

### 🚦 Managing the Submission Queue
1.  Go to **Naboo Database > Submission Queue**.
2.  **Reviewing:** Click on a submission to see the full details and uploaded files.
3.  **Approving:** Click **"Approve"**. This automatically changes the post status to *Published* and notifies the author.
4.  **Requesting Changes:** If data is missing, click **"Request Changes"**, type your feedback (e.g., "Please provide validity data"), and send.
5.  **Rejecting:** If the scale is a duplicate or low quality, click **"Reject"** and select a reason from the dropdown.

### 🎨 Customizing the Aesthetic
1.  Go to **Naboo Database > Theme Customizer**.
2.  **General Tab:** Set your site's primary accent colors and choose between Light and Dark modes.
3.  **Header Tab:** Select a style (e.g., *Glass Blur*) and upload your logo.
4.  **Cards Tab:** Choose how scale results appear. The *Modern* style adds a color-coded accent border based on the category.
5.  **Advanced Tab:** Enable **"Full Theme Override"** if you want the plugin to suppress your active WordPress theme's CSS for a pure academic look.

### 📊 Using Analytics & Reports
1.  **Search Trends:** Go to **Naboo Database > Search Analytics**. Look at "Zero Result Searches" to identify gaps in your database that need new content.
2.  **Popularity:** Check **Scale Popularity** to see which instruments are most downloaded. You can feature these on your homepage.
3.  **Generating Reports:**
    *   Go to **Naboo Database > Reports**.
    *   Select a date range and report type (e.g., *User Activity*).
    *   Click **"Generate"**, then download as **PDF** or **Excel** for stakeholder meetings.

### 🛠️ Bulk Tools
1.  **Importing:** Go to **Naboo Database > Bulk Import**. Upload a CSV. Map your CSV columns to the plugin's fields (e.g., `col_1` -> `Construct`). Run the **Preview** first to catch errors.
2.  **Validation:** Run the **Scale Validation** tool to find existing scales that have broken download links or missing required metadata.

---

## 5. Developer Documentation

### Architecture
The plugin follows a modular, object-oriented architecture.
*   **Namespace:** `ArabPsychology\NabooDatabase`
*   **Autoloader:** Maps classes to `includes/` directory following WP standards.
*   **Entry Point:** `naboodatabase.php` initializes the `Core` class.

### Data Model
*   **Post Type:** `psych_scale`
*   **Meta Keys:**
    *   `_naboo_scale_construct`
    *   `_naboo_scale_items`
    *   `_naboo_scale_reliability`
    *   `_naboo_scale_validity`
    *   `_naboo_scale_year`
    *   `_naboo_scale_file` (Attachment ID)
    *   `_naboo_view_count`
*   **Custom Tables:**
    *   `wp_naboo_favorites`: Stores user favorites.
    *   `wp_naboo_ratings`: Stores scale ratings.
    *   `wp_naboo_file_downloads`: Tracks file download stats.
    *   `wp_naboo_search_suggestions`: Stores search query analytics.

### REST API
The plugin exposes over 50 REST endpoints under the `apa/v1` namespace.

**Example Endpoints:**
*   `GET /apa/v1/scales`: List scales with filters.
*   `GET /apa/v1/favorites`: Get current user's favorites.
*   `POST /apa/v1/favorites`: Add a scale to favorites.
*   `GET /apa/v1/analytics/search/trending`: Get trending search terms.
*   `POST /apa/v1/submissions`: Submit a new scale.

### Hooks & Filters
*   **Actions:**
    *   `naboo_before_scale_content`: Fires before the main content of a scale.
    *   `naboo_after_scale_content`: Fires after the main content.
*   **Filters:**
    *   `naboo_scale_meta_fields`: Modify the list of meta fields displayed on the frontend.
    *   `naboo_submission_validation_errors`: Modify validation logic for submissions.

---

## 6. Project Roadmap

The following features are planned for future releases to enhance the plugin's academic value and user experience.

### 🚀 Upcoming Features (Phase 4)
*   **Automatic Citation Integration (Crossref/OpenAlex):** Fetch real-time citation counts from academic literature to show the impact of each scale.
*   **Bibliographic Export (RIS / BibTeX):** One-click "Cite this Scale" button with support for NABOO, MLA, and Chicago styles, plus export for Zotero/Mendeley.
*   **Multilingual Search Support:** Full support for dual-language metadata (Arabic/English) to bridge regional and global research standards.
*   **Scale Versioning & Lineage:** Link original scales to their "Short Forms," "Revised Editions," and "Translated Versions" for easier navigation.

### 🌟 Future Vision (Phase 5+)
*   **ORCID Integration:** Allow scale authors to link their verified ORCID iD to their profile and publications.
*   **"Verified Researcher" Badge:** A verification workflow for institutions and original authors to claim and vouch for scale metadata.
*   **AI-Driven Recommendation Engine:** Smart suggestions based on keyword similarity and user behavior (e.g., "Researchers studying X also used Y").
*   **Interactive Scoring Previews:** A "Safe Mode" sandbox for public domain scales to demonstrate scoring logic without requiring a full manual download.

---

*Last Updated: 1.5.4*
