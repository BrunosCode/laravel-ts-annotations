<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Fixtures;

use BrunosCode\LaravelTsAnnotations\Attributes\TSEnum;

#[TSEnum]
enum DirectionEnum
{
    case North;
    case South;
    case East;
    case West;
}
