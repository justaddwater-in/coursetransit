<?php
namespace CourseTransit\Services;

use CourseTransit\Support\Logger;
use CourseTransit\Services\EmailTemplate;
use CourseTransit\Emails\Mailer;

class MoodleEnrollmentService
{
    public static function enroll(string $email, string $first, string $last, int $product_id): void
    {
        global $wpdb;

        Logger::log('ENROLL START', [
            'email' => $email,
            'product_id' => $product_id,
        ]);

        $settings = get_option('coursetransit_settings', []);
        if (empty($settings['moodle_url']) || empty($settings['moodle_token'])) {
            Logger::log('ENROLLMENT FAILED', 'Moodle settings missing');
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $course = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT moodle_id FROM " . esc_sql($wpdb->prefix . 'coursetransit_courses') . " WHERE wc_product_id = %d",
                $product_id
            )
        );

        if (!$course) {
            Logger::log('ENROLLMENT FAILED', 'No course mapping found');
            return;
        }

        $moodle_url = rtrim($settings['moodle_url'], '/');
        $token = $settings['moodle_token'];

        $username = strtolower(trim($email));
        $password = null;

        // ---------------------------------
        // CHECK IF USER EXISTS FIRST
        // ---------------------------------
        $existing = self::call($moodle_url, $token, 'core_user_get_users_by_field', [
            'field' => 'email',
            'values' => [$email],
        ]);

        $user_id = null;

        if (isset($existing[0]['id'])) {

            $user_id = (int) $existing[0]['id'];

            Logger::log('USER FOUND (SKIP CREATE)', [
                'email' => $email,
                'user_id' => $user_id
            ]);

        } else {

            // ---------------------------------
            // CREATE USER
            // ---------------------------------
            $password = self::generateStrongPassword();

            $created = self::call($moodle_url, $token, 'core_user_create_users', [
                'users' => [
                    [
                        'username' => $username,
                        'password' => $password,
                        'email' => $email,
                        'firstname' => $first ?: 'Student',
                        'lastname' => $last ?: 'User',
                        'auth' => 'manual',
                    ]
                ]
            ]);

            if (isset($created[0]['id'])) {
                $user_id = (int) $created[0]['id'];
            } elseif (
                isset($created['success']) &&
                $created['success'] === true &&
                !empty($created['data'][0]['id'])
            ) {
                $user_id = (int) $created['data'][0]['id'];
            } else {
                Logger::log('USER CREATE FAILED RESPONSE', $created);
            }
        }

        // ---------------------------------
        // FALLBACK: Lookup
        // ---------------------------------
        if (!$user_id) {

            Logger::log('CREATE FAILED → LOOKUP', ['email' => $email]);

            $existing = self::call($moodle_url, $token, 'core_user_get_users_by_field', [
                'field' => 'email',
                'values' => [$email],
            ]);

            if (
                isset($existing['success']) &&
                $existing['success'] === true &&
                !empty($existing['data'][0]['id'])
            ) {
                $user_id = (int) $existing['data'][0]['id'];
            } elseif (isset($existing[0]['id'])) {
                $user_id = (int) $existing[0]['id'];
            } else {
                Logger::log('USER LOOKUP FAILED RESPONSE', $existing);
            }
        }

        if (!$user_id) {
            Logger::log('ENROLLMENT FAILED', 'User creation failed');
            return;
        }

        // ---------------------------------
        // COURSE DURATION
        // ---------------------------------
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $courseDates = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT start_date, end_date FROM " . esc_sql($wpdb->prefix . 'coursetransit_courses') . " WHERE moodle_id = %d",
                $course->moodle_id
            )
        );

        // ---------------------------------
        // ENROLLMENT PERIOD
        // ---------------------------------

        $product_enrollment = get_post_meta(
            $product_id,
            '_coursetransit_enrollment_period',
            true
        );

        $product_enrollment = $product_enrollment !== ''
            ? (int) $product_enrollment
            : null;

        $default_enrollment = (int) (
            $settings['default_enrollment_period']
            ?? 0
        );

        // Product setting takes priority
        $enrollment_days = $product_enrollment !== null
            ? $product_enrollment
            : $default_enrollment;

        $timestart = time();

        $enrolment = [
            'roleid' => 5,
            'userid' => $user_id,
            'courseid' => $course->moodle_id,
            'timestart' => $timestart,
        ];

        // Add timeend only when not lifetime
        if ($enrollment_days > 0) {

            $enrolment['timeend'] = strtotime(
                "+{$enrollment_days} days",
                $timestart
            );

        }

        $enrol_response = self::call(
            $moodle_url,
            $token,
            'enrol_manual_enrol_users',
            [
                'enrolments' => [
                    $enrolment
                ]
            ]
        );

        if (
            (isset($enrol_response['success']) && $enrol_response['success'] === true)
            || $enrol_response === null
            || $enrol_response === []
        ) {
            Logger::log('ENROLLMENT SUCCESS', [
                'user_id' => $user_id,
                'course_id' => $course->moodle_id,
            ]);
        } else {
            Logger::log('ENROLLMENT FAILED', [
                'response' => $enrol_response
            ]);
            return;
        }

        // ---------------------------------
        // EMAIL
        // ---------------------------------
        $product = wc_get_product($product_id);
        $course_name = $product ? $product->get_name() : 'Your course';
        $is_new_user = !empty($password);

        self::sendEnrollmentEmail(
            $email,
            $first,
            $last,
            $username,
            $password ?? '',
            $course_name,
            $is_new_user
        );
    }

    protected static function call(
        string $base,
        string $token,
        string $function,
        array $params = []
    ) {

        $endpoint =
            rtrim(
                $base,
                '/'
            ) .
            '/webservice/rest/server.php';

        $payload =
            array_merge(
                [
                    'siteurl' =>
                        site_url(),
                ],
                $params
            );

        $response =
            wp_remote_post(
                $endpoint,
                [
                    'timeout' => 90,
                    'sslverify' => true,

                    'headers' => [
                        'Accept' =>
                            'application/json',

                        'Origin' =>
                            site_url(),

                        'Referer' =>
                            site_url(),
                    ],

                    'body' => [
                        'wstoken' =>
                            $token,

                        'wsfunction' =>
                            'auth_coursetransit_execute_action',

                        'moodlewsrestformat' =>
                            'json',

                        'function' =>
                            $function,

                        'payload' =>
                            wp_json_encode(
                                $payload
                            ),
                    ],
                ]
            );

        if (
            is_wp_error(
                $response
            )
        ) {
            return [
                'success' => false,
                'error' =>
                    $response
                        ->get_error_message(),
            ];
        }

        $status =
            wp_remote_retrieve_response_code(
                $response
            );

        $body =
            wp_remote_retrieve_body(
                $response
            );

        if ($status !== 200) {
            return [
                'success' => false,
                'error' =>
                    'Connection failed',
                'raw' =>
                    $body,
            ];
        }

        $json =
            json_decode(
                $body,
                true
            );

        if (
            !is_array(
                $json
            )
        ) {
            return [
                'success' => false,
                'error' =>
                    'Invalid JSON response',
                'raw' =>
                    $body,
            ];
        }

        // Native Moodle exception.
        if (
            !empty(
            $json['exception']
        )
        ) {
            return [
                'success' => false,
                'error' =>
                    $json['message']
                    ?? 'Moodle error',
            ];
        }

        // CourseTransit wrapper failure.
        if (
            empty(
            $json['success']
        )
        ) {

            $errordata = [];

            if (
                !empty(
                $json['data']
            )
            ) {
                $errordata =
                    json_decode(
                        $json['data'],
                        true
                    );
            }

            return [
                'success' => false,
                'error' =>
                    $errordata['message']
                    ?? $errordata['error']
                    ?? 'Request failed',
            ];
        }

        // Decode wrapped Moodle response.
        $decoded = null;

        if (
            !empty(
            $json['data']
        )
        ) {
            $decoded =
                json_decode(
                    $json['data'],
                    true
                );
        }

        // Moodle sometimes returns null on success
        // (manual enrol/unenrol etc.)
        if (
            $json['data'] === 'null'
            || $decoded === null
        ) {
            return null;
        }

        return $decoded;
    }

    protected static function sendEnrollmentEmail(
        string $email,
        string $first,
        string $last,
        string $username,
        ?string $password,
        string $course_name,
        bool $is_new_user
    ): void {

        $templates = get_option('coursetransit_email_templates', []);

        /*
        |--------------------------------------------------------------------------
        | Default Templates
        |--------------------------------------------------------------------------
        */
        $defaultTemplates = [

            /*
            |--------------------------------------------------------------------------
            | NEW USER TEMPLATE
            |--------------------------------------------------------------------------
            */
            'enrollment' => [

                'subject' => 'You are enrolled in {course_name}',

                'body' => '
                <p>Hi {first_name},</p>

                <p>
                    We’re excited to let you know that you’ve been successfully enrolled in:
                </p>
                <p style="font-size: 16px;"><strong>{course_name}</strong></p>
                You can start learning immediately by logging into your dashboard here:

                <a href="{login_url}" target="_blank" rel="noopener"> Access your course </a>

                <hr />

                <strong>Your account details:</strong>
                <ul>
                    <li>Email: {email}</li>
                    <li>Password: {password}</li>
                </ul>
                If you have any questions, just reply to this email — we’re happy to help.

                Happy learning,
                <strong>The {site_name} Team</strong>
                <p style="font-size: 12px; color: #6b7280;">If the button above doesn’t work, copy and paste this link into your browser:
                {login_url}</p>
            '
            ],

            /*
            |--------------------------------------------------------------------------
            | EXISTING USER TEMPLATE
            |--------------------------------------------------------------------------
            */
            'enrollment_existing' => [

                'subject' => 'You are enrolled in {course_name}',

                'body' => '
                <p>Hi {first_name},</p>

                <p>
                    You have been successfully added to the course:
                </p>
                <p style="font-size: 16px;"><strong>{course_name}</strong></p>
                <p>
                    You can continue learning by logging into your dashboard:
                </p>
                <a href="{login_url}" target="_blank" rel="noopener"> Go to your dashboard </a>

                <p>
                    Happy learning,
                    <strong>The {site_name} Team</strong>
                </p>
            '
            ]
        ];

        /*
        |--------------------------------------------------------------------------
        | Select Template
        |--------------------------------------------------------------------------
        */
        $templateKey = $is_new_user
            ? 'enrollment'
            : 'enrollment_existing';

        /*
        |--------------------------------------------------------------------------
        | Merge Saved Template With Defaults
        |--------------------------------------------------------------------------
        */
        $template = wp_parse_args(
            $templates[$templateKey] ?? [],
            $defaultTemplates[$templateKey]
        );

        /*
        |--------------------------------------------------------------------------
        | Moodle Login URL
        |--------------------------------------------------------------------------
        */
        $settings = get_option('coursetransit_settings', []);

        $moodle_base = rtrim(
            (string) ($settings['moodle_url'] ?? ''),
            '/'
        );

        $login_url = $moodle_base . '/login/index.php';

        /*
        |--------------------------------------------------------------------------
        | Placeholder Replacements
        |--------------------------------------------------------------------------
        */
        $replacements = [
            '{first_name}' => $first,
            '{last_name}' => $last,
            '{email}' => $email,
            '{username}' => $username,
            '{password}' => $password ?? '',
            '{course_name}' => $course_name,
            '{site_name}' => get_bloginfo('name'),
            '{login_url}' => $login_url,
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Templates
        |--------------------------------------------------------------------------
        */
        $subjectTemplate = trim(
            (string) ($template['subject'] ?? '')
        );

        $bodyTemplate = trim(
            (string) ($template['body'] ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | Replace Variables
        |--------------------------------------------------------------------------
        */
        $subject = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $subjectTemplate
        );

        $body = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $bodyTemplate
        );

        /*
        |--------------------------------------------------------------------------
        | Send Email
        |--------------------------------------------------------------------------
        */
        Mailer::send(
            $email,
            $subject,
            EmailTemplate::wrap($body)
        );
    }


    protected static function generateStrongPassword(): string
    {
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '*-#@!$%^&';

        // Ensure at least one of each
        $password = [
            $lower[wp_rand(0, strlen($lower) - 1)],
            $upper[wp_rand(0, strlen($upper) - 1)],
            $numbers[wp_rand(0, strlen($numbers) - 1)],
            $special[wp_rand(0, strlen($special) - 1)],
        ];

        // Fill remaining length
        $all = $lower . $upper . $numbers . $special;

        for ($i = 0; $i < 8; $i++) {
            $password[] = $all[wp_rand(0, strlen($all) - 1)];
        }

        // Secure shuffle
        usort($password, static function () {
            return wp_rand(-1, 1);
        });

        return implode('', $password);
    }
}