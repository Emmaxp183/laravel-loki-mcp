<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Support\Collection;

class LogReader
{
    public function recent(?int $lines = 100, ?string $level = null): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return [];
        }

        $entries = collect(preg_split('/\R/', (string) file_get_contents($path)))
            ->filter()
            ->map(fn (string $line): array => [
                'line' => $line,
                'level' => $this->extractLevel($line),
            ]);

        if ($level !== null) {
            $entries = $entries->filter(fn (array $entry): bool => strtolower($entry['level']) === strtolower($level));
        }

        return $entries
            ->take(-1 * max($lines ?? 100, 1))
            ->values()
            ->all();
    }

    protected function extractLevel(string $line): string
    {
        if (preg_match('/\.(DEBUG|INFO|NOTICE|WARNING|ERROR|CRITICAL|ALERT|EMERGENCY):/i', $line, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return 'unknown';
    }
}
