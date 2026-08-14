# CourseTransit

CourseTransit is a WooCommerce + Moodle integration plugin for WordPress that helps sync Moodle courses into WooCommerce products and automate enrollment workflows after purchase.

It is designed for online course businesses that want to:

* Sell Moodle courses through WooCommerce
* Sync Moodle courses into WordPress
* Manage instructors and enrollments
* Automate course access after successful orders
* Monitor order activity from the WordPress admin

---

# Features

## Moodle Integration

* Connect WordPress with Moodle using the dedicated CourseTransit Moodle plugin
* Generate secure connection tokens directly from the CourseTransit Moodle plugin
* Sync Moodle courses into WordPress
* Automatically create and link WooCommerce products during course synchronization
* Automatic enrollment after WooCommerce purchase

## WooCommerce Integration

* Attach Moodle courses to WooCommerce products
* Detect CourseTransit-enabled products in orders
* Process enrollments after successful payment
* Compatible with WooCommerce order lifecycle

## Admin Dashboard

* Custom admin dashboard built with Bootstrap/Shards UI
* Course overview and statistics
* Order insights
* Centralized plugin management panel

## Course Management

* Sync and manage Moodle courses
* View synced course records
* Associate courses with WooCommerce products
* Manage course metadata

## Instructor Management

* Instructor listing and management
* Instructor-course relationships
* Admin-side management workflow

## Email Templates

* Custom email template support
* Enrollment-related email handling
* Centralized email configuration

## Logging & Debugging

* Internal logging system
* Debug support using WordPress debug mode
* Enrollment and API troubleshooting support

---

# Requirements

## Minimum Requirements

* WordPress 6.0+
* PHP 7.4+
* WooCommerce installed and activated
* Moodle with the CourseTransit Moodle plugin installed

## Recommended

* Latest stable WooCommerce version
* PHP 8.1+
* HTTPS-enabled website

---

# Dependencies

* WordPress
* WooCommerce
* Moodle
* CourseTransit Moodle Plugin

---

# Installation

## Manual Installation

1. Download the plugin ZIP.
2. Go to WordPress Admin → Plugins → Add New.
3. Upload the plugin ZIP.
4. Activate the plugin.
5. Ensure WooCommerce is already installed and active.

## Development Installation

Clone or copy the plugin into:

```bash
wp-content/plugins/coursetransit
```

Activate the plugin from the WordPress admin.

---

# Moodle Setup

## CourseTransit Moodle Plugin

CourseTransit includes a dedicated Moodle plugin that simplifies the connection process between Moodle and WordPress.

The Moodle plugin provides:

* Secure token generation
* WordPress website registration
* Moodle-to-WordPress communication
* Course synchronization support
* Centralized integration management

After installing the Moodle plugin:

1. Register the WordPress website from Moodle
2. Generate the secure integration token automatically
3. Connect the WordPress plugin using the generated token
4. Start syncing courses

## Required Moodle Information

You will need:

* Moodle Site URL
* CourseTransit integration token

---

# Plugin Configuration

After activation:

1. Open:

```text
WordPress Admin → CourseTransit
```

2. Configure:

* Moodle URL
* CourseTransit Web Service Token
* Email settings

3. Save settings.

4. Test Moodle connection.

---

# Course Sync Workflow

## Sync Courses

The plugin can fetch Moodle courses and automatically create linked WooCommerce products inside WordPress.

Typical workflow:

1. Connect Moodle
2. Run course sync
3. Courses appear in CourseTransit admin panel
4. WooCommerce products are automatically created and linked
5. Course mappings are stored automatically

---

# WooCommerce Product Mapping

During synchronization, CourseTransit can automatically create WooCommerce products for Moodle courses.

The plugin automatically stores Moodle course relationships against WooCommerce products during synchronization.

Manual product mapping can still be used when needed.

When a customer purchases the linked product, the plugin processes Moodle enrollment automatically.

---

# Enrollment Flow

## Standard Enrollment Flow

1. Customer purchases a WooCommerce product
2. WooCommerce order is completed/processed
3. CourseTransit detects the mapped Moodle course
4. Moodle API enrollment request is sent
5. User receives access to the Moodle course

---

# Admin Sections

## Dashboard

Overview of:

* Orders
* Course statistics
* Enrollment information
* Activity summaries

## Courses

Manage synced Moodle courses.

## Instructors

Manage instructor records and mappings.

## Orders

View CourseTransit-related WooCommerce orders.

## Emails

Manage enrollment email templates.

## Settings

Configure Moodle connection.

---

# Plugin Architecture

## Main Structure

```text
coursetransit/
├── app/
│   ├── Controllers/
│   ├── Emails/
│   ├── Helpers/
│   ├── Models/
│   ├── Services/
│   ├── Support/
│   └── Views/
├── assets/
├── bootstrap/
├── languages/
├── coursetransit.php
└── uninstall.php
```

## Important Services

### MoodleClient

Handles Moodle API communication.

### MoodleEnrollmentService

Processes Moodle enrollments.

### CourseSyncService

Handles syncing Moodle courses into WordPress.

### ProductTab

Adds CourseTransit fields to WooCommerce products.

---

# Database & Storage

The plugin stores:

* Synced courses
* Instructor information
* Plugin settings
* Email templates
* Enrollment/order-related metadata

Custom tables may be created during plugin installation.

---

# Security Notes

The plugin includes:

* Nonce verification
* Sanitization and escaping
* WooCommerce dependency checks
* Safe AJAX handling
* WordPress coding standards improvements

---

# Uninstall Behavior

The uninstall routine can remove:

* Plugin options
* Custom database tables
* Stored plugin data

Always back up your database before uninstalling.

---

# Debugging

Enable WordPress debugging:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Logs may help diagnose:

* Moodle connection failures
* Enrollment issues
* Sync errors
* WooCommerce integration problems

---

# Development Notes

## Coding Standards

The plugin follows WordPress coding standards and is actively being improved for:

* PHPCS compliance
* Plugin Check compatibility
* Sanitization/security improvements
* Performance optimization

---

# Frequently Asked Questions

## Does this plugin require WooCommerce?

Yes. WooCommerce must be installed and activated.

## Does this plugin require Moodle?

Yes. A Moodle site with the CourseTransit Moodle plugin installed is required.

## Can I sell multiple Moodle courses?

Yes. Different WooCommerce products can map to different Moodle courses.

## Does the plugin support automatic enrollment?

Yes. Enrollment is triggered after WooCommerce order processing.

---

# Troubleshooting

## Moodle connection fails

Check:

* Moodle URL
* CourseTransit integration token
* CourseTransit Moodle plugin installed correctly
* Integration token is valid
* Website registration completed

## Enrollment not working

Check:

* WooCommerce order status
* Product-course mapping
* CourseTransit token permissions
* Debug logs

## Courses not syncing

Check:

* CourseTransit Moodle plugin connection
* Integration token validity
* Website registration status
* Server timeouts

---

# Changelog

## 1.4.3
### Enhancement
- Sync WooCommerce billing address to Moodle user profile

## 1.4.2

* Added: Demo Mode notice on the Select & Sync Courses screen to explicitly indicate that listed courses are dummy data for demonstration and testing purposes only.

## 1.4.1

* Added: Optional admin consent notice for receiving setup tips, changelog notifications, and license updates.
* Added: Consent preferences are only saved after an explicit admin action.
* Improved: Plugin stability and compatibility with the latest WordPress version.
* Improved: Code quality and WordPress Coding Standards compliance.
* Fixed: Minor bugs and performance improvements.

## 1.4.0

* Added dedicated Logs viewer in Settings with searchable DataTable and per-entry context viewer
* Added configurable log enable/disable toggle in Settings
* Added WooCommerce setup checklist modal shown automatically when required account settings are not configured
* Added default enrollment period selector in Settings (Sync tab)
* Added individual course re-sync support
* Fixed enrollment email template body not loading when switching between New User and Existing User templates
* Fixed email template admin preview showing stub body instead of full default template
* Improved enrollment email default template with richer HTML
* Refactored dashboard view
* Refactored Settings page into tabbed layout: General, Sync, and Logs
* Refactored CourseSyncService with chunked sync support and improved single-course sync
* Removed unused vendor folder and Composer dependencies

## 1.3.0

* Initial public release
* Moodle course synchronization
* WooCommerce product integration
* Automatic student enrollment after purchase
* Instructor management
* Enrollment email template customization
* Unified order and enrollment dashboard
* Activity logging and connection testing

---

# License

GPL v3 or later

```text
This plugin is licensed under the GNU General Public License v3.0.
```

---

# Author

JustAddWater

Website:
[https://justaddwater.in/](https://justaddwater.in/)