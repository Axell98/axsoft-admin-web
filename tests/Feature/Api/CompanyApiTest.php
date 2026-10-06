<?php

use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Storage;

test('devuelve 404 si la empresa aun no esta configurada', function () {
    $this->getJson('/api/v1/company')
        ->assertNotFound()
        ->assertJsonPath('message', 'Los datos de la empresa aún no han sido configurados.');

    expect(CompanyProfile::count())->toBe(0);
});

test('es publico y devuelve los datos de la empresa', function () {
    Storage::fake('public');

    CompanyProfile::create([
        'name' => 'Arq Studio',
        'ruc' => '20123456789',
        'email' => 'contacto@arqstudio.pe',
        'phone' => '01 234 5678',
        'instagram' => 'https://www.instagram.com/arqstudio',
        'meta_title' => 'Arq Studio | Arquitectura',
        'logo_path' => 'logos/logo.png',
    ]);

    $this->getJson('/api/v1/company')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertJsonPath('data.name', 'Arq Studio')
        ->assertJsonPath('data.ruc', '20123456789')
        ->assertJsonPath('data.contact.email', 'contacto@arqstudio.pe')
        ->assertJsonPath('data.contact.phone', '01 234 5678')
        ->assertJsonPath('data.contact.whatsapp', null)
        ->assertJsonPath('data.social.instagram', 'https://www.instagram.com/arqstudio')
        ->assertJsonPath('data.seo.title', 'Arq Studio | Arquitectura')
        ->assertJsonPath('data.logo_url', Storage::disk('public')->url('logos/logo.png'))
        ->assertJsonPath('data.favicon_url', null)
        ->assertJsonStructure(['data' => [
            'name', 'legal_name', 'ruc', 'slogan', 'description', 'logo_url', 'favicon_url',
            'contact' => ['address', 'maps_url', 'business_hours', 'email', 'email_2', 'phone', 'whatsapp', 'whatsapp_2'],
            'social' => ['website', 'facebook', 'instagram', 'youtube', 'tiktok', 'linkedin', 'behance'],
            'seo' => ['title', 'description'],
            'updated_at',
        ]]);
});

test('no expone campos internos', function () {
    CompanyProfile::create(['name' => 'Arq Studio', 'logo_path' => 'logos/logo.png']);

    $this->getJson('/api/v1/company')
        ->assertJsonMissingPath('data.id')
        ->assertJsonMissingPath('data.logo_path')
        ->assertJsonMissingPath('data.favicon_path')
        ->assertJsonMissingPath('data.created_at');
});

test('permite peticiones desde otros dominios (CORS)', function () {
    CompanyProfile::create(['name' => 'Arq Studio']);

    $this->withHeaders(['Origin' => 'https://www.sitio-cliente.com'])
        ->getJson('/api/v1/company')
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', '*');
});

test('solo permite lectura', function () {
    $this->postJson('/api/v1/company', ['name' => 'X'])->assertStatus(405);
    $this->deleteJson('/api/v1/company')->assertStatus(405);
});

test('aplica limite de peticiones por minuto', function () {
    CompanyProfile::create(['name' => 'Arq Studio']);

    foreach (range(1, 60) as $_) {
        $this->getJson('/api/v1/company')->assertOk();
    }

    $this->getJson('/api/v1/company')->assertStatus(429);
});
