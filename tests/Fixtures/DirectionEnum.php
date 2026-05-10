<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Fixtures;

use Brunoscode\LaravelTsAnnotations\Attributes\TSEnum;

#[TSEnum]
enum DirectionEnum
{
    case North;
    case South;
    case East;
    case West;
}
