<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Support\Facades\File;

class CrudGenerator
{
    public function __construct(
        protected FileEditor $editor,
        protected CrudRouteWriter $routeWriter,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public function generate(string $mode, string $resource, array $fields): array
    {
        $blueprint = new CrudBlueprint($resource, $mode);
        $mapper = new CrudFieldMapper($fields);

        $created = [];
        $updated = [];
        $skipped = [];

        foreach ($this->sharedFiles($blueprint, $mapper) as $path => $content) {
            $this->writeFile($path, $content, $created, $skipped);
        }

        foreach ($this->modeFiles($mode, $blueprint, $mapper) as $path => $content) {
            $this->writeFile($path, $content, $created, $skipped);
        }

        $route = $this->routeWriter->write(
            $this->editor,
            $mode,
            $blueprint->route(),
            $mode === 'api'
                ? '\\App\\Http\\Controllers\\Api\\'.$blueprint->model().'Controller'
                : '\\App\\Http\\Controllers\\'.$blueprint->model().'Controller'
        );

        if ($route['updated'] ?? false) {
            $updated[] = $route['path'];
        }

        return [
            'allowed' => true,
            'resource' => $blueprint->model(),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'route' => $blueprint->route(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function sharedFiles(CrudBlueprint $blueprint, CrudFieldMapper $mapper): array
    {
        $migrationPath = $this->existingMigrationPath($blueprint) ?? $this->newMigrationPath($blueprint);

        return [
            $blueprint->modelPath() => $this->renderModel($blueprint, $mapper),
            $migrationPath => $this->renderMigration($blueprint, $mapper),
            $blueprint->storeRequestPath() => $this->renderRequest($blueprint, 'store', $mapper),
            $blueprint->updateRequestPath() => $this->renderRequest($blueprint, 'update', $mapper),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function modeFiles(string $mode, CrudBlueprint $blueprint, CrudFieldMapper $mapper): array
    {
        if ($mode === 'api') {
            return [
                $blueprint->resourcePath() => $this->renderApiResource($blueprint, $mapper),
                $blueprint->controllerPath() => $this->renderApiController($blueprint),
                $blueprint->testPath() => $this->renderApiTest($blueprint),
            ];
        }

        return [
            $blueprint->controllerPath() => $this->renderWebController($blueprint),
            $blueprint->viewPath('index') => $this->renderView($blueprint, 'index'),
            $blueprint->viewPath('create') => $this->renderView($blueprint, 'create'),
            $blueprint->viewPath('edit') => $this->renderView($blueprint, 'edit'),
            $blueprint->viewPath('show') => $this->renderView($blueprint, 'show'),
            $blueprint->viewPath('_form') => $this->renderView($blueprint, '_form'),
            $blueprint->testPath() => $this->renderWebTest($blueprint),
        ];
    }

    protected function writeFile(string $path, string $content, array &$created, array &$skipped): void
    {
        if (File::exists(base_path($path))) {
            $skipped[] = $path;

            return;
        }

        $result = $this->editor->write($path, $content);

        if ($result['allowed'] ?? false) {
            $created[] = $path;

            return;
        }

        $skipped[] = $path;
    }

    protected function existingMigrationPath(CrudBlueprint $blueprint): ?string
    {
        $matches = glob(base_path($blueprint->migrationPathPattern())) ?: [];

        if ($matches === []) {
            return null;
        }

        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $matches[0]);
    }

    protected function newMigrationPath(CrudBlueprint $blueprint): string
    {
        return 'database/migrations/'.now()->format('Y_m_d_His').'_create_'.$blueprint->table().'_table.php';
    }

    protected function renderModel(CrudBlueprint $blueprint, CrudFieldMapper $mapper): string
    {
        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class {$blueprint->model()} extends Model
{
    protected \$fillable = [
{$this->renderList($mapper->fillable(), 8)}
    ];
}
PHP;
    }

    protected function renderMigration(CrudBlueprint $blueprint, CrudFieldMapper $mapper): string
    {
        $class = 'Create'.ucfirst($blueprint->table()).'Table';

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$blueprint->table()}', function (Blueprint \$table): void {
            \$table->id();
{$this->renderLines($mapper->migrationColumns(), 12)}
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$blueprint->table()}');
    }
};
PHP;
    }

    protected function renderRequest(CrudBlueprint $blueprint, string $mode, CrudFieldMapper $mapper): string
    {
        $rules = $mode === 'store' ? $mapper->storeRules() : $mapper->updateRules();
        $class = ucfirst($mode).$blueprint->model().'Request';

        return <<<PHP
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$class} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
{$this->renderRules($rules, 12)}
        ];
    }
}
PHP;
    }

    protected function renderApiResource(CrudBlueprint $blueprint, CrudFieldMapper $mapper): string
    {
        $fields = array_merge(['id'], $mapper->fillable(), ['created_at', 'updated_at']);

        return <<<PHP
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$blueprint->model()}Resource extends JsonResource
{
    public function toArray(Request \$request): array
    {
        return [
{$this->renderArrayMap($fields, 12)}
        ];
    }
}
PHP;
    }

    protected function renderApiController(CrudBlueprint $blueprint): string
    {
        $model = $blueprint->model();

        return <<<PHP
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store{$model}Request;
use App\Http\Requests\Update{$model}Request;
use App\Http\Resources\\{$model}Resource;
use App\Models\\{$model};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class {$model}Controller extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return {$model}Resource::collection({$model}::query()->latest()->get());
    }

    public function store(Store{$model}Request \$request): {$model}Resource
    {
        \$record = {$model}::create(\$request->validated());

        return new {$model}Resource(\$record);
    }

    public function show({$model} \$record): {$model}Resource
    {
        return new {$model}Resource(\$record);
    }

    public function update(Update{$model}Request \$request, {$model} \$record): {$model}Resource
    {
        \$record->update(\$request->validated());

        return new {$model}Resource(\$record);
    }

    public function destroy({$model} \$record): Response
    {
        \$record->delete();

        return response()->noContent();
    }
}
PHP;
    }

    protected function renderWebController(CrudBlueprint $blueprint): string
    {
        $model = $blueprint->model();
        $route = $blueprint->route();
        $view = $blueprint->route();
        $variable = lcfirst($model);

        return <<<PHP
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store{$model}Request;
use App\Http\Requests\Update{$model}Request;
use App\Models\\{$model};
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class {$model}Controller extends Controller
{
    public function index(): View
    {
        return view('{$view}.index', [
            '{$route}' => {$model}::query()->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('{$view}.create');
    }

    public function store(Store{$model}Request \$request): RedirectResponse
    {
        {$variable} = {$model}::create(\$request->validated());

        return redirect()->route('{$route}.show', {$variable});
    }

    public function show({$model} \${$variable}): View
    {
        return view('{$view}.show', compact('{$variable}'));
    }

    public function edit({$model} \${$variable}): View
    {
        return view('{$view}.edit', compact('{$variable}'));
    }

    public function update(Update{$model}Request \$request, {$model} \${$variable}): RedirectResponse
    {
        \${$variable}->update(\$request->validated());

        return redirect()->route('{$route}.show', \${$variable});
    }

    public function destroy({$model} \${$variable}): RedirectResponse
    {
        \${$variable}->delete();

        return redirect()->route('{$route}.index');
    }
}
PHP;
    }

    protected function renderView(CrudBlueprint $blueprint, string $view): string
    {
        $title = ucfirst($view).' '.$blueprint->model();

        return <<<BLADE
<h1>{$title}</h1>
BLADE;
    }

    protected function renderApiTest(CrudBlueprint $blueprint): string
    {
        $model = $blueprint->model();

        return <<<PHP
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class {$model}CrudTest extends TestCase
{
    public function test_placeholder(): void
    {
        \$this->assertTrue(true);
    }
}
PHP;
    }

    protected function renderWebTest(CrudBlueprint $blueprint): string
    {
        $model = $blueprint->model();

        return <<<PHP
<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class {$model}CrudTest extends TestCase
{
    public function test_placeholder(): void
    {
        \$this->assertTrue(true);
    }
}
PHP;
    }

    /**
     * @param  array<int, string>  $items
     */
    protected function renderList(array $items, int $indent): string
    {
        $prefix = str_repeat(' ', $indent);

        return implode("\n", array_map(
            fn (string $item): string => $prefix."'{$item}',",
            $items,
        ));
    }

    /**
     * @param  array<int, string>  $lines
     */
    protected function renderLines(array $lines, int $indent): string
    {
        $prefix = str_repeat(' ', $indent);

        return implode("\n", array_map(
            fn (string $line): string => $prefix.$line,
            $lines,
        ));
    }

    /**
     * @param  array<string, array<int, string>>  $rules
     */
    protected function renderRules(array $rules, int $indent): string
    {
        $prefix = str_repeat(' ', $indent);
        $lines = [];

        foreach ($rules as $field => $fieldRules) {
            $quoted = implode(', ', array_map(fn (string $rule): string => "'{$rule}'", $fieldRules));
            $lines[] = "{$prefix}'{$field}' => [{$quoted}],";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, string>  $fields
     */
    protected function renderArrayMap(array $fields, int $indent): string
    {
        $prefix = str_repeat(' ', $indent);

        return implode("\n", array_map(
            fn (string $field): string => "{$prefix}'{$field}' => \$this->{$field},",
            $fields,
        ));
    }
}
