=== CourseTransit ===
Contributors: justaddwater
Tags: moodle, woocommerce, lms, elearning, course enrollment
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Connect Moodle with WooCommerce. Sync courses, sell them as WooCommerce products, and automatically enroll students after purchase.

== Description ==

**CourseTransit** is a WordPress plugin that bridges Moodle LMS and WooCommerce, letting you sell Moodle courses directly from your WordPress site and automatically enroll students the moment a purchase is completed.

No manual enrollment. No toggling between platforms. Just a clean, reliable Moodle–WooCommerce integration that handles the whole workflow for you.

🔗 [Learn more about CourseTransit](https://justaddwater.in/products/coursetransit-wordpress-moodle-integration/)

**How It Works**

1. Connect your Moodle site to WordPress using a secure token.
2. Sync your Moodle course catalog to WordPress in one click.
3. WooCommerce products are created and linked to each course automatically.
4. When a student purchases a course, CourseTransit enrolls them in Moodle instantly.

**Key Features**

* 🔄 **One-click Moodle course sync** — import course titles, descriptions, categories, and instructor details directly into WordPress
* 🛒 **Automatic WooCommerce product creation** — each synced Moodle course becomes a purchasable WooCommerce product
* 👨‍🎓 **Automated student enrollment** — students are enrolled in Moodle as soon as their WooCommerce order is marked complete
* 📧 **Customizable enrollment emails** — personalize notification templates with dynamic tags
* 👨‍🏫 **Instructor management** — manage instructor profiles, expertise, and course assignments from WordPress
* 📊 **Unified dashboard** — view revenue, orders, courses, and enrollments in one place
* 🔍 **Activity logs & connection testing** — built-in tools to monitor sync activity and troubleshoot connectivity
* 🔒 **Secure token-based communication** — all data between WordPress and Moodle is transmitted via authenticated API tokens
* ✨ **Clean admin UI** — purpose-built interface, not a generic settings dump

**Who Is This For?**

CourseTransit is built for anyone running Moodle as their LMS and WooCommerce as their storefront:

* 🎓 Online academies and e-learning platforms
* 👨‍🏫 Independent coaches and corporate trainers
* 🏫 Schools, universities, and training centers
* 🤝 NGOs and non-profit organizations
* 📜 Professional certification and compliance training providers

**Requirements**

* WordPress 6.0 or later
* WooCommerce (installed and active)
* A self-hosted Moodle site (any recent version)
* The free [CourseTransit Moodle companion plugin](https://moodle.org/plugins/auth_coursetransit)

== Installation ==

1. Upload the CourseTransit plugin to `/wp-content/plugins/` or install it directly from the WordPress plugin directory.
2. Activate the plugin from the **Plugins** menu in WordPress.
3. Make sure WooCommerce is installed and activated.
4. Install the free [CourseTransit Moodle plugin](https://moodle.org/plugins/auth_coursetransit) on your Moodle site.
5. Generate a secure connection token from within the Moodle plugin settings.
6. In WordPress, go to **CourseTransit → Settings** and enter your Moodle site URL and connection token.
7. Navigate to **Courses** and click **Sync Courses** to import your Moodle course catalog.
8. Review the imported courses and their linked WooCommerce products — you're ready to sell.

== Frequently Asked Questions ==

= What does CourseTransit do? =

CourseTransit integrates Moodle LMS with WooCommerce so you can sell Moodle courses from your WordPress site and automatically enroll students after purchase. It eliminates manual enrollment and keeps your course catalog in sync between both platforms.

= Do I need WooCommerce installed? =

Yes. CourseTransit uses WooCommerce to handle course sales and trigger automatic Moodle enrollments on successful orders.

= Do I need a companion Moodle plugin? =

Yes. CourseTransit requires the free [CourseTransit Moodle plugin](https://moodle.org/plugins/auth_coursetransit) for secure API communication and course synchronization. It's free and available on the Moodle plugin directory.

= Can I connect an existing Moodle site with existing courses? =

Yes. CourseTransit syncs courses from your existing Moodle installation — no need to recreate anything. Just connect, sync, and your courses are ready to sell.

= Do WordPress and Moodle need to be on the same server? =

No. They can run on the same server or on completely separate hosting environments, as long as both can communicate over HTTPS.

= How does automatic student enrollment work? =

When a WooCommerce order is marked complete, CourseTransit sends an enrollment request to your Moodle site via the API. The student receives access immediately — no manual intervention needed.

= What course data gets synchronized from Moodle? =

CourseTransit syncs course title, description, category, instructor information, and other supported course metadata from Moodle into WordPress.

= Can I re-sync courses after making changes in Moodle? =

Yes. You can run synchronization at any time to pull updated course information from Moodle into WordPress.

= Is any coding or technical setup required? =

No. CourseTransit provides a guided setup wizard and an intuitive admin interface. If you can configure a WooCommerce product, you can set up CourseTransit.

= Where can I find more information about CourseTransit? =

Visit the [CourseTransit product page](https://justaddwater.in/products/coursetransit-wordpress-moodle-integration/) for documentation, use cases, and support options.

== Screenshots ==

1. Dashboard with revenue, orders, course statistics, and recent activity.
2. Browse and manage synchronized Moodle courses with one-click actions.
3. Synchronize courses from Moodle with a single confirmation dialog.
4. View complete course details including curriculum and resources.
5. Quickly edit course information and linked WooCommerce product settings.
6. Manage instructors with profiles, expertise, and course assignments.
7. View WooCommerce orders and enrollment details in one unified view.
8. Customize enrollment email templates using dynamic tags.
9. Configure Moodle connection settings and test API connectivity.
10. Configure synchronization settings including enrollment period, product status, content sync, and missing course behaviour.


== Changelog ==

= 1.4.0 =
 
* Added dedicated Logs viewer in Settings with searchable DataTable and per-entry context viewer
* Added configurable log enable/disable toggle in Settings
* Added WooCommerce setup checklist modal — shown automatically when required account settings are not configured
* Added default enrollment period selector in Settings (Sync tab)
* Added individual course re-sync support
* Fixed enrollment email template body not loading when switching between New User and Existing User templates
* Fixed email template admin preview showing a generic stub body instead of the full default template
* Improved enrollment email default template with richer HTML — course name, login link, and account credentials formatted correctly
* Refactored dashboard view — removed duplicate WooCommerce checklist (now handled by the setup modal)
* Refactored Settings page into tabbed layout: General, Sync, and Logs
* Refactored CourseSyncService with chunked sync support and improved single-course sync
 
= 1.3.0 =

* Initial public release
* Moodle course synchronization
* WooCommerce product integration
* Automatic student enrollment after purchase
* Instructor management
* Enrollment email template customization
* Unified order and enrollment dashboard
* Activity logging and connection testing

== Upgrade Notice ==

= 1.4.0 =
 
This update fixes enrollment email delivery issues and improves the email template editor. The Settings page has been reorganised into tabs. A WooCommerce setup checklist modal is now shown automatically if required account settings are not configured. Upgrade recommended for all users.

= 1.3.0 =

Initial public release of CourseTransit.

== External Services ==

This plugin communicates with a Moodle LMS website configured by the site administrator to synchronize courses, process enrollments, and exchange related learning data.

**Data sent to the external Moodle site:**

* Moodle REST API token (for authentication)
* Course synchronization requests
* Student enrollment requests

This data is only transmitted when the administrator has configured Moodle integration and explicitly initiates synchronization or when a WooCommerce order triggers an enrollment. The Moodle site URL is entered by the administrator during plugin setup.

== Development ==

Source code is available on GitHub:
[https://github.com/justaddwater-in/coursetransit](https://github.com/justaddwater-in/coursetransit)

== Credits ==

Developed by [JustAddWater](https://justaddwater.in/) — a web agency specializing in Moodle LMS consulting, WooCommerce development, and e-learning integrations.

Learn more about CourseTransit: [https://justaddwater.in/products/coursetransit-wordpress-moodle-integration/](https://justaddwater.in/products/coursetransit-wordpress-moodle-integration/)