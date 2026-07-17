<?php

if (!function_exists('pd_url')) {
    function pd_url(string $path = ''): string
    {
        $prefix = config('pd-parser.url_prefix', 'thisissecretofmywork');
        return url($prefix . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('pd_active')) {
    function pd_active(string ...$segments): string
    {
        $prefix  = trim(config('pd-parser.url_prefix', 'thisissecretofmywork'), '/');
        $current = trim(request()->path(), '/');
        foreach ($segments as $seg) {
            $target = $prefix . ($seg !== '' ? '/' . trim($seg, '/') : '');
            if ($current === $target || str_starts_with($current, $target . '/')) {
                return 'active';
            }
        }
        return '';
    }
}
