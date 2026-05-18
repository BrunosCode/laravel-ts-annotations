<?php

namespace Brunoscode\LaravelTsAnnotations\Parser;

use Brunoscode\LaravelTsAnnotations\Attributes\TS;
use Brunoscode\LaravelTsAnnotations\Attributes\TSEnum;
use Brunoscode\LaravelTsAnnotations\Attributes\TSType;
use ReflectionClass;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

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

            // 1. Class-level #[TS] attributes
            $this->collectAttributes(
                $reflection->getAttributes(TS::class),
                label: $fqcn,
                result: $result,
            );

            // 1b. #[TSEnum] on PHP enums — body auto-generated from cases
            if (function_exists('enum_exists') && enum_exists($fqcn)) {
                $this->collectEnumAttributes(
                    $reflection->getAttributes(TSEnum::class),
                    fqcn: $fqcn,
                    result: $result,
                );
            }

            // 1c. #[TSType] on PHP classes — body inferred from public properties
            $this->collectTypeAttributes(
                $reflection->getAttributes(TSType::class),
                fqcn: $fqcn,
                reflection: $reflection,
                result: $result,
            );

            // 2. Method-level attributes, ordered by line of declaration
            $methods = $reflection->getMethods(
                ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE,
            );

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

    /**
     * @param  \ReflectionAttribute[]                                          $attributes
     * @param  string                                                           $fqcn
     * @param  array<string, list<array{class: string, body: string}>>  $result
     */
    private function collectEnumAttributes(array $attributes, string $fqcn, array &$result): void
    {
        foreach ($attributes as $attribute) {
            try {
                /** @var TSEnum $tsEnum */
                $tsEnum = $attribute->newInstance();
            } catch (\Throwable) {
                continue;
            }

            $result[$tsEnum->output][] = [
                'class' => $fqcn,
                'body'  => $this->generateEnumBody($fqcn),
            ];
        }
    }

    /**
     * @param  \ReflectionAttribute[]                                         $attributes
     * @param  string                                                          $fqcn
     * @param  ReflectionClass                                                 $reflection
     * @param  array<string, list<array{class: string, body: string}>> $result
     */
    private function collectTypeAttributes(
        array $attributes,
        string $fqcn,
        ReflectionClass $reflection,
        array &$result,
    ): void {
        foreach ($attributes as $attribute) {
            try {
                /** @var TSType $tsType */
                $tsType = $attribute->newInstance();
            } catch (\Throwable) {
                continue;
            }

            $result[$tsType->output][] = [
                'class' => $fqcn,
                'body'  => $this->generateTypeBody($fqcn, $reflection, $tsType->name),
            ];
        }
    }

    private function generateTypeBody(string $fqcn, ReflectionClass $reflection, ?string $tsName): string
    {
        $name   = $tsName ?? $reflection->getShortName();
        $mapper = new PhpToTsTypeMapper();
        $lines  = ["export type {$name} = {"];

        $properties = array_filter(
            $reflection->getProperties(ReflectionProperty::IS_PUBLIC),
            fn (ReflectionProperty $p) => ! $p->isStatic()
                && $p->getDeclaringClass()->getName() === $fqcn,
        );

        foreach ($properties as $property) {
            $readonly = $property->isReadOnly() ? 'readonly ' : '';
            $type     = $property->getType();
            $tsType   = $type !== null ? $mapper->map($type) : 'unknown';

            $lines[] = "    {$readonly}{$property->getName()}: {$tsType};";
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    private function generateEnumBody(string $fqcn): string
    {
        $enumReflection = new ReflectionEnum($fqcn);
        $shortName      = $enumReflection->getShortName();
        $lines          = ["export enum {$shortName} {"];

        foreach ($enumReflection->getCases() as $case) {
            if ($case instanceof ReflectionEnumBackedCase) {
                $value     = $case->getBackingValue();
                $formatted = is_string($value) ? "'{$value}'" : $value;
            } else {
                // Unit enum: no backing type — use case name as string value
                $formatted = "'{$case->getName()}'";
            }

            $lines[] = "    {$case->getName()} = {$formatted},";
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    private function classExists(string $fqcn): bool
    {
        return class_exists($fqcn)
            || interface_exists($fqcn)
            || (function_exists('enum_exists') && enum_exists($fqcn));
    }
}
