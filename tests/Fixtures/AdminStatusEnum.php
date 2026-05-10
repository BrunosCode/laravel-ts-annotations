<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Fixtures;

use Brunoscode\LaravelTsAnnotations\Attributes\TSEnum;

#[TSEnum(output: 'admin')]
enum AdminStatusEnum: string
{
    case Active    = 'active';
    case Suspended = 'suspended';
}
