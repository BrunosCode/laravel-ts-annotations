# laravel-ts-annotations

Generate TypeScript types from PHP attributes with a single Artisan command. Three annotation styles — raw TypeScript, auto-inferred from class properties, and auto-inferred from enums — cover every common case.

## Laravel Boost

This package ships a [Laravel Boost](https://laravel.com/docs/boost) skill. If you use Boost, run:

```bash
php artisan boost:install
```

and select `brunoscode/laravel-ts-annotations` when prompted. The skill teaches your AI agent how to use `#[TS]`, `#[TSType]`, and `#[TSEnum]` attributes, run `ts:generate`, and manage the generated output.

---

## Quick start

```php
// Raw TypeScript — full control
#[TS(<<<'TS'
    export type UserResponse = {
        id: number;
        name: string;
        role: 'admin' | 'editor' | 'viewer';
    }
    TS)]
class UserResource extends JsonResource {}

// Auto-inferred from class properties
#[TSType]
class UserData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $email,
    ) {}
}

// Auto-inferred from PHP enum
#[TSEnum]
enum Status: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}
```

```bash
php artisan ts:generate
```

```typescript
// resources/js/types/generated.ts  ← generated automatically

// [ts-annotations:start]
// ⚠️  Auto-generated — do not edit between these comments.

// --- App\Http\Resources\UserResource ---
export type UserResponse = {
    id: number;
    name: string;
    role: 'admin' | 'editor' | 'viewer';
}

// --- App\Data\UserData ---
export type UserData = {
    readonly id: number;
    readonly name: string;
    readonly email: string | null;
}

// --- App\Enums\Status ---
export enum Status {
    Active = 'active',
    Inactive = 'inactive',
}
// [ts-annotations:end]
```

---

## Why this package?

Most solutions either **infer** TypeScript from PHP types (losing union types, template literals, generics) or go through a Swagger/OpenAPI intermediary (indirect and verbose). This package gives you three levels of control:

- `#[TS]` — write **real TypeScript** verbatim when you need unions, templates, or generics
- `#[TSType]` — **auto-infer** from PHP property types for simple DTOs and data classes
- `#[TSEnum]` — **auto-generate** TypeScript enums from PHP backed or unit enums

---

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

---

## Installation

```bash
composer require brunoscode/laravel-ts-annotations
```

Publish the config file:

```bash
php artisan vendor:publish --tag=ts-annotations-config
```

---

## Configuration

```php
// config/ts-annotations.php

return [

    // Directories scanned recursively for all annotation types.
    'scan' => [
        app_path('Http'),       // covers Resources, Controllers, Requests, Middleware
        app_path('Data'),       // DTOs annotated with #[TSType]
        app_path('Enums'),      // enums annotated with #[TSEnum]
    ],

    // Output .ts files. The array key is referenced in the `output` param.
    'outputs' => [
        'default' => [
            'path'    => resource_path('js/types/generated.ts'),
            'imports' => [
                // "import type { PageProps } from '@inertiajs/core'",
            ],
        ],
        // 'admin' => [
        //     'path'    => resource_path('js/types/admin.ts'),
        //     'imports' => [],
        // ],
    ],

    // Comment markers that delimit the generated section.
    // Everything outside the markers is preserved on re-generation.
    'markers' => [
        'start' => '// [ts-annotations:start]',
        'end'   => '// [ts-annotations:end]',
    ],

];
```

---

## Usage

### `#[TS]` — raw TypeScript

Write any TypeScript verbatim. Use this when you need union types, template literals, generics, or any construct that can't be inferred from PHP types.

Usable on classes and on individual methods. `#[TS]` is repeatable — stack it as many times as needed.

```php
use Brunoscode\LaravelTsAnnotations\Attributes\TS;

#[TS(<<<'TS'
    export type UserResponse = {
        id: number;
        name: string;
        role: 'admin' | 'editor' | 'viewer';
    }
    TS)]
class UserResource extends JsonResource {}
```

On controller methods — keeps each type next to the action it describes:

```php
class UserController extends Controller
{
    #[TS(<<<'TS'
        export type UserListResponse = {
            data: UserResponse[];
            total: number;
            per_page: number;
        }
        TS)]
    public function index(): JsonResponse { ... }

    #[TS(<<<'TS'
        export type UserStoreResponse = {
            data: UserResponse;
            message: string;
        }
        TS)]
    public function store(StoreUserRequest $request): JsonResponse { ... }
}
```

> **Heredoc indentation:** Place the closing `TS` marker at the same indentation level as the type body. PHP strips that many leading spaces from every line, giving zero-based indentation in the output.

---

### `#[TSType]` — auto-infer from class properties

Inspects all public non-static properties (including promoted constructor params) via Reflection and maps PHP types to TypeScript. The `readonly` modifier is preserved.

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
| `Carbon\Carbon` | `string` |
| `Collection` | `unknown[]` |
| Other class | short class name |

Use the optional `name` parameter to override the TypeScript identifier:

```php
#[TSType(name: 'IOrder')]
class OrderData { ... }
// → export type IOrder = { ... }
```

---

### `#[TSEnum]` — auto-generate from PHP enums

Reads enum cases and their backing values automatically. No body to write.

```php
use Brunoscode\LaravelTsAnnotations\Attributes\TSEnum;

// String-backed
#[TSEnum]
enum Status: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Pending  = 'pending';
}
// → export enum Status { Active = 'active', Inactive = 'inactive', Pending = 'pending', }

// Int-backed
#[TSEnum]
enum Priority: int
{
    case Low    = 1;
    case Medium = 2;
    case High   = 3;
}
// → export enum Priority { Low = 1, Medium = 2, High = 3, }

// Unit enum (no backing type) — case name used as string value
#[TSEnum]
enum Direction
{
    case North;
    case South;
    case East;
    case West;
}
// → export enum Direction { North = 'North', South = 'South', East = 'East', West = 'West', }
```

---

### Targeting a specific output file

All three annotations accept an `output` parameter:

```php
#[TS(<<<'TS'
    export type AdminDashboard = { users_count: number; revenue: number; }
    TS, output: 'admin')]

#[TSType(output: 'admin')]
class AdminUserData { ... }

#[TSEnum(output: 'admin')]
enum AdminRole: string { ... }
```

The key must match one defined in `config/ts-annotations.php`.

---

### Run the generator

```bash
# Generate all output files
php artisan ts:generate

# Generate only one specific file
php artisan ts:generate --output=admin

# Preview what would be written without touching any file
php artisan ts:generate --dry-run
```

---

## Ordering in the output file

Within each output file, entries are written in this order:

1. Class-level `#[TS]` attributes, in file-scan order
2. `#[TSEnum]` entries, in file-scan order
3. `#[TSType]` entries, in file-scan order
4. Method-level `#[TS]` attributes, sorted by line number within each class

Each entry is preceded by a source comment:

```typescript
// --- App\Http\Resources\UserResource ---
export type UserResponse = { ... }

// --- App\Enums\Status ---
export enum Status { ... }

// --- App\Data\UserData ---
export type UserData = { ... }
```

---

## File preservation

The generator only touches the section between the two marker comments. Everything outside the markers — manual imports, custom types, hand-written utilities — is left untouched on every run.

```typescript
// My manual import — never overwritten
import type { CustomHelper } from './helpers'

// [ts-annotations:start]
// ⚠️  Auto-generated — do not edit between these comments.
// Generated at: 2026-05-10 12:00:00

// --- App\Http\Resources\UserResource ---
export type UserResponse = { ... }
// [ts-annotations:end]

// My local type — never overwritten
export type LocalState = 'idle' | 'loading' | 'error'
```

If a file doesn't exist yet, it is created from scratch. If it exists but has no markers, the generated block is appended at the end.

---

## Roadmap

- [ ] `--watch` flag for automatic regeneration on file change

---

## Testing

```bash
composer install
vendor/bin/phpunit
```

---

## License

MIT — see [LICENSE](LICENSE).
