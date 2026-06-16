<?php

namespace BrunosCode\LaravelTsAnnotations\Scanner;

/**
 * Recursively scans directories for PHP files and extracts the fully
 * qualified class name (FQCN) of the first class/interface/enum found
 * in each file — without requiring or evaluating the file.
 *
 * The caller is responsible for ensuring autoloading is available so that
 * the returned FQCNs can be instantiated via reflection.
 */
class PhpFileScanner
{
    /**
     * @param  string[]  $paths  Absolute directory paths to scan.
     * @return string[] List of FQCNs found across all files.
     */
    public function scan(array $paths): array
    {
        $fqcns = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $fqcn = $this->extractFqcnFromFile($file->getRealPath());

                if ($fqcn !== null) {
                    $fqcns[] = $fqcn;
                }
            }
        }

        return $fqcns;
    }

    /**
     * Parse a PHP file with the tokenizer and return the FQCN of the first
     * class-like structure found (class, interface, trait, enum).
     *
     * Supports both PHP 8.0+ qualified-name tokens and the older string +
     * namespace-separator token style, so it works across PHP 8.1–8.4.
     */
    private function extractFqcnFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        if ($content === false || $content === '') {
            return null;
        }

        $tokens = token_get_all($content);
        $count = count($tokens);

        $namespace = '';
        $class = null;

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (! is_array($token)) {
                continue;
            }

            // ── Capture the namespace ────────────────────────────────────────
            if ($token[0] === T_NAMESPACE) {
                $ns = '';
                $i++;

                while ($i < $count) {
                    $t = $tokens[$i];

                    if (is_array($t)) {
                        if (in_array($t[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                            $ns .= $t[1];
                        } elseif ($t[0] === T_WHITESPACE) {
                            // skip whitespace between T_NAMESPACE and the name
                        } else {
                            break;
                        }
                    } else {
                        break; // hit ';' or '{'
                    }

                    $i++;
                }

                $namespace = $ns;

                continue;
            }

            // ── Capture the first class-like name ────────────────────────────
            if ($class === null && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                // Peek ahead to find the identifier (skip whitespace, handle
                // edge-cases like "class" used as a keyword in ::class).
                for ($j = $i + 1; $j < $count; $j++) {
                    $t = $tokens[$j];

                    if (is_array($t) && $t[0] === T_WHITESPACE) {
                        continue;
                    }

                    if (is_array($t) && $t[0] === T_STRING) {
                        $class = $t[1];
                    }

                    break;
                }
            }

            if ($class !== null) {
                break;
            }
        }

        if ($class === null) {
            return null;
        }

        return $namespace !== '' ? $namespace.'\\'.$class : $class;
    }
}
