<?php

use App\Models\BannerSlider;
use App\Models\MediaFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function apiMedia(string $name): MediaFile
{
    $extension = pathinfo($name, PATHINFO_EXTENSION);

    return MediaFile::create([
        'name' => $name,
        'path' => "files/2026/10/{$name}",
        'extension' => $extension,
        'mime_type' => 'application/octet-stream',
        'size' => 100,
    ]);
}

test('sin configuracion devuelve valores por defecto y ningun banner sin escribir en la base de datos', function () {
    $this->getJson('/api/v1/banners')
        ->assertOk()
        ->assertJsonPath('data.screen_percentage', 100)
        ->assertJsonPath('data.show_arrows', true)
        ->assertJsonPath('data.show_indicators', true)
        ->assertJsonPath('data.slides', []);

    expect(BannerSlider::count())->toBe(0);
});

test('es publico y devuelve opciones y banners en orden', function () {
    $slider = BannerSlider::create(['name' => 'Principal', 'screen_percentage' => 60, 'show_arrows' => false, 'show_indicators' => true]);

    $slider->slides()->create(['type' => 'file', 'media_file_id' => apiMedia('segundo.jpg')->id, 'position' => 1, 'link_url' => '/contacto']);
    $slider->slides()->create(['type' => 'file', 'media_file_id' => apiMedia('primero.mp4')->id, 'position' => 0]);
    $slider->slides()->create(['type' => 'image_url', 'external_url' => 'https://sitio.com/x.jpg', 'position' => 2]);
    $slider->slides()->create(['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ', 'position' => 3]);

    $this->getJson('/api/v1/banners')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertJsonPath('data.screen_percentage', 60)
        ->assertJsonPath('data.show_arrows', false)
        ->assertJsonPath('data.show_indicators', true)
        ->assertJsonCount(4, 'data.slides')
        ->assertJsonPath('data.slides.0.type', 'video')
        ->assertJsonPath('data.slides.0.url', Storage::disk('public')->url('files/2026/10/primero.mp4'))
        ->assertJsonPath('data.slides.1.type', 'image')
        ->assertJsonPath('data.slides.1.link_url', '/contacto')
        ->assertJsonPath('data.slides.2.url', 'https://sitio.com/x.jpg')
        ->assertJsonPath('data.slides.3.type', 'youtube')
        ->assertJsonPath('data.slides.3.url', null)
        ->assertJsonPath('data.slides.3.embed_url', 'https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->assertJsonPath('data.slides.3.thumbnail_url', 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg');
});

test('no expone campos internos', function () {
    $slider = BannerSlider::create(['name' => 'Principal']);
    $slider->slides()->create(['type' => 'image_url', 'external_url' => 'https://sitio.com/x.jpg', 'position' => 0]);

    $this->getJson('/api/v1/banners')
        ->assertJsonMissingPath('data.id')
        ->assertJsonMissingPath('data.name')
        ->assertJsonMissingPath('data.slides.0.id')
        ->assertJsonMissingPath('data.slides.0.media_file_id')
        ->assertJsonMissingPath('data.slides.0.position');
});

test('solo permite lectura', function () {
    $this->postJson('/api/v1/banners', [])->assertStatus(405);
    $this->deleteJson('/api/v1/banners')->assertStatus(405);
});

test('incluye texto y descripcion cuando los indicadores estan activos', function () {
    $slider = BannerSlider::create(['name' => 'Principal', 'screen_percentage' => 100, 'show_arrows' => true, 'show_indicators' => true]);
    $slider->slides()->create(['type' => 'image_url', 'external_url' => 'https://sitio.com/x.jpg', 'title' => 'Titulo', 'description' => 'Descripcion', 'position' => 0]);

    $this->getJson('/api/v1/banners')
        ->assertJsonPath('data.slides.0.title', 'Titulo')
        ->assertJsonPath('data.slides.0.description', 'Descripcion');
});

test('oculta texto y descripcion cuando los indicadores estan desactivados', function () {
    $slider = BannerSlider::create(['name' => 'Principal', 'screen_percentage' => 100, 'show_arrows' => true, 'show_indicators' => false]);
    $slider->slides()->create(['type' => 'image_url', 'external_url' => 'https://sitio.com/x.jpg', 'title' => 'Titulo', 'description' => 'Descripcion', 'position' => 0]);

    $this->getJson('/api/v1/banners')
        ->assertJsonPath('data.slides.0.title', null)
        ->assertJsonPath('data.slides.0.description', null);
});
