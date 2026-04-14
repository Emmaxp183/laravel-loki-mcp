<?php

return [
    'server' => [
        'name' => env('LARAVEL_MCP_SERVER_NAME', 'Laravel App Server'),
        'local_command' => 'app',
        'enable_web_server' => false,
        'web_path' => '/mcp/app',
        'web_middleware' => ['api'],
        'auth' => [
            'mode' => env('LARAVEL_MCP_WEB_AUTH_MODE', 'shared_token'),
            'middleware' => [],
            'shared_token' => env('LARAVEL_MCP_SHARED_TOKEN'),
            'shared_token_header' => env('LARAVEL_MCP_SHARED_TOKEN_HEADER', 'X-MCP-Token'),
            'oauth_prefix' => env('LARAVEL_MCP_OAUTH_PREFIX', 'oauth'),
        ],
    ],
    'modules' => [
        'core' => true,
        'artisan' => true,
        'database' => true,
        'files' => true,
        'logs' => true,
        'tests' => true,
        'queues' => false,
        'generators' => true,
    ],
    'write_tools' => [
        'enabled_in_local' => true,
        'enabled_elsewhere' => false,
    ],
    'artisan' => [
        'allowlist' => [
            'about',
            'route:list',
            'test',
            'db:seed',
            'migrate:status',
            'queue:failed',
            'tinker',
        ],
    ],
    'file_tools' => [
        'allow_code_edits' => true,
        'writable_paths' => [
            'app',
            'routes',
            'database',
            'config',
            'tests',
        ],
        'blocked_paths' => [
            '.env',
            'vendor',
            'storage',
            'bootstrap/cache',
            'node_modules',
        ],
    ],
    'redaction' => [
        'enabled' => true,
    ],
];
