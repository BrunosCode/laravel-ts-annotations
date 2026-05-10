<?php

namespace Brunoscode\LaravelTsAnnotations\Tests\Feature;

use Brunoscode\LaravelTsAnnotations\Tests\TestCase;

class GenerateTypesCommandTest extends TestCase
{
    private string $tmpOutput;

    protected function setUp(): void
    {
        $this->tmpOutput = sys_get_temp_dir() . '/ts-annotations-cmd-' . uniqid() . '.ts';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->tmpOutput)) {
            unlink($this->tmpOutput);
        }
    }

    // ── Config ───────────────────────────────────────────────────────────────

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ts-annotations', [
            'scan' => [
                __DIR__ . '/../Fixtures',
            ],
            'outputs' => [
                'default' => [
                    'path'    => $this->tmpOutput,
                    'imports' => [
                        "import type { PageProps } from '@inertiajs/core'",
                    ],
                ],
            ],
            'markers' => [
                'start' => '// [ts-annotations:start]',
                'end'   => '// [ts-annotations:end]',
            ],
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_command_exits_successfully(): void
    {
        $this->artisan('ts:generate')->assertSuccessful();
    }

    public function test_command_creates_output_file(): void
    {
        $this->artisan('ts:generate')->assertSuccessful();

        $this->assertFileExists($this->tmpOutput);
    }

    public function test_output_file_contains_typescript_from_fixture(): void
    {
        $this->artisan('ts:generate')->assertSuccessful();

        $content = file_get_contents($this->tmpOutput);

        $this->assertStringContainsString('export type UserResponse', $content);
        $this->assertStringContainsString('export type UserIndex', $content);
        $this->assertStringContainsString("role: 'admin' | 'editor' | 'viewer'", $content);
    }

    public function test_output_file_contains_configured_imports(): void
    {
        $this->artisan('ts:generate')->assertSuccessful();

        $content = file_get_contents($this->tmpOutput);

        $this->assertStringContainsString("import type { PageProps } from '@inertiajs/core'", $content);
    }

    public function test_output_file_contains_source_class_comment(): void
    {
        $this->artisan('ts:generate')->assertSuccessful();

        $content = file_get_contents($this->tmpOutput);

        $this->assertStringContainsString(
            '// --- Brunoscode\\LaravelTsAnnotations\\Tests\\Fixtures\\UserResource ---',
            $content,
        );
    }

    public function test_dry_run_does_not_create_file(): void
    {
        $this->artisan('ts:generate --dry-run')->assertSuccessful();

        $this->assertFileDoesNotExist($this->tmpOutput);
    }

    public function test_output_option_filters_to_specific_key(): void
    {
        $this->artisan('ts:generate --output=default')->assertSuccessful();

        $this->assertFileExists($this->tmpOutput);
    }

    public function test_output_option_with_invalid_key_fails(): void
    {
        $this->artisan('ts:generate --output=nonexistent')->assertFailed();
    }

    public function test_regenerating_preserves_manual_content_outside_markers(): void
    {
        $manualContent = <<<TS
        // My manual import
        import type { CustomType } from './custom'

        // [ts-annotations:start]
        // old content
        // [ts-annotations:end]

        export type LocalHelper = string
        TS;

        file_put_contents($this->tmpOutput, $manualContent);

        $this->artisan('ts:generate')->assertSuccessful();

        $content = file_get_contents($this->tmpOutput);

        $this->assertStringContainsString("import type { CustomType } from './custom'", $content);
        $this->assertStringContainsString('export type LocalHelper = string', $content);
        $this->assertStringNotContainsString('// old content', $content);
        $this->assertStringContainsString('export type UserResponse', $content);
    }
}
