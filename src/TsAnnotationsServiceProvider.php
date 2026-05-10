<?php

namespace Brunoscode\LaravelTsAnnotations;

use Brunoscode\LaravelTsAnnotations\Commands\GenerateTypesCommand;
use Brunoscode\LaravelTsAnnotations\Parser\AttributeParser;
use Brunoscode\LaravelTsAnnotations\Scanner\PhpFileScanner;
use Brunoscode\LaravelTsAnnotations\Writer\TypeScriptFileWriter;
use Illuminate\Support\ServiceProvider;

class TsAnnotationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ts-annotations.php',
            'ts-annotations',
        );

        $this->app->singleton(PhpFileScanner::class);
        $this->app->singleton(AttributeParser::class);

        $this->app->singleton(TypeScriptFileWriter::class, function (): TypeScriptFileWriter {
            /** @var array{start: string, end: string} $markers */
            $markers = config('ts-annotations.markers', []);

            return new TypeScriptFileWriter(
                startMarker: $markers['start'] ?? '// [ts-annotations:start]',
                endMarker:   $markers['end']   ?? '// [ts-annotations:end]',
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/ts-annotations.php' => config_path('ts-annotations.php'),
            ], 'ts-annotations-config');

            $this->commands([
                GenerateTypesCommand::class,
            ]);
        }
    }
}
