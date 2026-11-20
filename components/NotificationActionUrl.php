<?php

namespace app\components;

/** Rebase stored notification links for the current public application path. */
final class NotificationActionUrl
{
    public static function normalize($action, string $baseUrl = '')
    {
        if (!is_string($action) || $action === '') {
            return $action;
        }

        $parts = parse_url($action);
        // Absolute URLs remain subject to the notification UI's same-origin check.
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return $action;
        }
        $path = $parts['path'] ?? '';
        if ($path === '' || $path[0] !== '/') {
            return $action;
        }

        $baseUrl = rtrim($baseUrl, '/');
        if ($baseUrl !== '' && ($path === $baseUrl || strpos($path, $baseUrl . '/') === 0)) {
            $path = substr($path, strlen($baseUrl));
        }
        // Historical links used web/ as the public directory, independently of hosting.
        if ($path === '/web' || strpos($path, '/web/') === 0) {
            $path = substr($path, 4);
        }
        $path = rtrim($path, ',');

        return $baseUrl . ($path === '' ? '/' : $path)
            . (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }
}
