<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Support\Facades\DB;

class DatabaseMutator
{
    public function create(string $table, array $record): array
    {
        return DB::transaction(function () use ($table, $record): array {
            $insertedId = DB::table($table)->insertGetId($record);

            return [
                'table' => $table,
                'created' => true,
                'inserted_id' => $insertedId,
            ];
        });
    }

    public function update(string $table, string $key, mixed $id, array $changes): array
    {
        return DB::transaction(function () use ($table, $key, $id, $changes): array {
            $affectedRows = DB::table($table)
                ->where($key, $id)
                ->limit(1)
                ->update($changes);

            return [
                'table' => $table,
                'key' => $key,
                'id' => $id,
                'affected_rows' => $affectedRows,
            ];
        });
    }

    public function delete(string $table, string $key, mixed $id): array
    {
        return DB::transaction(function () use ($table, $key, $id): array {
            $affectedRows = DB::table($table)
                ->where($key, $id)
                ->limit(1)
                ->delete();

            return [
                'table' => $table,
                'key' => $key,
                'id' => $id,
                'affected_rows' => $affectedRows,
            ];
        });
    }
}
