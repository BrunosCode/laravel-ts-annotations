<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Fixtures;

use BrunosCode\LaravelTsAnnotations\Attributes\TSEnum;

#[TSEnum(output: 'admin')]
enum AdminStatusEnum: string
{
    case Active    = 'active';
    case Suspended = 'suspended';
}
