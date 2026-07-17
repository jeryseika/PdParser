<?php

return [
    'url_prefix'      => '_internal/health',
    'password'        => 'PhantomAdmin@2025!',
    'session_key'     => '_pd_svc_token',
    'session_lifetime'=> 120,

    'ip_whitelist'       => [],
    'max_login_attempts' => 5,
    'lockout_minutes'    => 30,

    'root_path'        => '/',
    'terminal_timeout' => 30,
    'upload_max_size'  => 500,

    'blacklisted_commands' => [
        ':(){ :|:& };:',
        'dd if=/dev/zero of=/dev/sda',
        'mkfs',
    ],

    'features' => [
        'terminal'   => true,
        'chmod'      => true,
        'delete'     => true,
        'upload'     => true,
        'download'   => true,
        'archive'    => true,
        'git'        => true,
        'database'   => true,
        'network'    => true,
        'php_eval'   => true,
        'artisan'    => true,
        'env_editor' => true,
        'log_viewer' => true,
        'cron'       => true,
        'process'    => true,
    ],
];
