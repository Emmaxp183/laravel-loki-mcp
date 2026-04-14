# Queue Failed Jobs Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add explicit MCP tools for listing, retrying, and deleting failed Laravel queue jobs.

**Architecture:** Turn the existing `queues` capability into a real failed-job module with one read-only list tool and two write-capable mutation tools. Keep failed-job normalization and retry/delete behavior in focused support classes, while reusing the package's existing audited response model and environment guards.

**Tech Stack:** PHP 8.3, Laravel 13, `laravel/mcp`, Orchestra Testbench, PHPUnit 11

---

## File Map

- `config/laravel-mcp.php`: add `queue_tools` config and enable the `queues` module
- `src/Policies/EnvironmentPolicy.php`: add queue mutation enablement checks
- `src/Capabilities/Queues/QueuesCapabilities.php`: register the queue tools
- `src/LaravelMcpSuiteServiceProvider.php`: bind queue support services
- `src/Support/QueueFailedJobInspector.php`: read and normalize failed jobs from the framework failer
- `src/Support/QueueFailedJobOperator.php`: retry and delete one failed job id
- `src/MCP/Tools/LaravelQueueFailedListTool.php`: read-only failed job listing
- `src/MCP/Tools/LaravelQueueFailedRetryTool.php`: retry one failed job
- `src/MCP/Tools/LaravelQueueFailedDeleteTool.php`: delete one failed job
- `tests/Feature/CapabilityRegistryTest.php`: assert queue tool registration
- `tests/Unit/EnvironmentPolicyTest.php`: assert queue mutation gating
- `tests/Unit/QueueFailedJobInspectorTest.php`: normalized failed-job listing tests
- `tests/Unit/QueueFailedJobOperatorTest.php`: retry/delete behavior tests with focused fakes
- `tests/Feature/Tools/LaravelQueueFailedListToolTest.php`: MCP list behavior
- `tests/Feature/Tools/LaravelQueueFailedRetryToolTest.php`: MCP retry behavior
- `tests/Feature/Tools/LaravelQueueFailedDeleteToolTest.php`: MCP delete behavior
- `README.md`: document the queue tools and config

## Chunk 1: Register The Queue Capability

### Task 1: Add failing capability registration coverage

**Files:**
- Modify: `tests/Feature/CapabilityRegistryTest.php`
- Modify: `src/Capabilities/Queues/QueuesCapabilities.php`
- Create: `src/MCP/Tools/LaravelQueueFailedListTool.php`
- Create: `src/MCP/Tools/LaravelQueueFailedRetryTool.php`
- Create: `src/MCP/Tools/LaravelQueueFailedDeleteTool.php`
- Test: `tests/Feature/CapabilityRegistryTest.php`

- [ ] **Step 1: Write the failing test**

Add assertions that `CapabilityRegistry::tools()` contains the three queue tool classes.

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php
```

Expected: FAIL because the queue capability is empty.

- [ ] **Step 3: Write minimal implementation**

Add tool stubs and return them from `QueuesCapabilities::tools()`.

- [ ] **Step 4: Run test to verify it passes**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php
```

Expected: PASS.

## Chunk 2: Lock Down Queue Policy And Support Contracts

### Task 2: Add failing policy and inspector/operator tests

**Files:**
- Modify: `config/laravel-mcp.php`
- Modify: `src/Policies/EnvironmentPolicy.php`
- Modify: `tests/Unit/EnvironmentPolicyTest.php`
- Create: `tests/Unit/QueueFailedJobInspectorTest.php`
- Create: `tests/Unit/QueueFailedJobOperatorTest.php`
- Create: `src/Support/QueueFailedJobInspector.php`
- Create: `src/Support/QueueFailedJobOperator.php`
- Modify: `src/LaravelMcpSuiteServiceProvider.php`
- Test: `tests/Unit/EnvironmentPolicyTest.php`
- Test: `tests/Unit/QueueFailedJobInspectorTest.php`
- Test: `tests/Unit/QueueFailedJobOperatorTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- queue mutations are enabled in local only by default
- inspector normalizes failed-job entries and respects the limit
- operator retries one failed job id and forgets it on success
- operator deletes one failed job id
- operator reports cleanly when an id is missing

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
vendor/bin/phpunit tests/Unit/EnvironmentPolicyTest.php tests/Unit/QueueFailedJobInspectorTest.php tests/Unit/QueueFailedJobOperatorTest.php
```

Expected: FAIL because the queue policy method and support classes do not exist.

- [ ] **Step 3: Write minimal implementation**

Implement queue mutation config, add `queueMutationsEnabled()` to `EnvironmentPolicy`, and add focused support classes bound through the service provider.

- [ ] **Step 4: Run tests to verify they pass**

Run:

```bash
vendor/bin/phpunit tests/Unit/EnvironmentPolicyTest.php tests/Unit/QueueFailedJobInspectorTest.php tests/Unit/QueueFailedJobOperatorTest.php
```

Expected: PASS.

## Chunk 3: Add MCP Tool Behavior

### Task 3: Add failing feature tests for the queue tools

**Files:**
- Create: `tests/Feature/Tools/LaravelQueueFailedListToolTest.php`
- Create: `tests/Feature/Tools/LaravelQueueFailedRetryToolTest.php`
- Create: `tests/Feature/Tools/LaravelQueueFailedDeleteToolTest.php`
- Modify: `src/MCP/Tools/LaravelQueueFailedListTool.php`
- Modify: `src/MCP/Tools/LaravelQueueFailedRetryTool.php`
- Modify: `src/MCP/Tools/LaravelQueueFailedDeleteTool.php`
- Test: `tests/Feature/Tools/LaravelQueueFailedListToolTest.php`
- Test: `tests/Feature/Tools/LaravelQueueFailedRetryToolTest.php`
- Test: `tests/Feature/Tools/LaravelQueueFailedDeleteToolTest.php`

- [ ] **Step 1: Write the failing tests**

Cover:
- failed list returns normalized entries and `meta.module = queues`
- retry is denied when queue mutations are disabled
- retry succeeds for an existing failed job id
- delete succeeds for an existing failed job id
- retry/delete handle missing ids cleanly

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelQueueFailedListToolTest.php tests/Feature/Tools/LaravelQueueFailedRetryToolTest.php tests/Feature/Tools/LaravelQueueFailedDeleteToolTest.php
```

Expected: FAIL because the tools have no behavior.

- [ ] **Step 3: Write minimal implementation**

Add schemas, validation, environment gates, support service calls, and structured MCP responses.

- [ ] **Step 4: Run tests to verify they pass**

Run:

```bash
vendor/bin/phpunit tests/Feature/Tools/LaravelQueueFailedListToolTest.php tests/Feature/Tools/LaravelQueueFailedRetryToolTest.php tests/Feature/Tools/LaravelQueueFailedDeleteToolTest.php
```

Expected: PASS.

## Chunk 4: Document And Verify

### Task 4: Update docs and run focused verification

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Update docs**

Document the three queue tools and the `queue_tools` mutation gate.

- [ ] **Step 2: Run focused verification**

Run:

```bash
vendor/bin/phpunit tests/Feature/CapabilityRegistryTest.php tests/Unit/EnvironmentPolicyTest.php tests/Unit/QueueFailedJobInspectorTest.php tests/Unit/QueueFailedJobOperatorTest.php tests/Feature/Tools/LaravelQueueFailedListToolTest.php tests/Feature/Tools/LaravelQueueFailedRetryToolTest.php tests/Feature/Tools/LaravelQueueFailedDeleteToolTest.php
```

Expected: PASS.
