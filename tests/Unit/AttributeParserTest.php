<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Unit;

use Brunoscode\LaravelTsAnnotations\Parser\AttributeParser;
use Brunoscode\LaravelTsAnnotations\Tests\Fixtures\UserController;
use Brunoscode\LaravelTsAnnotations\Tests\Fixtures\UserResource;
use Brunoscode\LaravelTsAnnotations\Tests\TestCase;

class AttributeParserTest extends TestCase
{
    private AttributeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AttributeParser();
    }

    // ── Class-level attributes ────────────────────────────────────────────────

    public function test_collects_class_level_attributes(): void
    {
        $result = $this->parser->parse([UserResource::class]);

        $this->assertArrayHasKey('default', $result);
        $this->assertCount(2, $result['default']);
    }

    public function test_class_label_is_the_fqcn(): void
    {
        $result = $this->parser->parse([UserResource::class]);

        $this->assertSame(UserResource::class, $result['default'][0]['class']);
    }

    public function test_class_body_contains_typescript(): void
    {
        $result = $this->parser->parse([UserResource::class]);

        $this->assertStringContainsString('export type UserResponse', $result['default'][0]['body']);
        $this->assertStringContainsString('export type UserIndex',    $result['default'][1]['body']);
    }

    // ── Method-level attributes ───────────────────────────────────────────────

    public function test_collects_method_level_attributes(): void
    {
        $result = $this->parser->parse([UserController::class]);

        $this->assertArrayHasKey('default', $result);
        $this->assertCount(3, $result['default']);
    }

    public function test_method_label_includes_method_name(): void
    {
        $result = $this->parser->parse([UserController::class]);

        $labels = array_column($result['default'], 'class');

        $this->assertContains(UserController::class . '::index()',  $labels);
        $this->assertContains(UserController::class . '::show()',   $labels);
        $this->assertContains(UserController::class . '::store()',  $labels);
    }

    public function test_methods_are_ordered_by_declaration(): void
    {
        $result = $this->parser->parse([UserController::class]);

        $labels = array_column($result['default'], 'class');

        $this->assertSame([
            UserController::class . '::index()',
            UserController::class . '::show()',
            UserController::class . '::store()',
        ], $labels);
    }

    public function test_method_body_contains_typescript(): void
    {
        $result = $this->parser->parse([UserController::class]);

        $this->assertStringContainsString('UserListResponse', $result['default'][0]['body']);
        $this->assertStringContainsString('UserShowResponse', $result['default'][1]['body']);
        $this->assertStringContainsString('UserStoreResponse', $result['default'][2]['body']);
    }

    // ── Mixed class + method ──────────────────────────────────────────────────

    public function test_class_attributes_come_before_method_attributes(): void
    {
        $result = $this->parser->parse([UserResource::class, UserController::class]);

        $labels = array_column($result['default'], 'class');

        // UserResource has class-level attrs → appear first
        $this->assertSame(UserResource::class, $labels[0]);
        $this->assertSame(UserResource::class, $labels[1]);

        // UserController has method-level attrs → appear after
        $this->assertStringContainsString('::index()', $labels[2]);
    }

    // ── Edge cases ────────────────────────────────────────────────────────────

    public function test_returns_empty_for_class_without_attributes(): void
    {
        $result = $this->parser->parse([\stdClass::class]);

        $this->assertEmpty($result);
    }

    public function test_skips_nonexistent_class(): void
    {
        $result = $this->parser->parse(['NonExistent\\ClassName']);

        $this->assertEmpty($result);
    }

    public function test_does_not_collect_inherited_methods(): void
    {
        // UserController doesn't extend anything with #[TS] methods,
        // but we verify no inherited methods bleed in.
        $result = $this->parser->parse([UserController::class]);

        foreach ($result['default'] as $entry) {
            $this->assertStringStartsWith(UserController::class, $entry['class']);
        }
    }
}
