<?php

namespace LaravelMcpSuite\Tests\Fixtures\Support;

use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;

class FakeQueueFactory implements Factory
{
    /**
     * @var array<int, string|null>
     */
    public array $requestedConnections = [];

    public function __construct(
        protected Queue $queue,
    ) {
    }

    public function connection($name = null): Queue
    {
        $this->requestedConnections[] = $name === null ? null : (string) $name;

        return $this->queue;
    }
}
