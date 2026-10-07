<?php

use App\Models\MediaFile;
use App\Models\Popup;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function apiPopup(array $attributes = [], array $items = []): Popup
{
    $popup = Popup::create(array_merge(['title' => 'Promo', 'display_type' => 'image', 'is_visible' => true], $attributes));

    foreach ($items as $position => $item) {
        $popup->items()->create(array_merge($item, ['position' => $position]));
    }

    return $popup;
}

test('es publico y devuelve null cuando no hay pop-up', function () {
    $this->getJson('/api/v1/popup')->assertOk()->assertExactJson(['data' => null]);

    expect(Popup::count())->toBe(0);
});

test('devuelve null si esta oculto', function () {
    apiPopup(['is_visible' => false], [['type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg']]);

    $this->getJson('/api/v1/popup')->assertOk()->assertJsonPath('data', null);
});

test('devuelve null si no tiene contenido', function () {
    apiPopup();

    $this->getJson('/api/v1/popup')->assertOk()->assertJsonPath('data', null);
});

test('devuelve las opciones y la imagen', function () {
    $file = MediaFile::create(['name' => 'promo.jpg', 'path' => 'files/promo.jpg', 'extension' => 'jpg', 'size' => 1]);

    apiPopup(['title' => 'Gran promo', 'show_header' => true, 'show_border' => true, 'link_url' => '/contacto'], [['type' => 'file', 'media_file_id' => $file->id]]);

    $this->getJson('/api/v1/popup')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertJsonPath('data.type', 'image')
        ->assertJsonPath('data.title', 'Gran promo')
        ->assertJsonPath('data.show_header', true)
        ->assertJsonPath('data.show_border', true)
        ->assertJsonPath('data.link_url', '/contacto')
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.kind', 'image')
        ->assertJsonPath('data.items.0.url', Storage::disk('public')->url('files/promo.jpg'))
        ->assertJsonPath('data.items.0.embed_url', null)
        ->assertJsonPath('data.updated_at', Popup::first()->updated_at->toIso8601String());
});

test('el titulo solo se entrega con el encabezado activo', function () {
    apiPopup(['title' => 'Interno', 'show_header' => false], [['type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg']]);

    $this->getJson('/api/v1/popup')->assertJsonPath('data.title', null)->assertJsonPath('data.show_header', false);
});

test('el slider devuelve las imagenes en orden', function () {
    apiPopup(['display_type' => 'slider'], [
        ['type' => 'image_url', 'external_url' => 'https://sitio.com/1.jpg'],
        ['type' => 'image_url', 'external_url' => 'https://sitio.com/2.jpg'],
        ['type' => 'image_url', 'external_url' => 'https://sitio.com/3.jpg'],
    ]);

    $this->getJson('/api/v1/popup')
        ->assertJsonPath('data.type', 'slider')
        ->assertJsonPath('data.items.0.url', 'https://sitio.com/1.jpg')
        ->assertJsonPath('data.items.2.url', 'https://sitio.com/3.jpg');
});

test('devuelve un video de archivo y uno de youtube', function () {
    $file = MediaFile::create(['name' => 'promo.mp4', 'path' => 'files/promo.mp4', 'extension' => 'mp4', 'size' => 1]);

    apiPopup(['display_type' => 'video'], [['type' => 'file', 'media_file_id' => $file->id]]);

    $this->getJson('/api/v1/popup')
        ->assertJsonPath('data.type', 'video')
        ->assertJsonPath('data.items.0.kind', 'video')
        ->assertJsonPath('data.items.0.url', Storage::disk('public')->url('files/promo.mp4'));

    Popup::first()->items()->delete();
    Popup::first()->items()->create(['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ', 'position' => 0]);

    $this->getJson('/api/v1/popup')
        ->assertJsonPath('data.items.0.kind', 'youtube')
        ->assertJsonPath('data.items.0.url', null)
        ->assertJsonPath('data.items.0.embed_url', 'https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->assertJsonPath('data.items.0.thumbnail_url', 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg');
});

test('no expone campos internos y solo permite lectura', function () {
    apiPopup([], [['type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg']]);

    $this->getJson('/api/v1/popup')
        ->assertJsonMissingPath('data.id')
        ->assertJsonMissingPath('data.is_visible')
        ->assertJsonMissingPath('data.items.0.id')
        ->assertJsonMissingPath('data.items.0.media_file_id')
        ->assertJsonMissingPath('data.items.0.position');

    $this->postJson('/api/v1/popup', [])->assertStatus(405);
});
