<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Database\ConnectionInterface;

class SchemaInspector
{
    public function __construct(
        protected ConnectionInterface $connection,
    ) {
    }

    public function overview(?string $table = null): array
    {
        $builder = $this->connection->getSchemaBuilder();
        $tables = collect($builder->getTables())
            ->map(function (array $tableDefinition) use ($builder): array {
                $name = $tableDefinition['name'];

                return [
                    'name' => $name,
                    'columns' => $builder->getColumns($name),
                    'indexes' => $builder->getIndexes($name),
                    'foreign_keys' => $builder->getForeignKeys($name),
                ];
            });

        if ($table !== null) {
            $tables = $tables->where('name', $table);
        }

        return $tables->values()->all();
    }
}
