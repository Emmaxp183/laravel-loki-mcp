<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;

class ModelInspector
{
    public function list(?string $namespacePrefix = null): array
    {
        return collect(get_declared_classes())
            ->filter(fn (string $class): bool => is_subclass_of($class, Model::class))
            ->filter(fn (string $class): bool => $namespacePrefix === null || str_starts_with($class, $namespacePrefix))
            ->map(fn (string $class): array => $this->summarize($class))
            ->values()
            ->all();
    }

    public function describe(string $modelClass): array
    {
        return $this->summarize($modelClass, true);
    }

    protected function summarize(string $modelClass, bool $withRelationships = false): array
    {
        /** @var Model $model */
        $model = new $modelClass();
        $reflection = new ReflectionClass($modelClass);

        $summary = [
            'class' => $modelClass,
            'table' => $model->getTable(),
            'fillable' => $model->getFillable(),
            'casts' => method_exists($model, 'getCasts') ? $model->getCasts() : [],
            'file' => $reflection->getFileName(),
        ];

        if ($withRelationships) {
            $summary['relationships'] = collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
                ->filter(fn (ReflectionMethod $method): bool => $method->class === $modelClass && $method->getNumberOfParameters() === 0)
                ->filter(function (ReflectionMethod $method) use ($model): bool {
                    try {
                        return $method->invoke($model) instanceof Relation;
                    } catch (\Throwable) {
                        return false;
                    }
                })
                ->map(function (ReflectionMethod $method) use ($model): array {
                    /** @var Relation $relation */
                    $relation = $method->invoke($model);

                    return [
                        'name' => $method->getName(),
                        'type' => class_basename($relation::class),
                        'related' => $relation->getRelated()::class,
                    ];
                })
                ->values()
                ->all();
        }

        return $summary;
    }
}
