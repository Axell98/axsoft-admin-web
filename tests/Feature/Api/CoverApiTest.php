<?php

use App\Models\MediaFile;
use App\Models\PageCover;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    // Las páginas internas dependen de cada cliente: los tests usan una lista fija.
    config(['covers.pages' => ['nosotros' => 'Nosotros', 'servicios' => 'Servicios', 'contacto' => 'Contacto']]);
});

function apiCover(string $page, array $attributes = []): PageCover
{
    return PageCover::create(array_merge([
        'page' => $page,
        'title' => "Portada de {$page}",
        'image_type' => 'image_url',
        'external_url' => "https://sitio.com/{$page}.jpg",
    ], $attributes));
}

// --- Listado ---------------------------------------------------------------------------

test('es publico y devuelve una lista vacia sin portadas', function () {
    $this->getJson('/api/v1/covers')->assertOk()->assertExactJson(['data' => []]);
});

test('devuelve solo las portadas activas en el orden de las paginas', function () {
    $file = MediaFile::create(['name' => 'nosotros.jpg', 'path' => 'files/nosotros.jpg', 'extension' => 'jpg', 'size' => 1]);

    apiCover('contacto', ['title' => null]);
    apiCover('servicios', ['is_active' => false]);
    apiCover('nosotros', ['image_type' => 'file', 'media_file_id' => $file->id, 'external_url' => null]);

    $this->getJson('/api/v1/covers')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertExactJson(['data' => [
            [
                'page' => 'nosotros',
                'title' => 'Portada de nosotros',
                'image_url' => Storage::disk('public')->url('files/nosotros.jpg'),
            ],
            [
                'page' => 'contacto',
                'title' => null,
                'image_url' => 'https://sitio.com/contacto.jpg',
            ],
        ]]);
});

test('omite las portadas sin imagen y las de paginas que ya no estan configuradas', function () {
    $file = MediaFile::create(['name' => 'a.jpg', 'path' => 'files/a.jpg', 'extension' => 'jpg', 'size' => 1]);

    apiCover('nosotros', ['image_type' => 'file', 'media_file_id' => $file->id, 'external_url' => null]);
    apiCover('antigua');
    apiCover('contacto');

    $file->delete();

    $this->getJson('/api/v1/covers')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.page', 'contacto');
});

// --- Una página ------------------------------------------------------------------------

test('devuelve la portada de una pagina', function () {
    apiCover('nosotros');
    apiCover('contacto');

    $this->getJson('/api/v1/covers/contacto')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertExactJson(['data' => [
            'page' => 'contacto',
            'title' => 'Portada de contacto',
            'image_url' => 'https://sitio.com/contacto.jpg',
        ]]);
});

test('devuelve null cuando la pagina no tiene portada que mostrar', function (string $page) {
    $file = MediaFile::create(['name' => 'a.jpg', 'path' => 'files/a.jpg', 'extension' => 'jpg', 'size' => 1]);

    apiCover('servicios', ['is_active' => false]);
    apiCover('contacto', ['image_type' => 'file', 'media_file_id' => $file->id, 'external_url' => null]);
    apiCover('antigua');

    $file->delete();

    $this->getJson("/api/v1/covers/{$page}")->assertOk()->assertExactJson(['data' => null]);
})->with([
    'sin portada' => ['nosotros'],
    'inactiva' => ['servicios'],
    'archivo eliminado' => ['contacto'],
    'pagina que ya no esta configurada' => ['antigua'],
    'pagina desconocida' => ['inexistente'],
]);

test('no expone campos internos y solo permite lectura', function () {
    apiCover('nosotros');

    $this->getJson('/api/v1/covers')
        ->assertJsonMissingPath('data.0.id')
        ->assertJsonMissingPath('data.0.is_active')
        ->assertJsonMissingPath('data.0.media_file_id');

    $this->postJson('/api/v1/covers', [])->assertStatus(405);
    $this->postJson('/api/v1/covers/nosotros', [])->assertStatus(405);
});
