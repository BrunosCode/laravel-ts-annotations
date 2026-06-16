<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Fixtures;

use BrunosCode\LaravelTsAnnotations\Attributes\TSEnum;

#[TSEnum]
enum PriorityEnum: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
}
