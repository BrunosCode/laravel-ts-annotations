<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Fixtures;

use Brunoscode\LaravelTsAnnotations\Attributes\TSEnum;

#[TSEnum]
enum PriorityEnum: int
{
    case Low    = 1;
    case Medium = 2;
    case High   = 3;
}
