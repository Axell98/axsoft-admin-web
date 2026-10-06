<?php

use App\Livewire\Admin\CompanyForm;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

test('la pagina requiere autenticacion', function () {
    auth()->logout();

    $this->get(route('admin.client'))->assertRedirect('/login');
});

test('la pagina carga y crea el registro de empresa si no existe', function () {
    expect(CompanyProfile::count())->toBe(0);

    $this->get(route('admin.client'))
        ->assertOk()
        ->assertSeeLivewire(CompanyForm::class);

    expect(CompanyProfile::count())->toBe(1);
});

test('el formulario se llena con los datos guardados', function () {
    CompanyProfile::create(['name' => 'Arq Studio', 'ruc' => '20123456789']);

    Livewire::test(CompanyForm::class)
        ->assertSet('name', 'Arq Studio')
        ->assertSet('ruc', '20123456789');
});

test('se pueden actualizar los datos de la empresa', function () {
    Livewire::test(CompanyForm::class)
        ->set('name', 'Arq Studio SAC')
        ->set('ruc', '20123456789')
        ->set('address', 'Av. Arequipa 123, Lima')
        ->set('email', 'contacto@arqstudio.pe')
        ->set('whatsapp', '+51 999 888 777')
        ->set('facebook', 'https://www.facebook.com/arqstudio')
        ->set('description', 'Estudio de arquitectura.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('saved');

    $profile = CompanyProfile::first();

    expect($profile->name)->toBe('Arq Studio SAC')
        ->and($profile->ruc)->toBe('20123456789')
        ->and($profile->email)->toBe('contacto@arqstudio.pe')
        ->and($profile->instagram)->toBeNull();
});

test('valida los campos del formulario', function () {
    Livewire::test(CompanyForm::class)
        ->set('name', '')
        ->set('ruc', '123')
        ->set('email', 'no-es-correo')
        ->set('whatsapp', 'abc')
        ->set('facebook', 'facebook')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'ruc' => 'digits', 'email' => 'email', 'whatsapp' => 'regex', 'facebook' => 'url']);
});

test('se puede subir un logo', function () {
    Livewire::test(CompanyForm::class)
        ->set('logo', UploadedFile::fake()->image('logo.png', 300, 300))
        ->call('save')
        ->assertHasNoErrors();

    $path = CompanyProfile::first()->logo_path;

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

test('al cambiar el logo se elimina el anterior', function () {
    $component = Livewire::test(CompanyForm::class)
        ->set('logo', UploadedFile::fake()->image('uno.png'))
        ->call('save');

    $old = CompanyProfile::first()->logo_path;

    $component
        ->set('logo', UploadedFile::fake()->image('dos.jpg'))
        ->call('save');

    $new = CompanyProfile::first()->logo_path;

    expect($new)->not->toBe($old);
    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($new);
});

test('se puede quitar el logo', function () {
    $component = Livewire::test(CompanyForm::class)
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('save');

    $path = CompanyProfile::first()->logo_path;

    $component->call('removeImage', 'logo');

    expect(CompanyProfile::first()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('rechaza archivos que no son imagenes o son muy pesados', function () {
    Livewire::test(CompanyForm::class)
        ->set('logo', UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'))
        ->assertHasErrors(['logo']);

    Livewire::test(CompanyForm::class)
        ->set('logo', UploadedFile::fake()->image('grande.png')->size(3000))
        ->assertHasErrors(['logo' => 'max']);
});

test('todos los campos excepto el nombre son opcionales', function () {
    Livewire::test(CompanyForm::class)
        ->set('name', 'Arq Studio')
        ->call('save')
        ->assertHasNoErrors();

    $profile = CompanyProfile::first();

    expect($profile->name)->toBe('Arq Studio')
        ->and($profile->legal_name)->toBeNull()
        ->and($profile->phone)->toBeNull()
        ->and($profile->website)->toBeNull()
        ->and($profile->meta_title)->toBeNull();
});

test('se guardan los campos adicionales', function () {
    Livewire::test(CompanyForm::class)
        ->set('name', 'Arq Studio')
        ->set('legal_name', 'Arq Studio S.A.C.')
        ->set('slogan', 'Diseñamos espacios')
        ->set('email_2', 'ventas@arqstudio.pe')
        ->set('phone', '01 234 5678')
        ->set('whatsapp_2', '+51 988 777 666')
        ->set('maps_url', 'https://maps.google.com/?q=lima')
        ->set('business_hours', 'Lun a Vie 9:00 - 18:00')
        ->set('website', 'https://arqstudio.pe')
        ->set('linkedin', 'https://www.linkedin.com/company/arqstudio')
        ->set('behance', 'https://www.behance.net/arqstudio')
        ->set('meta_title', 'Arq Studio | Arquitectura')
        ->set('meta_description', 'Estudio de arquitectura en Lima.')
        ->call('save')
        ->assertHasNoErrors();

    expect(CompanyProfile::first())
        ->legal_name->toBe('Arq Studio S.A.C.')
        ->phone->toBe('01 234 5678')
        ->linkedin->toBe('https://www.linkedin.com/company/arqstudio')
        ->meta_description->toBe('Estudio de arquitectura en Lima.');
});

test('valida los limites y formatos de los campos adicionales', function () {
    Livewire::test(CompanyForm::class)
        ->set('email_2', 'no-es-correo')
        ->set('phone', 'abc')
        ->set('website', 'sitio')
        ->set('linkedin', 'linkedin')
        ->set('meta_title', str_repeat('a', 71))
        ->set('meta_description', str_repeat('a', 161))
        ->call('save')
        ->assertHasErrors(['email_2' => 'email', 'phone' => 'regex', 'website' => 'url', 'linkedin' => 'url', 'meta_title' => 'max', 'meta_description' => 'max']);
});

test('se puede subir y quitar el favicon sin afectar el logo', function () {
    $component = Livewire::test(CompanyForm::class)
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->set('favicon', UploadedFile::fake()->image('icon.png', 64, 64))
        ->call('save')
        ->assertHasNoErrors();

    $profile = CompanyProfile::first();
    Storage::disk('public')->assertExists($profile->favicon_path);

    $component->call('removeImage', 'favicon');

    $profile->refresh();
    expect($profile->favicon_path)->toBeNull()
        ->and($profile->logo_path)->not->toBeNull();
});

test('no se puede quitar una imagen que no existe en el formulario', function () {
    Livewire::test(CompanyForm::class)
        ->call('removeImage', 'name')
        ->assertNotFound();
});
