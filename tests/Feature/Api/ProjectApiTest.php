<?php

use App\Models\MediaFile;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

function apiProject(string $title, array $attributes = []): Project
{
    return Project::create(array_merge([
        'title' => $title,
        'slug' => Str::slug($title),
        'is_published' => true,
    ], $attributes));
}

beforeEach(function () {
    Storage::fake('public');
});

test('el listado es publico y solo muestra proyectos visibles', function () {
    apiProject('Visible');
    apiProject('Oculto', ['is_published' => false]);

    $this->getJson('/api/v1/projects')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'visible')
        ->assertJsonPath('data.0.title', 'Visible');
});

test('el listado devuelve lo necesario para las tarjetas', function () {
    $file = MediaFile::create(['name' => 'portada.jpg', 'path' => 'files/portada.jpg', 'extension' => 'jpg', 'size' => 1]);

    $project = apiProject('Edificio', [
        'category' => 'Residencial',
        'location' => 'San Borja, Lima',
        'execution_percentage' => 100,
        'year' => 2020,
        'description' => '<div>Desarrollo y modelado <strong>3D</strong> del edificio.</div>',
    ]);
    $project->phases()->create(['type' => 'file', 'media_file_id' => $file->id, 'position' => 0]);

    $this->getJson('/api/v1/projects')
        ->assertOk()
        ->assertJsonPath('data.0.category', 'Residencial')
        ->assertJsonPath('data.0.location', 'San Borja, Lima')
        ->assertJsonPath('data.0.execution_percentage', 100)
        ->assertJsonPath('data.0.year', 2020)
        ->assertJsonPath('data.0.cover_url', Storage::disk('public')->url('files/portada.jpg'))
        ->assertJsonPath('data.0.excerpt', 'Desarrollo y modelado 3D del edificio.')
        ->assertJsonMissingPath('data.0.description')
        ->assertJsonMissingPath('data.0.phases')
        ->assertJsonMissingPath('data.0.id');
});

test('el listado se puede guardar en cache por 60 segundos', function () {
    $this->getJson('/api/v1/projects')->assertOk()->assertHeader('Cache-Control', 'max-age=60, public');
});

test('el listado va del mas reciente al mas antiguo', function () {
    $old = apiProject('Antiguo');
    $old->forceFill(['created_at' => now()->subDays(5)])->save();
    apiProject('Nuevo');

    $this->getJson('/api/v1/projects')->assertJsonPath('data.0.slug', 'nuevo')->assertJsonPath('data.1.slug', 'antiguo');
});

test('el listado se pagina y limita per_page', function () {
    foreach (range(1, 15) as $i) {
        apiProject(sprintf('Proyecto %02d', $i));
    }

    $this->getJson('/api/v1/projects')->assertJsonCount(12, 'data')->assertJsonPath('meta.total', 15)->assertJsonPath('meta.per_page', 12);
    $this->getJson('/api/v1/projects?per_page=5&page=3')->assertJsonCount(5, 'data')->assertJsonPath('meta.current_page', 3);
    $this->getJson('/api/v1/projects?per_page=9999')->assertJsonPath('meta.per_page', 50);
    $this->getJson('/api/v1/projects?per_page=0')->assertJsonPath('meta.per_page', 1);
});

test('el detalle incluye descripcion, fases y video', function () {
    $a = MediaFile::create(['name' => 'a.jpg', 'path' => 'files/a.jpg', 'extension' => 'jpg', 'size' => 1]);

    $project = apiProject('Edificio', [
        'description' => '<div>Descripción <strong>completa</strong></div>',
        'video_type' => 'youtube',
        'video_youtube_id' => 'dQw4w9WgXcQ',
    ]);
    $project->phases()->create(['type' => 'image_url', 'external_url' => 'https://sitio.com/2.jpg', 'title' => 'Fase 2', 'content' => '<div>Segunda</div>', 'position' => 1]);
    $project->phases()->create(['type' => 'file', 'media_file_id' => $a->id, 'title' => 'Fase 1', 'content' => '<div>Primera</div>', 'position' => 0]);

    $this->getJson('/api/v1/projects/edificio')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertJsonPath('data.title', 'Edificio')
        ->assertJsonPath('data.description', '<div>Descripción <strong>completa</strong></div>')
        ->assertJsonCount(2, 'data.phases')
        ->assertJsonPath('data.phases.0.title', 'Fase 1')
        ->assertJsonPath('data.phases.0.image_url', Storage::disk('public')->url('files/a.jpg'))
        ->assertJsonPath('data.phases.0.content', '<div>Primera</div>')
        ->assertJsonPath('data.phases.1.image_url', 'https://sitio.com/2.jpg')
        ->assertJsonPath('data.video.type', 'youtube')
        ->assertJsonPath('data.video.embed_url', 'https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->assertJsonPath('data.video.url', null)
        ->assertJsonPath('data.video.thumbnail_url', 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg');
});

test('el detalle devuelve la url del video cuando viene del gestor de archivos', function () {
    $video = MediaFile::create(['name' => 'v.mp4', 'path' => 'files/v.mp4', 'extension' => 'mp4', 'size' => 1]);
    apiProject('Con video', ['video_type' => 'file', 'video_media_file_id' => $video->id]);

    $this->getJson('/api/v1/projects/con-video')
        ->assertJsonPath('data.video.type', 'video')
        ->assertJsonPath('data.video.url', Storage::disk('public')->url('files/v.mp4'))
        ->assertJsonPath('data.video.embed_url', null);
});

test('el detalle devuelve video nulo si el proyecto no tiene', function () {
    apiProject('Sin video');

    $this->getJson('/api/v1/projects/sin-video')->assertOk()->assertJsonPath('data.video', null);
});

test('el detalle devuelve 404 si no existe o esta oculto', function () {
    apiProject('Oculto', ['is_published' => false]);

    $this->getJson('/api/v1/projects/oculto')->assertNotFound()->assertJsonPath('message', 'Proyecto no encontrado.');
    $this->getJson('/api/v1/projects/no-existe')->assertNotFound();
});

test('no expone campos internos', function () {
    $project = apiProject('Casa');
    $project->phases()->create(['type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg', 'position' => 0]);

    $this->getJson('/api/v1/projects/casa')
        ->assertJsonMissingPath('data.id')
        ->assertJsonMissingPath('data.is_published')
        ->assertJsonMissingPath('data.video_type')
        ->assertJsonMissingPath('data.video_media_file_id')
        ->assertJsonMissingPath('data.phases.0.id')
        ->assertJsonMissingPath('data.phases.0.media_file_id')
        ->assertJsonMissingPath('data.phases.0.position');
});

test('solo permite lectura', function () {
    apiProject('Casa');

    $this->postJson('/api/v1/projects', [])->assertStatus(405);
    $this->deleteJson('/api/v1/projects/casa')->assertStatus(405);
});

test('el detalle entrega el html con colores ya sanitizado', function () {
    apiProject('Colores', ['description' => '<div><span style="color: #dc2626;">rojo</span></div>']);

    $this->getJson('/api/v1/projects/colores')
        ->assertJsonPath('data.description', '<div><span style="color: #dc2626;">rojo</span></div>');
});
