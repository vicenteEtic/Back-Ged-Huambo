<?php

return [

    'default_disk' => env('UPLOAD_DISK', 'public'),

    'max_file_size' => env('UPLOAD_MAX_SIZE', 20480),

    'allowed_types' => [
        'pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif',
        'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv',
    ],

    'pdf' => [
        'quality' => env('GS_PDF_QUALITY', 'screen'),
        'dpi' => env('GS_DPI', 72),
        'image_quality' => env('GS_IMAGE_QUALITY', 60),
    ],

    'image' => [
        'jpeg_quality' => env('IMAGICK_JPEG_QUALITY', 80),
        'png_quality' => env('IMAGICK_PNG_QUALITY', 8),
        'webp_quality' => env('IMAGICK_WEBP_QUALITY', 80),
        'max_width' => env('IMAGICK_MAX_WIDTH', 2000),
        'max_height' => env('IMAGICK_MAX_HEIGHT', 2000),
    ],

    'zip' => [
        'level' => env('ZIP_COMPRESSION_LEVEL', 6),
    ],

    'directories' => [
        'employees' => 'employees',
        'documents' => 'employee-documents',
        'processes' => 'process-documents',
        'archive' => 'archive',
        'disciplinary' => 'disciplinary-evidence',
    ],
];
