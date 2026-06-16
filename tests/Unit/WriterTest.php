<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Unit;

use BrunosCode\LaravelTsAnnotations\Tests\TestCase;
use BrunosCode\LaravelTsAnnotations\Writer\TypeScriptFileWriter;

class WriterTest extends TestCase
{
    private const START = '// [ts-annotations:start]';

    private const END = '// [ts-annotations:end]';

    private string $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFile = sys_get_temp_dir().'/ts-annotations-'.uniqid().'.ts';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeWriter(): TypeScriptFileWriter
    {
        return new TypeScriptFileWriter(self::START, self::END);
    }

    private function entries(string ...$bodies): array
    {
        return array_map(
            fn (string $body, int $i) => ['class' => 'App\\Resource'.$i, 'body' => $body],
            $bodies,
            array_keys($bodies),
        );
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_creates_file_when_it_does_not_exist(): void
    {
        $this->assertFileDoesNotExist($this->tmpFile);

        $this->makeWriter()->write(
            $this->tmpFile,
            $this->entries('export type Foo = { id: number }'),
            [],
        );

        $this->assertFileExists($this->tmpFile);
    }

    public function test_generated_body_is_present_in_new_file(): void
    {
        $this->makeWriter()->write(
            $this->tmpFile,
            $this->entries('export type Foo = { id: number }'),
            [],
        );

        $content = file_get_contents($this->tmpFile);

        $this->assertStringContainsString('export type Foo = { id: number }', $content);
        $this->assertStringContainsString(self::START, $content);
        $this->assertStringContainsString(self::END, $content);
    }

    public function test_imports_appear_inside_generated_block(): void
    {
        $this->makeWriter()->write(
            $this->tmpFile,
            $this->entries('export type Foo = {}'),
            ["import type { PageProps } from '@inertiajs/core'"],
        );

        $content = file_get_contents($this->tmpFile);

        $this->assertStringContainsString("import type { PageProps } from '@inertiajs/core'", $content);

        // Imports must be between the markers
        $start = strpos($content, self::START);
        $end = strpos($content, self::END);
        $pos = strpos($content, 'import type { PageProps }');

        $this->assertGreaterThan($start, $pos);
        $this->assertLessThan($end, $pos);
    }

    public function test_replaces_generated_section_and_preserves_manual_content(): void
    {
        $existing = <<<'TS'
        // Manual import above markers
        import type { Foo } from './foo'

        // [ts-annotations:start]
        // ⚠️  old generated content
        export type OldType = { obsolete: true }
        // [ts-annotations:end]

        // Manual type below markers
        export type Manual = string | number
        TS;

        file_put_contents($this->tmpFile, $existing);

        $this->makeWriter()->write(
            $this->tmpFile,
            $this->entries('export type NewType = { fresh: true }'),
            [],
        );

        $content = file_get_contents($this->tmpFile);

        // Manual content preserved
        $this->assertStringContainsString("import type { Foo } from './foo'", $content);
        $this->assertStringContainsString('export type Manual = string | number', $content);

        // New type present
        $this->assertStringContainsString('export type NewType = { fresh: true }', $content);

        // Old generated content gone
        $this->assertStringNotContainsString('export type OldType', $content);
    }

    public function test_appends_markers_when_file_has_no_markers(): void
    {
        $existing = "// Existing manual file\nexport type Manual = string\n";
        file_put_contents($this->tmpFile, $existing);

        $this->makeWriter()->write(
            $this->tmpFile,
            $this->entries('export type Appended = number'),
            [],
        );

        $content = file_get_contents($this->tmpFile);

        $this->assertStringContainsString('export type Manual = string', $content);
        $this->assertStringContainsString('export type Appended = number', $content);
        $this->assertStringContainsString(self::START, $content);
        $this->assertStringContainsString(self::END, $content);
    }

    public function test_source_class_comment_is_present(): void
    {
        $entries = [['class' => 'App\\Http\\Resources\\OrderResource', 'body' => 'export type Order = {}']];

        $this->makeWriter()->write($this->tmpFile, $entries, []);

        $content = file_get_contents($this->tmpFile);

        $this->assertStringContainsString('// --- App\\Http\\Resources\\OrderResource ---', $content);
    }

    public function test_multiple_entries_are_all_written(): void
    {
        $this->makeWriter()->write(
            $this->tmpFile,
            $this->entries(
                'export type TypeA = { a: string }',
                'export type TypeB = { b: number }',
                'export type TypeC = { c: boolean }',
            ),
            [],
        );

        $content = file_get_contents($this->tmpFile);

        $this->assertStringContainsString('export type TypeA', $content);
        $this->assertStringContainsString('export type TypeB', $content);
        $this->assertStringContainsString('export type TypeC', $content);
    }

    // ── Marker edge cases ─────────────────────────────────────────────────────

    public function test_appends_when_only_start_marker_present(): void
    {
        file_put_contents($this->tmpFile, "manual\n".self::START."\nold content\n");

        $this->makeWriter()->write($this->tmpFile, $this->entries('export type New = {}'), []);

        $content = file_get_contents($this->tmpFile);

        $this->assertStringContainsString('manual', $content);
        $this->assertStringContainsString('export type New', $content);
        // Falls to append — original start marker plus the new generated block
        $this->assertSame(2, substr_count($content, self::START));
    }

    public function test_appends_when_only_end_marker_present(): void
    {
        file_put_contents($this->tmpFile, "manual\nold content\n".self::END."\n");

        $this->makeWriter()->write($this->tmpFile, $this->entries('export type New = {}'), []);

        $content = file_get_contents($this->tmpFile);

        $this->assertStringContainsString('manual', $content);
        $this->assertStringContainsString('export type New', $content);
        $this->assertStringContainsString(self::START, $content);
    }

    public function test_appends_when_end_marker_precedes_start_marker(): void
    {
        // Reversed markers — condition $endPos > $startPos is false → fall to append
        file_put_contents($this->tmpFile, self::END."\nmanual\n".self::START."\n");

        $this->makeWriter()->write($this->tmpFile, $this->entries('export type New = {}'), []);

        $content = file_get_contents($this->tmpFile);

        $this->assertStringContainsString('manual', $content);
        $this->assertStringContainsString('export type New', $content);
    }

    public function test_empty_entries_produces_valid_block_with_only_markers(): void
    {
        $this->makeWriter()->write($this->tmpFile, [], []);

        $content = file_get_contents($this->tmpFile);

        $this->assertStringContainsString(self::START, $content);
        $this->assertStringContainsString(self::END, $content);
        $this->assertStringNotContainsString('// ---', $content);
    }
}
