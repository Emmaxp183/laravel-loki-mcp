<?php

namespace LaravelMcpSuite\Sanitizers;

class OutputSanitizer
{
    /**
     * @param  mixed  $value
     * @return mixed
     */
    public function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                $sanitized[$key] = $this->shouldRedactKey((string) $key)
                    ? '[REDACTED]'
                    : $this->sanitize($item);
            }

            return $sanitized;
        }

        if (! is_string($value)) {
            return $value;
        }

        return $this->sanitizeString($value);
    }

    protected function shouldRedactKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (['password', 'token', 'secret', 'key', 'cookie'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function sanitizeString(string $value): string
    {
        $patterns = [
            '/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i' => 'Bearer [REDACTED]',
            '/([A-Z0-9_]+)=([^\s]+)/' => '$1=[REDACTED]',
            '/"([A-Za-z0-9_]*(password|token|secret|key|cookie)[A-Za-z0-9_]*)"\s*:\s*"[^"]*"/i' => '"$1":"[REDACTED]"',
            '/:\/\/([^:\s]+):([^@\s]+)@/' => '://$1:[REDACTED]@',
            '/-----BEGIN [A-Z ]+ PRIVATE KEY-----.*?-----END [A-Z ]+ PRIVATE KEY-----/s' => '[REDACTED PRIVATE KEY]',
            '/sk-(live|test)-[A-Za-z0-9]+/i' => '[REDACTED]',
        ];

        return (string) preg_replace(array_keys($patterns), array_values($patterns), $value);
    }
}
