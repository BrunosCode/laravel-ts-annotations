<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths to scan
    |--------------------------------------------------------------------------
    |
    | Directories that will be scanned recursively for PHP classes that carry
    | the #[TS] attribute. By default the whole app/Http folder is scanned,
    | which covers Resources, Controllers, Requests and Middleware.
    |
    */
    'scan' => [
        app_path('Http'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Output files
    |--------------------------------------------------------------------------
    |
    | Define one or more TypeScript output files. The array key is the name
    | you reference inside the #[TS] attribute:
    |
    |     #[TS(output: 'default')]
    |     #[TS(output: 'admin')]
    |
    | Each entry requires:
    |   - path    : absolute path to the .ts file to write/update
    |   - imports : lines added at the top of the generated section on every
    |               run (useful for types you always need, e.g. Inertia types)
    |
    */
    'outputs' => [
        'default' => [
            'path'    => resource_path('js/types/generated.ts'),
            'imports' => [
                // "import type { PageProps } from '@inertiajs/core'",
                // "import type { AxiosInstance } from 'axios'",
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Section markers
    |--------------------------------------------------------------------------
    |
    | The generator writes TypeScript between these two comment lines.
    | Everything outside the markers is left untouched, so you can keep
    | manual imports and custom types in the same file.
    |
    */
    'markers' => [
        'start' => '// [ts-annotations:start]',
        'end'   => '// [ts-annotations:end]',
    ],

];
