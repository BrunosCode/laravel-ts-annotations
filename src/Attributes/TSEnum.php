<?php

namespace BrunosCode\LaravelTsAnnotations\Attributes;

/**
 * Generate a TypeScript enum automatically from a PHP enum.
 *
 * Only the output key is required — the TypeScript body is derived from the
 * enum cases and their backing values (or case names for unit enums).
 *
 *   #[TSEnum]
 *   enum Status: string {
 *       case Active   = 'active';
 *       case Inactive = 'inactive';
 *   }
 *
 * Generates:
 *
 *   export enum Status {
 *       Active   = 'active',
 *       Inactive = 'inactive',
 *   }
 *
 * For unit enums (no backing type), case names are used as string values.
 * For int-backed enums, numeric values are emitted without quotes.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class TSEnum
{
    public function __construct(
        public readonly string $output = 'default',
    ) {}
}
