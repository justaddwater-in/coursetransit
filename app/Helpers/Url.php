<?php
namespace CourseTransit\Helpers;

use CourseTransit\Router;

class Url
{
    /**
     * Check if route is active
     */
    public static function isActive(string $route): bool
    {
        return Router::current() === $route;
    }

    /**
     * Return active class
     */
    public static function active(string $route, string $class = 'active'): string
    {
        return self::isActive($route) ? $class : '';
    }

}
