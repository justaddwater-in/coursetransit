<?php
/**
 * Emails Settings Page
 *
 * - Displays email templates settings
 * - Save handled via AJAX (coursetransit_save_email_template)
 * - Test email handled via AJAX (coursetransit_send_test_email)
 * - Load default template via AJAX (coursetransit_load_default_template)
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="page-header row no-gutters py-4" style="padding-top: 0 !important; padding-bottom: 0 !important;">
    <div class="col-12 col-sm-6">
        <h3 class="page-title mb-1">Email Templates</h3>
    </div>

    
</div>


<div class="card shadow-sm" style="max-width: 100% !important;">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div class="d-flex align-items-center">
                <span class="text-muted small me-2">Template:</span>

                <select id="coursetransit-template-select" class="custom-select custom-select-sm"
                    style="max-width: 260px;">
                    <option value="enrollment" selected>Enrollment Email (New User)</option>
                    <option value="enrollment_existing">Enrollment Email (Existing User)</option>
                </select>
            </div>

        </div>

        <form id="coursetransit-email-form">

            <div class="row">

                <!-- LEFT: Tags -->
                <div class="col-12 col-lg-4 my-3">

                    <div class="card border shadow-sm h-100">
                        <div class="card-body p-3">
                            <h6 class="mb-3 font-weight-semibold">Dynamic Tags</h6>

                            <ul class="list-group list-group-flush">

                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center coursetransit-tag"
                                    data-tag="{first_name}">
                                    <span>First name</span>
                                    <code>{first_name}</code>
                                </li>

                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center coursetransit-tag"
                                    data-tag="{last_name}">
                                    <span>Last name</span>
                                    <code>{last_name}</code>
                                </li>

                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center coursetransit-tag"
                                    data-tag="{email}">
                                    <span>Email</span>
                                    <code>{email}</code>
                                </li>

                                <!-- <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center coursetransit-tag"
                                    data-tag="{username}">
                                    <span>Username</span>
                                    <code>{username}</code>
                                </li> -->

                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center coursetransit-tag"
                                    data-tag="{password}">
                                    <span>Password</span>
                                    <code>{password}</code>
                                </li>

                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center coursetransit-tag"
                                    data-tag="{course_name}">
                                    <span>Course name</span>
                                    <code>{course_name}</code>
                                </li>

                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center coursetransit-tag"
                                    data-tag="{site_name}">
                                    <span>Site name</span>
                                    <code>{site_name}</code>
                                </li>

                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center coursetransit-tag"
                                    data-tag="{login_url}">
                                    <span>Login URL</span>
                                    <code>{login_url}</code>
                                </li>

                            </ul>

                            <p class="text-muted small mt-3 mb-0">
                                Click a tag to insert it into subject or content.
                            </p>
                        </div>
                    </div>

                </div>

                <!-- RIGHT: Editor -->
                <div class="col-12 col-lg-8 my-3">

                    <div class="form-group mb-3">
                        <label class="font-weight-semibold small ct-font">Email Subject</label>
                        <input type="text" id="coursetransit-subject" name="subject"
                            value="<?php echo esc_attr($template['subject']); ?>" class="form-control"
                            placeholder="You’re enrolled in {course_name}">
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-semibold small ct-font">Email Content</label>

                        <div class="border rounded">
                            <?php
                            wp_editor(
                                $template['body'],
                                'body',
                                [
                                    'textarea_name' => 'body',
                                    'media_buttons' => true,
                                    'teeny' => false,
                                    'textarea_rows' => 18,
                                    'editor_height' => 360,
                                ]
                            );
                            ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" id="coursetransit-load-default" class="btn btn-outline-secondary">
                            Use Default Template
                        </button>

                        <div>
                            <button type="button" id="coursetransit-send-test" class="button button-secondary me-2">
                                Send Test Email
                            </button>

                            <button type="submit" class="button button-primary px-4">
                                Save
                            </button>
                        </div>
                    </div>



                </div>
            </div>

        </form>

    </div>
</div>