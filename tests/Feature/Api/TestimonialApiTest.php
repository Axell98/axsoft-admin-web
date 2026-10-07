<?php

use App\Models\MediaFile;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function apiTestimonial(string $name, array $attributes = []): Testimonial
{
    return Testimonial::create(array_merge(['name' => $name, 'role' => 'Cliente', 'content' => "Testimonio de {$name}."], $attributes));
}

test('es publico y devuelve una lista vacia sin testimonios', function () {
    $this->getJson('/api/v1/testimonials')->assertOk()->assertExactJson(['data' => []]);
});

test('devuelve solo los visibles con lo necesario para mostrarlos', function () {
    $file = MediaFile::create(['name' => 'maria.jpg', 'path' => 'files/maria.jpg', 'extension' => 'jpg', 'size' => 1]);

    apiTestimonial('María', ['role' => 'Gerente', 'image_type' => 'file', 'media_file_id' => $file->id]);
    apiTestimonial('Oculto', ['is_published' => false]);

    $this->getJson('/api/v1/testimonials')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertJsonCount(1, 'data')
        ->assertExactJson(['data' => [[
            'name' => 'María',
            'role' => 'Gerente',
            'content' => 'Testimonio de María.',
            'image_url' => Storage::disk('public')->url('files/maria.jpg'),
        ]]]);
});

test('la imagen puede ser un link o no existir', function () {
    apiTestimonial('Con link', ['image_type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg']);
    apiTestimonial('Sin imagen', ['role' => null]);

    $this->getJson('/api/v1/testimonials')
        ->assertJsonPath('data.1.image_url', 'https://sitio.com/a.jpg')
        ->assertJsonPath('data.0.image_url', null)
        ->assertJsonPath('data.0.role', null);
});

test('va del mas reciente al mas antiguo', function () {
    $old = apiTestimonial('Antiguo');
    $old->forceFill(['created_at' => now()->subDays(5)])->save();
    apiTestimonial('Nuevo');

    $this->getJson('/api/v1/testimonials')->assertJsonPath('data.0.name', 'Nuevo')->assertJsonPath('data.1.name', 'Antiguo');
});

test('no expone campos internos y solo permite lectura', function () {
    apiTestimonial('María');

    $this->getJson('/api/v1/testimonials')
        ->assertJsonMissingPath('data.0.id')
        ->assertJsonMissingPath('data.0.is_published')
        ->assertJsonMissingPath('data.0.media_file_id');

    $this->postJson('/api/v1/testimonials', [])->assertStatus(405);
});
