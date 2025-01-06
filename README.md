# Network Site Details Plugin

## Overview
The **Network Site Details Plugin** is a WordPress multisite plugin designed to provide comprehensive insights into subsites within a network. It displays key information such as post counts, visitor statistics, and last activity details. The plugin also includes features for exporting data and visualizing insights via dynamic charts.

---

## Features

### 1. Subsite Table
- Displays a comprehensive overview of subsites in a multisite network.
- Key data includes:
  - **Subsite Name**
  - **Post Count**
  - **Visitor Count (Last 3 Months)**: Based on subsite-specific cookies.
  - **Last Updated Post Date**
  - **Last Login User Email**
- Sortable columns for easy data navigation.

### 2. Graphical Charts
- Provides visual insights into:
  - **Post Counts** across subsites.
  - **Visitor Counts** (last 3 months) across subsites.
- Built with [Chart.js](https://www.chartjs.org/).

### 3. Export to Excel
- Exports the subsite data to an Excel file.
- Data includes all fields visible in the Subsite Table.
- Compatible with Excel for further analysis and sharing.

---

## Installation

1. Download or clone the plugin repository.
2. Upload the plugin folder to `/wp-content/plugins/`.
3. Activate the plugin from the WordPress Network Admin panel.
4. Navigate to the **Dashboard** section in Network Admin to access the plugin's features.

---

## Usage

1. **Subsite Data Overview:**
   - Navigate to the **Dashboard** menu.
   - View and sort the table to explore details about each subsite.

2. **Visual Insights:**
   - Scroll down to the **Post Count Chart** and **Visitor Count Chart** to analyze trends and patterns.

3. **Export Data:**
   - Click the **Export to Excel** button to download subsite details for offline analysis.

---

## Technical Details

- **Visitor Count Calculation:**
  - Based on subsite-specific cookies (`COOKIEPATH` and `COOKIE_DOMAIN`).

- **Post Count Retrieval:**
  - Extracted directly from WordPress's built-in `wp_count_posts()` function.

- **Last Activity Details:**
  - **Post Updates:** Pulled using SQL queries on the `wp_posts` table.
  - **User Logins:** Extracted from the `wp_users` table.

---

## Requirements

- WordPress Multisite installation.
- PHP 7.4 or higher.
- Chart.js for data visualization (auto-included via CDN).

---

## License
This plugin is licensed under the **MBKM Team PuTI** license. For inquiries, contact the development team.

---

## Credits
Developed by the **MBKM Team** during the PuTI Internship Program at Telkom University.
