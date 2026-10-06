<?php

/*
|--------------------------------------------------------------------------
| Menú del panel de administración
|--------------------------------------------------------------------------
|
| Cada cliente tiene su propio menú. `icon` corresponde a un nombre del
| componente <x-icon>.
|
*/

return [
    [
        'label' => 'Dashboard',
        'icon' => 'dashboard',
        'route' => 'dashboard',
    ],
    [
        'label' => 'Datos del cliente',
        'icon' => 'building',
        'route' => 'admin.client',
    ],
    [
        'label' => 'Banners',
        'icon' => 'image',
        'route' => 'admin.banners',
    ],
    [
        'label' => 'Proyectos',
        'icon' => 'layers',
        'route' => 'admin.projects',
    ],
    [
        'label' => 'Archivos',
        'icon' => 'folder',
        'route' => 'admin.files',
    ],
];
