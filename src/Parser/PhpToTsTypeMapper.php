<?php

namespace Brunoscode\LaravelTsAnnotations\Parser;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/**
 * Maps PHP Reflection types to their TypeScript equivalents.
 */
class PhpToTsTypeMapper
{
    /** @var array<string, string> */
    private const SCALARS = [
        'string' => 'string',
        'int'    => 'number',
        'float'  => 'number',
        'double' => 'number',
        'bool'   => 'boolean',
        'true'   => 'true',
        'false'  => 'false',
        'null'   => 'null',
        'array'  => 'unknown[]',
        'mixed'  => 'any',
        'object' => 'object',
        'void'   => 'void',
        'never'  => 'never',
        'self'   => 'this',
        'static' => 'this',
    ];

    /** @var array<string, string> */
    private const CLASS_OVERRIDES = [
        'Carbon\\Carbon'                              => 'string',
        'Carbon\\CarbonImmutable'                    => 'string',
        'Illuminate\\Support\\Carbon'                => 'string',
        'Illuminate\\Support\\Collection'            => 'unknown[]',
        'Illuminate\\Database\\Eloquent\\Collection' => 'unknown[]',
    ];

    public function map(ReflectionType $type): string
    {
        if ($type instanceof ReflectionUnionType) {
            return implode(' | ', array_map([$this, 'map'], $type->getTypes()));
        }

        if ($type instanceof ReflectionIntersectionType) {
            return implode(' & ', array_map([$this, 'map'], $type->getTypes()));
        }

        if ($type instanceof ReflectionNamedType) {
            return $this->mapNamed($type);
        }

        return 'unknown';
    }

    private function mapNamed(ReflectionNamedType $type): string
    {
        $name = $type->getName();

        $tsType = self::SCALARS[$name]
            ?? self::CLASS_OVERRIDES[$name]
            ?? $this->shortName($name);

        // ?T shorthand → T | null (not applied when the type is already null/mixed)
        if ($type->allowsNull() && $name !== 'null' && $name !== 'mixed') {
            return $tsType . ' | null';
        }

        return $tsType;
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
