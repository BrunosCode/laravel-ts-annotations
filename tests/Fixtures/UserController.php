<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Fixtures;

use Brunoscode\LaravelTsAnnotations\Attributes\TS;

/**
 * Fixture that mimics a Laravel controller with #[TS] on individual methods.
 */
class UserController
{
    #[TS(<<<'TS'
        export type UserListResponse = {
            data: UserResponse[];
            total: number;
            per_page: number;
        }
        TS)]
    public function index(): void {}

    #[TS(<<<'TS'
        export type UserShowResponse = {
            data: UserResponse;
        }
        TS)]
    public function show(): void {}

    #[TS(<<<'TS'
        export type UserStoreResponse = {
            data: UserResponse;
            message: string;
        }
        TS)]
    public function store(): void {}
}
