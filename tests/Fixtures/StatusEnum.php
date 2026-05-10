<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Fixtures;

use Brunoscode\LaravelTsAnnotations\Attributes\TSEnum;

#[TSEnum]
enum StatusEnum: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Pending  = 'pending';
}
