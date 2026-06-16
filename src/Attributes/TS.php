<?php

namespace BrunosCode\LaravelTsAnnotations\Attributes;

/**
 * Attach raw TypeScript to a PHP class or method.
 *
 * The first argument is the TypeScript body (best written as a nowdoc string).
 * The `output` argument must match a key defined in config('ts-annotations.outputs').
 *
 * The attribute is repeatable: use it multiple times on the same class or method
 * to emit multiple types, optionally targeting different output files.
 *
 * On a class — the types are collected before the method-level ones:
 *
 *   #[TS(<<<'TS'
 *       export type UserResponse = {
 *           id: number;
 *           name: string;
 *           role: 'admin' | 'user';
 *       }
 *   TS)]
 *   class UserResource extends JsonResource {}
 *
 * On individual controller methods — keeps each type next to the action it describes:
 *
 *   class UserController extends Controller
 *   {
 *       #[TS(<<<'TS'
 *           export type UserListResponse = {
 *               data: UserResponse[];
 *               total: number;
 *           }
 *       TS)]
 *       public function index(): JsonResponse { ... }
 *   }
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class TS
{
    public function __construct(
        public readonly string $body,
        public readonly string $output = 'default',
    ) {}
}
