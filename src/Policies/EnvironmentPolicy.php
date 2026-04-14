<?php

namespace LaravelMcpSuite\Policies;

class EnvironmentPolicy
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {
    }

    public function readToolsEnabled(string $environment): bool
    {
        return in_array($environment, ['local', 'testing', 'staging', 'production'], true);
    }

    public function writeToolsEnabled(string $environment): bool
    {
        if ($environment === 'local') {
            return (bool) ($this->config['write_tools']['enabled_in_local'] ?? false);
        }

        return (bool) ($this->config['write_tools']['enabled_elsewhere'] ?? false);
    }

    public function codeEditsEnabled(string $environment): bool
    {
        return $this->writeToolsEnabled($environment)
            && (bool) ($this->config['file_tools']['allow_code_edits'] ?? false);
    }

    public function storageWritesEnabled(string $environment): bool
    {
        if ($environment === 'local') {
            return (bool) ($this->config['storage_tools']['allow_writes_in_local'] ?? false);
        }

        return (bool) ($this->config['storage_tools']['allow_writes_elsewhere'] ?? false);
    }
}
