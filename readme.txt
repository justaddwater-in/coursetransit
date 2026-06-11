=== CourseTransit ===
Contributors: him-developer,justaddwater
Tags: moodle, woocommerce, lms, course sync, elearning
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Sync Moodle courses with WooCommerce directly from WordPress.

== Description ==

CourseTransit is a powerful integration plugin that syncs courses from Moodle into WordPress and WooCommerce.

It allows administrators to manage, sync, and sell Moodle courses directly through WooCommerce, making it ideal for LMS-based businesses.

Key features include:

* Sync courses from Moodle to WordPress
* Automatically create WooCommerce products for courses
* Instructor and course management dashboard
* AJAX-powered DataTables for fast performance
* Secure API handling with nonce protection
* Clean admin UI built with Shards Dashboard

CourseTransit is designed for performance, scalability, and seamless LMS + eCommerce integration.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to **CourseTransit** in the WordPress admin menu
4. Configure your Moodle API credentials in the settings tab
5. Start syncing courses

== Frequently Asked Questions ==

= Does this plugin require Moodle? =
Yes, you need access to a Moodle site with web services enabled.

= Does it work with WooCommerce? =
Yes, WooCommerce is required for product creation and course selling.

= Can I sync courses manually? =
Yes, you can sync courses individually or in bulk from the admin panel.

= Is it secure? =
Yes, all AJAX requests are nonce-protected and follow WordPress security standards.

== Screenshots ==

1. Dashboard overview
2. Courses listing with sync options
3. Instructor management panel
4. Settings page

== Changelog ==

= 1.3.0 =

* Initial release
* Moodle course sync
* WooCommerce integration
* Admin dashboard UI
* DataTables integration

== Upgrade Notice ==

= 1.3.0 =

Improved Moodle sync, WooCommerce integration, instructor management, and admin dashboard enhancements.

== External services ==

This plugin connects to a Moodle LMS website configured by the administrator to synchronize courses, enrollments, and related learning data.

Data sent:
- Moodle API token
- Course synchronization requests
- Enrollment-related requests

This data is only sent when the administrator configures Moodle integration and performs synchronization actions.

The external Moodle LMS website URL is determined by the administrator during plugin setup.

== Development ==

Source code:
https://github.com/justaddwater-in/coursetransit

== Credits ==

Developed by JustAddWater.
