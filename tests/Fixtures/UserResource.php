<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Fixtures;

use Brunoscode\LaravelTsAnnotations\Attributes\TS;

/**
 * A minimal fixture that mimics a Laravel API Resource carrying #[TS] annotations.
 *
 * Note: the nowdoc closing marker is indented at the same level as the body —
 * PHP 7.3 flexible heredoc will strip that many leading spaces from every line,
 * so the resulting TypeScript body has clean, zero-based indentation.
 */
#[TS(<<<'TS'
    export type UserResponse = {
        id: number;
        name: string;
        email: string;
        role: 'admin' | 'editor' | 'viewer';
    }
    TS, output: 'default')]
#[TS(<<<'TS'
    export type UserIndex = {
        data: UserResponse[];
        total: number;
    }
    TS, output: 'default')]
class UserResource
{
}
