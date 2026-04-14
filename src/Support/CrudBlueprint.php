<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Support\Str;

class CrudBlueprint
{
    public function __construct(
        protected string $resource,
        protected string $mode,
    ) {
    }

    public function resource(): string
    {
        return $this->resource;
    }

    public function model(): string
    {
        return Str::studly($this->resource);
    }

    public function table(): string
    {
        return Str::snake(Str::pluralStudly($this->model()));
    }

    public function route(): string
    {
        return Str::kebab(Str::pluralStudly($this->model()));
    }

    public function modelPath(): string
    {
        return 'app/Models/'.$this->model().'.php';
    }

    public function migrationPathPattern(): string
    {
        return 'database/migrations/*_create_'.$this->table().'_table.php';
    }

    public function storeRequestPath(): string
    {
        return 'app/Http/Requests/Store'.$this->model().'Request.php';
    }

    public function updateRequestPath(): string
    {
        return 'app/Http/Requests/Update'.$this->model().'Request.php';
    }

    public function resourcePath(): string
    {
        return 'app/Http/Resources/'.$this->model().'Resource.php';
    }

    public function controllerPath(): string
    {
        return $this->mode === 'api'
            ? 'app/Http/Controllers/Api/'.$this->model().'Controller.php'
            : 'app/Http/Controllers/'.$this->model().'Controller.php';
    }

    public function routeFilePath(): string
    {
        return $this->mode === 'api' ? 'routes/api.php' : 'routes/web.php';
    }

    public function testPath(): string
    {
        return $this->mode === 'api'
            ? 'tests/Feature/Api/'.$this->model().'CrudTest.php'
            : 'tests/Feature/Web/'.$this->model().'CrudTest.php';
    }

    public function viewPath(string $view): string
    {
        return 'resources/views/'.$this->route().'/'.$view.'.blade.php';
    }
}
