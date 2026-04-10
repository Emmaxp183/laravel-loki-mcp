# Laravel MCP Suite Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a reusable Laravel package on top of `laravel/mcp` that exposes safe Laravel-native tools, resources, and prompts, plus a demo app and setup docs for `Codex CLI` and `Claude Code`.

**Architecture:** Use `laravel/mcp` as the server and protocol foundation, then layer a package-owned capability registry, policy guard, sanitizer pipeline, and install command on top. Keep v1 read-first, enable safe write tools automatically in `local`, and prove the package through a minimal demo app and client documentation.

**Tech Stack:** PHP 8.3+, Laravel package development, `laravel/mcp`, Orchestra Testbench, PHPUnit, Composer, Markdown docs

---

## File Structure

This plan assumes a standalone package repository with these main boundaries:

- Package source lives under `src/`, `config/`, `resources/`, and `stubs/`
- Package tests live under `tests/`
- Demo application lives under `demo/laravel-app/`
- Product docs live under `docs/`

Planned high-responsibility files:

- `composer.json`: package dependencies, autoloading, scripts
- `src/LaravelMcpSuiteServiceProvider.php`: package bootstrapping and publishing
- `config/laravel-mcp.php`: modules, server defaults, allowlists, write-tool policy
- `src/MCP/Servers/LaravelAppServer.php`: default server aggregating enabled capabilities
- `src/Support/CapabilityRegistry.php`: discovers enabled tools, resources, and prompts
- `src/Policies/EnvironmentPolicy.php`: determines read or write availability by environment
- `src/Sanitizers/OutputSanitizer.php`: centralized redaction pipeline
- `src/Console/Commands/InstallMcpCommand.php`: install flow and snippet generation
- `src/MCP/Tools/*`: one class per tool
- `src/MCP/Resources/*`: one class per resource
- `src/MCP/Prompts/*`: one class per prompt
- `tests/Feature/*`: package integration tests through Testbench
- `tests/Unit/*`: focused unit tests for policy and sanitizer behavior
- `demo/laravel-app/routes/ai.php`: demo server registration
- `docs/clients/codex-cli.md`: Codex CLI setup
- `docs/clients/claude-code.md`: Claude Code setup

## Chunk 1: Package Skeleton And Runtime Foundations

### Task 1: Bootstrap the package repository

**Files:**
- Create: `composer.json`
- Create: `src/LaravelMcpSuiteServiceProvider.php`
- Create: `phpunit.xml`
- Create: `tests/TestCase.php`
- Create: `tests/Feature/PackageBootTest.php`
- Create: `tests/Fixtures/.gitkeep`
- Create: `README.md`

- [ ] **Step 1: Write the failing bootstrap test**

```php
<?php

namespace LaravelMcpSuite\Tests\Feature;

use LaravelMcpSuite\Tests\TestCase;

class PackageBootTest extends TestCase
{
    public function test_service_provider_loads(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(\LaravelMcpSuite\LaravelMcpSuiteServiceProvider::class));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/PackageBootTest.php`
Expected: FAIL because package autoloading or service provider wiring does not exist yet.

- [ ] **Step 3: Write minimal implementation**

Create `composer.json` with:

```json
{
  "name": "your-vendor/laravel-mcp-suite",
  "type": "library",
  "require": {
    "php": "^8.3",
    "laravel/framework": "^12.0|^13.0",
    "laravel/mcp": "^1.0"
  },
  "require-dev": {
    "orchestra/testbench": "^10.0",
    "phpunit/phpunit": "^11.0"
  },
  "autoload": {
    "psr-4": {
      "LaravelMcpSuite\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "LaravelMcpSuite\\Tests\\": "tests/"
    }
  }
}
```

Create `src/LaravelMcpSuiteServiceProvider.php` with a minimal `ServiceProvider` subclass that registers config and command hooks.

Create `phpunit.xml` with:

- `testsuites` for `Unit` and `Feature`
- bootstrap to Composer autoload
- sane failure output settings

Create `tests/TestCase.php` extending `Orchestra\Testbench\TestCase` and loading the package provider.

- [ ] **Step 4: Run test to verify it passes**

Run: `composer install && vendor/bin/phpunit tests/Feature/PackageBootTest.php`
Expected: PASS with 1 test passing.

- [ ] **Step 5: Commit**

```bash
git add composer.json src/LaravelMcpSuiteServiceProvider.php phpunit.xml tests/TestCase.php tests/Feature/PackageBootTest.php README.md
git commit -m "chore: bootstrap laravel mcp suite package"
```

### Task 2: Add package config and environment policy

**Files:**
- Create: `config/laravel-mcp.php`
- Create: `src/Policies/EnvironmentPolicy.php`
- Create: `tests/Unit/EnvironmentPolicyTest.php`
- Modify: `src/LaravelMcpSuiteServiceProvider.php`

- [ ] **Step 1: Write the failing policy test**

```php
<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Policies\EnvironmentPolicy;
use PHPUnit\Framework\TestCase;

class EnvironmentPolicyTest extends TestCase
{
    public function test_read_tools_are_enabled_in_all_supported_environments(): void
    {
        $policy = new EnvironmentPolicy([
            'write_tools' => [
                'enabled_in_local' => true,
                'enabled_elsewhere' => false,
            ],
        ]);

        $this->assertTrue($policy->readToolsEnabled('local'));
        $this->assertTrue($policy->readToolsEnabled('testing'));
        $this->assertTrue($policy->readToolsEnabled('staging'));
        $this->assertTrue($policy->readToolsEnabled('production'));
    }

    public function test_write_tools_are_enabled_in_local_only_by_default(): void
    {
        $policy = new EnvironmentPolicy([
            'write_tools' => [
                'enabled_in_local' => true,
                'enabled_elsewhere' => false,
            ],
        ]);

        $this->assertTrue($policy->writeToolsEnabled('local'));
        $this->assertFalse($policy->writeToolsEnabled('testing'));
        $this->assertFalse($policy->writeToolsEnabled('staging'));
        $this->assertFalse($policy->writeToolsEnabled('production'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/EnvironmentPolicyTest.php`
Expected: FAIL because the policy class does not exist.

- [ ] **Step 3: Write minimal implementation**

Create `config/laravel-mcp.php` with sections for:

```php
return [
    'server' => [
        'name' => env('LARAVEL_MCP_SERVER_NAME', 'Laravel App Server'),
        'local_command' => 'app',
        'enable_web_server' => false,
        'web_path' => '/mcp/app',
    ],
    'modules' => [
        'core' => true,
        'artisan' => true,
        'database' => true,
        'logs' => true,
        'tests' => true,
        'queues' => false,
        'generators' => true,
    ],
    'write_tools' => [
        'enabled_in_local' => true,
        'enabled_elsewhere' => false,
    ],
    'artisan' => [
        'allowlist' => [
            'about',
            'route:list',
            'test',
            'migrate:status',
            'queue:failed',
        ],
    ],
    'redaction' => [
        'enabled' => true,
    ],
];
```

Create `src/Policies/EnvironmentPolicy.php` with methods:

```php
public function readToolsEnabled(string $environment): bool;
public function writeToolsEnabled(string $environment): bool;
```

Update `src/LaravelMcpSuiteServiceProvider.php` to:

- `mergeConfigFrom(__DIR__.'/../config/laravel-mcp.php', 'laravel-mcp')`
- publish the config file with a package tag
- register `EnvironmentPolicy` in the container

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/EnvironmentPolicyTest.php`
Expected: PASS with 2 tests passing.

- [ ] **Step 5: Commit**

```bash
git add config/laravel-mcp.php src/Policies/EnvironmentPolicy.php tests/Unit/EnvironmentPolicyTest.php src/LaravelMcpSuiteServiceProvider.php
git commit -m "feat: add environment policy defaults"
```

### Task 3: Build the sanitizer pipeline

**Files:**
- Create: `src/Sanitizers/OutputSanitizer.php`
- Create: `tests/Unit/OutputSanitizerTest.php`

- [ ] **Step 1: Write the failing sanitizer test**

```php
<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Sanitizers\OutputSanitizer;
use PHPUnit\Framework\TestCase;

class OutputSanitizerTest extends TestCase
{
    public function test_it_redacts_common_secret_patterns(): void
    {
        $sanitizer = new OutputSanitizer();

        $result = $sanitizer->sanitize([
            'token' => 'Bearer abc123secret',
            'password' => 'super-secret',
            'api_key' => 'sk-live-abc123',
            'dsn' => 'mysql://user:pass@example.com:3306/db',
            'private_key' => '-----BEGIN PRIVATE KEY----- abc -----END PRIVATE KEY-----',
            'message' => 'DB_PASSWORD=hidden-value',
        ]);

        $this->assertSame('[REDACTED]', $result['password']);
        $this->assertSame('[REDACTED]', $result['api_key']);
        $this->assertStringNotContainsString('abc123secret', $result['token']);
        $this->assertStringNotContainsString('pass@example.com', $result['dsn']);
        $this->assertStringNotContainsString('BEGIN PRIVATE KEY', $result['private_key']);
        $this->assertStringNotContainsString('hidden-value', $result['message']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/OutputSanitizerTest.php`
Expected: FAIL because the sanitizer does not exist.

- [ ] **Step 3: Write minimal implementation**

Create `src/Sanitizers/OutputSanitizer.php` with:

- Recursive array sanitization
- Key-based redaction for `password`, `token`, `secret`, `key`, `cookie`
- String pattern redaction for `.env` style assignments, DSN secrets, private keys, API keys, and bearer tokens

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/OutputSanitizerTest.php`
Expected: PASS with all assertions passing.

- [ ] **Step 5: Commit**

```bash
git add src/Sanitizers/OutputSanitizer.php tests/Unit/OutputSanitizerTest.php
git commit -m "feat: add output sanitization pipeline"
```

### Task 4: Add capability registry and default server

**Files:**
- Create: `src/MCP/Tools/LaravelAppInfoTool.php`
- Create: `src/MCP/Resources/RoutesResource.php`
- Create: `src/MCP/Prompts/DebugLastExceptionPrompt.php`
- Create: `src/Capabilities/Core/CoreCapabilities.php`
- Create: `src/Capabilities/Artisan/ArtisanCapabilities.php`
- Create: `src/Capabilities/Database/DatabaseCapabilities.php`
- Create: `src/Capabilities/Logs/LogsCapabilities.php`
- Create: `src/Capabilities/Tests/TestsCapabilities.php`
- Create: `src/Capabilities/Queues/QueuesCapabilities.php`
- Create: `src/Capabilities/Generators/GeneratorsCapabilities.php`
- Create: `src/Support/CapabilityRegistry.php`
- Create: `src/MCP/Servers/LaravelAppServer.php`
- Create: `tests/Feature/CapabilityRegistryTest.php`

- [ ] **Step 1: Write the failing registry test**

```php
<?php

namespace LaravelMcpSuite\Tests\Feature;

use LaravelMcpSuite\Support\CapabilityRegistry;
use LaravelMcpSuite\Tests\TestCase;

class CapabilityRegistryTest extends TestCase
{
    public function test_it_returns_enabled_capabilities_for_the_server(): void
    {
        $registry = $this->app->make(CapabilityRegistry::class);

        $this->assertContains(\LaravelMcpSuite\MCP\Tools\LaravelAppInfoTool::class, $registry->tools());
        $this->assertContains(\LaravelMcpSuite\MCP\Resources\RoutesResource::class, $registry->resources());
    }

    public function test_server_resolves_with_registry_backed_capabilities(): void
    {
        $server = $this->app->make(\LaravelMcpSuite\MCP\Servers\LaravelAppServer::class);

        $this->assertContains(\LaravelMcpSuite\MCP\Tools\LaravelAppInfoTool::class, $server->tools());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php`
Expected: FAIL because the registry and placeholder capability classes do not exist.

- [ ] **Step 3: Write minimal implementation**

Create `src/Support/CapabilityRegistry.php` that:

- Reads enabled modules from config
- Loads capability providers from:
  - `src/Capabilities/Core/CoreCapabilities.php`
  - `src/Capabilities/Artisan/ArtisanCapabilities.php`
  - `src/Capabilities/Database/DatabaseCapabilities.php`
  - `src/Capabilities/Logs/LogsCapabilities.php`
  - `src/Capabilities/Tests/TestsCapabilities.php`
  - `src/Capabilities/Queues/QueuesCapabilities.php`
  - `src/Capabilities/Generators/GeneratorsCapabilities.php`
- Returns class lists for tools, resources, and prompts from those module definitions

Create placeholder classes only for the registry assertions in this chunk:

- `src/MCP/Tools/LaravelAppInfoTool.php`
- `src/MCP/Resources/RoutesResource.php`
- `src/MCP/Prompts/DebugLastExceptionPrompt.php`

Create `src/MCP/Servers/LaravelAppServer.php` extending `Laravel\Mcp\Server` and assigning:

```php
protected array $tools = [];
protected array $resources = [];
protected array $prompts = [];
```

Populate those arrays in the constructor or from registry helpers so the server remains the aggregation point.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php`
Expected: PASS with enabled capabilities returned from config.

- [ ] **Step 5: Commit**

```bash
git add src/Capabilities src/Support/CapabilityRegistry.php src/MCP/Servers/LaravelAppServer.php tests/Feature/CapabilityRegistryTest.php src/MCP/Tools/LaravelAppInfoTool.php src/MCP/Resources/RoutesResource.php src/MCP/Prompts/DebugLastExceptionPrompt.php
git commit -m "feat: add capability registry and default mcp server"
```

## Chunk 2: Read-Only Tools, Resources, And Prompt Foundations

### Task 5: Implement `laravel_app_info`

**Files:**
- Create: `src/MCP/Tools/LaravelAppInfoTool.php`
- Create: `tests/Feature/Tools/LaravelAppInfoToolTest.php`

- [ ] **Step 1: Write the failing feature test**

```php
<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use LaravelMcpSuite\MCP\Tools\LaravelAppInfoTool;
use LaravelMcpSuite\Tests\TestCase;

class LaravelAppInfoToolTest extends TestCase
{
    public function test_it_returns_framework_and_environment_metadata(): void
    {
        $tool = $this->app->make(LaravelAppInfoTool::class);
        $response = $tool->handle(request());

        $payload = $response->toArray();

        $this->assertSame('local', $payload['data']['app']['environment']);
        $this->assertArrayHasKey('laravel_version', $payload['data']['framework']);
        $this->assertTrue($payload['meta']['read_only']);
        $this->assertSame('core', $payload['meta']['module']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelAppInfoToolTest.php`
Expected: FAIL because the tool implementation does not exist or does not return structured output.

- [ ] **Step 3: Write minimal implementation**

Implement `LaravelAppInfoTool` with:

- MCP name `laravel_app_info`
- Empty input schema
- failing tests that prove the tool returns structured output and read-only metadata
- failing tests that prove read-tool environment and authorization checks succeed in supported environments
- Structured payload with `summary`, `data`, `warnings`, `meta`
- App environment, debug flag, PHP version, Laravel version
- Optional integration detection for Horizon and Telescope
- `meta.read_only = true` and module or environment metadata

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelAppInfoToolTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/MCP/Tools/LaravelAppInfoTool.php tests/Feature/Tools/LaravelAppInfoToolTest.php
git commit -m "feat: add laravel app info tool"
```

### Task 6: Implement route inventory tool and resource

**Files:**
- Create: `src/MCP/Tools/LaravelRoutesListTool.php`
- Create: `src/MCP/Resources/RoutesResource.php`
- Create: `tests/Feature/Tools/LaravelRoutesListToolTest.php`
- Create: `tests/Feature/Resources/RoutesResourceTest.php`

- [ ] **Step 1: Write the failing route tool and resource tests**

```php
public function test_route_tool_returns_named_routes(): void
{
    $response = $this->app->make(\LaravelMcpSuite\MCP\Tools\LaravelRoutesListTool::class)->handle(request());
    $payload = $response->toArray();

    $this->assertNotEmpty($payload['data']['routes']);
    $this->assertTrue($payload['meta']['read_only']);
}
```

```php
public function test_routes_resource_returns_route_context(): void
{
    $response = $this->app->make(\LaravelMcpSuite\MCP\Resources\RoutesResource::class)->handle(request());
    $payload = $response->toArray();

    $this->assertArrayHasKey('by_method', $payload['data']);
    $this->assertArrayHasKey('by_controller', $payload['data']);
    $this->assertTrue($payload['meta']['read_only']);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelRoutesListToolTest.php tests/Feature/Resources/RoutesResourceTest.php`
Expected: FAIL because route capability classes do not exist.

- [ ] **Step 3: Write minimal implementation**

Implement the tool and resource using the Laravel router to return:

- tool payload fields:
  - URI
  - methods
  - route name
  - action
  - middleware summary
- resource payload fields:
  - grouped route inventory by method
  - grouped route inventory by controller
  - cache-friendly summary structure for `laravel://app/routes`

Keep the actual route extraction logic in one small support class:

- Create: `src/Support/RouteInspector.php`

Require input-schema handling for:

- `method`
- `middleware`
- `path_contains`

Require the failing tests to assert:

- the tool honors route filters from its input schema
- the tool returns `summary`, `data`, `warnings`, and `meta`
- the resource returns `by_method` and `by_controller`
- both tool and resource return `meta.read_only = true`

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelRoutesListToolTest.php tests/Feature/Resources/RoutesResourceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/MCP/Tools/LaravelRoutesListTool.php src/MCP/Resources/RoutesResource.php src/Support/RouteInspector.php tests/Feature/Tools/LaravelRoutesListToolTest.php tests/Feature/Resources/RoutesResourceTest.php
git commit -m "feat: add route tool and resource"
```

### Task 7: Implement schema tool and schema resource

**Files:**
- Create: `src/MCP/Tools/LaravelDbSchemaReadTool.php`
- Create: `src/MCP/Resources/SchemaResource.php`
- Create: `src/Support/SchemaInspector.php`
- Create: `tests/Feature/Tools/LaravelDbSchemaReadToolTest.php`
- Create: `tests/Feature/Resources/SchemaResourceTest.php`

- [ ] **Step 1: Write the failing schema tests**

Add:

- one tool test asserting schema payload includes a table list and supports `table` filtering
- one resource test asserting `laravel://db/schema` returns a read-only schema overview
- one assertion that `meta.read_only = true`
- one assertion that the tool returns expected environment or module metadata
- one assertion that the tool returns `summary`, `data`, `warnings`, and `meta`
- one assertion that the read-tool guard and authorization path succeeds for supported environments

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelDbSchemaReadToolTest.php tests/Feature/Resources/SchemaResourceTest.php`
Expected: FAIL because the inspector, tool, and resource do not exist.

- [ ] **Step 3: Write minimal implementation**

Implement:

- `SchemaInspector` for read-only table, column, and index summaries
- `LaravelDbSchemaReadTool`
- `SchemaResource`

Require explicit input-schema handling for the optional `table` filter and return structured `meta` fields.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelDbSchemaReadToolTest.php tests/Feature/Resources/SchemaResourceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/MCP/Tools/LaravelDbSchemaReadTool.php src/MCP/Resources/SchemaResource.php src/Support/SchemaInspector.php tests/Feature/Tools/LaravelDbSchemaReadToolTest.php tests/Feature/Resources/SchemaResourceTest.php
git commit -m "feat: add schema read capabilities"
```

### Task 8: Implement log and recent-exception read capabilities

**Files:**
- Create: `src/MCP/Tools/LaravelLogsRecentTool.php`
- Create: `src/MCP/Resources/RecentErrorsResource.php`
- Create: `src/Support/LogReader.php`
- Create: `src/Support/ExceptionSummarizer.php`
- Create: `tests/Feature/Tools/LaravelLogsRecentToolTest.php`
- Create: `tests/Feature/Resources/RecentErrorsResourceTest.php`

- [ ] **Step 1: Write the failing log and recent-error tests**

Add:

- one tool test asserting the `lines` and `level` input schema is enforced and sanitized output is returned
- one resource test asserting `laravel://app/errors/recent` returns recent exception summaries, not raw log lines
- one assertion that both responses include `meta.read_only = true`
- one assertion that sanitization removes credential-like strings from tool and resource payloads
- one assertion that both responses return `summary`, `data`, `warnings`, and `meta`
- one assertion that the read-tool guard and authorization path succeeds for supported environments

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelLogsRecentToolTest.php tests/Feature/Resources/RecentErrorsResourceTest.php`
Expected: FAIL because the reader, summarizer, tool, and resource do not exist.

- [ ] **Step 3: Write minimal implementation**

Implement:

- `LogReader` for reading recent log lines with `lines` and `level` filtering
- `ExceptionSummarizer` for extracting recent exception summaries from logs
- `LaravelLogsRecentTool`
- `RecentErrorsResource`

Pass both log and exception output through `OutputSanitizer`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelLogsRecentToolTest.php tests/Feature/Resources/RecentErrorsResourceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/MCP/Tools/LaravelLogsRecentTool.php src/MCP/Resources/RecentErrorsResource.php src/Support/LogReader.php src/Support/ExceptionSummarizer.php tests/Feature/Tools/LaravelLogsRecentToolTest.php tests/Feature/Resources/RecentErrorsResourceTest.php
git commit -m "feat: add log and exception read capabilities"
```

### Task 9: Implement project conventions resource

**Files:**
- Create: `src/MCP/Resources/ProjectConventionsResource.php`
- Create: `tests/Feature/Resources/ProjectConventionsResourceTest.php`
- Create: `resources/project-conventions/default.md`

- [ ] **Step 1: Write the failing resource test**

Add assertions that `laravel://docs/project-conventions`:

- resolves successfully
- returns markdown content
- includes `meta.read_only = true`
- prefers an app-owned conventions file when present
- falls back to the package default document when the app file is absent

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Resources/ProjectConventionsResourceTest.php`
Expected: FAIL because the resource and default document do not exist.

- [ ] **Step 3: Write minimal implementation**

Implement `ProjectConventionsResource` to read:

- published app-owned conventions file when present
- package default conventions markdown otherwise

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Resources/ProjectConventionsResourceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/MCP/Resources/ProjectConventionsResource.php tests/Feature/Resources/ProjectConventionsResourceTest.php resources/project-conventions/default.md
git commit -m "feat: add project conventions resource"
```

### Task 10: Implement prompt foundations and core prompt pack

**Files:**
- Create: `src/MCP/Prompts/DebugLastExceptionPrompt.php`
- Create: `src/MCP/Prompts/ScaffoldCrudPrompt.php`
- Create: `src/MCP/Prompts/GenerateFeatureTestPrompt.php`
- Create: `src/MCP/Prompts/ReviewRouteControllerConsistencyPrompt.php`
- Create: `tests/Feature/Prompts/CorePromptsTest.php`

- [ ] **Step 1: Write the failing prompt test**

Add assertions that the prompt registry exposes the four v1 prompts and that required prompt arguments are declared where applicable.
Also assert each prompt body references the spec-defined capabilities in the expected order:

- `/laravel:debug-last-exception` references `laravel://app/errors/recent`, `laravel_exception_last`, then `laravel_logs_recent`
- `/laravel:scaffold-crud` references `laravel://app/routes`, `laravel://app/models`, and `laravel://docs/project-conventions` before generator behavior
- `/laravel:generate-feature-test` references route or controller context, then `laravel://docs/project-conventions`, then test generation
- `/laravel:review-route-controller-consistency` references `laravel://app/routes` before consistency checks

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Prompts/CorePromptsTest.php`
Expected: FAIL because prompt classes do not exist.

- [ ] **Step 3: Write minimal implementation**

Create the four prompt classes with:

- explicit name and description metadata
- arguments only where the prompt truly needs external input
- body text that teaches the client what tools and resources to call, in order
- prompt flows that explicitly reference `ProjectConventionsResource` where the spec requires it

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Prompts/CorePromptsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/MCP/Prompts/DebugLastExceptionPrompt.php src/MCP/Prompts/ScaffoldCrudPrompt.php src/MCP/Prompts/GenerateFeatureTestPrompt.php src/MCP/Prompts/ReviewRouteControllerConsistencyPrompt.php tests/Feature/Prompts/CorePromptsTest.php
git commit -m "feat: add core prompt pack"
```

## Chunk 3: Safe Write Tools, Install Flow, Demo App, And Documentation

### Task 11: Implement safe Artisan inventory and execution

**Files:**
- Create: `src/MCP/Tools/LaravelArtisanCommandsTool.php`
- Create: `src/MCP/Tools/LaravelArtisanRunSafeTool.php`
- Create: `src/Support/ArtisanCommandPolicy.php`
- Create: `tests/Feature/Tools/LaravelArtisanCommandsToolTest.php`
- Create: `tests/Feature/Tools/LaravelArtisanRunSafeToolTest.php`

- [ ] **Step 1: Write the failing Artisan tests**

Add:

- one test asserting the command inventory includes an allowlist marker
- one test asserting an allowlisted command succeeds in `local`
- one test asserting a non-allowlisted command is rejected
- one test asserting safe execution is denied outside `local`
- one assertion that the structured response includes command and argument handling metadata

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelArtisanCommandsToolTest.php tests/Feature/Tools/LaravelArtisanRunSafeToolTest.php`
Expected: FAIL because the classes do not exist.

- [ ] **Step 3: Write minimal implementation**

Implement:

- `ArtisanCommandPolicy` to match configured allowlist
- `LaravelArtisanCommandsTool` for command inventory
- `LaravelArtisanRunSafeTool` for allowlisted execution only

Make the run-safe tool consult `EnvironmentPolicy` before dispatch.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelArtisanCommandsToolTest.php tests/Feature/Tools/LaravelArtisanRunSafeToolTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/MCP/Tools/LaravelArtisanCommandsTool.php src/MCP/Tools/LaravelArtisanRunSafeTool.php src/Support/ArtisanCommandPolicy.php tests/Feature/Tools/LaravelArtisanCommandsToolTest.php tests/Feature/Tools/LaravelArtisanRunSafeToolTest.php
git commit -m "feat: add safe artisan capabilities"
```

### Task 12: Implement model, config, exception, and test tools

**Files:**
- Create: `src/MCP/Tools/LaravelModelsListTool.php`
- Create: `src/MCP/Tools/LaravelModelDescribeTool.php`
- Create: `src/MCP/Tools/LaravelExceptionLastTool.php`
- Create: `src/MCP/Tools/LaravelConfigSummaryTool.php`
- Create: `src/MCP/Tools/LaravelTestsRunTool.php`
- Create: `src/MCP/Resources/ModelsResource.php`
- Create: `src/Support/ModelInspector.php`
- Create: `tests/Feature/Tools/LaravelModelsListToolTest.php`
- Create: `tests/Feature/Tools/LaravelModelDescribeToolTest.php`
- Create: `tests/Feature/Tools/LaravelExceptionLastToolTest.php`
- Create: `tests/Feature/Tools/LaravelConfigSummaryToolTest.php`
- Create: `tests/Feature/Tools/LaravelTestsRunToolTest.php`
- Create: `tests/Feature/Resources/ModelsResourceTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:

- model discovery
- model description fields
- models resource output
- exception summary extraction
- config summary without secrets
- constrained test execution argument handling

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelModelsListToolTest.php tests/Feature/Tools/LaravelModelDescribeToolTest.php tests/Feature/Tools/LaravelExceptionLastToolTest.php tests/Feature/Tools/LaravelConfigSummaryToolTest.php tests/Feature/Tools/LaravelTestsRunToolTest.php tests/Feature/Resources/ModelsResourceTest.php`
Expected: FAIL because the implementations do not exist.

- [ ] **Step 3: Write minimal implementation**

Add `ModelInspector` for model discovery and relationship hints. Implement each tool with structured output and sanitizer integration where needed.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelModelsListToolTest.php tests/Feature/Tools/LaravelModelDescribeToolTest.php tests/Feature/Tools/LaravelExceptionLastToolTest.php tests/Feature/Tools/LaravelConfigSummaryToolTest.php tests/Feature/Tools/LaravelTestsRunToolTest.php tests/Feature/Resources/ModelsResourceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/MCP/Tools/LaravelModelsListTool.php src/MCP/Tools/LaravelModelDescribeTool.php src/MCP/Tools/LaravelExceptionLastTool.php src/MCP/Tools/LaravelConfigSummaryTool.php src/MCP/Tools/LaravelTestsRunTool.php src/MCP/Resources/ModelsResource.php src/Support/ModelInspector.php tests/Feature/Tools/LaravelModelsListToolTest.php tests/Feature/Tools/LaravelModelDescribeToolTest.php tests/Feature/Tools/LaravelExceptionLastToolTest.php tests/Feature/Tools/LaravelConfigSummaryToolTest.php tests/Feature/Tools/LaravelTestsRunToolTest.php tests/Feature/Resources/ModelsResourceTest.php
git commit -m "feat: add remaining v1 tools"
```

### Task 13: Build `mcp:install` and config snippet generation

**Files:**
- Create: `src/Console/Commands/InstallMcpCommand.php`
- Create: `stubs/routes.ai.stub`
- Create: `stubs/project-conventions.stub`
- Create: `src/Support/ClientSnippetRenderer.php`
- Create: `tests/Feature/Console/InstallMcpCommandTest.php`

- [ ] **Step 1: Write the failing install command test**

Add assertions that running `php artisan mcp:install`:

- publishes the config file
- writes or appends MCP route registration
- creates or publishes a `project-conventions` markdown resource
- preserves or publishes the Artisan allowlist section in config
- emits `Codex CLI` and `Claude Code` snippets
- prints a clear note that write-capable tools are enabled automatically only in `local`

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Console/InstallMcpCommandTest.php`
Expected: FAIL because the command does not exist.

- [ ] **Step 3: Write minimal implementation**

Implement `InstallMcpCommand` to:

- publish `config/laravel-mcp.php`
- create `routes/ai.php` when missing
- add `Mcp::local(...)` registration for the package server
- optionally scaffold a protected web server block
- create or publish `docs/project-conventions.md` from `stubs/project-conventions.stub`
- confirm the generated config includes the safe Artisan allowlist section
- render markdown or console snippets for `Codex CLI` and `Claude Code`
- print the local-only write-tools rule in command output

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Console/InstallMcpCommandTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Console/Commands/InstallMcpCommand.php stubs/routes.ai.stub stubs/project-conventions.stub src/Support/ClientSnippetRenderer.php tests/Feature/Console/InstallMcpCommandTest.php
git commit -m "feat: add install command and client snippets"
```

### Task 14: Create the demo application and seed proof-of-use context

**Files:**
- Create: `demo/laravel-app/`
- Create: `demo/laravel-app/routes/web.php`
- Create: `demo/laravel-app/routes/ai.php`
- Create: `demo/laravel-app/app/Models/Project.php`
- Create: `demo/laravel-app/database/migrations/*`
- Create: `demo/laravel-app/database/seeders/DatabaseSeeder.php`
- Create: `demo/laravel-app/storage/logs/laravel.log` (fixture or generated in setup)
- Create: `tests/Feature/Demo/DemoAppSmokeTest.php`

- [ ] **Step 1: Write the failing demo smoke test**

Add a smoke test or scripted check that the demo app:

- boots with the package installed
- exposes the expected MCP registration entrypoint
- has seeded model data
- exposes named routes visible to route inventory
- exposes schema visible to schema inspection
- includes sanitized log data visible to log inspection
- renders client setup snippets through the install flow

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Demo/DemoAppSmokeTest.php`
Expected: FAIL because the demo app does not exist.

- [ ] **Step 3: Write minimal implementation**

Create a small demo app with:

- one model and migration
- seeded model records
- a few named routes
- `routes/ai.php` registration for the package server
- package installation wired through the demo app
- a sample log entry containing redactable secrets for sanitizer proof
- sample project conventions markdown content

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Demo/DemoAppSmokeTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add demo/laravel-app tests/Feature/Demo/DemoAppSmokeTest.php
git commit -m "feat: add demo laravel app"
```

### Task 15: Write end-user documentation

**Files:**
- Modify: `README.md`
- Create: `docs/installation.md`
- Create: `docs/safety-model.md`
- Create: `docs/clients/codex-cli.md`
- Create: `docs/clients/claude-code.md`
- Create: `docs/extending.md`

- [ ] **Step 1: Write the failing docs checklist**

Create a manual verification checklist in:

- Create: `docs/release-checklist.md`

Include checks for:

- install flow
- local-only write behavior
- client setup steps
- custom extension guidance
- Laravel and PHP version requirements
- local server startup expectations
- optional web-mode auth guidance
- policy override guidance

- [ ] **Step 2: Run docs verification to confirm gaps**

Run: `rg -n "TODO|TBD|coming soon" README.md docs || true`
Expected: either missing files or placeholders that confirm docs are incomplete.

- [ ] **Step 3: Write the documentation**

Document:

- package purpose
- quick start
- exact install command
- Laravel and PHP version support
- local server startup expectations
- `Codex CLI` setup
- `Codex CLI` web-mode auth notes
- `Claude Code` setup
- `Claude Code` optional remote wiring
- safety guarantees and exclusions
- adding a custom tool, resource, or prompt
- policy and environment overrides

- [ ] **Step 4: Run section-level docs verification**

Run: `rg -n "Laravel and PHP version|local server|Codex CLI|Claude Code|auth|policy override|custom tool|custom resource|custom prompt" README.md docs`
Expected: matches in the expected files, confirming each required section exists.

- [ ] **Step 5: Run placeholder verification**

Run: `rg -n "TODO|TBD|coming soon" README.md docs`
Expected: no matches.

- [ ] **Step 6: Commit**

```bash
git add README.md docs/installation.md docs/safety-model.md docs/clients/codex-cli.md docs/clients/claude-code.md docs/extending.md docs/release-checklist.md
git commit -m "docs: add installation and client setup guides"
```

### Task 16: Add audit logging for tool calls

**Files:**
- Create: `src/Support/AuditLogger.php`
- Create: `src/Support/AuditedToolRunner.php`
- Create: `tests/Feature/Audit/AuditLoggerTest.php`

- [ ] **Step 1: Write the failing audit test**

Add assertions that both allowed and denied tool calls record:

- timestamp
- tool name
- environment
- allowed or denied result
- sanitized argument summary

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Audit/AuditLoggerTest.php`
Expected: FAIL because audit support does not exist.

- [ ] **Step 3: Write minimal implementation**

Implement:

- `AuditLogger` for structured audit entries
- `AuditedToolRunner` or equivalent wrapper that records tool execution results without duplicating tool business logic

Apply the wrapper to every tool path so both read and write capabilities produce audit records.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Audit/AuditLoggerTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Support/AuditLogger.php src/Support/AuditedToolRunner.php tests/Feature/Audit/AuditLoggerTest.php
git commit -m "feat: add tool audit logging"
```

### Task 17: Run the final package verification matrix

**Files:**
- Modify: `docs/release-checklist.md`

- [ ] **Step 1: Run the unit and feature suite**

Run: `vendor/bin/phpunit`
Expected: PASS with all package tests green.

- [ ] **Step 2: Verify install flow in the demo app**

Run: `cd demo/laravel-app && php artisan mcp:install`
Expected: PASS with config published, MCP route registered, and client snippets rendered.

- [ ] **Step 3: Verify local-only write behavior**

Run: `vendor/bin/phpunit tests/Feature/Tools/LaravelArtisanRunSafeToolTest.php`
Expected: PASS, including denial assertions for non-local environments.

- [ ] **Step 4: Verify audit logging**

Run: `vendor/bin/phpunit tests/Feature/Audit/AuditLoggerTest.php`
Expected: PASS, including allowed and denied audit entries.

- [ ] **Step 5: Record verification results**

Update `docs/release-checklist.md` with:

- date verified
- commands run
- pass or fail result
- follow-up issues if any

- [ ] **Step 6: Commit**

```bash
git add docs/release-checklist.md
git commit -m "chore: record verification results"
```
