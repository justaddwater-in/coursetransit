<?php
namespace CourseTransit\Services;

class EmailTemplate
{
    /**
     * Core email templates. Add-ons extend this registry through filters.
     */
    public static function getDefaultTemplates(): array
    {
        $templates = [
            'enrollment' => [
                'subject' => 'You are enrolled in {course_name}',
                'body' => '<p>Hi {first_name},</p><p>We’re excited to let you know that you’ve been successfully enrolled in:</p><p style="font-size:16px;"><strong>{course_name}</strong></p><p>You can start learning immediately by logging into your dashboard here:</p><p><a href="{login_url}" target="_blank" rel="noopener">Access your course</a></p><hr><p><strong>Your account details:</strong></p><ul><li>Email: {email}</li><li>Password: {password}</li></ul><p>If you have any questions, just reply to this email — we’re happy to help.</p><p>Happy learning,<br><strong>The {site_name} Team</strong></p>',
            ],
            'enrollment_existing' => [
                'subject' => 'You are enrolled in {course_name}',
                'body' => '<p>Hi {first_name},</p><p>You have been successfully added to the course:</p><p style="font-size:16px;"><strong>{course_name}</strong></p><p>You can continue learning by logging into your dashboard:</p><p><a href="{login_url}" target="_blank" rel="noopener">Go to your dashboard</a></p><p>Happy learning,<br><strong>The {site_name} Team</strong></p>',
            ],
        ];

        return apply_filters('coursetransit_email_template_defaults', $templates);
    }

    /** Template labels used by the Core editor. */
    public static function getTemplateOptions(): array
    {
        $options = [
            'enrollment' => 'Enrollment Email (New User)',
            'enrollment_existing' => 'Enrollment Email (Existing User)',
        ];

        return apply_filters('coursetransit_email_template_options', $options);
    }

    /**
     * Dynamic tags for a specific template. The Core list is contextual so
     * the editor does not show irrelevant or duplicate tags.
     */
    public static function getTemplateTags(string $template_key = 'enrollment'): array
    {
        $tags = [
            '{first_name}' => 'First name',
            '{last_name}' => 'Last name',
            '{email}' => 'Email',
            '{course_name}' => 'Course name',
            '{site_name}' => 'Site name',
            '{login_url}' => 'Login URL',
        ];

        if ($template_key === 'enrollment') {
            $tags = [
                '{first_name}' => 'First name',
                '{last_name}' => 'Last name',
                '{email}' => 'Email',
                '{username}' => 'Username',
                '{password}' => 'Password',
                '{course_name}' => 'Course name',
                '{site_name}' => 'Site name',
                '{login_url}' => 'Login URL',
            ];
        }

        return apply_filters('coursetransit_email_template_tags', $tags, $template_key);
    }

    public static function wrap(string $content): string
    {
        $site = esc_html(get_bloginfo('name'));
        $site_url = esc_url(home_url());
        $year = wp_date('Y');
        $logo_html = "<span style='font-size:18px;font-weight:600;color:#3c3c3c;'>{$site}</span>";

        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $logo = wp_get_attachment_image_src($logo_id, 'full');
            if (!empty($logo[0])) {
                $logo_url = esc_url($logo[0]);
                $logo_html = "<a href='{$site_url}' style='display:inline-block;text-decoration:none;'><img src='{$logo_url}' alt='{$site}' style='max-height:60px;width:auto;display:block;border:0;'></a>";
            }
        } elseif (get_site_icon_url(512)) {
            $site_icon = esc_url(get_site_icon_url(512));
            $logo_html = "<a href='{$site_url}' style='display:inline-block;text-decoration:none;'><img src='{$site_icon}' alt='{$site}' style='max-height:60px;width:auto;display:block;border:0;'></a>";
        }

        return "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>{$site}</title></head><body style='margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,sans-serif;'><table width='100%' cellpadding='0' cellspacing='0' style='padding:30px 10px;'><tr><td align='center'><table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff;border:1px solid #e5e5e5;'><tr><td style='padding:24px 30px;border-bottom:1px solid #e5e5e5;text-align:center;'>{$logo_html}</td></tr><tr><td style='padding:30px;font-size:14px;line-height:1.7;color:#3c3c3c;'>{$content}</td></tr><tr><td style='padding:20px 30px;border-top:1px solid #e5e5e5;font-size:12px;color:#777;text-align:center;'><p style='margin:0 0 6px 0;'>Thanks for using <a href='{$site_url}' style='color:#3c3c3c;text-decoration:none;'>{$site}</a></p><p style='margin:0;'>&copy; {$year} {$site}. All rights reserved.</p></td></tr></table></td></tr></table></body></html>";
    }
}
