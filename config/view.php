<?php

return [
    'paths' => [
        resource_path('views'),
    ],

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        env('VERCEL') ? '/tmp/framework/views' : storage_path('framework/views')
    ),

    'relative_hash' => false,
    'cache' => true,
    'check_cache_timestamps' => true,
    'compiled_extension' => 'php',
];
