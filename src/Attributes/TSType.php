<?php

namespace Brunoscode\LaravelTsAnnotations\Attributes;

/**
 * Generate a TypeScript type alias automatically from a PHP class.
 *
 * Public non-static properties (including promoted constructor params) are
 * inspected via Reflection. PHP types are mapped to TypeScript equivalents.
 * The `readonly` modifier is preserved.
 *
 *   #[TSType]
 *   class UserData {
 *       public function __construct(
 *           public readonly int $id,
 *           public readonly string $name,
 *           public readonly ?string $email,
 *       ) {}
 *   }
 *
 * Generates:
 *
 *   export type UserData = {
 *       readonly id: number;
 *       readonly name: string;
 *       readonly email: string | null;
 *   }
 *
 * Use `name` to override the TypeScript identifier when it must differ from
 * the PHP class short name.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class TSType
{
    public function __construct(
        public readonly string $output = 'default',
        public readonly ?string $name  = null,
    ) {}
}
