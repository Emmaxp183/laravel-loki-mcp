<?php

namespace LaravelMcpSuite\Support;

class ArtisanCommandPolicy
{
    public function allowed(string $command): bool
    {
        return in_array($command, config('laravel-mcp.artisan.allowlist', []), true);
    }
}
