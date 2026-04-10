<?php

namespace LaravelMcpSuite\Support;

class ExceptionSummarizer
{
    public function summarize(array $entries): array
    {
        return collect($entries)
            ->filter(fn (array $entry): bool => $entry['level'] === 'error')
            ->map(function (array $entry): array {
                preg_match('/:\s*([A-Za-z0-9_\\\\]+):\s([^\\{]+)/', $entry['line'], $matches);

                return [
                    'type' => class_basename($matches[1] ?? 'UnknownException'),
                    'message' => trim($matches[2] ?? 'Unknown exception'),
                    'context' => $entry['line'],
                ];
            })
            ->values()
            ->all();
    }
}
