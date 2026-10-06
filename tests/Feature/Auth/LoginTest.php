<?php

use App\Livewire\Auth\Login;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('la raiz redirige al login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('la pantalla de login se muestra', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSeeLivewire(Login::class);
});

test('un invitado no puede ver el dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('un usuario puede iniciar sesion con credenciales correctas', function () {
    $user = User::factory()->create(['password' => 'secreto123']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'secreto123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('el login falla con contrasena incorrecta', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'incorrecta')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('el login valida los campos obligatorios', function () {
    Livewire::test(Login::class)
        ->call('login')
        ->assertHasErrors(['email' => 'required', 'password' => 'required']);
});

test('el login se bloquea tras demasiados intentos fallidos', function () {
    $user = User::factory()->create();

    $component = Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'incorrecta');

    foreach (range(1, 5) as $_) {
        $component->call('login');
    }

    $component->call('login')->assertHasErrors(['email']);

    expect($component->errors()->first('email'))->toContain('Demasiados intentos');
});

test('un usuario autenticado puede cerrar sesion', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});

test('un usuario autenticado es redirigido fuera del login', function () {
    $this->actingAs(User::factory()->create())
        ->get('/login')
        ->assertRedirect();
});

test('el login muestra solo el formulario centrado, sin panel lateral', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Bienvenido')
        ->assertSee('Correo electrónico')
        ->assertSee('Iniciar sesión')
        ->assertDontSee('Administra tu sitio web')
        ->assertDontSee('Banners, popups y comunicados');
});

test('el login muestra el nombre y el logo de la empresa cuando existen', function () {
    Storage::fake('public');

    CompanyProfile::create(['name' => 'Voladizo Arquitectos', 'logo_path' => 'logos/voladizo.png']);

    $this->get('/login')
        ->assertOk()
        ->assertSee('Voladizo Arquitectos')
        ->assertSeeHtml(Storage::disk('public')->url('logos/voladizo.png'));
});

test('el login usa el nombre de la aplicacion y un icono cuando no hay empresa', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertDontSee('<img', false);

    expect(CompanyProfile::count())->toBe(0);
});
