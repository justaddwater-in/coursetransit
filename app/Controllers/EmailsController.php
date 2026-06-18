<?php
namespace CourseTransit\Controllers;

use CourseTransit\Services\EmailTemplate;
use CourseTransit\Emails\Mailer;

class EmailsController extends BaseController
{
    public function index()
    {
        $templates = get_option('coursetransit_email_templates', []);

        $default_key = 'enrollment';

        $template = $templates[$default_key] ?? [
            'subject' => 'You are enrolled in {course_name}',
            'body' => '<p>Hi {first_name},</p>

            <p>
                We’re excited to let you know that you’ve been successfully enrolled in:
            </p>

            <p style="font-size: 16px;">
                <strong>{course_name}</strong>
            </p>

            <p>
                You can start learning immediately by logging into your dashboard here:
            </p>

            <p>
                <a href="{login_url}" target="_blank" rel="noopener">
                    Access your course
                </a>
            </p>

            <hr />

            <strong>Your account details:</strong>

            <ul>
                <li>Email: {email}</li>
                <li>Password: {password}</li>
            </ul>

            <p>
                If you have any questions, just reply to this email — we’re happy to help.
            </p>

            <p>
                Happy learning,<br>
                <strong>The {site_name} Team</strong>
            </p>

            <p style="font-size:12px;color:#6b7280;">
                If the button above doesn’t work, copy and paste this link into your browser:<br>
                {login_url}
            </p>'
        ];

        $this->render('emails/index', [
            'template' => $template,
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
