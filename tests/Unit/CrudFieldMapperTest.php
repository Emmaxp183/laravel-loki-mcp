<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Support\CrudFieldMapper;
use PHPUnit\Framework\TestCase;

class CrudFieldMapperTest extends TestCase
{
    public function test_it_maps_fields_to_columns_rules_fillable_and_payloads(): void
    {
        $mapper = new CrudFieldMapper([
            ['name' => 'title', 'type' => 'string', 'required' => true, 'rules' => ['string', 'max:255']],
            ['name' => 'body', 'type' => 'text', 'required' => true, 'rules' => ['string']],
            ['name' => 'published_at', 'type' => 'timestamp', 'required' => false, 'nullable' => true],
        ]);

        $this->assertSame([
            "\$table->string('title');",
            "\$table->text('body');",
            "\$table->timestamp('published_at')->nullable();",
        ], $mapper->migrationColumns());

        $this->assertSame([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
        ], $mapper->storeRules());

        $this->assertSame([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ], $mapper->updateRules());

        $this->assertSame(['title', 'body', 'published_at'], $mapper->fillable());
        $this->assertSame([
            'title' => 'Sample Title',
            'body' => 'Sample body text.',
            'published_at' => '2026-04-14 10:00:00',
        ], $mapper->storePayload());
    }
}
