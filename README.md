# laravel-ts-annotations

Write raw TypeScript types directly in PHP attributes and generate `.ts` files with a single Artisan command.

Place the attribute on a class or on individual methods — whichever keeps your code cleaner:

```php
// On a class (e.g. an API Resource)
#[TS(<<<'TS'
    export type UserResponse = {
        id: number;
        name: string;
        role: 'admin' | 'editor' | 'viewer';
    }
    TS)]
class UserResource extends JsonResource {}

// On individual controller methods
class UserController extends Controller
{
    #[TS(<<<'TS'
        export type UserListResponse = {
            data: UserResponse[];
            total: number;
        }
        TS)]
    public function index(): JsonResponse { ... }

    #[TS(<<<'TS'
        export type UserShowResponse = {
            data: UserResponse;
        }
        TS)]
    public function show(User $user): JsonResponse { ... }
}
```

```bash
php artisan ts:generate
```

```typescript
// resources/js/types/generated.ts  ← generated automatically

// [ts-annotations:start]
// ⚠️  Auto-generated — do not edit between these comments.

import type { PageProps } from '@inertiajs/core'

// --- App\Http\Resources\UserResource ---
export type UserResponse = {
    id: number;
    name: string;
    role: 'admin' | 'editor' | 'viewer';
}

// --- App\Http\Controllers\UserController::index() ---
export type UserListResponse = {
    data: UserResponse[];
    total: number;
}

// --- App\Http\Controllers\UserController::show() ---
export type UserShowResponse = {
    data: UserResponse;
}
// [ts-annotations:end]
```

---

## Why this package?

Most existing solutions either **infer** TypeScript from PHP types (losing union types, template literals, generics) or go through a Swagger/OpenAPI intermediary (indirect and verbose). This package lets you write **real TypeScript** in PHP attributes — no inference, no intermediate format.

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

    // Directories scanned recursively for #[TS] attributes.
    'scan' => [
        app_path('Http'),       // covers Resources, Controllers, Requests, Middleware
        // app_path('Data'),    // add more paths as needed
    ],

    // Output .ts files. The array key is referenced inside #[TS(output: 'key')].
    'outputs' => [
        'default' => [
            'path'    => resource_path('js/types/generated.ts'),
            'imports' => [
                "import type { PageProps } from '@inertiajs/core'",
                // add any imports that must always appear in this file
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

### Annotate a class

Place `#[TS]` above any class in a scanned directory. Useful for API Resources, Form Requests, DTOs, and any class whose shape maps to a single TypeScript type.

```php
use Brunoscode\LaravelTsAnnotations\Attributes\TS;

#[TS(<<<'TS'
    export type UserResponse = {
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

### Annotate controller methods

Place `#[TS]` above individual methods to keep each type next to the action it describes. Types are written in declaration order.

```php
use Brunoscode\LaravelTsAnnotations\Attributes\TS;

class UserController extends Controller
{
    #[TS(<<<'TS'
        export type UserListResponse = {
            data: UserResponse[];
            total: number;
            per_page: number;
        }
        TS)]
    public function index(): JsonResponse
    {
        return response()->json(UserResource::collection(User::paginate()));
    }

    #[TS(<<<'TS'
        export type UserShowResponse = {
            data: UserResponse;
        }
        TS)]
    public function show(User $user): JsonResponse
    {
        return response()->json(new UserResource($user));
    }

    #[TS(<<<'TS'
        export type UserStoreResponse = {
            data: UserResponse;
            message: string;
        }
        TS)]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        return response()->json(['data' => new UserResource($user), 'message' => 'Created']);
    }
}
```

### Define multiple types on the same class or method

`#[TS]` is repeatable — stack it as many times as needed:

```php
#[TS(<<<'TS'
    export type UserResponse = {
        id: number;
        name: string;
    }
    TS)]
#[TS(<<<'TS'
    export type UserCollection = {
        data: UserResponse[];
        total: number;
        per_page: number;
    }
    TS)]
class UserResource extends JsonResource {}
```

### Target a specific output file

```php
#[TS(<<<'TS'
    export type AdminDashboard = {
        users_count: number;
        revenue: number;
    }
    TS, output: 'admin')]
class DashboardController extends Controller {}
```

### Run the generator

```bash
# Generate all output files
php artisan ts:generate

# Generate only one specific file
php artisan ts:generate --output=admin

# Preview what would be written without touching any file
php artisan ts:generate --dry-run
```

> **Tip — heredoc indentation:** Use PHP 7.3 flexible heredoc by placing the closing `TS` marker at the same indentation level as the type body. PHP strips that many leading spaces from every line, giving you clean zero-based indentation in the output.

---

## Ordering in the output file

Types are written in this order within each output file:

1. Class-level `#[TS]` attributes, in the order the files are found during directory scan
2. Method-level `#[TS]` attributes, sorted by line number within each class

The source is always noted in a comment above each type:

```typescript
// --- App\Http\Resources\UserResource ---
export type UserResponse = { ... }

// --- App\Http\Controllers\UserController::index() ---
export type UserListResponse = { ... }
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

import type { PageProps } from '@inertiajs/core'

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
- [ ] Hybrid mode: infer TypeScript from PHP property types with `#[TSProp]` overrides

---

## Testing

```bash
composer install
vendor/bin/phpunit
```

---

## License

MIT — see [LICENSE](LICENSE).
