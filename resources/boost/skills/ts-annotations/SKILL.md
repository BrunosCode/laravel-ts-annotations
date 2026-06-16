---
name: ts-annotations
description: "Generate TypeScript types from PHP attributes using ts:generate. Use when the user wants to annotate PHP classes or methods with #[TS] (raw TypeScript), #[TSType] (auto-infer from class properties), or #[TSEnum] (auto-generate from PHP enums), run the generator, configure output files, or manage file-preservation markers."
---

# TS Annotations

## Overview

`brunoscode/laravel-ts-annotations` generates TypeScript types from PHP attributes
and emits them to `.ts` files with a single Artisan command. Three annotation
styles cover every common case:

| Attribute | When to use |
|-----------|-------------|
| `#[TS]` | Write real TypeScript verbatim — unions, generics, template literals |
| `#[TSType]` | Auto-infer a `type` alias from public class properties |
| `#[TSEnum]` | Auto-generate a TypeScript `enum` from a PHP backed or unit enum |

---

## `#[TS]` — raw TypeScript

Usable on classes and individual methods. Repeatable.

```php
use Brunoscode\LaravelTsAnnotations\Attributes\TS;

// On a class (API Resource, DTO, Form Request…)
#[TS(<<<'TS'
    export type UserResponse = {
        id: number;
        name: string;
        role: 'admin' | 'editor' | 'viewer';
    }
    TS)]
class UserResource extends JsonResource {}

// On a controller method
#[TS(<<<'TS'
    export type UserListResponse = {
        data: UserResponse[];
        total: number;
    }
    TS)]
public function index(): JsonResponse { ... }
```

### Heredoc indentation rule

Always use PHP flexible heredoc (`<<<'TS'`). Indent the closing `TS` marker at
the same level as the type body — PHP strips that many leading spaces
automatically, giving zero-based indentation in the output.

```php
// Good — closing TS aligns with the type body
#[TS(<<<'TS'
    export type Foo = {
        id: number;
    }
    TS)]

// Bad — closing TS at column 0 means no stripping
#[TS(<<<'TS'
    export type Foo = {
        id: number;
    }
TS)]
```

---

## `#[TSType]` — auto-infer from class properties

Inspects all public non-static properties (including promoted constructor params)
via Reflection. The `readonly` modifier is preserved in the output.

Only properties **declared on the class itself** are emitted — properties
inherited from a parent class are skipped. Annotate the parent with `#[TSType]`
too if you need its properties in a separate type.

```php
use Brunoscode\LaravelTsAnnotations\Attributes\TSType;

#[TSType]
class OrderData
{
    public function __construct(
        public readonly int $id,
        public readonly string $reference,
        public readonly float $total,
        public readonly bool $paid,
        public readonly ?string $note,
    ) {}
}
```

Generates:

```typescript
export type OrderData = {
    readonly id: number;
    readonly reference: string;
    readonly total: number;
    readonly paid: boolean;
    readonly note: string | null;
}
```

PHP → TypeScript type mapping:

| PHP | TypeScript |
|-----|------------|
| `string` | `string` |
| `int`, `float` | `number` |
| `bool` | `boolean` |
| `?T` | `T \| null` |
| `T\|U` | `T \| U` |
| `array` | `unknown[]` |
| `mixed` | `any` |
| `object` | `object` |
| `void`, `never` | `void`, `never` |
| `self`, `static` | `this` |
| `T & U` (intersection) | `T & U` |
| `Carbon\Carbon`, `CarbonImmutable`, `Illuminate\Support\Carbon` | `string` |
| `Illuminate\Support\Collection`, Eloquent `Collection` | `unknown[]` |
| Any other class | short class name |

Only the exact Carbon/Collection FQCNs above are remapped. A different class
named `Collection` (e.g. your own `App\Support\Collection`) falls through to the
short-name rule and becomes the literal `Collection`, **not** `unknown[]`.

Use the optional `name` parameter to override the TypeScript identifier:

```php
#[TSType(name: 'IOrder')]
class OrderData { ... }
// → export type IOrder = { ... }
```

---

## `#[TSEnum]` — auto-generate from PHP enums

No body to write — cases and backing values are read automatically.

```php
use Brunoscode\LaravelTsAnnotations\Attributes\TSEnum;

#[TSEnum]
enum Status: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}
// → export enum Status { Active = 'active', Inactive = 'inactive', }

#[TSEnum]
enum Priority: int
{
    case Low  = 1;
    case High = 3;
}
// → export enum Priority { Low = 1, High = 3, }

// Unit enum — case name used as string value
#[TSEnum]
enum Direction
{
    case North;
    case South;
}
// → export enum Direction { North = 'North', South = 'South', }
```

---

## Targeting a specific output file

All three annotations accept an `output` parameter:

```php
#[TS(<<<'TS' ... TS, output: 'admin')]
#[TSType(output: 'admin')]
#[TSEnum(output: 'admin')]
```

The key must match one defined in `config/ts-annotations.php`.

---

## Running the generator

```bash
php artisan ts:generate                    # all configured outputs
php artisan ts:generate --output=admin     # one specific output
php artisan ts:generate --dry-run          # preview without writing
```

---

## Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=ts-annotations-config
```

```php
// config/ts-annotations.php
return [
    'scan' => [
        app_path('Http'),       // classes/methods with #[TS]
        app_path('Enum'),       // enums with #[TSEnum]
        app_path('Data'),       // DTOs with #[TSType]
    ],
    'outputs' => [
        'default' => [
            'path'    => resource_path('js/types/generated.ts'),
            // Lines prepended inside the generated block on every run.
            // The shipped default provides the Inertia resource wrappers:
            'imports' => [
                'export type CollectionResource<T> = { data: T[] };',
                '',
                'export type PaginatedResource<T> = {',
                '    data: T[];',
                '    total: number;',
                '    per_page: number;',
                '    current_page: number;',
                '    last_page: number;',
                '    from: number | null;',
                '    to: number | null;',
                '    first_page_url: string;',
                '    last_page_url: string;',
                '    next_page_url: string | null;',
                '    prev_page_url: string | null;',
                '    path: string;',
                '};',
            ],
        ],
        // 'admin' => ['path' => resource_path('js/types/admin.ts'), 'imports' => []],
    ],
    'markers' => [
        'start' => '// [ts-annotations:start]',
        'end'   => '// [ts-annotations:end]',
    ],
];
```

---

## File preservation

The generator only replaces the section between the two marker comments.
Everything outside — manual imports, hand-written types — is untouched on every
run. If the file has no markers the generated block is appended at the end.

---

## Output order

Entries follow **file-scan order across classes**. Within a single class they
are emitted in this fixed order:

1. Class-level `#[TS]` attributes
2. `#[TSEnum]` (only on enums)
3. `#[TSType]` (inferred from the class)
4. Method-level `#[TS]` attributes, sorted by line number

There is **no** global grouping by attribute type. A `#[TSEnum]` in a class
that is scanned before a `#[TS]` resource appears first in the output. The
result looks grouped only when your `scan` paths are themselves ordered by
kind (e.g. `Http`, then `Enum`, then `Data`) — that grouping comes from scan
order, not from an intrinsic sort.

Each entry is preceded by a source comment:

```typescript
// --- App\Http\Resources\UserResource ---
export type UserResponse = { ... }

// --- App\Enums\Status ---
export enum Status { ... }

// --- App\Data\OrderData ---
export type OrderData = { ... }
```

---

## Rules

- Never edit between `// [ts-annotations:start]` and `// [ts-annotations:end]`.
- Run `ts:generate` after adding or changing any annotation.
- When adding a new output file, define it in config before referencing it in an attribute.
- Use `--dry-run` to verify output before committing generated files.
- Add scan paths for `Data/` and `Enum/` directories when using `#[TSType]` or `#[TSEnum]`.
