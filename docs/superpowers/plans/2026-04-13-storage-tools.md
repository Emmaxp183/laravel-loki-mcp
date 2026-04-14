# Storage Tools Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add explicit MCP tools for listing, reading, writing, and deleting text files on allowlisted Laravel storage disks.

**Architecture:** Introduce a dedicated `storage` capability with four MCP tools and keep storage policy and disk operations in focused support classes. Reuse the package's existing audited response pattern and environment guards while keeping runtime storage access separate from source-file editing.

**Tech Stack:** PHP 8.3, Laravel 13, `laravel/mcp`, Orchestra Testbench, PHPUnit 11

---

## File Map

- `config/laravel-mcp.php`: add the `storage` module toggle and `storage_tools` configuration
- `src/LaravelMcpSuiteServiceProvider.php`: register storage capability and support services
- `src/Support/CapabilityRegistry.php`: include the storage module in capability resolution
- `src/Capabilities/Storage/StorageCapabilities.php`: register the new storage tools
- `src/Policies/EnvironmentPolicy.php`: add storage write enablement checks
- `src/Support/StorageAccessPolicy.php`: normalize disk and path input, enforce disk allowlists, prefix limits, and byte limits
- `src/Support/StorageEditor.php`: perform list/read/write/delete operations through `Storage::disk(...)`
- `src/MCP/Tools/LaravelStorageListTool.php`: read-only storage list tool
- `src/MCP/Tools/LaravelStorageReadTool.php`: read-only storage read tool
- `src/MCP/Tools/LaravelStorageWriteTool.php`: write-capable storage write tool
- `src/MCP/Tools/LaravelStorageDeleteTool.php`: write-capable storage delete tool
- `tests/Feature/CapabilityRegistryTest.php`: assert storage tool registration
- `tests/Feature/Tools/LaravelStorageListToolTest.php`: end-to-end list behavior
- `tests/Feature/Tools/LaravelStorageReadToolTest.php`: end-to-end read behavior
- `tests/Feature/Tools/LaravelStorageWriteToolTest.php`: end-to-end write behavior
- `tests/Feature/Tools/LaravelStorageDeleteToolTest.php`: end-to-end delete behavior
- `tests/Unit/StorageAccessPolicyTest.php`: focused storage policy behavior
- `README.md`: document the new storage tools and config

## Chunk 1: Register The Storage Capability

### Task 1: Add failing registration coverage

**Files:**
- Create: `src/Capabilities/Storage/StorageCapabilities.php`
- Modify: `src/LaravelMcpSuiteServiceProvider.php`
- Modify: `src/Support/CapabilityRegistry.php`
- Modify: `config/laravel-mcp.php`
- Modify: `tests/Feature/CapabilityRegistryTest.php`
- Test: `tests/Feature/CapabilityRegistryTest.php`

- [ ] **Step 1: Write the failing test**

Add assertions that `CapabilityRegistry::tools()` contains `LaravelStorageListTool::class` and that the server context resolves the storage tool set when the module is enabled.

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php
```

Expected: FAIL because the storage capability and tool classes do not exist.

- [ ] **Step 3: Write minimal implementation**

Add the storage module config entry, create the capability class, and wire it into the service provider and capability registry map.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php
```

Expected: PASS.

## Chunk 2: Lock Down Storage Policy Behavior

### Task 2: Add failing policy tests

**Files:**
- Create: `tests/Unit/StorageAccessPolicyTest.php`
- Create: `src/Support/StorageAccessPolicy.php`
- Modify: `src/Policies/EnvironmentPolicy.php`
- Test: `tests/Unit/StorageAccessPolicyTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- allowed disk and allowed prefix acceptance
- disallowed disk rejection
- traversal or empty path normalization failure
- byte limit decisions for reads and writes
- local vs non-local storage write enablement

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Unit/StorageAccessPolicyTest.php
```

Expected: FAIL because the policy class does not exist.

- [ ] **Step 3: Write minimal implementation**

Implement `StorageAccessPolicy` as a pure policy helper and add `storageWritesEnabled()` to `EnvironmentPolicy`.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Unit/StorageAccessPolicyTest.php
```

Expected: PASS.

## Chunk 3: Implement Storage Editor Behavior

### Task 3: Add failing support tests through MCP-facing flows

**Files:**
- Create: `src/Support/StorageEditor.php`
- Create: `tests/Feature/Tools/LaravelStorageListToolTest.php`
- Create: `tests/Feature/Tools/LaravelStorageReadToolTest.php`
- Test: `tests/Feature/Tools/LaravelStorageListToolTest.php`
- Test: `tests/Feature/Tools/LaravelStorageReadToolTest.php`

- [ ] **Step 1: Write the failing tests**

Add tests that seed fake storage disks and verify:
- `list` returns only paths inside the allowlisted prefix
- `read` returns text content and metadata for an allowed path
- oversized or disallowed reads are denied cleanly

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelStorageListToolTest.php tests/Feature/Tools/LaravelStorageReadToolTest.php
```

Expected: FAIL because the tools and editor do not exist.

- [ ] **Step 3: Write minimal implementation**

Implement `StorageEditor::list()` and `StorageEditor::read()` with structured return payloads that expose `allowed`, `disk`, `path`, and the relevant data fields.

- [ ] **Step 4: Run tests to verify they pass**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelStorageListToolTest.php tests/Feature/Tools/LaravelStorageReadToolTest.php
```

Expected: PASS.

## Chunk 4: Implement Write And Delete Tools

### Task 4: Add failing mutation tests

**Files:**
- Create: `src/MCP/Tools/LaravelStorageWriteTool.php`
- Create: `src/MCP/Tools/LaravelStorageDeleteTool.php`
- Create: `tests/Feature/Tools/LaravelStorageWriteToolTest.php`
- Create: `tests/Feature/Tools/LaravelStorageDeleteToolTest.php`
- Modify: `src/Support/StorageEditor.php`
- Modify: `src/Capabilities/Storage/StorageCapabilities.php`
- Test: `tests/Feature/Tools/LaravelStorageWriteToolTest.php`
- Test: `tests/Feature/Tools/LaravelStorageDeleteToolTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- write succeeds for an allowed path in local
- write fails when the target exists and `overwrite` is false
- write fails when storage writes are disabled
- delete removes an allowed object in local
- delete fails when storage writes are disabled

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelStorageWriteToolTest.php tests/Feature/Tools/LaravelStorageDeleteToolTest.php
```

Expected: FAIL because write and delete tools do not exist.

- [ ] **Step 3: Write minimal implementation**

Implement `StorageEditor::write()` and `StorageEditor::delete()` and add MCP tools that mirror the existing package response shape with `meta.module = storage`.

- [ ] **Step 4: Run tests to verify they pass**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelStorageWriteToolTest.php tests/Feature/Tools/LaravelStorageDeleteToolTest.php
```

Expected: PASS.

## Chunk 5: Document And Verify

### Task 5: Update package docs and run focused verification

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Update docs**

Document the four storage tools, the new `storage` module toggle, and the `storage_tools` allowlist configuration.

- [ ] **Step 2: Run focused verification**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php tests/Feature/Tools/LaravelStorageListToolTest.php tests/Feature/Tools/LaravelStorageReadToolTest.php tests/Feature/Tools/LaravelStorageWriteToolTest.php tests/Feature/Tools/LaravelStorageDeleteToolTest.php tests/Unit/StorageAccessPolicyTest.php
```

Expected: PASS.
