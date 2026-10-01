<?php
/**
 * CourseTransit Email Templates editor.
 * Free owns the page; Pro extends the template registry and tags through filters.
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variables supplied by the view context.
$template_options = $template_options ?? \CourseTransit\Services\EmailTemplate::getTemplateOptions();
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable.
$selected_key = 'enrollment';
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable.
$tags = \CourseTransit\Services\EmailTemplate::getTemplateTags($selected_key);
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable.
$tag_groups = [
    'Recipient' => ['{first_name}', '{last_name}', '{email}', '{username}', '{password}'],
    'Course & Bundle' => ['{course_name}', '{bundle_name}', '{course_count}', '{course_list}'],
    'Account & Site' => ['{site_name}', '{login_url}', '{set_password_url}'],
];
?>

<!-- PAGE HEADER -->
<div class="page-header row no-gutters py-4 ct-page-header">
    <div class="col-12 text-sm-left mb-0">
        <h3 class="page-title">
            Email Templates
        </h3>
        <p class="ct-email-page-description">
            Customize the emails learners receive after enrollment.
        </p>
    </div>
</div>

<!-- EMAIL TEMPLATE CARD -->
<div class="row">
    <div class="col-12">
        <div class="card card-small ct-email-card">
            <div class="card-header border-bottom ct-card-header ct-email-card-header">
                <div class="row no-gutters align-items-center">
                    <div class="col-12 col-md-7">
                        <h6 class="m-0">Template Settings</h6>
                        <small class="ct-email-card-help">Select an email type and customize its subject and content.</small>
                    </div>
                    <div class="col-12 col-md-5 mt-3 mt-md-0 d-flex align-items-center justify-content-md-end">
                        <label class="ct-email-template-label" for="coursetransit-template-select">Template</label>
                        <select id="coursetransit-template-select" class="form-control ct-email-template-select">
                            <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local loop variables.
                            foreach ($template_options as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $selected_key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <form id="coursetransit-email-form">
                <div class="ct-email-workspace">
                    <aside class="ct-email-tags-panel">
                        <div class="ct-email-section-heading">
                            <h6 class="m-0">Dynamic Tags</h6>
                            <p>Click a tag to insert it into the subject or email content.</p>
                        </div>

                        <div id="coursetransit-tag-groups">
                            <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local loop variables.
                            foreach ($tag_groups as $group => $group_tags) :
                                $coursetransit_available = array_intersect_key(array_flip($group_tags), $tags);
                                if (!$coursetransit_available) {
                                    continue;
                                }
                            ?>
                                <section class="ct-tag-group">
                                    <h6><?php echo esc_html($group); ?></h6>
                                    <div class="ct-tag-list">
                                        <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local loop variable.
                                        foreach (array_keys($coursetransit_available) as $tag) : ?>
                                            <button type="button" class="ct-tag-chip coursetransit-tag" data-tag="<?php echo esc_attr($tag); ?>">
                                                <span><?php echo esc_html($tags[$tag]); ?></span>
                                                <code><?php echo esc_html($tag); ?></code>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endforeach; ?>
                        </div>

                        <div class="ct-email-tag-note">
                            <span class="material-icons" aria-hidden="true">info_outline</span>
                            <span>Tags are replaced automatically when the email is sent.</span>
                        </div>
                    </aside>

                    <section class="ct-email-editor-panel">
                        <div class="ct-email-field">
                            <div class="ct-email-field-label">
                                <label for="coursetransit-subject">Email Subject</label>
                                <span id="ct-subject-count">0 / 180</span>
                            </div>
                            <input
                                type="text"
                                id="coursetransit-subject"
                                name="subject"
                                class="form-control"
                                value="<?php echo esc_attr($template['subject']); ?>"
                                maxlength="180"
                                placeholder="You are enrolled in {course_name}"
                            >
                        </div>

                        <div class="ct-email-field ct-email-content-field">
                            <div class="ct-email-field-label">
                                <label>Email Content</label>
                                <span>Visual / Code editor</span>
                            </div>
                            <div class="ct-email-editor-shell">
                                <?php
                                wp_editor($template['body'], 'body', [
                                    'textarea_name' => 'body',
                                    'media_buttons' => false,
                                    'teeny' => false,
                                    'textarea_rows' => 16,
                                    'editor_height' => 390,
                                ]);
                                ?>
                            </div>
                        </div>

                        <div class="ct-email-footer">
                            <div class="ct-email-status" id="coursetransit-template-status">Editing default template</div>
                            <div class="ct-email-actions">
                                <button type="button" id="coursetransit-preview" class="button">Preview</button>
                                <button type="button" id="coursetransit-load-default" class="button">Use Default</button>
                                <button type="button" id="coursetransit-send-test" class="button">Send Test Email</button>
                                <button type="submit" class="button button-primary">Save Template</button>
                            </div>
                        </div>
                    </section>
                </div>
            </form>
        </div>
    </div>
</div>
