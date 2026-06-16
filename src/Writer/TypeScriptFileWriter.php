<?php

namespace BrunosCode\LaravelTsAnnotations\Writer;

/**
 * Writes (or updates) a TypeScript file, inserting generated content between
 * two marker comments and leaving everything outside the markers untouched.
 *
 * File scenarios handled:
 *   1. File does not exist → created from scratch with the generated block.
 *   2. File exists with both markers → content between markers is replaced.
 *   3. File exists but markers are missing → generated block is appended.
 */
class TypeScriptFileWriter
{
    public function __construct(
        private readonly string $startMarker,
        private readonly string $endMarker,
    ) {}

    /**
     * @param  string  $filePath  Absolute path to the .ts file.
     * @param  list<array{class: string, body: string}>  $entries  Ordered list of types to emit.
     * @param  string[]  $imports  Import lines from config.
     * @return string Final file content written to disk.
     */
    public function write(string $filePath, array $entries, array $imports): string
    {
        $generatedBlock = $this->buildGeneratedBlock($entries, $imports);

        if (! file_exists($filePath)) {
            $this->ensureDirectory($filePath);
            file_put_contents($filePath, $generatedBlock."\n");

            return $generatedBlock;
        }

        $existing = file_get_contents($filePath);

        $startPos = strpos($existing, $this->startMarker);
        $endPos = strpos($existing, $this->endMarker);

        // Markers found — replace only the generated section
        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $before = substr($existing, 0, $startPos);
            $after = substr($existing, $endPos + strlen($this->endMarker));
            $content = $before.$generatedBlock.$after;

            file_put_contents($filePath, $content);

            return $content;
        }

        // No markers — append at the end so we never destroy manual content
        $content = rtrim($existing)."\n\n".$generatedBlock."\n";
        file_put_contents($filePath, $content);

        return $content;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function buildGeneratedBlock(array $entries, array $imports): string
    {
        $lines = [];

        $lines[] = $this->startMarker;
        $lines[] = '// ⚠️  Auto-generated — do not edit between these comments.';
        $lines[] = '// Generated at: '.date('Y-m-d H:i:s');

        if (! empty($imports)) {
            $lines[] = '';

            foreach ($imports as $import) {
                $lines[] = $import;
            }
        }

        foreach ($entries as $entry) {
            $lines[] = '';
            $lines[] = '// --- '.$entry['class'].' ---';
            $lines[] = $this->normalizeBody($entry['body']);
        }

        $lines[] = '';
        $lines[] = $this->endMarker;

        return implode("\n", $lines);
    }

    /**
     * Trim leading/trailing blank lines introduced by heredoc indentation,
     * while preserving the internal indentation of the TypeScript body.
     */
    private function normalizeBody(string $body): string
    {
        return trim($body);
    }

    private function ensureDirectory(string $filePath): void
    {
        $dir = dirname($filePath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
