<?php

namespace CourseTransit\Emails;

use CourseTransit\Support\Logger;

class Mailer
{
    public static function send(string $to, string $subject, string $html): bool
    {
        $subject = wp_strip_all_tags($subject);

        $from_name = get_bloginfo('name');
        $from_email = 'no-reply@' . wp_parse_url(home_url(), PHP_URL_HOST);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$from_name} <{$from_email}>",
        ];

        $headers = apply_filters('coursetransit_mail_headers', $headers, $to, $subject, $html);

        $mail_error = null;
        $failed_callback = static function ($error) use (&$mail_error): void {
            if ($error instanceof \WP_Error) {
                $mail_error = $error;
            }
        };

        add_action('wp_mail_failed', $failed_callback);
        $sent = (bool) wp_mail($to, $subject, $html, $headers);
        remove_action('wp_mail_failed', $failed_callback);

        if (!$sent) {
            Logger::error('EMAIL SEND FAILED', [
                'recipient_domain' => sanitize_text_field((string) wp_parse_url('mailto:' . $to, PHP_URL_HOST)),
                'subject' => $subject,
                'error' => $mail_error instanceof \WP_Error ? $mail_error->get_error_message() : 'wp_mail returned false',
            ]);
            return false;
        }

        Logger::success('EMAIL HANDOFF SUCCESS', [
            'recipient_domain' => sanitize_text_field((string) wp_parse_url('mailto:' . $to, PHP_URL_HOST)),
            'subject' => $subject,
        ]);

        return true;
    }
}
