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
        app_path('Enum'),
        app_path('Data'),
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
                'export type CollectionResource<T> = { data: T[] };',
                '',
                'export type PaginatedResource<T> = {',
                '    data: T[];',
                '    total: number;',
                '    per_page: number;',
                '    current_page: number;',
                '    last_page: number;',
                '    from: number | null;',
                '    to: number | null;',
                '    first_page_url: string;',
                '    last_page_url: string;',
                '    next_page_url: string | null;',
                '    prev_page_url: string | null;',
                '    path: string;',
                '};',
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
