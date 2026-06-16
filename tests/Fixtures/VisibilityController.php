<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Fixtures;

use BrunosCode\LaravelTsAnnotations\Attributes\TS;

/**
 * Fixture that verifies #[TS] is collected regardless of method visibility.
 */
class VisibilityController
{
    #[TS('export type PublicShape = { kind: "public" };')]
    public function publicMethod(): void {}

    #[TS('export type ProtectedShape = { kind: "protected" };')]
    protected function protectedMethod(): void {}

    #[TS('export type PrivateShape = { kind: "private" };')]
    private function privateMethod(): void {}
}
