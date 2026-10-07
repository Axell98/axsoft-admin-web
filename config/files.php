<?php

/*
|--------------------------------------------------------------------------
| Gestor de archivos
|--------------------------------------------------------------------------
|
| `max_size_kb` es el tamaño máximo por archivo. El límite real también
| depende de upload_max_filesize y post_max_size en php.ini.
|
| `types` agrupa las extensiones permitidas por categoría. Cualquier otra
| extensión (php, html, svg, exe...) se rechaza.
|
*/

return [

    'max_size_kb' => (int) env('FILES_MAX_SIZE_KB', 81920),

    'types' => [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'video' => ['mp4', 'webm', 'mov'],
        'pdf' => ['pdf'],
        'word' => ['doc', 'docx'],
        'excel' => ['xls', 'xlsx', 'csv'],
        'powerpoint' => ['ppt', 'pptx'],
        'text' => ['txt'],
        'archive' => ['zip'],
    ],

];
