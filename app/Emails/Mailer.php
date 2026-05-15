<?php

namespace CourseTransit\Emails;

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

        return (bool) wp_mail($to, $subject, $html, $headers);
    }
}
