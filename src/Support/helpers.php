<?php

if (!function_exists('pd_url')) {
    function pd_url(string $path = ''): string
    {
        $prefix = config('pd-parser.prefix', '_internal/health');
        $full   = $prefix . ($path !== '' ? '/' . ltrim($path, '/') : '');

        $proto    = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['HTTP_X_FORWARDED_SSL'] ?? null;
        $isSecure = request()->isSecure() || $proto === 'https' || $proto === 'on';

        return $isSecure ? secure_url($full) : url($full);
    }
}

if (!function_exists('pd_active')) {
    function pd_active(string ...$segments): string
    {
        $prefix  = trim(config('pd-parser.prefix', '_internal/health'), '/');
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
