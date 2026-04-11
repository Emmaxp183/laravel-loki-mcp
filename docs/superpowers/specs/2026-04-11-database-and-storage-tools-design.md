# Database And Storage Tools Design

Date: 2026-04-11
Status: Draft

## Summary

Add explicit MCP tools for safe database record mutations and Laravel storage access to `Laravel MCP Suite`. The package already supports read-only database inspection and controlled source-file editing, but it does not yet expose a safe way to mutate application data or interact with configured `Storage` disks.

This design keeps the current safety posture intact by introducing narrow, structured tools instead of arbitrary SQL execution, unrestricted Artisan access, or direct source-file access to `storage/`.

## Goal

Give MCP clients a small, explicit tool surface for:

- creating, updating, and deleting one database record at a time in allowlisted tables
- listing, reading, writing, and deleting one storage object at a time on allowlisted Laravel disks

while staying inside the package's current environment guards, audit model, and conservative response shape.

## Scope

V1 should add the following tools:

- `laravel-db-record-create`
- `laravel-db-record-update`
- `laravel-db-record-delete`
- `laravel-storage-list`
- `laravel-storage-read`
- `laravel-storage-write`
- `laravel-storage-delete`

V1 should support:

- direct table access for database writes, not arbitrary SQL and not Eloquent model dispatch
- one-row database mutations per call
- text-oriented storage reads and writes
- allowlisted disks and allowlisted path prefixes
- structured success and denial responses

V1 should not support:

- schema mutation
- raw SQL execution
- multi-row bulk database writes
- arbitrary `where` clauses
- directory-recursive storage deletes
- storage copy or move operations
- binary upload workflows
- model observers, events, or policy integration through Eloquent

## Product Shape

### Public MCP capability

Database writes and storage access should be exposed as first-class MCP tools, named consistently with the existing package tool set. These tools should become the only executable entrypoints for application-data mutation in the package.

The existing database capability remains read-first, but expands from schema inspection only into a split model:

- read-only schema inspection
- write-capable record mutation tools

Storage access should be introduced as its own capability module instead of being folded into the file editor module. This keeps runtime storage operations separate from source-file editing.

### Why not broaden existing tools

This feature should not:

- add `storage/` to file editor writable paths
- add `migrate`, `db:wipe`, or similar commands to the safe Artisan allowlist
- expose generic SQL or shell-like mutation tools

Those approaches would weaken the package's existing safety model and make it harder for clients to reason about what a tool call can do.

## Internal Architecture

The tool layer should stay thin and delegate most behavior to dedicated support services under `src/Support/`.

Recommended responsibilities:

- `LaravelDbRecordCreateTool`: validate input, check environment policy, invoke database mutator, return structured response
- `LaravelDbRecordUpdateTool`: validate input, check environment policy, invoke database mutator, return structured response
- `LaravelDbRecordDeleteTool`: validate input, check environment policy, invoke database mutator, return structured response
- `DatabaseMutationPolicy`: centralize table allowlist checks and mutation constraints
- `DatabaseMutator`: perform transactional insert, update, and delete operations against the query builder

- `LaravelStorageListTool`: validate input, invoke storage editor in read mode, return structured response
- `LaravelStorageReadTool`: validate input, invoke storage editor in read mode, return structured response
- `LaravelStorageWriteTool`: validate input, check environment policy, invoke storage editor, return structured response
- `LaravelStorageDeleteTool`: validate input, check environment policy, invoke storage editor, return structured response
- `StorageAccessPolicy`: centralize disk allowlist, prefix checks, path normalization, and size limits
- `StorageEditor`: perform `Storage` facade operations and normalize output

This split keeps MCP protocol concerns out of the core logic and makes the tricky policy behavior independently testable.

## Capability Registration

The new design should fit the existing capability registry shape.

Recommended integration points:

- extend `src/Capabilities/Database/DatabaseCapabilities.php` with the three new database mutation tools
- add `src/Capabilities/Storage/StorageCapabilities.php`
- register the new storage capability in the service provider and capability registry
- add a new `storage` module toggle to `config/laravel-mcp.php`

The storage capability should be enabled by default in the same environments where other read tools are supported, while write behavior remains subject to environment and configuration checks.

## Input Contracts

The new tools should use narrow, structured contracts. They should not parse freeform instructions.

### Database create

Recommended request shape:

```json
{
  "table": "posts",
  "record": {
    "title": "Hello",
    "body": "World"
  }
}
```

Required input:

- `table`: allowlisted table name
- `record`: associative object of columns to values

### Database update

Recommended request shape:

```json
{
  "table": "posts",
  "key": "id",
  "id": 42,
  "changes": {
    "title": "Updated"
  }
}
```

Required input:

- `table`: allowlisted table name
- `key`: lookup column name
- `id`: lookup value
- `changes`: associative object of columns to values

### Database delete

Recommended request shape:

```json
{
  "table": "posts",
  "key": "id",
  "id": 42
}
```

Required input:

- `table`: allowlisted table name
- `key`: lookup column name
- `id`: lookup value

### Storage list

Recommended request shape:

```json
{
  "disk": "local",
  "path": "mcp/"
}
```

### Storage read

Recommended request shape:

```json
{
  "disk": "local",
  "path": "mcp/example.txt"
}
```

### Storage write

Recommended request shape:

```json
{
  "disk": "local",
  "path": "mcp/example.txt",
  "content": "hello",
  "overwrite": false
}
```

### Storage delete

Recommended request shape:

```json
{
  "disk": "local",
  "path": "mcp/example.txt"
}
```

Storage input rules:

- `disk` is required for all storage tools
- `path` is required for all storage tools
- `content` is required for storage write
- `overwrite` is optional for storage write and defaults to `false`

## Configuration Shape

Add two new config sections.

Recommended config:

```php
'modules' => [
    'storage' => true,
],

'database_tools' => [
    'allow_mutations_in_local' => true,
    'allow_mutations_elsewhere' => false,
    'allowed_tables' => [],
    'max_rows_per_call' => 1,
],

'storage_tools' => [
    'allow_writes_in_local' => true,
    'allow_writes_elsewhere' => false,
    'allowed_disks' => ['local'],
    'allowed_prefixes' => [
        'local' => ['mcp/'],
    ],
    'max_bytes' => 262144,
],
```

Behavioral intent:

- an empty `allowed_tables` list should deny all database mutations until the user opts in
- an empty `allowed_disks` list should deny all storage access until the user opts in
- prefix restrictions should be evaluated per disk
- write toggles should be separate from source-file edit toggles because runtime data operations are not the same as code edits

## Execution Flow

### Database mutation tools

The tools should run in this order:

1. validate request payload
2. check that record mutations are enabled for the current environment
3. confirm the target table is allowlisted
4. validate the lookup key and payload shape
5. execute the mutation in a transaction
6. return a structured result

Database mutation behavior:

- `create` inserts one row and returns a compact summary
- `update` updates at most one row by explicit key and explicit value
- `delete` deletes at most one row by explicit key and explicit value
- `update` should reject attempts to change the lookup key column in the same request
- `update` and `delete` should return a denied or not-found style result when zero rows match
- callers should not be able to pass arbitrary `where` arrays or SQL fragments

### Storage tools

The tools should run in this order:

1. validate request payload
2. normalize disk and path
3. confirm the disk is allowlisted
4. confirm the normalized path stays inside an allowlisted prefix
5. enforce size and overwrite rules
6. perform the storage operation
7. return a structured result

Storage behavior:

- `list` returns file paths under an allowlisted prefix
- `read` returns text content only up to `max_bytes`
- `write` creates or overwrites one object depending on `overwrite`
- `delete` removes one object path only

## Safety Model

The package's current safety posture should remain the controlling principle.

### Database safety rules

- record mutations should be denied outside explicitly enabled environments
- table access should be allowlist-only
- one row per call should remain the hard default in v1
- each mutation should run inside a transaction
- raw SQL should not be accepted anywhere in the request shape
- schema changes should stay out of scope

### Storage safety rules

- reads should follow normal read-tool environment availability
- writes and deletes should be denied outside explicitly enabled environments
- disks should be allowlist-only
- paths should be normalized and traversal-resistant
- operations should stay inside allowed prefixes per disk
- writes should default to `overwrite = false`
- large files should be rejected before content is returned or written

### Existing boundaries that should remain in place

- `storage/` should remain blocked in the source-file editing policy
- safe Artisan should remain allowlist-only and should not become the execution path for these features
- audit logging should continue to apply to both allowed and denied calls

## Response Contract

The new tools should follow the package's standard structured response shape.

### Database mutation response

Recommended response payload:

```json
{
  "summary": "Record created in posts.",
  "data": {
    "allowed": true,
    "success": true,
    "table": "posts",
    "operation": "create",
    "record_key": "id",
    "record_id": 42
  },
  "warnings": [],
  "meta": {
    "module": "database",
    "read_only": false,
    "environment": "local"
  }
}
```

Recommended denial payload:

```json
{
  "summary": "Database mutation request was denied.",
  "data": {
    "allowed": false,
    "success": false,
    "table": "posts",
    "operation": "update"
  },
  "warnings": [
    "Database mutations are disabled for the current environment or table."
  ],
  "meta": {
    "module": "database",
    "read_only": false,
    "environment": "production"
  }
}
```

### Storage response

Recommended storage write payload:

```json
{
  "summary": "Storage object written.",
  "data": {
    "allowed": true,
    "success": true,
    "disk": "local",
    "path": "mcp/example.txt",
    "bytes": 5
  },
  "warnings": [],
  "meta": {
    "module": "storage",
    "read_only": false,
    "environment": "local"
  }
}
```

Response rules:

- keep responses compact and structured
- avoid returning full database rows by default
- do not echo large content in write and delete responses
- `read` may return content, but should still respect sanitization and size limits

## Audit And Sanitization

The new tools should reuse the existing audited response path and output sanitization pipeline rather than introducing new logging code paths.

Expected behavior:

- successful and denied operations both produce audit entries
- audit log entries include the tool name, environment, result, and argument summary
- sensitive values in response payloads should still pass through the package's sanitizer
- large content should not be duplicated into audit payloads if avoidable

The design should prefer summaries over echoing raw data. This is especially important for storage reads and database payloads that may contain secrets or tokens.

## Testing Strategy

Testing should be split between MCP tool behavior and focused policy or service behavior.

### Tool-level tests

Feature tests should prove that:

- database mutations are denied outside enabled environments
- storage writes and deletes are denied outside enabled environments
- disallowed tables are rejected
- disallowed disks are rejected
- disallowed prefixes are rejected
- create, update, and delete succeed for allowlisted tables
- storage list, read, write, and delete succeed for allowlisted disks and prefixes
- storage write respects `overwrite = false`
- large storage reads and writes are rejected
- structured response payloads follow the package's conventions
- allowed and denied calls both emit audit entries

### Policy and support-service tests

Focused tests should cover:

- table allowlist checks
- mutation environment gating
- storage path normalization
- prefix matching rules
- overwrite behavior
- transaction behavior for database mutation paths

This should avoid one oversized end-to-end filesystem or database test becoming the only proof of correctness.

## File Layout

Recommended new files:

- `src/Capabilities/Storage/StorageCapabilities.php`
- `src/MCP/Tools/LaravelDbRecordCreateTool.php`
- `src/MCP/Tools/LaravelDbRecordUpdateTool.php`
- `src/MCP/Tools/LaravelDbRecordDeleteTool.php`
- `src/MCP/Tools/LaravelStorageListTool.php`
- `src/MCP/Tools/LaravelStorageReadTool.php`
- `src/MCP/Tools/LaravelStorageWriteTool.php`
- `src/MCP/Tools/LaravelStorageDeleteTool.php`
- `src/Support/DatabaseMutationPolicy.php`
- `src/Support/DatabaseMutator.php`
- `src/Support/StorageAccessPolicy.php`
- `src/Support/StorageEditor.php`
- `tests/Feature/Tools/LaravelDbRecordCreateToolTest.php`
- `tests/Feature/Tools/LaravelDbRecordUpdateToolTest.php`
- `tests/Feature/Tools/LaravelDbRecordDeleteToolTest.php`
- `tests/Feature/Tools/LaravelStorageListToolTest.php`
- `tests/Feature/Tools/LaravelStorageReadToolTest.php`
- `tests/Feature/Tools/LaravelStorageWriteToolTest.php`
- `tests/Feature/Tools/LaravelStorageDeleteToolTest.php`
- `tests/Unit/DatabaseMutationPolicyTest.php`
- `tests/Unit/StorageAccessPolicyTest.php`

Recommended modified files:

- `config/laravel-mcp.php`
- `src/LaravelMcpSuiteServiceProvider.php`
- `src/Support/CapabilityRegistry.php`
- documentation files that describe module toggles and safety defaults

## Open Decisions Deferred From V1

The following should remain out of scope unless later approved:

- model-based mutation tools through Eloquent
- custom validation rules per table
- batch upserts
- freeform query filters
- storage copy and move operations
- binary uploads and downloads
- signed URL generation
- recursive directory delete
- schema migrations through MCP mutation tools

## Success Criteria

This design is successful when:

- a client can call explicit MCP tools to create, update, and delete one record in an allowlisted table
- a client can call explicit MCP tools to list, read, write, and delete one object on an allowlisted Laravel disk
- writes remain disabled outside approved environments by default
- source-file editing and runtime storage access remain separate concerns
- both capabilities reuse the package's existing audit and structured response model
- the implementation fits the package's current capability and safety posture without introducing arbitrary execution paths
