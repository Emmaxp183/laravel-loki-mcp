# Laravel MCP Suite Design

Date: 2026-04-06
Status: Draft

## Summary

`Laravel MCP Suite` is a reusable Composer package built on top of Laravel's official [`laravel/mcp`](https://github.com/laravel/mcp) project. It provides a Firebase-style MCP experience for Laravel applications by exposing three capability classes cleanly:

- Tools for direct, structured actions
- Resources for read-only project context
- Prompts for guided workflows

The package is package-first, includes a minimal demo Laravel app for proof of use, and ships setup documentation optimized first for `Codex CLI` and `Claude Code`.

## Product Goal

Give AI clients safe, structured access to a Laravel application so they can inspect app state, debug failures, generate common code, and run approved development actions without exposing arbitrary shell access or secrets.

## Product Positioning

Primary angle:

- Firebase-style MCP capability suite for Laravel apps

Secondary angle:

- Read-first local development assistant for inspection, debugging, and scaffolding

## Core Decisions

- Build on top of `laravel/mcp`, not a custom MCP protocol implementation
- Package-first delivery, with a minimal demo app in the same repo
- Read tools available by default
- Write-capable tools enabled automatically in `local`, disabled elsewhere
- Client setup and generated snippets optimized first for `Codex CLI` and `Claude Code`
- Tool-centric product, not a chat endpoint

## Non-Goals for V1

- Arbitrary shell execution
- Raw SQL execution
- Full filesystem exposure
- Unrestricted Artisan command execution
- Deployment automation
- Secret-reading tools
- Automatic code modification without preview or explicit tool boundaries

## Architecture

The system has three layers.

### Layer A: Core MCP Package

This layer wraps Laravel MCP primitives and provides:

- Server registration
- Tool, resource, and prompt discovery
- Config publishing
- Capability module enablement
- Policy hooks
- Sanitization and redaction
- Install command and client config generation

### Layer B: Capability Modules

Modules keep the install surface and security boundaries clear.

- `core`
- `artisan`
- `database`
- `logs`
- `tests`
- `queues`
- `generators`

Modules are independently toggleable in config.

### Layer C: Project Adapter

Each consuming app customizes behavior through published config:

- Allowed Artisan commands
- Hidden paths and excluded context
- Enabled capability modules
- Environment-specific policy rules
- Custom resources, tools, and prompts

## Repo Shape

```text
/
├─ packages/laravel-mcp-suite/
│  ├─ src/
│  │  ├─ Console/Commands/
│  │  ├─ MCP/Servers/
│  │  ├─ MCP/Tools/
│  │  ├─ MCP/Resources/
│  │  ├─ MCP/Prompts/
│  │  ├─ Capabilities/
│  │  │  ├─ Core/
│  │  │  ├─ Artisan/
│  │  │  ├─ Database/
│  │  │  ├─ Logs/
│  │  │  ├─ Tests/
│  │  │  ├─ Queues/
│  │  │  └─ Generators/
│  │  ├─ Policies/
│  │  ├─ Sanitizers/
│  │  ├─ Support/
│  │  └─ LaravelMcpSuiteServiceProvider.php
│  ├─ config/laravel-mcp.php
│  ├─ resources/prompts/
│  ├─ stubs/
│  └─ README.md
├─ demo/
│  └─ laravel-app/
└─ docs/
   ├─ installation.md
   ├─ safety-model.md
   ├─ clients/
   │  ├─ codex-cli.md
   │  └─ claude-code.md
   └─ extending.md
```

If the project remains a single package repository rather than a monorepo, the package contents above move to repo root and `demo/` plus `docs/` remain beside it.

## Server Model

The package provides one default server class, for example `LaravelAppServer`, which aggregates enabled tools, resources, and prompts from configured modules.

It supports:

- Local server registration through `Mcp::local(...)`
- Optional protected web server registration through `Mcp::web(...)`

Recommended default:

- Enable local server by default
- Offer optional web server setup behind explicit install flags and auth middleware

## V1 Capability Scope

### Tools

V1 tools should be explicit, narrow, and boringly named:

- `laravel_app_info`
- `laravel_routes_list`
- `laravel_models_list`
- `laravel_model_describe`
- `laravel_db_schema_read`
- `laravel_logs_recent`
- `laravel_exception_last`
- `laravel_artisan_commands`
- `laravel_artisan_run_safe`
- `laravel_tests_run`
- `laravel_config_summary`

### Resources

- `laravel://app/routes`
- `laravel://app/models`
- `laravel://db/schema`
- `laravel://app/errors/recent`
- `laravel://docs/project-conventions`

### Prompts

- `/laravel:debug-last-exception`
- `/laravel:scaffold-crud`
- `/laravel:generate-feature-test`
- `/laravel:review-route-controller-consistency`

## Tool Contracts

Every tool should follow the same execution pipeline:

1. Validate JSON schema input
2. Evaluate environment guard
3. Evaluate authorization policy
4. Execute through a narrow service
5. Sanitize output
6. Return structured response

Recommended response contract:

```json
{
  "summary": "Short plain-language result",
  "data": {},
  "warnings": [],
  "meta": {
    "module": "logs",
    "read_only": true,
    "environment": "local"
  }
}
```

## Initial Tool Schemas

### `laravel_app_info`

Purpose:

- Summarize framework and application metadata

Input:

```json
{
  "type": "object",
  "properties": {},
  "additionalProperties": false
}
```

Output highlights:

- Laravel version
- PHP version
- App environment
- Debug mode enabled or disabled
- Installed optional integrations detected such as Horizon or Telescope

### `laravel_routes_list`

Purpose:

- Return a filtered route inventory without exposing source code contents

Input:

```json
{
  "type": "object",
  "properties": {
    "method": { "type": "string" },
    "middleware": { "type": "string" },
    "path_contains": { "type": "string" }
  },
  "additionalProperties": false
}
```

Output highlights:

- URI
- HTTP methods
- Route name
- Action/controller
- Middleware summary

### `laravel_models_list`

Purpose:

- Enumerate discovered Eloquent models

Input:

```json
{
  "type": "object",
  "properties": {
    "namespace_prefix": { "type": "string" }
  },
  "additionalProperties": false
}
```

Output highlights:

- Class name
- File path
- Table name if resolvable

### `laravel_model_describe`

Purpose:

- Summarize a specific model and likely relationships

Input:

```json
{
  "type": "object",
  "required": ["model"],
  "properties": {
    "model": { "type": "string" }
  },
  "additionalProperties": false
}
```

Output highlights:

- Table
- Fillable or guarded summary
- Casts summary
- Relationship method candidates
- Traits summary

### `laravel_db_schema_read`

Purpose:

- Provide schema introspection only

Input:

```json
{
  "type": "object",
  "properties": {
    "table": { "type": "string" }
  },
  "additionalProperties": false
}
```

Output highlights:

- Tables
- Columns
- Indexes
- Foreign keys

Notes:

- No raw SQL input in v1

### `laravel_logs_recent`

Purpose:

- Return sanitized recent log entries

Input:

```json
{
  "type": "object",
  "properties": {
    "lines": { "type": "integer", "minimum": 1, "maximum": 500 },
    "level": { "type": "string" }
  },
  "additionalProperties": false
}
```

Output highlights:

- Timestamp
- Level
- Message
- Sanitized context excerpt

### `laravel_exception_last`

Purpose:

- Return the most recent exception with redacted context

Input:

```json
{
  "type": "object",
  "properties": {},
  "additionalProperties": false
}
```

Output highlights:

- Exception class
- Message
- Top stack frames
- Relevant request metadata if available

### `laravel_artisan_commands`

Purpose:

- Show allowed and detected Artisan commands

Input:

```json
{
  "type": "object",
  "properties": {
    "search": { "type": "string" }
  },
  "additionalProperties": false
}
```

Output highlights:

- Command name
- Description
- Whether allowed for MCP execution

### `laravel_artisan_run_safe`

Purpose:

- Execute only explicitly allowed Artisan commands

Input:

```json
{
  "type": "object",
  "required": ["command"],
  "properties": {
    "command": { "type": "string" },
    "arguments": {
      "type": "object",
      "additionalProperties": true
    }
  },
  "additionalProperties": false
}
```

Rules:

- Enabled automatically in `local`
- Disabled outside `local` unless explicitly overridden
- Only commands on the allowlist may run

### `laravel_tests_run`

Purpose:

- Run a constrained test target and return structured results

Input:

```json
{
  "type": "object",
  "properties": {
    "filter": { "type": "string" },
    "suite": { "type": "string" },
    "path": { "type": "string" }
  },
  "additionalProperties": false
}
```

Rules:

- Constrain execution to PHPUnit or Pest test entrypoints
- Return pass or fail summary, failing tests, and excerpted output

### `laravel_config_summary`

Purpose:

- Summarize safe, non-secret application configuration

Input:

```json
{
  "type": "object",
  "properties": {
    "section": { "type": "string" }
  },
  "additionalProperties": false
}
```

Output highlights:

- App mode summary
- Cache, queue, mail, session, database driver names
- No secret values

## Resource Design

Resources are read-only context surfaces intended to help models reason before calling tools.

### `laravel://app/routes`

- Cached route inventory
- Grouped by method and controller

### `laravel://app/models`

- Model index
- Tables and relationship hints

### `laravel://db/schema`

- Schema summary produced from migrations and database introspection

### `laravel://app/errors/recent`

- Recent exception summaries
- Sanitized by the same redaction pipeline as log tools

### `laravel://docs/project-conventions`

- Published markdown file owned by the application
- Intended for domain rules, coding conventions, naming rules, and architecture notes

## Prompt Design

Prompts should guide clients toward the correct capability sequence instead of embedding hidden magic.

### `/laravel:debug-last-exception`

Intended flow:

1. Read `laravel://app/errors/recent`
2. Call `laravel_exception_last`
3. Call `laravel_logs_recent`
4. Optionally inspect routes or config summary
5. Produce diagnosis and next steps

### `/laravel:scaffold-crud`

Intended flow:

1. Inspect routes, models, and project conventions
2. Confirm local environment and write-tool availability
3. Use constrained generators or stub generation tools
4. Return a change plan or diff-ready output

### `/laravel:generate-feature-test`

Intended flow:

1. Read route and controller context
2. Inspect relevant model and conventions
3. Generate feature test scaffold
4. Optionally run constrained tests

### `/laravel:review-route-controller-consistency`

Intended flow:

1. Read route inventory
2. Compare controller naming and route naming patterns
3. Flag inconsistencies and missing conventions

## Safety Model

Safety is a primary product feature, not an afterthought.

### Environment Defaults

- `local`: read tools enabled, write-capable safe tools enabled
- `testing`: read tools enabled, write tools disabled by default
- `staging`: read tools enabled, write tools disabled by default
- `production`: read-only mode by default

### Redaction Rules

Always redact:

- `.env` values
- API keys
- Bearer tokens
- Cookies
- Passwords
- Private keys
- DSN secrets
- Known credential-like strings in logs and config

### Access Boundaries

- No arbitrary shell access
- No raw SQL in v1
- No unrestricted file reads
- No execution of non-allowlisted Artisan commands

### Auditability

Every tool call should produce an audit record containing:

- Timestamp
- Tool name
- Authenticated identity if available
- Environment
- Allowed or denied result
- High-level argument summary

## Config Shape

Proposed config sections:

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

## Install Flow

Primary package install:

```bash
composer require your-vendor/laravel-mcp-suite
php artisan mcp:install
```

`mcp:install` should:

- Publish config
- Register a local MCP server in `routes/ai.php`
- Optionally scaffold a protected web MCP route
- Create or publish a starter `project-conventions` markdown resource
- Publish an Artisan allowlist config section
- Print generated client config snippets for `Codex CLI` and `Claude Code`
- Explain local-only write behavior

## Documentation Plan

### `README.md`

- What the package is
- What it exposes
- Quick install
- Supported clients

### `docs/installation.md`

- Laravel and PHP version requirements
- Composer installation
- `mcp:install`
- Local server startup expectations

### `docs/clients/codex-cli.md`

- Client configuration format
- How to point `Codex CLI` to the local MCP command
- How auth works if web mode is used

### `docs/clients/claude-code.md`

- Example client registration
- Local command wiring
- Optional remote server wiring

### `docs/safety-model.md`

- Read-first behavior
- Local-only writes
- Redaction rules
- Allowlist model

### `docs/extending.md`

- Add a custom tool
- Add a custom resource
- Add a custom prompt
- Override policies

## Demo App

The demo app exists to prove the product, not to contain product logic.

It should demonstrate:

- Local MCP route registration
- A few seeded models and routes
- Working schema, log, and route tools
- Generated client setup snippets
- A sample `project-conventions` resource

## Roadmap

### First 2 Weeks

- Create package skeleton
- Add service provider and config publishing
- Add default server class
- Implement `core`, `database`, and `logs` modules
- Implement the first four tools:
  - `laravel_app_info`
  - `laravel_routes_list`
  - `laravel_db_schema_read`
  - `laravel_logs_recent`
- Add initial resources:
  - routes
  - schema
  - recent errors
- Build `mcp:install`
- Write install docs for `Codex CLI` and `Claude Code`

Success criteria:

- Package installs into demo app
- Local MCP server registers correctly
- Tools return sanitized structured output

### First 6 Weeks

- Add `models`, `tests`, and `artisan` modules
- Implement:
  - `laravel_models_list`
  - `laravel_model_describe`
  - `laravel_artisan_commands`
  - `laravel_artisan_run_safe`
  - `laravel_tests_run`
  - `laravel_config_summary`
- Add prompt pack v1
- Add audit logging
- Add integration tests across local and non-local environments

Success criteria:

- V1 tool and prompt set complete
- Local write behavior works as designed
- Non-local environments reject write tools by default

### First 3 Months

- Add optional `queues` module
- Add generator tools with diff or preview-first output
- Add optional web-server auth docs for Sanctum and Passport
- Add app-specific extension examples
- Tighten output contracts and client onboarding
- Publish public release with compatibility matrix

Success criteria:

- Stable public package
- Clear extension story
- Strong install and client setup docs

## Open Questions for Implementation Planning

- Whether the package repo should be standalone or monorepo with embedded demo app
- Whether generator tools should emit files directly or generate previews first in v1.1
- How deeply model relationship discovery should rely on reflection versus application bootstrapping
- Whether queue and Horizon support belongs in v1 or v1.1

## References

- Laravel MCP docs: https://laravel.com/docs/13.x/mcp
- Laravel MCP repository: https://github.com/laravel/mcp
- Firebase MCP server docs: https://firebase.google.com/docs/ai-assistance/mcp-server
