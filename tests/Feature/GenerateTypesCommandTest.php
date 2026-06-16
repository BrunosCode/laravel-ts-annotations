<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Feature;

use BrunosCode\LaravelTsAnnotations\Tests\TestCase;

class GenerateTypesCommandTest extends TestCase
{
    private string $tmpOutput;

    protected function setUp(): void
    {
        $this->tmpOutput = sys_get_temp_dir().'/ts-annotations-cmd-'.uniqid().'.ts';
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
                __DIR__.'/../Fixtures',
            ],
            'outputs' => [
                'default' => [
                    'path' => $this->tmpOutput,
                    'imports' => [
                        "import type { PageProps } from '@inertiajs/core'",
                    ],
                ],
            ],
            'markers' => [
                'start' => '// [ts-annotations:start]',
                'end' => '// [ts-annotations:end]',
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
            '// --- BrunosCode\\LaravelTsAnnotations\\Tests\\Fixtures\\UserResource ---',
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

    public function test_output_file_contains_tsenum_from_fixture(): void
    {
        $this->artisan('ts:generate')->assertSuccessful();

        $content = file_get_contents($this->tmpOutput);

        $this->assertStringContainsString('export enum StatusEnum {', $content);
        $this->assertStringContainsString("Active = 'active',", $content);
        $this->assertStringContainsString('export enum PriorityEnum {', $content);
        $this->assertStringContainsString('Low = 1,', $content);
        $this->assertStringContainsString('export enum DirectionEnum {', $content);
        $this->assertStringContainsString("North = 'North',", $content);
    }

    public function test_output_file_contains_tstype_from_fixture(): void
    {
        $this->artisan('ts:generate')->assertSuccessful();

        $content = file_get_contents($this->tmpOutput);

        $this->assertStringContainsString('export type UserData = {', $content);
        $this->assertStringContainsString('readonly id: number;', $content);
        $this->assertStringContainsString('readonly email: string | null;', $content);
        $this->assertStringContainsString('export type ProductData = {', $content);
        $this->assertStringContainsString('price: number;', $content);
    }

    public function test_skips_output_key_with_no_matching_annotations(): void
    {
        $reportsPath = sys_get_temp_dir().'/ts-reports-'.uniqid().'.ts';

        $this->app['config']->set('ts-annotations.outputs.reports', [
            'path' => $reportsPath,
            'imports' => [],
        ]);

        $this->artisan('ts:generate')->assertSuccessful();

        // No annotation targets 'reports' — file must not be created
        $this->assertFileDoesNotExist($reportsPath);
    }

    public function test_warns_and_succeeds_when_no_annotations_found(): void
    {
        $emptyDir = sys_get_temp_dir().'/ts-empty-'.uniqid();
        mkdir($emptyDir, 0755, true);

        $this->app['config']->set('ts-annotations.scan', [$emptyDir]);

        $this->artisan('ts:generate')->assertSuccessful();

        $this->assertFileDoesNotExist($this->tmpOutput);

        rmdir($emptyDir);
    }

    public function test_admin_output_key_not_written_when_not_in_outputs_config(): void
    {
        // AdminStatusEnum targets 'admin' output, which is not in the config
        $this->artisan('ts:generate')->assertSuccessful();

        // Default output should exist and not contain admin enum
        $this->assertFileExists($this->tmpOutput);
        $content = file_get_contents($this->tmpOutput);
        $this->assertStringNotContainsString('AdminStatusEnum', $content);
    }

    public function test_regenerating_preserves_manual_content_outside_markers(): void
    {
        $manualContent = <<<'TS'
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
