# Laravel TS Annotations

> Generate TypeScript types from PHP attributes and emit them to `.ts` files with a single Artisan command — for Laravel apps with a typed frontend.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/brunoscode/laravel-ts-annotations.svg?style=flat-square)](https://packagist.org/packages/brunoscode/laravel-ts-annotations)
[![Tests](https://img.shields.io/github/actions/workflow/status/BrunosCode/laravel-ts-annotations/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/BrunosCode/laravel-ts-annotations/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/brunoscode/laravel-ts-annotations.svg?style=flat-square)](https://packagist.org/packages/brunoscode/laravel-ts-annotations)
[![License](https://img.shields.io/packagist/l/brunoscode/laravel-ts-annotations.svg?style=flat-square)](LICENSE.md)

Three annotation styles — raw TypeScript, auto-inferred from class properties, and auto-inferred from enums — cover every common case.

- [Why this package?](#why-this-package)
- [Requirements](#requirements)
- [Installation](#installation)
- [Laravel Boost](#laravel-boost)
- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Usage](#usage)
- [Resources, collections, and Inertia](#resources-collections-and-inertia)
- [Ordering in the output file](#ordering-in-the-output-file)
- [File preservation](#file-preservation)
- [Roadmap](#roadmap)
- [Testing](#testing)
- [Changelog](#changelog)
- [Credits](#credits)
- [License](#license)

---

## Why this package?

Most solutions either **infer** TypeScript from PHP types (losing union types, template literals, generics) or go through a Swagger/OpenAPI intermediary (indirect and verbose). This package gives you three levels of control:

- `#[TS]` — write **real TypeScript** verbatim when you need unions, templates, or generics
- `#[TSType]` — **auto-infer** from PHP property types for simple DTOs and data classes
- `#[TSEnum]` — **auto-generate** TypeScript enums from PHP backed or unit enums

---

## Requirements

| Laravel | PHP    |
| ------- | ------ |
| 10.x    | 8.1+   |
| 11.x    | 8.2+   |
| 12.x    | 8.2+   |
| 13.x    | 8.3+   |

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

## Configuration

```php
// config/ts-annotations.php

return [

    // Directories scanned recursively for all annotation types.
    'scan' => [
        app_path('Http'),       // covers Resources, Controllers, Requests, Middleware
        app_path('Enum'),       // enums annotated with #[TSEnum]
        app_path('Data'),       // DTOs annotated with #[TSType]
    ],

    // Output .ts files. The array key is referenced in the `output` param.
    'outputs' => [
        'default' => [
            'path'    => resource_path('js/types/generated.ts'),
            // Lines written verbatim at the top of the generated section on every run.
            // Useful for shared generics like CollectionResource / PaginatedResource.
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

Only properties **declared on the class itself** are emitted — properties inherited from a parent class are skipped. Annotate the parent with `#[TSType]` too if you need its properties in a separate type.

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

Only the exact Carbon/Collection FQCNs above are remapped. A different class named `Collection` (e.g. your own `App\Support\Collection`) falls through to the short-name rule and becomes the literal `Collection`, **not** `unknown[]`.

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

## Resources, collections, and Inertia

Laravel Resources give you explicit control over the shape of data sent to the frontend — they transform Eloquent models rather than leaking raw attributes. Annotating them with `#[TS]` keeps that contract in sync with your TypeScript automatically.

### 1. Define the resource shape

```php
use Brunoscode\LaravelTsAnnotations\Attributes\TS;
use Illuminate\Http\Resources\Json\JsonResource;

#[TS(<<<'TS'
    export type UserResource = {
        id: number;
        name: string;
        email: string;
        role: 'admin' | 'editor' | 'viewer';
    }
    TS)]
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,
        ];
    }
}
```

Keeping the annotation on the Resource rather than the controller means the type and the transformation logic live together. If you tighten `toArray`, you update the `#[TS]` block in the same file.

### 2. Annotate controller methods for Inertia

The default config injects two generic helpers at the top of every generated file:

```typescript
export type CollectionResource<T> = { data: T[] };

export type PaginatedResource<T> = {
    data: T[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    // ...
};
```

Reference them directly in the `#[TS]` attribute on each controller method that renders an Inertia page:

```php
use Brunoscode\LaravelTsAnnotations\Attributes\TS;
use Inertia\Inertia;

class UserController extends Controller
{
    #[TS('export type UserIndexProps = { users: PaginatedResource<UserResource> }')]
    public function index(): \Inertia\Response
    {
        return Inertia::render('Users/Index', [
            'users' => UserResource::collection(User::paginate()),
        ]);
    }

    #[TS('export type UserListProps = { users: CollectionResource<UserResource> }')]
    public function list(): \Inertia\Response
    {
        return Inertia::render('Users/List', [
            'users' => UserResource::collection(User::all()),
        ]);
    }

    #[TS('export type UserShowProps = { user: UserResource }')]
    public function show(User $user): \Inertia\Response
    {
        return Inertia::render('Users/Show', [
            'user' => new UserResource($user),
        ]);
    }
}
```

### 3. Generated TypeScript

```typescript
// resources/js/types/generated.ts

// [ts-annotations:start]
// ⚠️  Auto-generated — do not edit between these comments.

export type CollectionResource<T> = { data: T[] };

export type PaginatedResource<T> = {
    data: T[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    first_page_url: string;
    last_page_url: string;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
};

// --- App\Http\Resources\UserResource ---
export type UserResource = {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'editor' | 'viewer';
}

// --- App\Http\Controllers\UserController ---
export type UserIndexProps = { users: PaginatedResource<UserResource> }
export type UserListProps  = { users: CollectionResource<UserResource> }
export type UserShowProps  = { user: UserResource }
// [ts-annotations:end]
```

### 4. Consume in an Inertia component

```vue
<script setup lang="ts">
import type { UserIndexProps } from '@/types/generated'

const props = defineProps<UserIndexProps>()
// props.users.data        → UserResource[]
// props.users.total       → number
// props.users.current_page → number
</script>
```

```vue
<script setup lang="ts">
import type { UserShowProps } from '@/types/generated'

const props = defineProps<UserShowProps>()
// props.user.id, props.user.name, props.user.role — fully typed
</script>
```

> `CollectionResource<T>` and `PaginatedResource<T>` are injected via the `imports` key in `config/ts-annotations.php`. Customise them there or add any other shared helpers your app needs.

---

## Ordering in the output file

Entries follow **file-scan order across classes**. Within a single class they are emitted in this fixed order:

1. Class-level `#[TS]` attributes
2. `#[TSEnum]` (only on enums)
3. `#[TSType]` (inferred from the class)
4. Method-level `#[TS]` attributes, sorted by line number

There is **no** global grouping by attribute type. A `#[TSEnum]` in a class that is scanned before a `#[TS]` resource appears first in the output. The result looks grouped only when your `scan` paths are themselves ordered by kind (e.g. `Http`, then `Enum`, then `Data`) — that grouping comes from scan order, not from an intrinsic sort.

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

Planned, not yet shipped:

- `--watch` flag for automatic regeneration on file change

---

## Testing

```bash
composer test
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for what has changed recently.

---

## Credits

- [Bruno Magnani](https://github.com/BrunosCode)
- [All Contributors](https://github.com/BrunosCode/laravel-ts-annotations/contributors)

---

## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
