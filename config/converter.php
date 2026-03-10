<?php

return [
    /*
    |--------------------------------------------------------------------------
    | UV Path
    |--------------------------------------------------------------------------
    | Path to the uv executable. Leave null to auto-detect.
    | uv is used to run Python without requiring a system Python install.
    */
    'uv_path' => env('CONVERTER_UV_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Conversion Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('CONVERTER_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Supported formats
    |--------------------------------------------------------------------------
    | Formats that can be converted to PDF.
    */
    'supported_formats' => [
        'docx', 'doc',
        'pptx', 'ppt',
        'xlsx', 'xls',
        'odt', 'ods', 'odp',
        'html', 'htm',
        'md', 'markdown',
        'txt', 'rtf',
        'epub',
        'csv',
        'zip',
        'png', 'jpg', 'jpeg', 'gif', 'bmp', 'tiff', 'tif', 'webp',
    ],
];
