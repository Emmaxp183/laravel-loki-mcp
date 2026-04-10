<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Support\Facades\File;

class FileEditor
{
    public function __construct(
        protected FileEditPolicy $policy,
        protected FileDiffPreview $diffPreview,
    ) {
    }

    public function list(string $path, int $depth): array
    {
        $normalized = $this->policy->normalize($path);

        if (! $this->policy->allows($normalized)) {
            return ['allowed' => false, 'path' => $normalized, 'entries' => []];
        }

        $absolute = $this->absolutePath($normalized);

        if (! File::exists($absolute)) {
            return ['allowed' => false, 'path' => $normalized, 'entries' => []];
        }

        $entries = collect(File::allFiles($absolute))
            ->map(fn ($file): string => str_replace('\\', '/', $file->getRelativePathname()))
            ->map(fn (string $relative): string => trim($normalized.'/'.$relative, '/'))
            ->filter(fn (string $relative): bool => substr_count($relative, '/') - substr_count($normalized, '/') <= max($depth, 0))
            ->values()
            ->all();

        return ['allowed' => true, 'path' => $normalized, 'entries' => $entries];
    }

    public function read(string $path): array
    {
        $normalized = $this->policy->normalize($path);

        if (! $this->policy->allows($normalized)) {
            return ['allowed' => false, 'path' => $normalized, 'content' => null];
        }

        $absolute = $this->absolutePath($normalized);

        if (! File::exists($absolute) || File::isDirectory($absolute)) {
            return ['allowed' => false, 'path' => $normalized, 'content' => null];
        }

        return ['allowed' => true, 'path' => $normalized, 'content' => (string) File::get($absolute)];
    }

    public function write(string $path, string $content): array
    {
        $normalized = $this->policy->normalize($path);

        if (! $this->policy->allows($normalized)) {
            return ['allowed' => false, 'path' => $normalized, 'diff_preview' => null];
        }

        $absolute = $this->absolutePath($normalized);
        File::ensureDirectoryExists(dirname($absolute));

        $before = File::exists($absolute) ? (string) File::get($absolute) : '';
        File::put($absolute, $content);

        return [
            'allowed' => true,
            'path' => $normalized,
            'diff_preview' => $this->diffPreview->render($normalized, $before, $content),
        ];
    }

    public function patch(string $path, string $search, string $replace): array
    {
        $current = $this->read($path);

        if (! ($current['allowed'] ?? false)) {
            return ['allowed' => false, 'path' => $current['path'] ?? $path, 'diff_preview' => null];
        }

        $before = (string) ($current['content'] ?? '');

        if (! str_contains($before, $search)) {
            return ['allowed' => false, 'path' => $current['path'], 'diff_preview' => null];
        }

        $after = str_replace($search, $replace, $before);

        return $this->write((string) $current['path'], $after);
    }

    protected function absolutePath(string $relativePath): string
    {
        return base_path($relativePath);
    }
}
