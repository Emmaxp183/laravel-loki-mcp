<?php

namespace LaravelMcpSuite\Support;

class DatabaseMutationPolicy
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {
    }

    public function allowsTable(string $table): bool
    {
        return in_array($table, $this->allowedTables(), true);
    }

    public function allowsKey(string $key): bool
    {
        return in_array($key, $this->allowedKeys(), true);
    }

    public function maxRowsPerCall(): int
    {
        return (int) ($this->config['database_tools']['max_rows_per_call'] ?? 1);
    }

    /**
     * @return array<int, string>
     */
    protected function allowedTables(): array
    {
        $tables = $this->config['database_tools']['allowed_tables'] ?? [];

        return array_values(array_filter(array_map(
            fn (mixed $table): string => trim((string) $table),
            is_array($tables) ? $tables : [],
        )));
    }

    /**
     * @return array<int, string>
     */
    protected function allowedKeys(): array
    {
        $keys = $this->config['database_tools']['allowed_keys'] ?? ['id'];

        return array_values(array_filter(array_map(
            fn (mixed $key): string => trim((string) $key),
            is_array($keys) ? $keys : [],
        )));
    }
}
