<?php

namespace LaravelMcpSuite\Support;

class CrudFieldMapper
{
    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    public function __construct(
        protected array $fields,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function migrationColumns(): array
    {
        return array_map(function (array $field): string {
            $column = match ((string) $field['type']) {
                'text' => "\$table->text('{$field['name']}')",
                'timestamp' => "\$table->timestamp('{$field['name']}')",
                default => "\$table->string('{$field['name']}')",
            };

            if (($field['nullable'] ?? false) === true) {
                $column .= '->nullable()';
            }

            return $column.';';
        }, $this->fields);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function storeRules(): array
    {
        return $this->rules(false);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function updateRules(): array
    {
        return $this->rules(true);
    }

    /**
     * @return array<int, string>
     */
    public function fillable(): array
    {
        return array_map(
            fn (array $field): string => (string) $field['name'],
            $this->fields,
        );
    }

    /**
     * @return array<string, string>
     */
    public function storePayload(): array
    {
        $payload = [];

        foreach ($this->fields as $field) {
            $payload[$field['name']] = match ((string) $field['type']) {
                'text' => 'Sample body text.',
                'timestamp' => '2026-04-14 10:00:00',
                default => 'Sample '.ucfirst((string) $field['name']),
            };
        }

        return $payload;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(bool $forUpdate): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $fieldRules = [];

            if ($forUpdate) {
                $fieldRules[] = 'sometimes';
            }

            if (($field['required'] ?? false) === true) {
                $fieldRules[] = 'required';
            } elseif (($field['nullable'] ?? false) === true) {
                $fieldRules[] = 'nullable';
            }

            if (isset($field['rules']) && is_array($field['rules']) && $field['rules'] !== []) {
                foreach ($field['rules'] as $rule) {
                    $fieldRules[] = (string) $rule;
                }
            } elseif ((string) $field['type'] === 'timestamp') {
                $fieldRules[] = 'date';
            } else {
                $fieldRules[] = 'string';
            }

            if (($field['nullable'] ?? false) === true && ! in_array('nullable', $fieldRules, true)) {
                $fieldRules[] = 'nullable';
            }

            if ((string) $field['type'] === 'timestamp' && ! in_array('date', $fieldRules, true)) {
                $fieldRules[] = 'date';
            }

            $rules[(string) $field['name']] = $fieldRules;
        }

        return $rules;
    }
}
