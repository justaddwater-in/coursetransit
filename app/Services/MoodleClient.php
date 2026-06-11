<?php
namespace CourseTransit\Services;
use CourseTransit\Support\Logger;

class MoodleClient
{
    protected string $baseUrl;
    protected string $token;

    public function __construct(string $baseUrl, string $token)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
    }

    public function fetchCourses(): array
    {
        $endpoint = rtrim($this->baseUrl, '/') . '/webservice/rest/server.php';

        $payload = [
            'siteurl' => site_url(),
        ];

        $response = wp_remote_post($endpoint,
            [
                'timeout' => 30,
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
                        $this->token,

                    'wsfunction' =>
                        'auth_coursetransit_execute_action',

                    'moodlewsrestformat' =>
                        'json',

                    'function' =>
                        'core_course_get_courses',

                    'payload' =>
                        wp_json_encode(
                            $payload
                        ),
                ],
            ]
        );

        if (\is_wp_error($response)) {
            throw new \Exception(
                esc_html(
                    $response->get_error_message()
                )
            );
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status !== 200) {
            throw new \Exception(
                'Connection failed'
            );
        }

        $json = json_decode($body, true);

        if (!is_array($json)) {
            throw new \Exception(
                'Invalid JSON response from Moodle'
            );
        }

        // Moodle webservice exception.
        if (!empty($json['exception'])) {
            throw new \Exception(
                esc_html(
                    $json['message']
                    ?? 'Moodle error'
                )
            );
        }

        // CourseTransit wrapper error.
        if (empty($json['success'])) {

            $errordata = [];

            if (!empty($json['data'])) {
                $errordata =
                    json_decode(
                        $json['data'],
                        true
                    );
            }

            throw new \Exception(
                esc_html(
                    $errordata['message']
                    ?? 'Course fetch failed'
                )
            );
        }

        // Decode wrapped data.
        $courses = [];

        if (!empty($json['data'])) {
            $courses =
                json_decode(
                    $json['data'],
                    true
                );
        }

        if (!is_array($courses)) {
            throw new \Exception(
                'Invalid course response'
            );
        }

        return $courses;
    }

    public function fetchCourseContents(
        int $courseId
    ): array {

        $endpoint =
            rtrim(
                $this->baseUrl,
                '/'
            ) .
            '/webservice/rest/server.php';

        $payload = [
            'siteurl' =>
                site_url(),

            'courseid' =>
                $courseId,
        ];

        $response = wp_remote_post(
            $endpoint,
            [
                'timeout' => 30,
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
                        $this->token,

                    'wsfunction' =>
                        'auth_coursetransit_execute_action',

                    'moodlewsrestformat' =>
                        'json',

                    'function' =>
                        'core_course_get_contents',

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
            Logger::log(
                'fetchCourseContents WP_ERROR',
                $response
                    ->get_error_message()
            );

            return [];
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

            Logger::log(
                'fetchCourseContents HTTP_ERROR',
                [
                    'status' =>
                        $status,

                    'body' =>
                        $body,
                ]
            );

            return [];
        }

        $json = json_decode(
            $body,
            true
        );

        if (
            !is_array(
                $json
            )
        ) {
            Logger::log(
                'fetchCourseContents INVALID_JSON',
                $body
            );

            return [];
        }

        // Moodle exception.
        if (
            !empty(
                $json['exception']
            )
        ) {

            Logger::log(
                'fetchCourseContents MOODLE_EXCEPTION',
                $json
            );

            return [];
        }

        // CourseTransit wrapper error.
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

            Logger::log(
                'fetchCourseContents BLOCKED',
                $errordata
            );

            return [];
        }

        // Decode wrapped Moodle data.
        $contents = [];

        if (
            !empty(
                $json['data']
            )
        ) {
            $contents =
                json_decode(
                    $json['data'],
                    true
                );
        }

        return is_array(
            $contents
        )
            ? $contents
            : [];
    }



    public function fetchCourseById(
        int $courseId
    ): array {

        $endpoint =
            rtrim(
                $this->baseUrl,
                '/'
            ) .
            '/webservice/rest/server.php';

        $payload = [
            'siteurl' =>
                site_url(),

            'field' =>
                'id',

            'value' =>
                $courseId,
        ];

        $response = wp_remote_post(
            $endpoint,
            [
                'timeout' => 30,
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
                        $this->token,

                    'wsfunction' =>
                        'auth_coursetransit_execute_action',

                    'moodlewsrestformat' =>
                        'json',

                    'function' =>
                        'core_course_get_courses_by_field',

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

            Logger::log(
                'fetchCourseById WP_ERROR',
                $response
                    ->get_error_message()
            );

            return [];
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

            Logger::log(
                'fetchCourseById HTTP_ERROR',
                [
                    'status' =>
                        $status,

                    'body' =>
                        $body,
                ]
            );

            return [];
        }

        $json = json_decode(
            $body,
            true
        );

        if (
            !is_array(
                $json
            )
        ) {

            Logger::log(
                'fetchCourseById INVALID_JSON',
                $body
            );

            return [];
        }

        // Moodle exception.
        if (
            !empty(
                $json['exception']
            )
        ) {

            Logger::log(
                'fetchCourseById MOODLE_EXCEPTION',
                $json
            );

            return [];
        }

        // CourseTransit wrapper error.
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

            Logger::log(
                'fetchCourseById INVALID',
                $errordata
            );

            return [];
        }

        // Decode wrapped Moodle data.
        $coursedata = [];

        if (
            !empty(
                $json['data']
            )
        ) {
            $coursedata =
                json_decode(
                    $json['data'],
                    true
                );
        }

        if (
            !is_array(
                $coursedata
            )
        ) {
            return [];
        }

        // Moodle returns:
        // ['courses' => [...]]
        return
            $coursedata['courses'][0]
            ?? [];
    }


    public function fetchCoursesWithImages(): array
    {
        $endpoint =
            rtrim(
                $this->baseUrl,
                '/'
            ) .
            '/webservice/rest/server.php';

        $payload = [
            'siteurl' =>
                site_url(),

            'field' =>
                '',

            'value' =>
                '',
        ];

        $response = wp_remote_post(
            $endpoint,
            [
                'timeout' => 30,
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
                        $this->token,

                    'wsfunction' =>
                        'auth_coursetransit_execute_action',

                    'moodlewsrestformat' =>
                        'json',

                    'function' =>
                        'core_course_get_courses_by_field',

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
            throw new \Exception(
                esc_html(
                    $response
                        ->get_error_message()
                )
            );
        }

        $status =
            wp_remote_retrieve_response_code(
                $response
            );

        $body =
            wp_remote_retrieve_body(
                $response
            );

        Logger::log(
            'COURSES WITH IMAGES RAW',
            $body
        );

        if ($status !== 200) {
            throw new \Exception(
                'Connection failed'
            );
        }

        $json = json_decode(
            $body,
            true
        );

        Logger::log(
            'COURSES WITH IMAGES DECODED',
            $json
        );

        if (
            !is_array(
                $json
            )
        ) {
            throw new \Exception(
                'Invalid response from Moodle'
            );
        }

        // Moodle exception.
        if (
            !empty(
                $json['exception']
            )
        ) {
            throw new \Exception(
                esc_html(
                    $json['message']
                    ?? 'Moodle error'
                )
            );
        }

        // CourseTransit wrapper error.
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

            Logger::log(
                'COURSES WITH IMAGES ERROR',
                $errordata
            );

            throw new \Exception(
                esc_html(
                    $errordata['message']
                    ?? 'Invalid response from Moodle'
                )
            );
        }

        // Decode wrapped Moodle data.
        $coursedata = [];

        if (
            !empty(
                $json['data']
            )
        ) {
            $coursedata =
                json_decode(
                    $json['data'],
                    true
                );
        }

        if (
            !is_array(
                $coursedata
            )
        ) {
            return [];
        }

        // Moodle returns:
        // ['courses' => [...]]
        return
            $coursedata['courses']
            ?? [];
    }


}
