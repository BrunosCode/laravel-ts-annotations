---
name: ts-annotations
description: "Generate TypeScript types from PHP #[TS] attributes using the ts:generate Artisan command. Use when the user wants to annotate PHP classes or methods with TypeScript types, run the generator, add or configure output files, manage the generated .ts file, or understand how the file-preservation markers work."
---

# TS Annotations

## Overview

`brunoscode/laravel-ts-annotations` lets you write raw TypeScript types directly
in PHP attributes and emit them to `.ts` files with a single Artisan command.

## Annotating classes and methods

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

`#[TS]` is repeatable — stack multiple attributes on the same target.

## Heredoc indentation rule

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

## Running the generator

```bash
php artisan ts:generate                    # all configured outputs
php artisan ts:generate --output=admin     # one specific output
php artisan ts:generate --dry-run          # preview without writing
```

## Targeting a specific output file

```php
#[TS(<<<'TS'
    export type AdminDashboard = { ... }
    TS, output: 'admin')]
```

The `output` key must match one defined in `config/ts-annotations.php`.

## Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=ts-annotations-config
```

```php
// config/ts-annotations.php
return [
    'scan' => [
        app_path('Http'),       // directories scanned recursively
    ],
    'outputs' => [
        'default' => [
            'path'    => resource_path('js/types/generated.ts'),
            'imports' => [
                "import type { PageProps } from '@inertiajs/core'",
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

## File preservation

The generator only replaces the section between the two marker comments.
Everything outside — manual imports, hand-written types — is untouched on every
run. If the file has no markers the generated block is appended at the end.

## Output order

Within each output file, types are written:
1. Class-level `#[TS]` attributes, in file-scan order
2. Method-level `#[TS]` attributes, sorted by line number within each class

Each type is preceded by a source comment:

```typescript
// --- App\Http\Resources\UserResource ---
export type UserResponse = { ... }

// --- App\Http\Controllers\UserController::index() ---
export type UserListResponse = { ... }
```

## Rules

- Never edit between `// [ts-annotations:start]` and `// [ts-annotations:end]`.
- Run `ts:generate` after adding or changing any `#[TS]` attribute.
- When adding a new output file, define it in config before referencing it in an attribute.
- Use `--dry-run` to verify output before committing generated files.
