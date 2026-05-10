<?php

namespace Brunoscode\LaravelTsAnnotations\Tests;

use Brunoscode\LaravelTsAnnotations\TsAnnotationsServiceProvider;
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
