<?php

namespace LaravelMcpSuite\Tests\Fixtures;

class TestRouteController
{
    public function show(string $user): string
    {
        return $user;
    }

    public function store(): string
    {
        return 'created';
    }
}
