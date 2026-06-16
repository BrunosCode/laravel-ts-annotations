<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Unit;

use BrunosCode\LaravelTsAnnotations\Attributes\TSType;
use BrunosCode\LaravelTsAnnotations\Parser\AttributeParser;
use BrunosCode\LaravelTsAnnotations\Tests\Fixtures\ProductData;
use BrunosCode\LaravelTsAnnotations\Tests\Fixtures\UserData;
use BrunosCode\LaravelTsAnnotations\Tests\Fixtures\UserDataChild;
use BrunosCode\LaravelTsAnnotations\Tests\TestCase;

class TSTypeParserTest extends TestCase
{
    private AttributeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AttributeParser;
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
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('export type UserData = {', $body);
        $this->assertStringEndsWith('}', $body);
    }

    public function test_promoted_constructor_params_are_included(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('id:', $body);
        $this->assertStringContainsString('name:', $body);
        $this->assertStringContainsString('email:', $body);
        $this->assertStringContainsString('active:', $body);
    }

    // ── Type mapping ──────────────────────────────────────────────────────────

    public function test_int_maps_to_number(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('id: number;', $body);
    }

    public function test_string_maps_to_string(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('name: string;', $body);
    }

    public function test_bool_maps_to_boolean(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('active: boolean;', $body);
    }

    public function test_nullable_string_maps_to_string_or_null(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('email: string | null;', $body);
    }

    public function test_float_maps_to_number(): void
    {
        $result = $this->parser->parse([ProductData::class]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('price: number;', $body);
    }

    public function test_array_maps_to_unknown_array(): void
    {
        $result = $this->parser->parse([ProductData::class]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('tags: unknown[];', $body);
    }

    // ── readonly modifier ─────────────────────────────────────────────────────

    public function test_readonly_property_gets_readonly_modifier(): void
    {
        $result = $this->parser->parse([UserData::class]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('readonly id: number;', $body);
        $this->assertStringContainsString('readonly name: string;', $body);
        $this->assertStringContainsString('readonly email: string | null;', $body);
    }

    public function test_non_readonly_property_has_no_readonly_modifier(): void
    {
        $result = $this->parser->parse([ProductData::class]);
        $body = $result['default'][0]['body'];

        $this->assertStringNotContainsString('readonly id:', $body);
    }

    // ── Static properties excluded ────────────────────────────────────────────

    public function test_static_properties_are_excluded(): void
    {
        $result = $this->parser->parse([ProductData::class]);
        $body = $result['default'][0]['body'];

        $this->assertStringNotContainsString('ignored', $body);
    }

    // ── Custom name override ──────────────────────────────────────────────────

    public function test_custom_name_overrides_class_short_name(): void
    {
        $tsTypeAttr = new TSType(name: 'IUser');
        $this->assertSame('IUser', $tsTypeAttr->name);
    }

    public function test_custom_name_appears_in_generated_body(): void
    {
        $obj = new #[TSType(name: 'IProduct')] class
        {
            public int $id;
        };
        $fqcn = get_class($obj);

        $result = $this->parser->parse([$fqcn]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('export type IProduct = {', $body);
        $this->assertStringNotContainsString('export type class@', $body);
    }

    // ── Inherited properties excluded ─────────────────────────────────────────

    public function test_inherited_properties_are_not_included(): void
    {
        $result = $this->parser->parse([UserDataChild::class]);
        $body = $result['default'][0]['body'];

        // Only the child's own property
        $this->assertStringContainsString('childOnly: string;', $body);

        // Parent properties must not leak through
        $this->assertStringNotContainsString('id:', $body);
        $this->assertStringNotContainsString('name:', $body);
        $this->assertStringNotContainsString('email:', $body);
        $this->assertStringNotContainsString('active:', $body);
    }

    // ── Untyped property ──────────────────────────────────────────────────────

    public function test_untyped_property_maps_to_unknown(): void
    {
        $obj = new #[TSType] class
        {
            public $untyped;
        };
        $fqcn = get_class($obj);

        $result = $this->parser->parse([$fqcn]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('untyped: unknown;', $body);
    }

    // ── Union type property ───────────────────────────────────────────────────

    public function test_union_type_property_maps_correctly(): void
    {
        $obj = new #[TSType] class
        {
            public int|string $value;
        };
        $fqcn = get_class($obj);

        $result = $this->parser->parse([$fqcn]);
        $body = $result['default'][0]['body'];

        // PHP may reorder union type members — assert both parts present
        $this->assertStringContainsString('value:', $body);
        $this->assertStringContainsString('number', $body);
        $this->assertStringContainsString('string', $body);
        $this->assertStringContainsString(' | ', $body);
    }

    // ── Class with no public properties ──────────────────────────────────────

    public function test_class_with_no_properties_produces_empty_type_body(): void
    {
        $obj = new #[TSType] class {};
        $fqcn = get_class($obj);

        $result = $this->parser->parse([$fqcn]);
        $body = $result['default'][0]['body'];

        $this->assertStringContainsString('export type', $body);
        $this->assertStringContainsString('{', $body);
        $this->assertStringContainsString('}', $body);
        $this->assertStringNotContainsString('// ---', $body);
    }

    // ── Custom output key routing ─────────────────────────────────────────────

    public function test_custom_output_key_routes_correctly(): void
    {
        $obj = new #[TSType(output: 'admin')] class
        {
            public int $id;
        };
        $fqcn = get_class($obj);

        $result = $this->parser->parse([$fqcn]);

        $this->assertArrayHasKey('admin', $result);
        $this->assertArrayNotHasKey('default', $result);
    }
}
