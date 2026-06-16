<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Unit;

use BrunosCode\LaravelTsAnnotations\Parser\AttributeParser;
use BrunosCode\LaravelTsAnnotations\Tests\Fixtures\AdminStatusEnum;
use BrunosCode\LaravelTsAnnotations\Tests\Fixtures\DirectionEnum;
use BrunosCode\LaravelTsAnnotations\Tests\Fixtures\PriorityEnum;
use BrunosCode\LaravelTsAnnotations\Tests\Fixtures\StatusEnum;
use BrunosCode\LaravelTsAnnotations\Tests\TestCase;

class EnumParserTest extends TestCase
{
    private AttributeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AttributeParser();
    }

    // ── String-backed enum ────────────────────────────────────────────────────

    public function test_collects_string_backed_enum(): void
    {
        $result = $this->parser->parse([StatusEnum::class]);

        $this->assertArrayHasKey('default', $result);
        $this->assertCount(1, $result['default']);
    }

    public function test_string_backed_enum_label_is_fqcn(): void
    {
        $result = $this->parser->parse([StatusEnum::class]);

        $this->assertSame(StatusEnum::class, $result['default'][0]['class']);
    }

    public function test_string_backed_enum_generates_ts_enum_block(): void
    {
        $result = $this->parser->parse([StatusEnum::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('export enum StatusEnum {', $body);
        $this->assertStringContainsString("Active = 'active',",   $body);
        $this->assertStringContainsString("Inactive = 'inactive',", $body);
        $this->assertStringContainsString("Pending = 'pending',",  $body);
        $this->assertStringEndsWith('}', $body);
    }

    public function test_string_backed_enum_values_are_quoted(): void
    {
        $result = $this->parser->parse([StatusEnum::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString("'active'",   $body);
        $this->assertStringContainsString("'inactive'", $body);
        $this->assertStringContainsString("'pending'",  $body);
    }

    // ── Int-backed enum ───────────────────────────────────────────────────────

    public function test_int_backed_enum_generates_ts_enum_block(): void
    {
        $result = $this->parser->parse([PriorityEnum::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('export enum PriorityEnum {', $body);
        $this->assertStringContainsString('Low = 1,',    $body);
        $this->assertStringContainsString('Medium = 2,', $body);
        $this->assertStringContainsString('High = 3,',   $body);
    }

    public function test_int_backed_enum_values_are_not_quoted(): void
    {
        $result = $this->parser->parse([PriorityEnum::class]);
        $body   = $result['default'][0]['body'];

        // Values must be bare integers, not strings
        $this->assertStringNotContainsString("'1'", $body);
        $this->assertStringNotContainsString("'2'", $body);
        $this->assertStringNotContainsString("'3'", $body);
    }

    // ── Unit enum (no backing type) ────────────────────────────────────────────

    public function test_unit_enum_generates_ts_enum_block(): void
    {
        $result = $this->parser->parse([DirectionEnum::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('export enum DirectionEnum {', $body);
        $this->assertStringContainsString("North = 'North',", $body);
        $this->assertStringContainsString("South = 'South',", $body);
        $this->assertStringContainsString("East = 'East',",   $body);
        $this->assertStringContainsString("West = 'West',",   $body);
    }

    // ── Output routing ────────────────────────────────────────────────────────

    public function test_tsEnum_output_key_routes_to_correct_group(): void
    {
        $result = $this->parser->parse([StatusEnum::class]);

        $this->assertArrayHasKey('default', $result);
    }

    // ── Custom output key routing ─────────────────────────────────────────────

    public function test_custom_output_key_routes_to_correct_group(): void
    {
        $result = $this->parser->parse([AdminStatusEnum::class]);

        $this->assertArrayHasKey('admin', $result);
        $this->assertArrayNotHasKey('default', $result);
        $this->assertStringContainsString('export enum AdminStatusEnum', $result['admin'][0]['body']);
    }

    // ── No interference with plain classes ────────────────────────────────────

    public function test_tsEnum_not_applied_to_non_enum_classes(): void
    {
        // UserResource is a plain class — TSEnum should have no effect
        $result = $this->parser->parse([\BrunosCode\LaravelTsAnnotations\Tests\Fixtures\UserResource::class]);

        // Only #[TS] entries should be present, not any auto-generated enum body
        foreach ($result['default'] ?? [] as $entry) {
            $this->assertStringNotContainsString('export enum', $entry['body']);
        }
    }
}
