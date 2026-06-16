<?php

namespace BrunosCode\LaravelTsAnnotations\Tests;

use BrunosCode\LaravelTsAnnotations\TsAnnotationsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            TsAnnotationsServiceProvider::class,
        ];
    }
}
