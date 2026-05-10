<?php

namespace Brunoscode\LaravelTsAnnotations\Commands;

use Brunoscode\LaravelTsAnnotations\Parser\AttributeParser;
use Brunoscode\LaravelTsAnnotations\Scanner\PhpFileScanner;
use Brunoscode\LaravelTsAnnotations\Writer\TypeScriptFileWriter;
use Illuminate\Console\Command;

class GenerateTypesCommand extends Command
{
    protected $signature = 'ts:generate
                            {--output= : Generate only one specific output (config key)}
                            {--dry-run : Preview what would be written without touching any file}';

    protected $description = 'Generate TypeScript type files from #[TS] annotations';

    public function handle(
        PhpFileScanner $scanner,
        AttributeParser $parser,
        TypeScriptFileWriter $writer,
    ): int {
        /** @var array $config */
        $config = config('ts-annotations');

        $outputs   = $config['outputs']  ?? [];
        $scanPaths = $config['scan']     ?? [app_path('Http')];
        $isDryRun  = (bool) $this->option('dry-run');

        // ── Optional filter ─────────────────────────────────────────────────
        $filterKey = $this->option('output');

        if ($filterKey !== null) {
            if (! array_key_exists($filterKey, $outputs)) {
                $this->error("Output key \"{$filterKey}\" is not defined in config('ts-annotations.outputs').");

                return self::FAILURE;
            }

            $outputs = [$filterKey => $outputs[$filterKey]];
        }

        // ── Scan ─────────────────────────────────────────────────────────────
        $this->line('');
        $this->info('Scanning PHP files…');

        $fqcns = $scanner->scan($scanPaths);
        $this->line("  <fg=gray>Found " . count($fqcns) . " PHP class(es).</>");

        // ── Parse #[TS] attributes ───────────────────────────────────────────
        $grouped = $parser->parse($fqcns);

        if (empty($grouped)) {
            $this->newLine();
            $this->warn('No #[TS] annotations found in the scanned paths.');

            return self::SUCCESS;
        }

        // ── Write (or preview) each output file ──────────────────────────────
        $this->newLine();

        foreach ($outputs as $key => $outputConfig) {
            if (! array_key_exists($key, $grouped)) {
                $this->line("  <fg=yellow>SKIP</>  \"{$key}\" — no #[TS(output: '{$key}')] annotations found.");
                continue;
            }

            $entries = $grouped[$key];
            $path    = $outputConfig['path']    ?? '';
            $imports = $outputConfig['imports'] ?? [];
            $count   = count($entries);

            if ($isDryRun) {
                $this->line("  <fg=cyan>DRY</>   \"{$key}\" → {$path}  ({$count} type block(s))");

                foreach ($entries as $entry) {
                    $this->line("         <fg=gray>· {$entry['class']}</>");
                }

                continue;
            }

            $writer->write($path, $entries, $imports);

            $this->line("  <fg=green>DONE</>  \"{$key}\" → {$path}  ({$count} type block(s))");
        }

        $this->newLine();

        if ($isDryRun) {
            $this->warn('Dry run — no files were written.');
        } else {
            $this->info('All done!');
        }

        return self::SUCCESS;
    }
}
