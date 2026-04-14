# Database Mutations Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add explicit MCP tools for creating, updating, and deleting one database record at a time on allowlisted Laravel tables.

**Architecture:** Extend the existing `database` capability with three write-capable tools and keep the mutation logic in focused support classes. Reuse the package's audited response pattern and environment guards while keeping database mutations narrow, table-based, and predictable.

**Tech Stack:** PHP 8.3, Laravel 13, `laravel/mcp`, Orchestra Testbench, PHPUnit 11

---

## File Map

- `config/laravel-mcp.php`: add `database_tools` configuration
- `src/Policies/EnvironmentPolicy.php`: add database mutation enablement checks
- `src/Capabilities/Database/DatabaseCapabilities.php`: register the mutation tools alongside schema reads
- `src/Support/DatabaseMutationPolicy.php`: enforce allowed tables, allowed key columns, and one-row mutation constraints
- `src/Support/DatabaseMutator.php`: perform transactional insert, update, and delete operations through the query builder
- `src/LaravelMcpSuiteServiceProvider.php`: bind the new policy and mutator services
- `src/MCP/Tools/LaravelDbRecordCreateTool.php`: create one record on an allowlisted table
- `src/MCP/Tools/LaravelDbRecordUpdateTool.php`: update one record by allowlisted key and id
- `src/MCP/Tools/LaravelDbRecordDeleteTool.php`: delete one record by allowlisted key and id
- `tests/Feature/CapabilityRegistryTest.php`: assert the new database mutation tools are registered
- `tests/Unit/DatabaseMutationPolicyTest.php`: focused policy behavior coverage
- `tests/Unit/DatabaseMutatorTest.php`: transactional insert/update/delete coverage
- `tests/Feature/Tools/LaravelDbRecordCreateToolTest.php`: end-to-end create tool behavior
- `tests/Feature/Tools/LaravelDbRecordUpdateToolTest.php`: end-to-end update tool behavior
- `tests/Feature/Tools/LaravelDbRecordDeleteToolTest.php`: end-to-end delete tool behavior

## Chunk 1: Register Database Mutation Tools

### Task 1: Add failing capability registration coverage

**Files:**
- Modify: `tests/Feature/CapabilityRegistryTest.php`
- Modify: `src/Capabilities/Database/DatabaseCapabilities.php`
- Create: `src/MCP/Tools/LaravelDbRecordCreateTool.php`
- Create: `src/MCP/Tools/LaravelDbRecordUpdateTool.php`
- Create: `src/MCP/Tools/LaravelDbRecordDeleteTool.php`
- Test: `tests/Feature/CapabilityRegistryTest.php`

- [ ] **Step 1: Write the failing test**

Add assertions that `CapabilityRegistry::tools()` contains the three database mutation tool classes.

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php
```

Expected: FAIL because the tools are not registered yet.

- [ ] **Step 3: Write minimal implementation**

Create tool stubs and return them from `DatabaseCapabilities::tools()`.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php
```

Expected: PASS.

## Chunk 2: Lock Down Mutation Policy

### Task 2: Add failing policy tests

**Files:**
- Create: `tests/Unit/DatabaseMutationPolicyTest.php`
- Create: `src/Support/DatabaseMutationPolicy.php`
- Modify: `src/Policies/EnvironmentPolicy.php`
- Modify: `config/laravel-mcp.php`
- Modify: `src/LaravelMcpSuiteServiceProvider.php`
- Test: `tests/Unit/DatabaseMutationPolicyTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- allowed table acceptance
- denied table rejection when the allowlist is empty or missing the table
- allowed key acceptance
- denied key rejection
- local vs non-local mutation enablement

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Unit/DatabaseMutationPolicyTest.php
```

Expected: FAIL because the policy class and environment method do not exist.

- [ ] **Step 3: Write minimal implementation**

Implement `DatabaseMutationPolicy` as a pure policy helper and add `databaseMutationsEnabled()` to `EnvironmentPolicy`.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Unit/DatabaseMutationPolicyTest.php
```

Expected: PASS.

## Chunk 3: Implement The Query Builder Mutator

### Task 3: Add failing mutator tests

**Files:**
- Create: `tests/Unit/DatabaseMutatorTest.php`
- Create: `src/Support/DatabaseMutator.php`
- Test: `tests/Unit/DatabaseMutatorTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- insert creates one row and returns the inserted id when available
- update changes one matching row
- update reports zero affected rows when the record is missing
- delete removes one matching row
- delete reports zero affected rows when the record is missing

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Unit/DatabaseMutatorTest.php
```

Expected: FAIL because the mutator class does not exist.

- [ ] **Step 3: Write minimal implementation**

Implement transactional `create()`, `update()`, and `delete()` methods around the query builder.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Unit/DatabaseMutatorTest.php
```

Expected: PASS.

## Chunk 4: Add MCP Tool Behavior

### Task 4: Add failing create tool tests

**Files:**
- Create: `tests/Feature/Tools/LaravelDbRecordCreateToolTest.php`
- Modify: `src/MCP/Tools/LaravelDbRecordCreateTool.php`
- Test: `tests/Feature/Tools/LaravelDbRecordCreateToolTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- create succeeds for an allowlisted table
- create is denied when mutations are disabled
- create is denied for a disallowed table

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelDbRecordCreateToolTest.php
```

Expected: FAIL because the tool has no behavior.

- [ ] **Step 3: Write minimal implementation**

Add schema, validation, environment guard, policy checks, and mutator call.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelDbRecordCreateToolTest.php
```

Expected: PASS.

### Task 5: Add failing update tool tests

**Files:**
- Create: `tests/Feature/Tools/LaravelDbRecordUpdateToolTest.php`
- Modify: `src/MCP/Tools/LaravelDbRecordUpdateTool.php`
- Test: `tests/Feature/Tools/LaravelDbRecordUpdateToolTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- update succeeds for an allowlisted table and key
- update is denied for a disallowed key
- update returns zero affected rows when the record is missing

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelDbRecordUpdateToolTest.php
```

Expected: FAIL because the tool has no behavior.

- [ ] **Step 3: Write minimal implementation**

Add schema, validation, environment guard, policy checks, and mutator call.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelDbRecordUpdateToolTest.php
```

Expected: PASS.

### Task 6: Add failing delete tool tests

**Files:**
- Create: `tests/Feature/Tools/LaravelDbRecordDeleteToolTest.php`
- Modify: `src/MCP/Tools/LaravelDbRecordDeleteTool.php`
- Test: `tests/Feature/Tools/LaravelDbRecordDeleteToolTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- delete succeeds for an allowlisted table and key
- delete is denied when mutations are disabled
- delete returns zero affected rows when the record is missing

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelDbRecordDeleteToolTest.php
```

Expected: FAIL because the tool has no behavior.

- [ ] **Step 3: Write minimal implementation**

Add schema, validation, environment guard, policy checks, and mutator call.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelDbRecordDeleteToolTest.php
```

Expected: PASS.

## Chunk 5: Verify The Slice

### Task 7: Run focused verification

**Files:**
- Verify only: no additional files

- [ ] **Step 1: Run focused verification**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php tests/Unit/DatabaseMutationPolicyTest.php tests/Unit/DatabaseMutatorTest.php tests/Feature/Tools/LaravelDbRecordCreateToolTest.php tests/Feature/Tools/LaravelDbRecordUpdateToolTest.php tests/Feature/Tools/LaravelDbRecordDeleteToolTest.php
```

Expected: PASS.
