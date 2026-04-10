<?php

namespace LaravelMcpSuite\Support;

class FileDiffPreview
{
    public function render(string $path, string $before, string $after): string
    {
        if ($before === $after) {
            return "--- {$path}\n+++ {$path}\n@@\n  no changes";
        }

        $preview = [
            "--- {$path}",
            "+++ {$path}",
            '@@',
        ];

        foreach ($this->changedLines($before, $after) as $line) {
            $preview[] = $line;
        }

        return implode("\n", array_slice($preview, 0, 18));
    }

    /**
     * @return array<int, string>
     */
    protected function changedLines(string $before, string $after): array
    {
        $beforeLines = preg_split('/\R/', $before) ?: [];
        $afterLines = preg_split('/\R/', $after) ?: [];
        $max = max(count($beforeLines), count($afterLines));
        $changes = [];

        for ($index = 0; $index < $max; $index++) {
            $old = $beforeLines[$index] ?? null;
            $new = $afterLines[$index] ?? null;

            if ($old === $new) {
                continue;
            }

            if ($old !== null) {
                $changes[] = '- '.$old;
            }

            if ($new !== null) {
                $changes[] = '+ '.$new;
            }
        }

        return $changes === [] ? ['  no changes'] : $changes;
    }
}
