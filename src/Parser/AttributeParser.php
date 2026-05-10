<?php

namespace Brunoscode\LaravelTsAnnotations\Parser;

use Brunoscode\LaravelTsAnnotations\Attributes\TS;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Reads #[TS] attributes from a list of fully-qualified class names and
 * groups the results by their target output key.
 *
 * Attributes are collected in this order for each class:
 *   1. Class-level #[TS] attributes
 *   2. Method-level #[TS] attributes, sorted by line number (declaration order)
 */
class AttributeParser
{
    /**
     * @param  string[]  $fqcns  Fully-qualified class names to inspect.
     *
     * @return array<string, list<array{class: string, body: string}>>
     *         Keys are output names ('default', 'admin', …).
     *         Values are ordered lists of entries to write, each containing
     *         the source class/method label and the raw TypeScript body.
     */
    public function parse(array $fqcns): array
    {
        $result = [];

        foreach ($fqcns as $fqcn) {
            if (! $this->classExists($fqcn)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($fqcn);
            } catch (ReflectionException) {
                continue;
            }

            // 1. Class-level attributes
            $this->collectAttributes(
                $reflection->getAttributes(TS::class),
                label: $fqcn,
                result: $result,
            );

            // 2. Method-level attributes, ordered by line of declaration
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            usort($methods, fn (ReflectionMethod $a, ReflectionMethod $b)
                => ($a->getStartLine() ?? 0) <=> ($b->getStartLine() ?? 0));

            foreach ($methods as $method) {
                // Skip inherited methods — only process methods defined in this class
                if ($method->getDeclaringClass()->getName() !== $fqcn) {
                    continue;
                }

                $this->collectAttributes(
                    $method->getAttributes(TS::class),
                    label: $fqcn . '::' . $method->getName() . '()',
                    result: $result,
                );
            }
        }

        return $result;
    }

    /**
     * @param  \ReflectionAttribute[]                                          $attributes
     * @param  string                                                           $label
     * @param  array<string, list<array{class: string, body: string}>>  $result
     */
    private function collectAttributes(array $attributes, string $label, array &$result): void
    {
        foreach ($attributes as $attribute) {
            try {
                /** @var TS $ts */
                $ts = $attribute->newInstance();
            } catch (\Throwable) {
                continue;
            }

            $result[$ts->output][] = [
                'class' => $label,
                'body'  => $ts->body,
            ];
        }
    }

    private function classExists(string $fqcn): bool
    {
        return class_exists($fqcn)
            || interface_exists($fqcn)
            || (function_exists('enum_exists') && enum_exists($fqcn));
    }
}
