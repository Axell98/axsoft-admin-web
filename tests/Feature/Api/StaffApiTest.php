<?php

use App\Models\MediaFile;
use App\Models\StaffMember;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function apiStaff(string $name, int $order, array $attributes = []): StaffMember
{
    return StaffMember::create(array_merge(['name' => $name, 'role' => 'Arquitecto', 'sort_order' => $order], $attributes));
}

test('es publico y devuelve una lista vacia sin integrantes', function () {
    $this->getJson('/api/v1/staff')->assertOk()->assertExactJson(['data' => []]);
});

test('devuelve el equipo con lo necesario para mostrarlo', function () {
    $file = MediaFile::create(['name' => 'carlos.jpg', 'path' => 'files/carlos.jpg', 'extension' => 'jpg', 'size' => 1]);

    apiStaff('Carlos Ruiz', 1, ['role' => 'Arquitecto principal', 'image_type' => 'file', 'media_file_id' => $file->id]);

    $this->getJson('/api/v1/staff')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertExactJson(['data' => [[
            'name' => 'Carlos Ruiz',
            'role' => 'Arquitecto principal',
            'image_url' => Storage::disk('public')->url('files/carlos.jpg'),
        ]]]);
});

test('respeta el orden del panel', function () {
    apiStaff('Tercero', 3);
    apiStaff('Primero', 1);
    apiStaff('Segundo', 2);

    $this->getJson('/api/v1/staff')
        ->assertJsonPath('data.0.name', 'Primero')
        ->assertJsonPath('data.1.name', 'Segundo')
        ->assertJsonPath('data.2.name', 'Tercero');
});

test('la foto puede ser un link o no existir', function () {
    apiStaff('Con link', 1, ['image_type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg']);
    apiStaff('Sin foto', 2, ['role' => null]);

    $this->getJson('/api/v1/staff')
        ->assertJsonPath('data.0.image_url', 'https://sitio.com/a.jpg')
        ->assertJsonPath('data.1.image_url', null)
        ->assertJsonPath('data.1.role', null);
});

test('no expone campos internos y solo permite lectura', function () {
    apiStaff('Carlos', 1);

    $this->getJson('/api/v1/staff')
        ->assertJsonMissingPath('data.0.id')
        ->assertJsonMissingPath('data.0.sort_order')
        ->assertJsonMissingPath('data.0.media_file_id');

    $this->postJson('/api/v1/staff', [])->assertStatus(405);
});
