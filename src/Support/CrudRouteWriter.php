<?php

namespace LaravelMcpSuite\Support;

class CrudRouteWriter
{
    public function write(FileEditor $editor, string $mode, string $route, string $controllerClass): array
    {
        $path = $mode === 'api' ? 'routes/api.php' : 'routes/web.php';
        $routeLine = $mode === 'api'
            ? "Route::apiResource('{$route}', {$controllerClass}::class);"
            : "Route::resource('{$route}', {$controllerClass}::class);";

        $current = $editor->read($path);
        $content = ($current['allowed'] ?? false)
            ? (string) $current['content']
            : "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n";

        if (! str_contains($content, 'use Illuminate\\Support\\Facades\\Route;')) {
            $content = "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n".ltrim($content);
        }

        if (str_contains($content, $routeLine)) {
            return [
                'allowed' => true,
                'path' => $path,
                'updated' => false,
            ];
        }

        $trimmed = rtrim($content);
        $updated = $trimmed."\n\n".$routeLine."\n";

        $result = $editor->write($path, $updated);

        return [
            'allowed' => (bool) ($result['allowed'] ?? false),
            'path' => $path,
            'updated' => true,
        ];
    }
}
