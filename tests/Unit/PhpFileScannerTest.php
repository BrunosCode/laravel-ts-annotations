<?php

namespace BrunosCode\LaravelTsAnnotations\Tests\Unit;

use BrunosCode\LaravelTsAnnotations\Scanner\PhpFileScanner;
use BrunosCode\LaravelTsAnnotations\Tests\TestCase;

class PhpFileScannerTest extends TestCase
{
    private PhpFileScanner $scanner;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new PhpFileScanner;
        $this->tmpDir = sys_get_temp_dir().'/ts-scanner-'.uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDir($this->tmpDir);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function write(string $filename, string $content): void
    {
        file_put_contents($this->tmpDir.'/'.$filename, $content);
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir.'/'.$entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }

    // ── Basic extraction ──────────────────────────────────────────────────────

    public function test_returns_fqcn_for_namespaced_class(): void
    {
        $this->write('Foo.php', <<<'PHP'
            <?php
            namespace App\Http\Resources;
            class Foo {}
            PHP);

        $fqcns = $this->scanner->scan([$this->tmpDir]);

        $this->assertContains('App\Http\Resources\Foo', $fqcns);
    }

    public function test_returns_fqcn_for_interface(): void
    {
        $this->write('MyInterface.php', <<<'PHP'
            <?php
            namespace App\Contracts;
            interface MyInterface {}
            PHP);

        $fqcns = $this->scanner->scan([$this->tmpDir]);

        $this->assertContains('App\Contracts\MyInterface', $fqcns);
    }

    public function test_returns_fqcn_for_enum(): void
    {
        $this->write('Status.php', <<<'PHP'
            <?php
            namespace App\Enums;
            enum Status: string { case Active = 'active'; }
            PHP);

        $fqcns = $this->scanner->scan([$this->tmpDir]);

        $this->assertContains('App\Enums\Status', $fqcns);
    }

    public function test_returns_class_name_without_namespace(): void
    {
        $this->write('Bare.php', <<<'PHP'
            <?php
            class Bare {}
            PHP);

        $fqcns = $this->scanner->scan([$this->tmpDir]);

        $this->assertContains('Bare', $fqcns);
    }

    // ── Files without classes ─────────────────────────────────────────────────

    public function test_skips_file_with_no_class(): void
    {
        $this->write('script.php', <<<'PHP'
            <?php
            $x = 1 + 1;
            echo $x;
            PHP);

        $fqcns = $this->scanner->scan([$this->tmpDir]);

        $this->assertEmpty($fqcns);
    }

    public function test_skips_empty_file(): void
    {
        $this->write('empty.php', '');

        $fqcns = $this->scanner->scan([$this->tmpDir]);

        $this->assertEmpty($fqcns);
    }

    public function test_skips_anonymous_class(): void
    {
        $this->write('anon.php', <<<'PHP'
            <?php
            $obj = new class {};
            PHP);

        $fqcns = $this->scanner->scan([$this->tmpDir]);

        $this->assertEmpty($fqcns);
    }

    public function test_does_not_confuse_class_keyword_with_class_declaration(): void
    {
        // ::class magic constant must not be mistaken for a class definition
        $this->write('classconst.php', <<<'PHP'
            <?php
            $name = stdClass::class;
            PHP);

        $fqcns = $this->scanner->scan([$this->tmpDir]);

        $this->assertEmpty($fqcns);
    }

    // ── Directory handling ────────────────────────────────────────────────────

    public function test_returns_empty_for_nonexistent_directory(): void
    {
        $fqcns = $this->scanner->scan(['/this/path/does/not/exist']);

        $this->assertEmpty($fqcns);
    }

    public function test_ignores_non_php_files(): void
    {
        $this->write('types.ts', 'export type Foo = string;');
        $this->write('readme.md', '# hello');

        $fqcns = $this->scanner->scan([$this->tmpDir]);

        $this->assertEmpty($fqcns);
    }

    public function test_scans_subdirectories_recursively(): void
    {
        $sub = $this->tmpDir.'/Sub';
        mkdir($sub);
        file_put_contents($sub.'/Deep.php', <<<'PHP'
            <?php
            namespace App\Deep;
            class Deep {}
            PHP);

        $fqcns = $this->scanner->scan([$this->tmpDir]);

        $this->assertContains('App\Deep\Deep', $fqcns);
    }

    public function test_scans_multiple_paths(): void
    {
        $other = $this->tmpDir.'/Other';
        mkdir($other);

        $this->write('First.php', "<?php\nnamespace A;\nclass First {}");
        file_put_contents($other.'/Second.php', "<?php\nnamespace B;\nclass Second {}");

        $fqcns = $this->scanner->scan([$this->tmpDir, $other]);

        $this->assertContains('A\First', $fqcns);
        $this->assertContains('B\Second', $fqcns);
    }

    public function test_skips_path_that_is_a_file_not_a_directory(): void
    {
        $file = $this->tmpDir.'/notadir.php';
        file_put_contents($file, '<?php class Foo {}');

        // Pass the file path itself as a "directory" — should be skipped
        $fqcns = $this->scanner->scan([$file]);

        $this->assertEmpty($fqcns);
    }
}
