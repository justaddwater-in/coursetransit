<?php
namespace CourseTransit\Services;

class EmailTemplate
{
  public static function wrap(string $content): string
  {
    $site = esc_html(get_bloginfo('name'));
    $site_url = esc_url(home_url());
    $year = wp_date('Y');

    /*
|--------------------------------------------------------------------------
| Logo / Site Icon Fallback
|--------------------------------------------------------------------------
|
| Priority:
| 1. Custom Logo
| 2. Site Icon
| 3. Site Name
|
*/

    $logo_html = "
    <span style='font-size:18px;font-weight:600;color:#3c3c3c;'>
        {$site}
    </span>
    ";

    /*
    |--------------------------------------------------------------------------
    | Try Custom Logo
    |--------------------------------------------------------------------------
    */
    $logo_id = get_theme_mod('custom_logo');

    if ($logo_id) {
      $logo = wp_get_attachment_image_src($logo_id, 'full');

      if (!empty($logo[0])) {

        $logo_url = esc_url($logo[0]);

        $logo_html = "
            <a href='{$site_url}'
               style='display:inline-block;text-decoration:none;'>

                <img src='{$logo_url}'
                     alt='{$site}'
                     style='max-height:60px;width:auto;display:block;border:0;'>

            </a>
        ";
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Fallback To Site Icon
    |--------------------------------------------------------------------------
    */ elseif (get_site_icon_url(512)) {

      $site_icon = esc_url(get_site_icon_url(512));

      $logo_html = "
        <a href='{$site_url}'
           style='display:inline-block;text-decoration:none;'>

            <img src='{$site_icon}'
                 alt='{$site}'
                 style='max-height:60px;width:auto;display:block;border:0;'>

        </a>
    ";
    }

    return "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>{$site}</title>
</head>

<body style='margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,sans-serif;'>

  <table width='100%' cellpadding='0' cellspacing='0' style='padding:30px 10px;'>
    <tr>
      <td align='center'>

        <!-- Main Container -->
        <table width='600' cellpadding='0' cellspacing='0'
               style='background:#ffffff;border:1px solid #e5e5e5;'>

          <!-- Header -->
        <tr>
            <td style='padding:24px 30px;border-bottom:1px solid #e5e5e5;text-align:center;'>
                {$logo_html}
            </td>
        </tr>

          <!-- Body -->
          <tr>
            <td style='padding:30px;font-size:14px;line-height:1.7;color:#3c3c3c;'>
              {$content}
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style='padding:20px 30px;border-top:1px solid #e5e5e5;font-size:12px;color:#777;text-align:center;'>

              <p style='margin:0 0 6px 0;'>
                Thanks for using <a href='{$site_url}' style='color:#3c3c3c;text-decoration:none;'>{$site}</a>
              </p>

              <p style='margin:0;'>
                &copy; {$year} {$site}. All rights reserved.

            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>";
  }
}