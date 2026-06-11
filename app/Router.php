<?php
namespace CourseTransit;

if (!defined('ABSPATH')) {
    exit;
}

class Router
{
    protected static string $currentRoute = '';
    protected static array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    // Register GET route
    public static function get(string $route, string $controller, string $action = 'index'): void
    {
        self::$routes['GET'][$route] = [$controller, $action];
    }

    // Register POST route
    public static function post(string $route, string $controller, string $action): void
    {
        self::$routes['POST'][$route] = [$controller, $action];
    }

    public static function current(): string
    {
        return self::$currentRoute;
    }

    // Dispatch route
    public static function dispatch(): void
    {
        $route = 'dashboard.index';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing.
        if (isset($_GET['route'])) {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing.
            $route = sanitize_text_field(wp_unslash($_GET['route']));
        }

        $method = isset($_SERVER['REQUEST_METHOD'])
            ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))
            : 'GET';

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized access.', 'coursetransit'));
        }

        self::$currentRoute = $route;

        if (!isset(self::$routes[$method][$route])) {
            wp_die(
                sprintf(
                    'Route <strong>%s</strong> not registered',
                    esc_html($route)
                )
            );
        }

        [$controller, $action] = self::$routes[$method][$route];

        if (!class_exists($controller)) {
            wp_die(
                sprintf(
                    'Controller <strong>%s</strong> not found',
                    esc_html($controller)
                )
            );
        }

        $controllerObject = new $controller();

        if (!method_exists($controllerObject, $action)) {
            wp_die(
                sprintf(
                    'Action <strong>%s</strong> not found in %s',
                    esc_html($action),
                    esc_html($controller)
                )
            );
        }

        call_user_func([$controllerObject, $action]);
    }
}
