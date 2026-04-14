<?php

namespace LaravelMcpSuite\Tests\Feature\Prompts;

use LaravelMcpSuite\MCP\Prompts\DebugLastExceptionPrompt;
use LaravelMcpSuite\MCP\Prompts\GenerateFeatureTestPrompt;
use LaravelMcpSuite\MCP\Prompts\ReviewRouteControllerConsistencyPrompt;
use LaravelMcpSuite\MCP\Prompts\ScaffoldCrudPrompt;
use LaravelMcpSuite\Support\CapabilityRegistry;
use LaravelMcpSuite\Tests\TestCase;

class CorePromptsTest extends TestCase
{
    public function test_prompt_registry_exposes_the_core_prompts(): void
    {
        $prompts = $this->app->make(CapabilityRegistry::class)->prompts();

        $this->assertContains(DebugLastExceptionPrompt::class, $prompts);
        $this->assertContains(ScaffoldCrudPrompt::class, $prompts);
        $this->assertContains(GenerateFeatureTestPrompt::class, $prompts);
        $this->assertContains(ReviewRouteControllerConsistencyPrompt::class, $prompts);
    }

    public function test_debug_last_exception_prompt_references_exact_capabilities_in_order(): void
    {
        $messages = $this->app->make(DebugLastExceptionPrompt::class)->handle()->responses()->all();
        $text = (string) $messages[0]->content();

        $this->assertTrue(strpos($text, 'laravel://app/errors/recent') < strpos($text, 'laravel_exception_last'));
        $this->assertTrue(strpos($text, 'laravel_exception_last') < strpos($text, 'laravel_logs_recent'));
    }

    public function test_scaffold_crud_prompt_references_routes_models_and_conventions_before_generators(): void
    {
        $text = (string) $this->app->make(ScaffoldCrudPrompt::class)->handle()->responses()->first()->content();

        $this->assertTrue(strpos($text, 'laravel://app/routes') < strpos($text, 'laravel://app/models'));
        $this->assertTrue(strpos($text, 'laravel://app/models') < strpos($text, 'laravel://docs/project-conventions'));
        $this->assertTrue(strpos($text, 'laravel://docs/project-conventions') < strpos($text, 'laravel-crud-api-generate'));
        $this->assertTrue(strpos($text, 'laravel-crud-api-generate') < strpos($text, 'laravel-crud-web-generate'));
    }

    public function test_generate_feature_test_prompt_references_context_then_conventions_then_tests(): void
    {
        $text = (string) $this->app->make(GenerateFeatureTestPrompt::class)->handle()->responses()->first()->content();

        $this->assertTrue(strpos($text, 'laravel://app/routes') < strpos($text, 'laravel://docs/project-conventions'));
        $this->assertTrue(strpos($text, 'laravel://docs/project-conventions') < strpos($text, 'laravel_tests_run'));
    }

    public function test_review_route_controller_consistency_prompt_references_route_inventory(): void
    {
        $text = (string) $this->app->make(ReviewRouteControllerConsistencyPrompt::class)->handle()->responses()->first()->content();

        $this->assertStringContainsString('laravel://app/routes', $text);
    }
}
