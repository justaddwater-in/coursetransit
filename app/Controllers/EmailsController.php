<?php
namespace CourseTransit\Controllers;

use CourseTransit\Services\EmailTemplate;
use CourseTransit\Emails\Mailer;

class EmailsController extends BaseController
{
    public function index()
    {
        $templates = get_option('coursetransit_email_templates', []);
        $defaults = EmailTemplate::getDefaultTemplates();
        $options = EmailTemplate::getTemplateOptions();

        $default_key = 'enrollment';
        $template = wp_parse_args(
            $templates[$default_key] ?? [],
            $defaults[$default_key] ?? [
                'subject' => '',
                'body' => '',
            ]
        );

        $this->render('emails/index', [
            'template' => $template,
            'template_options' => $options,
        ]);
    }
    public static function sendTest()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Unauthorized']);
        }

        $email = isset($_POST['email'])
            ? sanitize_email(wp_unslash($_POST['email']))
            : '';

        $subject = isset($_POST['subject'])
            ? sanitize_text_field(wp_unslash($_POST['subject']))
            : '';

        $body = isset($_POST['body'])
            ? wp_kses_post(wp_unslash($_POST['body']))
            : '';

        $template = isset($_POST['template'])
            ? sanitize_key(wp_unslash($_POST['template']))
            : 'enrollment';

        if (!$email || !$subject || !$body) {
            wp_send_json_error(['error' => 'Missing data']);
        }

        $settings = get_option('coursetransit_settings', []);

        $moodle_base = rtrim(
            $settings['moodle_url'] ?? '',
            '/'
        );

        // Sample replacements
        $sample = [
            '{first_name}' => 'John',
            '{last_name}' => 'Doe',
            '{email}' => $email,
            '{username}' => 'john1234',
            '{password}' => 'demo-pass',
            '{course_name}' => 'Sample Course',
            '{site_name}' => get_bloginfo('name'),
            '{login_url}' => esc_url($moodle_base . '/login/index.php'),
        ];

        /**
         * Allow add-ons to change test-email replacements without replacing
         * the Core email controller or AJAX action.
         */
        $sample = apply_filters(
            'coursetransit_email_test_replacements',
            $sample,
            $email,
            $template
        );

        $subject = str_replace(
            array_keys($sample),
            array_values($sample),
            $subject
        );

        $body = str_replace(
            array_keys($sample),
            array_values($sample),
            $body
        );

        $final = \CourseTransit\Services\EmailTemplate::wrap($body);

        $sent = \CourseTransit\Emails\Mailer::send(
            $email,
            $subject,
            $final
        );

        if (!$sent) {
            wp_send_json_error(['error' => 'wp_mail failed']);
        }

        wp_send_json_success([
            'message' => 'Email sent',
        ]);
    }
}
