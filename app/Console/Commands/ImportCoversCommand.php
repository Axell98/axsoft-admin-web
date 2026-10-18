<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use App\Models\PageCover;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class ImportCoversCommand extends Command
{
    protected  = 'app:import-covers';
    protected  = 'Importa imagenes locales de la web al gestor multimedia y las asigna a las portadas';

    public function handle()
    {
         = realpath(base_path('../axsoft-voladizo-web/public/images'));
        if (!) {
            ->error('No se encontro la carpeta de imagenes de la web');
            return 1;
        }

         = [
            'slider' =>  . '/inicio/slider/slider1.jpg',
            'proyectos' =>  . '/inicio/servicios/servicio1/servicio_disenio.png',
            'contacto' =>  . '/inicio/servicios/servicio2/1.jpg',
        ];

         = [];

        foreach ( as  => ) {
            if (!file_exists()) {
                ->warn(No encontrado: );
                continue;
            }

             = basename();
             = Storage::disk('public')->putFile('files/covers', new File());

             = MediaFile::create([
                'name' => ,
                'path' => ,
                'extension' => strtolower(pathinfo(, PATHINFO_EXTENSION)),
                'mime_type' => mime_content_type(),
                'size' => filesize(),
            ]);

            [] = ;
            ->info(Importado: (ID: {->id}));
        }

         = [
            'quienes-somos' => [
                'key' => 'slider',
                'title' => 'TRANSFORMANDO ESPACIOS Y REALIDADES CON VISIÓN ARQUITECTÓNICA.',
            ],
            'diseno-e-ingenieria' => [
                'key' => 'slider',
                'title' => 'TRANSFORMANDO ESPACIOS Y REALIDADES CON VISIÓN ARQUITECTÓNICA.',
            ],
            'construccion-y-remodelacion' => [
                'key' => 'slider',
                'title' => 'TRANSFORMANDO ESPACIOS Y REALIDADES CON VISIÓN ARQUITECTÓNICA.',
            ],
            'diseno-interior-y-mobiliario' => [
                'key' => 'slider',
                'title' => 'TRANSFORMANDO ESPACIOS Y REALIDADES CON VISIÓN ARQUITECTÓNICA.',
            ],
            'gestion-y-supervision' => [
                'key' => 'slider',
                'title' => 'TRANSFORMANDO ESPACIOS Y REALIDADES CON VISIÓN ARQUITECTÓNICA.',
            ],
            'proyectos' => [
                'key' => 'proyectos',
                'title' => 'DE LA IDEA EN RENDER A LA OBRA TERMINADA.',
            ],
            'contacto' => [
                'key' => 'contacto',
                'title' => 'ESTAMOS LISTOS PARA ESCUCHAR TU IDEA.',
            ],
        ];

        foreach ( as  => ) {
             = [['key']] ?? null;
            if (!) continue;

            PageCover::updateOrCreate(['page' => ], [
                'title' => ['title'],
                'image_type' => PageCover::IMAGE_FILE,
                'media_file_id' => ->id,
                'external_url' => null,
                'is_active' => true,
            ]);

            ->info(Portada vinculada: -> Media ID {->id});
        }

        ->info(Completado exitosamente);
        return 0;
    }
}