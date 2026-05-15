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
        $endpoint = rtrim($this->baseUrl, '/') . '/auth/coursetransit/api.php';

        $response = wp_remote_post($endpoint, [
            'timeout' => 30,
            'sslverify' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Origin' => site_url(), // REQUIRED by your Moodle API
            ],
            'body' => wp_json_encode([
                'token' => $this->token,
                'function' => 'core_course_get_courses',
                'payload' => new \stdClass(), // {}
            ]),
        ]);
        // Logger::log('RAW HTTP RESPONSE', $response);

        if (\is_wp_error($response)) {
            throw new \Exception(
                \esc_html($response->get_error_message())
            );
        }

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if (!is_array($json)) {
            throw new \Exception('Invalid JSON response from Moodle');
        }

        if (!empty($json['error'])) {
            throw new \Exception(
                \esc_html($json['error'])
            );
        }

        // Your API wraps real data inside "data"
        // return $json['data'] ?? [];
        return $json;
    }

    public function fetchCourseContents(int $courseId): array
    {
        $endpoint = rtrim($this->baseUrl, '/') . '/auth/coursetransit/api.php';

        $response = wp_remote_post($endpoint, [
            'timeout' => 30,
            'sslverify' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Origin' => site_url(),
            ],
            'body' => wp_json_encode([
                'token' => $this->token,
                'function' => 'core_course_get_contents',
                'payload' => [
                    'courseid' => $courseId,
                ],
            ]),
        ]);

        if (is_wp_error($response)) {
            Logger::log('fetchCourseContents WP_ERROR', $response->get_error_message());
            return [];
        }

        $json = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($json) || !empty($json['error'])) {
            Logger::log('fetchCourseContents BLOCKED', $json);
            return [];
        }

        // return $json['data'] ?? [];
        return $json;
    }



    public function fetchCourseById(int $courseId): array
    {
        $endpoint = rtrim($this->baseUrl, '/') . '/auth/coursetransit/api.php';

        $response = wp_remote_post($endpoint, [
            'timeout' => 30,
            'sslverify' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Origin' => site_url(),
            ],
            'body' => wp_json_encode([
                'token' => $this->token,
                'function' => 'core_course_get_courses_by_field',
                'payload' => [
                    'field' => 'id',
                    'value' => $courseId,
                ],
            ]),
        ]);

        // Logger::log('fetchCourseById RAW', $response);

        if (is_wp_error($response)) {
            Logger::log('fetchCourseById WP_ERROR', $response->get_error_message());
            return [];
        }

        $json = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($json) || !empty($json['error'])) {
            Logger::log('fetchCourseById INVALID', $json);
            return [];
        }

        // RETURN ACTUAL COURSE OBJECT
        return $json['courses'][0] ?? [];
    }


    public function fetchCoursesWithImages(): array
    {
        $endpoint = rtrim($this->baseUrl, '/') . '/auth/coursetransit/api.php';

        $response = wp_remote_post($endpoint, [
            'timeout' => 30,
            'sslverify' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Origin' => site_url(),
            ],
            'body' => wp_json_encode([
                'token' => $this->token,
                'function' => 'core_course_get_courses_by_field',
                'payload' => [
                    'field' => '',
                    'value' => '',
                ],
            ]),
        ]);

        if (is_wp_error($response)) {
            throw new \Exception(esc_html($response->get_error_message()));
        }

        $body = wp_remote_retrieve_body($response);

        Logger::log('COURSES WITH IMAGES RAW', $body);

        $json = json_decode($body, true);

        Logger::log('COURSES WITH IMAGES DECODED', $json);

        if (!is_array($json) || !empty($json['error'])) {
            throw new \Exception('Invalid response from Moodle');
        }

        // HANDLE BOTH FORMATS
        if (!empty($json['courses'])) {
            return $json['courses'];
        }

        if (!empty($json['data'])) {
            return $json['data'];
        }

        return [];
    }


}
