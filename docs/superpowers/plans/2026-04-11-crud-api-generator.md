# CRUD API Generator Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a generator MCP tool that creates a conventional Laravel JSON CRUD API directly in downstream Laravel apps when local write tools are enabled.

**Architecture:** Keep one public MCP tool in the `generators` module and push generation logic into focused support services. Reuse the package's existing environment policy, file editor policy, audit wrapper, and prompt workflow rather than introducing a second write system.

**Tech Stack:** PHP 8.3, Laravel 13, `laravel/mcp`, Orchestra Testbench, PHPUnit 11

---

## File Map

- `src/Capabilities/Generators/GeneratorsCapabilities.php`: register the new generator tool
- `src/MCP/Tools/LaravelCrudApiGenerateTool.php`: public MCP entrypoint for CRUD generation
- `src/Support/CrudApiGenerator.php`: orchestrates generation steps and aggregates result payloads
- `src/Support/CrudApiBlueprint.php`: derives model names, table names, route names, file paths, and class names
- `src/Support/CrudApiFieldMapper.php`: maps structured fields to migration fragments, validation rules, fillable arrays, resource payloads, and test payloads
- `src/Support/CrudApiRouteWriter.php`: inserts `Route::apiResource(...)` idempotently into `routes/api.php`
- `src/MCP/Prompts/ScaffoldCrudPrompt.php`: update prompt text to point at the new generator tool
- `tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php`: end-to-end MCP tool behavior tests
- `tests/Feature/Prompts/CorePromptsTest.php`: prompt guidance assertions
- `tests/Unit/CrudApiBlueprintTest.php`: naming and path derivation tests
- `tests/Unit/CrudApiFieldMapperTest.php`: field mapping and validation rule tests
- `tests/Unit/CrudApiRouteWriterTest.php`: route insertion and idempotency tests

## Chunk 1: Register The Generator Capability

### Task 1: Add the failing capability registration test

**Files:**
- Modify: `tests/Feature/CapabilityRegistryTest.php`
- Modify: `src/Capabilities/Generators/GeneratorsCapabilities.php`
- Test: `tests/Feature/CapabilityRegistryTest.php`

- [ ] **Step 1: Write the failing test**

Add an assertion to `tests/Feature/CapabilityRegistryTest.php` that `CapabilityRegistry::tools()` contains `\LaravelMcpSuite\MCP\Tools\LaravelCrudApiGenerateTool::class`.

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php
```

Expected: FAIL because the tool class is missing and `GeneratorsCapabilities::tools()` returns an empty array.

- [ ] **Step 3: Write minimal implementation**

Create the tool class stub at `src/MCP/Tools/LaravelCrudApiGenerateTool.php` and return it from `src/Capabilities/Generators/GeneratorsCapabilities.php`.

Minimal registration target:

```php
public function tools(): array
{
    return [
        \LaravelMcpSuite\MCP\Tools\LaravelCrudApiGenerateTool::class,
    ];
}
```

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/CapabilityRegistryTest.php src/Capabilities/Generators/GeneratorsCapabilities.php src/MCP/Tools/LaravelCrudApiGenerateTool.php
git commit -m "feat: register CRUD API generator tool"
```

## Chunk 2: Define The Generator Contracts

### Task 2: Lock down the blueprint and field mapping rules

**Files:**
- Create: `tests/Unit/CrudApiBlueprintTest.php`
- Create: `tests/Unit/CrudApiFieldMapperTest.php`
- Create: `src/Support/CrudApiBlueprint.php`
- Create: `src/Support/CrudApiFieldMapper.php`
- Test: `tests/Unit/CrudApiBlueprintTest.php`
- Test: `tests/Unit/CrudApiFieldMapperTest.php`

- [ ] **Step 1: Write the failing blueprint tests**

In `tests/Unit/CrudApiBlueprintTest.php`, add assertions that:

- resource `Post` maps to model `Post`
- route defaults to `posts`
- table defaults to `posts`
- generated paths include:
  - `app/Models/Post.php`
  - `app/Http/Requests/StorePostRequest.php`
  - `app/Http/Requests/UpdatePostRequest.php`
  - `app/Http/Resources/PostResource.php`
  - `app/Http/Controllers/Api/PostController.php`
  - `tests/Feature/Api/PostCrudTest.php`

- [ ] **Step 2: Write the failing field mapper tests**

In `tests/Unit/CrudApiFieldMapperTest.php`, add one test covering a field set like:

```php
[
    ['name' => 'title', 'type' => 'string', 'required' => true, 'rules' => ['string', 'max:255']],
    ['name' => 'body', 'type' => 'text', 'required' => true, 'rules' => ['string']],
    ['name' => 'published_at', 'type' => 'timestamp', 'required' => false, 'nullable' => true],
]
```

Assert that mapping yields:

- migration expressions for `title`, `body`, and nullable `published_at`
- store/update validation rules
- fillable fields excluding timestamps and id
- test payload arrays for create and update flows

- [ ] **Step 3: Run tests to verify they fail**

Run:

```bash
vendor/bin/phpunit tests/Unit/CrudApiBlueprintTest.php tests/Unit/CrudApiFieldMapperTest.php
```

Expected: FAIL because the support classes do not exist.

- [ ] **Step 4: Write minimal implementation**

Implement `CrudApiBlueprint` as a deterministic naming helper and `CrudApiFieldMapper` as a pure mapper with no file I/O.

Keep the mapper narrow:

- support v1 field types only
- normalize required vs nullable behavior
- expose small methods such as `migrationColumns()`, `storeRules()`, `updateRules()`, `fillable()`, and `testPayload()`

- [ ] **Step 5: Run tests to verify they pass**

Run:

```bash
vendor/bin/phpunit tests/Unit/CrudApiBlueprintTest.php tests/Unit/CrudApiFieldMapperTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add tests/Unit/CrudApiBlueprintTest.php tests/Unit/CrudApiFieldMapperTest.php src/Support/CrudApiBlueprint.php src/Support/CrudApiFieldMapper.php
git commit -m "feat: add CRUD generator blueprint and field mapping"
```

### Task 3: Lock down route writing behavior

**Files:**
- Create: `tests/Unit/CrudApiRouteWriterTest.php`
- Create: `src/Support/CrudApiRouteWriter.php`
- Test: `tests/Unit/CrudApiRouteWriterTest.php`

- [ ] **Step 1: Write the failing route writer tests**

Cover two cases in `tests/Unit/CrudApiRouteWriterTest.php`:

1. inserts `Route::apiResource('posts', \App\Http\Controllers\Api\PostController::class);` into an existing `routes/api.php`
2. does not insert the same line twice on repeated runs

Use `FileEditor` in the test where practical so the policy-integrated path is exercised.

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Unit/CrudApiRouteWriterTest.php
```

Expected: FAIL because the route writer does not exist.

- [ ] **Step 3: Write minimal implementation**

Implement `CrudApiRouteWriter` to:

- create `routes/api.php` if needed with the standard opening and `use Illuminate\Support\Facades\Route;`
- append one `Route::apiResource(...)` line if absent
- preserve existing content when the route already exists

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Unit/CrudApiRouteWriterTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/CrudApiRouteWriterTest.php src/Support/CrudApiRouteWriter.php
git commit -m "feat: add CRUD route writer"
```

## Chunk 3: Implement The MCP Tool

### Task 4: Add the failing MCP tool behavior tests

**Files:**
- Create: `tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php`
- Modify: `src/MCP/Tools/LaravelCrudApiGenerateTool.php`
- Test: `tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php`

- [ ] **Step 1: Write the failing feature tests**

Add tests for these behaviors:

1. denies generation outside `local`
2. rejects invalid payloads that omit `resource` or `fields`
3. generates the expected file set for `Post`
4. returns `meta.module = generators`
5. reports created and updated paths in structured output
6. does not duplicate the route entry on repeated runs

Use a request payload like:

```php
new Request([
    'resource' => 'Post',
    'fields' => [
        ['name' => 'title', 'type' => 'string', 'required' => true, 'rules' => ['string', 'max:255']],
        ['name' => 'body', 'type' => 'text', 'required' => true, 'rules' => ['string']],
    ],
])
```

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php
```

Expected: FAIL because the tool has no schema and no behavior.

- [ ] **Step 3: Write minimal implementation**

Implement `LaravelCrudApiGenerateTool` with:

- request schema for `resource`, `fields`, optional `model`, `route`, and `force`
- validation rules aligned with the schema
- environment guard using `EnvironmentPolicy`
- a call into `CrudApiGenerator`
- audited structured responses

Keep the tool itself thin. Do not put generation templates or route patch logic in the tool class.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php src/MCP/Tools/LaravelCrudApiGenerateTool.php
git commit -m "feat: add CRUD API generator mcp tool"
```

### Task 5: Implement the generator orchestration service

**Files:**
- Create: `src/Support/CrudApiGenerator.php`
- Modify: `src/MCP/Tools/LaravelCrudApiGenerateTool.php`
- Test: `tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php`

- [ ] **Step 1: Write the failing orchestration assertions**

Extend `tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php` to assert that a successful run creates:

- model file
- migration file
- store/update request files
- API resource file
- API controller file
- feature test file
- `routes/api.php` update

Also assert one generated file contains expected mapped content, such as `title` validation or `fillable`.

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php
```

Expected: FAIL because the tool does not yet orchestrate file generation.

- [ ] **Step 3: Write minimal implementation**

Implement `CrudApiGenerator` to:

- build a blueprint from request input
- map fields via `CrudApiFieldMapper`
- write model, requests, resource, controller, and feature test files through `FileEditor`
- generate one migration file with a timestamped filename
- update `routes/api.php` via `CrudApiRouteWriter`
- aggregate `created`, `updated`, `skipped`, and `collisions`

Use small renderer methods in the generator or extracted private helpers. Avoid a large string blob with every template jammed into `handle()`.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Support/CrudApiGenerator.php src/MCP/Tools/LaravelCrudApiGenerateTool.php tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php
git commit -m "feat: generate CRUD api files"
```

## Chunk 4: Integrate The Prompt Surface

### Task 6: Update prompt guidance and prompt tests

**Files:**
- Modify: `src/MCP/Prompts/ScaffoldCrudPrompt.php`
- Modify: `tests/Feature/Prompts/CorePromptsTest.php`
- Test: `tests/Feature/Prompts/CorePromptsTest.php`

- [ ] **Step 1: Write the failing prompt test**

Update `tests/Feature/Prompts/CorePromptsTest.php` so the scaffold CRUD prompt asserts:

- `laravel://app/routes` appears before `laravel://app/models`
- `laravel://app/models` appears before `laravel://docs/project-conventions`
- `laravel://docs/project-conventions` appears before `laravel-crud-api-generate`

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/Prompts/CorePromptsTest.php
```

Expected: FAIL because the prompt still refers to a generic generator rather than the concrete tool.

- [ ] **Step 3: Write minimal implementation**

Update `ScaffoldCrudPrompt` text to instruct clients to inspect routes, models, and conventions first, then call `laravel-crud-api-generate` when local write tooling is allowed.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/Prompts/CorePromptsTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/MCP/Prompts/ScaffoldCrudPrompt.php tests/Feature/Prompts/CorePromptsTest.php
git commit -m "feat: point scaffold prompt at CRUD generator tool"
```

## Chunk 5: Full Verification

### Task 7: Run the focused suite, then the package suite

**Files:**
- Verify only

- [ ] **Step 1: Run focused CRUD tests**

Run:

```bash
vendor/bin/phpunit \
  tests/Feature/CapabilityRegistryTest.php \
  tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php \
  tests/Feature/Prompts/CorePromptsTest.php \
  tests/Unit/CrudApiBlueprintTest.php \
  tests/Unit/CrudApiFieldMapperTest.php \
  tests/Unit/CrudApiRouteWriterTest.php
```

Expected: PASS.

- [ ] **Step 2: Run the full package test suite**

Run:

```bash
vendor/bin/phpunit
```

Expected: PASS with no regressions in existing tools, prompts, or package boot behavior.

- [ ] **Step 3: Commit final verification checkpoint**

```bash
git add src tests
git commit -m "test: verify CRUD generator integration"
```
