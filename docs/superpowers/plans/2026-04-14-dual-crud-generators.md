# Dual CRUD Generators Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add two MCP generator tools that create conventional Laravel CRUD code for both JSON APIs and web controllers/views when local code-edit tooling is enabled.

**Architecture:** Keep two public tools in the `generators` module and push the shared naming, field mapping, route writing, and file rendering into focused support classes. Reuse the package's existing environment policy, file editor policy, and audited response model rather than introducing a second write path.

**Tech Stack:** PHP 8.3, Laravel 13, `laravel/mcp`, Orchestra Testbench, PHPUnit 11

---

## File Map

- `src/Capabilities/Generators/GeneratorsCapabilities.php`: register both generator tools
- `src/MCP/Tools/LaravelCrudApiGenerateTool.php`: API CRUD MCP entrypoint
- `src/MCP/Tools/LaravelCrudWebGenerateTool.php`: web CRUD MCP entrypoint
- `src/Support/CrudBlueprint.php`: derive names, routes, tables, and file paths for both modes
- `src/Support/CrudFieldMapper.php`: convert structured fields into migration columns, validation rules, fillable arrays, and test payloads
- `src/Support/CrudRouteWriter.php`: idempotently insert route lines into `routes/api.php` and `routes/web.php`
- `src/Support/CrudGenerator.php`: orchestrate shared generation and aggregate created/updated/skipped paths
- `src/LaravelMcpSuiteServiceProvider.php`: bind generator support services
- `src/MCP/Prompts/ScaffoldCrudPrompt.php`: point clients at the concrete generator tools
- `config/laravel-mcp.php`: extend writable paths to include `resources` for Blade view generation
- `tests/Feature/CapabilityRegistryTest.php`: assert both tools are registered
- `tests/Unit/FileEditPolicyTest.php`: confirm `resources` is writable by default
- `tests/Unit/CrudBlueprintTest.php`: shared naming and path derivation tests
- `tests/Unit/CrudFieldMapperTest.php`: field mapping and rule generation tests
- `tests/Unit/CrudRouteWriterTest.php`: API and web route insertion idempotency tests
- `tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php`: API generator MCP behavior
- `tests/Feature/Tools/LaravelCrudWebGenerateToolTest.php`: web generator MCP behavior
- `tests/Feature/Prompts/CorePromptsTest.php`: prompt guidance assertions

## Chunk 1: Register The Generator Tools

### Task 1: Add failing registration coverage

**Files:**
- Modify: `tests/Feature/CapabilityRegistryTest.php`
- Modify: `src/Capabilities/Generators/GeneratorsCapabilities.php`
- Create: `src/MCP/Tools/LaravelCrudApiGenerateTool.php`
- Create: `src/MCP/Tools/LaravelCrudWebGenerateTool.php`
- Test: `tests/Feature/CapabilityRegistryTest.php`

- [ ] **Step 1: Write the failing test**

Add assertions that `CapabilityRegistry::tools()` contains both generator tool classes.

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php
```

Expected: FAIL because the tools are not registered.

- [ ] **Step 3: Write minimal implementation**

Create tool stubs and return them from `GeneratorsCapabilities::tools()`.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php
```

Expected: PASS.

## Chunk 2: Lock Down Shared Contracts

### Task 2: Add failing blueprint and mapper tests

**Files:**
- Create: `tests/Unit/CrudBlueprintTest.php`
- Create: `tests/Unit/CrudFieldMapperTest.php`
- Create: `src/Support/CrudBlueprint.php`
- Create: `src/Support/CrudFieldMapper.php`
- Test: `tests/Unit/CrudBlueprintTest.php`
- Test: `tests/Unit/CrudFieldMapperTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- API and web controller paths differ correctly
- web mode includes Blade view paths under `resources/views/<resource>/`
- field mapping yields migration lines, validation rules, fillable fields, and test payloads

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
vendor/bin/phpunit tests/Unit/CrudBlueprintTest.php tests/Unit/CrudFieldMapperTest.php
```

Expected: FAIL because the shared helper classes do not exist.

- [ ] **Step 3: Write minimal implementation**

Implement focused helper classes with no file I/O.

- [ ] **Step 4: Run tests to verify they pass**

Run:

```bash
vendor/bin/phpunit tests/Unit/CrudBlueprintTest.php tests/Unit/CrudFieldMapperTest.php
```

Expected: PASS.

### Task 3: Add failing route writer and file policy tests

**Files:**
- Modify: `tests/Unit/FileEditPolicyTest.php`
- Create: `tests/Unit/CrudRouteWriterTest.php`
- Create: `src/Support/CrudRouteWriter.php`
- Modify: `config/laravel-mcp.php`
- Test: `tests/Unit/FileEditPolicyTest.php`
- Test: `tests/Unit/CrudRouteWriterTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- `resources/views/...` is writable by the default file policy
- API route insertion writes one `Route::apiResource(...)` line once
- web route insertion writes one `Route::resource(...)` line once

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
vendor/bin/phpunit tests/Unit/FileEditPolicyTest.php tests/Unit/CrudRouteWriterTest.php
```

Expected: FAIL because `resources` is not writable yet and the route writer does not exist.

- [ ] **Step 3: Write minimal implementation**

Add `resources` to writable paths and implement an idempotent route writer for both modes.

- [ ] **Step 4: Run tests to verify they pass**

Run:

```bash
vendor/bin/phpunit tests/Unit/FileEditPolicyTest.php tests/Unit/CrudRouteWriterTest.php
```

Expected: PASS.

## Chunk 3: Implement The Shared Generator

### Task 4: Add failing API and web tool tests

**Files:**
- Create: `tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php`
- Create: `tests/Feature/Tools/LaravelCrudWebGenerateToolTest.php`
- Create: `src/Support/CrudGenerator.php`
- Modify: `src/LaravelMcpSuiteServiceProvider.php`
- Modify: `src/MCP/Tools/LaravelCrudApiGenerateTool.php`
- Modify: `src/MCP/Tools/LaravelCrudWebGenerateTool.php`
- Test: `tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php`
- Test: `tests/Feature/Tools/LaravelCrudWebGenerateToolTest.php`

- [ ] **Step 1: Write the failing tests**

API tool coverage:
- denied when code edits are disabled
- generates model, migration, requests, API resource, API controller, API feature test, and `routes/api.php` entry
- returns `meta.module = generators`
- repeated runs do not duplicate the API route

Web tool coverage:
- denied when code edits are disabled
- generates model, migration, requests, web controller, index/create/edit/show form views, web feature test, and `routes/web.php` entry
- repeated runs do not duplicate the web route

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php tests/Feature/Tools/LaravelCrudWebGenerateToolTest.php
```

Expected: FAIL because the tools and shared generator have no behavior.

- [ ] **Step 3: Write minimal implementation**

Implement `CrudGenerator` to render and write shared files plus mode-specific files, and wire both MCP tools to it.

- [ ] **Step 4: Run tests to verify they pass**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php tests/Feature/Tools/LaravelCrudWebGenerateToolTest.php
```

Expected: PASS.

## Chunk 4: Update Prompt Guidance

### Task 5: Add failing prompt tests

**Files:**
- Modify: `tests/Feature/Prompts/CorePromptsTest.php`
- Modify: `src/MCP/Prompts/ScaffoldCrudPrompt.php`
- Test: `tests/Feature/Prompts/CorePromptsTest.php`

- [ ] **Step 1: Write the failing test**

Assert that the scaffold prompt references both `laravel-crud-api-generate` and `laravel-crud-web-generate` after routes, models, and project conventions.

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/Prompts/CorePromptsTest.php
```

Expected: FAIL because the prompt still refers only to a generic generator.

- [ ] **Step 3: Write minimal implementation**

Update `ScaffoldCrudPrompt` text to point clients at the two concrete generator tools.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/Prompts/CorePromptsTest.php
```

Expected: PASS.

## Chunk 5: Run Focused Verification

### Task 6: Run focused verification

**Files:**
- Verify only: no additional files

- [ ] **Step 1: Run focused verification**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php tests/Unit/FileEditPolicyTest.php tests/Unit/CrudBlueprintTest.php tests/Unit/CrudFieldMapperTest.php tests/Unit/CrudRouteWriterTest.php tests/Feature/Tools/LaravelCrudApiGenerateToolTest.php tests/Feature/Tools/LaravelCrudWebGenerateToolTest.php tests/Feature/Prompts/CorePromptsTest.php
```

Expected: PASS.
