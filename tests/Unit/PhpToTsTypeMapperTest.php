<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Unit;

use BrunosCode\LaravelTsAnnotations\Parser\PhpToTsTypeMapper;
use BrunosCode\LaravelTsAnnotations\Tests\TestCase;
use ReflectionClass;
use ReflectionType;

class PhpToTsTypeMapperTest extends TestCase
{
    private PhpToTsTypeMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new PhpToTsTypeMapper();
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function typeOf(object $obj, string $prop): ReflectionType
    {
        return (new ReflectionClass($obj))->getProperty($prop)->getType();
    }

    // ── Scalars ───────────────────────────────────────────────────────────────

    public function test_string_maps_to_string(): void
    {
        $obj = new class { public string $x; };
        $this->assertSame('string', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_int_maps_to_number(): void
    {
        $obj = new class { public int $x; };
        $this->assertSame('number', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_float_maps_to_number(): void
    {
        $obj = new class { public float $x; };
        $this->assertSame('number', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_bool_maps_to_boolean(): void
    {
        $obj = new class { public bool $x; };
        $this->assertSame('boolean', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_array_maps_to_unknown_array(): void
    {
        $obj = new class { public array $x; };
        $this->assertSame('unknown[]', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_mixed_maps_to_any(): void
    {
        $obj = new class { public mixed $x; };
        $this->assertSame('any', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_object_maps_to_object(): void
    {
        $obj = new class { public object $x; };
        $this->assertSame('object', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_self_maps_to_this(): void
    {
        $obj = new class { public self $x; };
        $this->assertSame('this', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    // ── Nullable (? shorthand) ────────────────────────────────────────────────

    public function test_nullable_string_maps_to_string_or_null(): void
    {
        $obj = new class { public ?string $x; };
        $this->assertSame('string | null', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_nullable_int_maps_to_number_or_null(): void
    {
        $obj = new class { public ?int $x; };
        $this->assertSame('number | null', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_mixed_does_not_get_null_suffix(): void
    {
        // mixed already encompasses null — appending | null would be wrong
        $obj = new class { public mixed $x; };
        $result = $this->mapper->map($this->typeOf($obj, 'x'));

        $this->assertSame('any', $result);
        $this->assertStringNotContainsString('| null', $result);
    }

    // ── Union types ───────────────────────────────────────────────────────────

    public function test_int_or_string_union_maps_correctly(): void
    {
        $obj    = new class { public int|string $x; };
        $result = $this->mapper->map($this->typeOf($obj, 'x'));

        // PHP may reorder union type members — assert both parts present
        $this->assertStringContainsString('number', $result);
        $this->assertStringContainsString('string', $result);
        $this->assertStringContainsString(' | ', $result);
    }

    public function test_string_or_null_union_maps_correctly(): void
    {
        $obj    = new class { public string|null $x; };
        $result = $this->mapper->map($this->typeOf($obj, 'x'));

        $this->assertStringContainsString('string', $result);
        $this->assertStringContainsString('null', $result);
        $this->assertStringContainsString(' | ', $result);
    }

    public function test_three_type_union_maps_correctly(): void
    {
        $obj    = new class { public int|string|bool $x; };
        $result = $this->mapper->map($this->typeOf($obj, 'x'));

        $this->assertStringContainsString('number', $result);
        $this->assertStringContainsString('string', $result);
        $this->assertStringContainsString('boolean', $result);
        $this->assertSame(2, substr_count($result, ' | '));
    }

    // ── Intersection types ────────────────────────────────────────────────────

    public function test_intersection_type_uses_ampersand_separator(): void
    {
        $obj = new class { public \Countable&\Stringable $x; };
        $this->assertSame('Countable & Stringable', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    // ── CLASS_OVERRIDES ───────────────────────────────────────────────────────

    public function test_carbon_maps_to_string(): void
    {
        $obj = new class { public \Carbon\Carbon $x; };
        $this->assertSame('string', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_carbon_immutable_maps_to_string(): void
    {
        $obj = new class { public \Carbon\CarbonImmutable $x; };
        $this->assertSame('string', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_illuminate_carbon_maps_to_string(): void
    {
        $obj = new class { public \Illuminate\Support\Carbon $x; };
        $this->assertSame('string', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_support_collection_maps_to_unknown_array(): void
    {
        $obj = new class { public \Illuminate\Support\Collection $x; };
        $this->assertSame('unknown[]', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_eloquent_collection_maps_to_unknown_array(): void
    {
        $obj = new class { public \Illuminate\Database\Eloquent\Collection $x; };
        $this->assertSame('unknown[]', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_nullable_carbon_appends_null(): void
    {
        $obj = new class { public ?\Carbon\Carbon $x; };
        $this->assertSame('string | null', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    // ── Unknown classes → short name ──────────────────────────────────────────

    public function test_unknown_fqcn_uses_short_class_name(): void
    {
        // stdClass is not in CLASS_OVERRIDES — should emit short name
        $obj = new class { public \stdClass $x; };
        $this->assertSame('stdClass', $this->mapper->map($this->typeOf($obj, 'x')));
    }

    public function test_nullable_unknown_class_emits_short_name_or_null(): void
    {
        $obj = new class { public ?\stdClass $x; };
        $this->assertSame('stdClass | null', $this->mapper->map($this->typeOf($obj, 'x')));
    }
}
