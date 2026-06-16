<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Fixtures;

use BrunosCode\LaravelTsAnnotations\Attributes\TSType;

#[TSType]
class UserDataChild extends UserData
{
    public string $childOnly;
}
