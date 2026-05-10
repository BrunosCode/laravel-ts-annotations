<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Fixtures;

use Brunoscode\LaravelTsAnnotations\Attributes\TSType;

#[TSType]
class UserDataChild extends UserData
{
    public string $childOnly;
}
