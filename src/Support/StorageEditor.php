<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Support\Facades\Storage;

class StorageEditor
{
    public function __construct(
        protected StorageAccessPolicy $policy,
    ) {
    }

    public function list(string $disk, string $path): array
    {
        $normalizedDisk = $this->policy->normalizeDisk($disk);
        $normalizedPath = $this->policy->normalizePath($path);

        if (! $this->policy->allows($normalizedDisk, $normalizedPath)) {
            return [
                'allowed' => false,
                'disk' => $normalizedDisk,
                'path' => $normalizedPath,
                'entries' => [],
            ];
        }

        $entries = collect(Storage::disk($normalizedDisk)->allFiles($normalizedPath))
            ->map(fn (string $entry): string => str_replace('\\', '/', $entry))
            ->sort()
            ->values()
            ->all();

        return [
            'allowed' => true,
            'disk' => $normalizedDisk,
            'path' => $normalizedPath,
            'entries' => $entries,
        ];
    }

    public function read(string $disk, string $path): array
    {
        $normalizedDisk = $this->policy->normalizeDisk($disk);
        $normalizedPath = $this->policy->normalizePath($path);

        if (! $this->policy->allows($normalizedDisk, $normalizedPath)) {
            return $this->denied($normalizedDisk, $normalizedPath);
        }

        $storage = Storage::disk($normalizedDisk);

        if (! $storage->exists($normalizedPath)) {
            return $this->denied($normalizedDisk, $normalizedPath);
        }

        $content = (string) $storage->get($normalizedPath);
        $bytes = strlen($content);

        if (! $this->policy->withinByteLimit($bytes)) {
            return [
                'allowed' => false,
                'disk' => $normalizedDisk,
                'path' => $normalizedPath,
                'bytes' => $bytes,
                'content' => null,
            ];
        }

        return [
            'allowed' => true,
            'disk' => $normalizedDisk,
            'path' => $normalizedPath,
            'bytes' => $bytes,
            'content' => $content,
        ];
    }

    public function write(string $disk, string $path, string $content, bool $overwrite = false): array
    {
        $normalizedDisk = $this->policy->normalizeDisk($disk);
        $normalizedPath = $this->policy->normalizePath($path);

        if (! $this->policy->allows($normalizedDisk, $normalizedPath)) {
            return [
                'allowed' => false,
                'disk' => $normalizedDisk,
                'path' => $normalizedPath,
                'bytes' => null,
                'overwritten' => false,
            ];
        }

        $bytes = strlen($content);

        if (! $this->policy->withinByteLimit($bytes)) {
            return [
                'allowed' => false,
                'disk' => $normalizedDisk,
                'path' => $normalizedPath,
                'bytes' => $bytes,
                'overwritten' => false,
            ];
        }

        $storage = Storage::disk($normalizedDisk);
        $exists = $storage->exists($normalizedPath);

        if ($exists && ! $overwrite) {
            return [
                'allowed' => false,
                'disk' => $normalizedDisk,
                'path' => $normalizedPath,
                'bytes' => $bytes,
                'overwritten' => false,
            ];
        }

        $storage->put($normalizedPath, $content);

        return [
            'allowed' => true,
            'disk' => $normalizedDisk,
            'path' => $normalizedPath,
            'bytes' => $bytes,
            'overwritten' => $exists,
        ];
    }

    public function delete(string $disk, string $path): array
    {
        $normalizedDisk = $this->policy->normalizeDisk($disk);
        $normalizedPath = $this->policy->normalizePath($path);

        if (! $this->policy->allows($normalizedDisk, $normalizedPath)) {
            return [
                'allowed' => false,
                'disk' => $normalizedDisk,
                'path' => $normalizedPath,
                'deleted' => false,
            ];
        }

        $deleted = Storage::disk($normalizedDisk)->delete($normalizedPath);

        return [
            'allowed' => $deleted,
            'disk' => $normalizedDisk,
            'path' => $normalizedPath,
            'deleted' => $deleted,
        ];
    }

    protected function denied(string $disk, string $path): array
    {
        return [
            'allowed' => false,
            'disk' => $disk,
            'path' => $path,
            'bytes' => null,
            'content' => null,
        ];
    }
}
