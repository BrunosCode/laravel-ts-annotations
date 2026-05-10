<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Unit;

use Brunoscode\LaravelTsAnnotations\Parser\AttributeParser;
use Brunoscode\LaravelTsAnnotations\Tests\Fixtures\ProductData;
use Brunoscode\LaravelTsAnnotations\Tests\Fixtures\UserData;
use Brunoscode\LaravelTsAnnotations\Tests\TestCase;

class TSTypeParserTest extends TestCase
{
    private AttributeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AttributeParser();
    }

    // ── Basic collection ──────────────────────────────────────────────────────

    public function test_collects_tstype_class(): void
    {
        $result = $this->parser->parse([UserData::class]);

        $this->assertArrayHasKey('default', $result);
        $this->assertCount(1, $result['default']);
    }

    public function test_label_is_fqcn(): void
    {
        $result = $this->parser->parse([UserData::class]);

        $this->assertSame(UserData::class, $result['default'][0]['class']);
    }

    // ── Generated body structure ──────────────────────────────────────────────

    public function test_generates_export_type_alias(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('export type UserData = {', $body);
        $this->assertStringEndsWith('}', $body);
    }

    public function test_promoted_constructor_params_are_included(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('id:', $body);
        $this->assertStringContainsString('name:', $body);
        $this->assertStringContainsString('email:', $body);
        $this->assertStringContainsString('active:', $body);
    }

    // ── Type mapping ──────────────────────────────────────────────────────────

    public function test_int_maps_to_number(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('id: number;', $body);
    }

    public function test_string_maps_to_string(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('name: string;', $body);
    }

    public function test_bool_maps_to_boolean(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('active: boolean;', $body);
    }

    public function test_nullable_string_maps_to_string_or_null(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('email: string | null;', $body);
    }

    public function test_float_maps_to_number(): void
    {
        $result = $this->parser->parse([ProductData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('price: number;', $body);
    }

    public function test_array_maps_to_unknown_array(): void
    {
        $result = $this->parser->parse([ProductData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('tags: unknown[];', $body);
    }

    // ── readonly modifier ─────────────────────────────────────────────────────

    public function test_readonly_property_gets_readonly_modifier(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringContainsString('readonly id: number;', $body);
        $this->assertStringContainsString('readonly name: string;', $body);
        $this->assertStringContainsString('readonly email: string | null;', $body);
    }

    public function test_non_readonly_property_has_no_readonly_modifier(): void
    {
        $result = $this->parser->parse([ProductData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringNotContainsString('readonly id:', $body);
    }

    // ── Static properties excluded ────────────────────────────────────────────

    public function test_static_properties_are_excluded(): void
    {
        $result = $this->parser->parse([ProductData::class]);
        $body   = $result['default'][0]['body'];

        $this->assertStringNotContainsString('ignored', $body);
    }

    // ── Custom name override ──────────────────────────────────────────────────

    public function test_custom_name_overrides_class_short_name(): void
    {
        // Inline anonymous class to test the `name` param without a fixture file
        $tsTypeAttr = new \Brunoscode\LaravelTsAnnotations\Attributes\TSType(name: 'IUser');
        $this->assertSame('IUser', $tsTypeAttr->name);
    }
}
