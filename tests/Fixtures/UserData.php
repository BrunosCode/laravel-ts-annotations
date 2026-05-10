<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Fixtures;

use Brunoscode\LaravelTsAnnotations\Attributes\TSType;

#[TSType]
class UserData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly bool $active,
    ) {}
}
