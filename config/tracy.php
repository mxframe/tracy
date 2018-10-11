<?php

return [
    'enabled'             => (env('APP_DEBUG') === true),
    'show_bar'            => (env('APP_ENV') !== 'production'),
    'remote_debug'        => (env('REMOTE_DEBUG') !== false),
    'remote_debug_header' => [
        'developer_name_tag'    => 'TK-DEVELOPER',
        'developer_name_values' => [
            'mbe',
        ],
        'allowed_ips'           => [],
    ],
    'show_exception'       => true,
    'route'               => [
        'prefix' => 'tracy',
        'as'     => 'tracy.',
    ],
    'accepts'             => [
        'text/html',
    ],
    'append_to'            => 'body',
    'editor'              => 'subl://open?url=file://%file&line=%line',
    'max_depth'            => 4,
    'max_length'           => 1000,
    'scream'              => true,
    'show_location'        => true,
    'strict_mode'          => true,
    'editor_mapping'       => [],
    'panels'              => [
        'routing'        => true,
        'database'       => true,
        'view'           => true,
        'event'          => false,
        'session'        => true,
        'request'        => true,
        'auth'           => true,
        'html_validator' => false,
        'terminal'       => true,
    ],
];