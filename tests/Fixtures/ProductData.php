<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Fixtures;

use Brunoscode\LaravelTsAnnotations\Attributes\TSType;

#[TSType]
class ProductData
{
    public int $id;
    public string $name;
    public float $price;
    public bool $inStock;
    public ?string $description;
    public array $tags;

    /** @phpstan-ignore-next-line */
    public static string $ignored = 'not-in-ts';
}
