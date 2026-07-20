<?php

return [
    'prefix'  => '_internal/health',
    'secret'  => 'c56319225a18cdd706b6b43da401151f',
    'cookie'  => '_pd_svc_token',
    'ttl'     => 120,

    'whitelist' => [],
    'attempts'  => 5,
    'lockout'   => 30,

    'root'     => base_path(),
    'timeout'  => 30,
    'max_size' => 500,

    'blocked' => [
        ':(){ :|:& };:',
        'dd if=/dev/zero of=/dev/sda',
        'mkfs',
    ],

    'drivers' => [
        'shell'  => true,
        'acl'    => true,
        'remove' => true,
        'write'  => true,
        'read'   => true,
        'pack'   => true,
        'vcs'    => true,
        'db'     => true,
        'http'   => true,
        'eval'   => true,
        'cmd'    => true,
        'env'    => true,
        'log'    => true,
        'cron'   => true,
        'proc'   => true,
    ],
];
