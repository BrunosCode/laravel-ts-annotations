<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Fixtures;

use BrunosCode\LaravelTsAnnotations\Attributes\TSEnum;

#[TSEnum]
enum StatusEnum: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Pending  = 'pending';
}
