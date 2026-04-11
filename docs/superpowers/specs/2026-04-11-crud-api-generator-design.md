# CRUD API Generator Design

Date: 2026-04-11
Status: Draft

## Summary

Add a real generator capability to `Laravel MCP Suite` that creates Laravel JSON CRUD APIs directly in downstream Laravel applications when local write tools are enabled. The package already exposes a `scaffold-crud` prompt and safe write infrastructure, so this change should fill the missing executable capability rather than introduce a separate generation path.

## Goal

Give MCP clients one explicit tool that can generate a conventional Laravel CRUD API end to end, while staying inside the package's existing environment guards, writable-path policy, and audit model.

## Scope

V1 should generate the following by default for one resource:

- migration
- model
- store form request
- update form request
- API resource
- API controller
- `Route::apiResource(...)` registration
- feature tests for `index`, `show`, `store`, `update`, and `destroy`

V1 should target standard JSON API CRUD only. It should not include nested resources, policies, soft deletes, custom pagination behavior, advanced query filters, or nonstandard route layouts.

## Product Shape

### Public MCP capability

Add a new generator tool, named consistently with the rest of the package, for example `laravel-crud-api-generate`. This tool should become the single executable entrypoint for CRUD generation.

The existing `scaffold-crud` prompt should remain as workflow guidance for clients, but it should be updated to point clients at the new generator tool after they inspect routes, models, and project conventions.

### Internal architecture

The generator tool should stay thin and delegate generation work to dedicated support services under `src/Support/`.

Recommended responsibilities:

- `LaravelCrudApiGenerateTool`: validates input, checks environment policy, invokes the generator service, returns structured output
- `CrudApiGenerator`: orchestrates generation steps and aggregates results
- `CrudApiBlueprint`: derives names, paths, route names, and file layout from the requested resource
- `CrudApiFieldMapper`: converts structured field input into migration columns, validation rules, fillable attributes, resource output, and test payloads
- `CrudApiRouteWriter`: inserts or updates `Route::apiResource(...)` declarations idempotently

This keeps MCP protocol concerns separate from generation logic and makes the tricky behavior independently testable.

## Input Contract

The tool should use a structured input contract instead of parsing freeform instructions.

Recommended request shape:

```json
{
  "resource": "Post",
  "fields": [
    {
      "name": "title",
      "type": "string",
      "required": true,
      "rules": ["string", "max:255"]
    },
    {
      "name": "body",
      "type": "text",
      "required": true,
      "rules": ["string"]
    },
    {
      "name": "published_at",
      "type": "timestamp",
      "required": false,
      "nullable": true
    }
  ],
  "model": "Post",
  "route": "posts",
  "force": false
}
```

Required input:

- `resource`: canonical resource name used to derive class names and default route name
- `fields`: structured list of fields to generate

Optional input:

- `model`: explicit model class name override
- `route`: explicit route segment override
- `force`: allow controlled overwrites of tool-owned files

Field definitions should remain explicit and predictable. The tool should not attempt to infer schema from prose.

## Execution Flow

The tool should run in this order:

1. validate request payload
2. check `writeToolsEnabled` and `codeEditsEnabled`
3. derive the file and naming blueprint
4. inspect existing files and routes for collisions
5. run safe Artisan generators where they provide a useful baseline
6. write or patch the remaining files through the existing file editor path policy
7. register the API route idempotently
8. return a structured result

Preferred generation split:

- use Artisan when Laravel already owns the base artifact shape, such as model, migration, controller, request, resource, and test stubs
- use package-owned writers and patchers for the file content Laravel does not generate in the desired form

This approach keeps the output aligned with Laravel conventions while avoiding overreliance on brittle string editing.

## Safety Model

The generator must honor the package's current safety boundaries:

- it only writes in `local`
- it only writes through approved writable paths
- it produces audit records like other write-capable tools
- it fails fast on collisions by default
- it uses idempotent route registration logic

`force` should remain conservative. It may overwrite files the tool directly owns, but it should not blindly rewrite arbitrary application files. Route insertion must remain idempotent even when `force` is set.

Outside `local`, the tool should return a denied structured response rather than partially generating output.

## Response Contract

The tool should follow the package's standard structured response shape and include enough detail for clients to understand what changed.

Recommended response payload:

```json
{
  "summary": "CRUD API generated for Post.",
  "data": {
    "resource": "Post",
    "created": [
      "app/Models/Post.php",
      "app/Http/Controllers/Api/PostController.php"
    ],
    "updated": [
      "routes/api.php"
    ],
    "skipped": [],
    "collisions": [],
    "route": "posts"
  },
  "warnings": [],
  "meta": {
    "module": "generators",
    "read_only": false,
    "environment": "local"
  }
}
```

If generation is denied or blocked, the response should clearly separate denied paths, collisions, or skipped files from successful writes.

## File and Output Conventions

The generated API should follow normal Laravel application structure:

- `app/Models/<Model>.php`
- `database/migrations/<timestamp>_create_<table>_table.php`
- `app/Http/Requests/Store<Model>Request.php`
- `app/Http/Requests/Update<Model>Request.php`
- `app/Http/Resources/<Model>Resource.php`
- `app/Http/Controllers/Api/<Model>Controller.php`
- `routes/api.php`
- `tests/Feature/Api/<Model>CrudTest.php`

The controller should be API-oriented rather than web-resource-oriented, and the route writer should target `routes/api.php`, not `routes/web.php`.

## Testing Strategy

Testing should be split between the MCP tool behavior and the generator internals.

### Tool-level tests

Feature tests should prove that:

- non-local environments are denied
- invalid payloads are rejected
- a basic resource request produces the expected file set and structured response
- repeated runs do not duplicate `Route::apiResource(...)`
- collisions are surfaced correctly

### Support-service tests

Focused tests should cover:

- field-to-migration column mapping
- field-to-validation rule mapping
- fillable and resource serialization generation
- route patch idempotency
- overwrite behavior with and without `force`

This should avoid one oversized filesystem-heavy test becoming the only proof of correctness.

## Integration Points

The new capability should plug into the existing package structure:

- register the tool in `src/Capabilities/Generators/GeneratorsCapabilities.php`
- add the tool class under `src/MCP/Tools/`
- add support services under `src/Support/`
- extend prompt tests and generator tool tests under `tests/Feature/`
- add support-service tests under `tests/Unit/` where appropriate

No new parallel write infrastructure should be introduced. The generator should reuse the current environment policy, file editor policy, and audit logger.

## Open Decisions Deferred From V1

The following should stay out of the first implementation unless later approved:

- nested resources
- policy generation
- request authorization customization
- factories and seeders
- soft delete support
- filter and sort query layers
- OpenAPI output
- preview-only generation mode

## Success Criteria

This design is successful when:

- a client can call one explicit MCP tool to generate a conventional Laravel CRUD API
- generation only works when local write tooling is allowed
- repeated generation stays idempotent at the route layer
- the result is explained in a structured response clients can use directly
- the implementation fits the package's existing capability and safety model
