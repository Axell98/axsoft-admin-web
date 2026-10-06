<?php

use App\Models\Project;
use App\Models\User;

test('el dashboard muestra el usuario logueado y la opcion de cerrar sesion', function () {
    $user = User::factory()->create(['name' => 'Ana Prueba', 'email' => 'ana@example.com']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Ana Prueba')
        ->assertSee('ana@example.com')
        ->assertSee('Cerrar sesión');
});

test('el menu lateral muestra todas las opciones configuradas', function () {
    $response = $this->actingAs(User::factory()->create())->get('/dashboard');

    foreach (config('menu') as $item) {
        $response->assertSee($item['label']);
        $response->assertSee(route($item['route']), false);
    }
});

test('cada opcion del menu es accesible para un usuario autenticado', function () {
    $this->actingAs(User::factory()->create());

    foreach (config('menu') as $item) {
        $this->get(route($item['route']))->assertOk();
    }
});

test('las opciones del menu requieren autenticacion', function () {
    foreach (config('menu') as $item) {
        $this->get(route($item['route']))->assertRedirect('/login');
    }
});

test('el panel es solo de tema claro: sin boton ni script de tema', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('<meta name="color-scheme" content="light">', false)
        ->assertDontSee('Cambiar a modo', false)
        ->assertDontSee('window.theme', false)
        ->assertDontSee('prefers-color-scheme', false)
        ->assertDontSee('MutationObserver', false);
});

test('el menu lateral tiene la seccion Administracion', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertSee('Administración');
});

test('solo la opcion de la pagina actual aparece como activa en el menu', function () {
    $this->actingAs(User::factory()->create());

    foreach (config('menu') as $current) {
        $html = $this->get(route($current['route']))->assertOk()->getContent();

        preg_match_all('/<a\s[^>]*class="[^"]*is-active[^"]*"[^>]*>/', $html, $matches);

        expect($matches[0])->toHaveCount(1)
            ->and($matches[0][0])->toContain('href="'.route($current['route']).'"')
            ->and($matches[0][0])->toContain('aria-current="page"');
    }
});

test('las paginas de proyectos mantienen activa la opcion Proyectos', function () {
    $this->actingAs(User::factory()->create());

    $html = $this->get(route('admin.projects.create'))->assertOk()->getContent();

    preg_match_all('/<a\s[^>]*class="[^"]*is-active[^"]*"[^>]*>/', $html, $matches);

    expect($matches[0])->toHaveCount(1)
        ->and($matches[0][0])->toContain('href="'.route('admin.projects').'"');
});

test('al editar un proyecto sigue activa la opcion Proyectos', function () {
    $this->actingAs(User::factory()->create());
    $project = Project::create(['title' => 'Casa', 'slug' => 'casa']);

    $html = $this->get(route('admin.projects.edit', $project))->assertOk()->getContent();

    preg_match_all('/<a\s[^>]*class="[^"]*is-active[^"]*"[^>]*>/', $html, $matches);

    expect($matches[0])->toHaveCount(1)
        ->and($matches[0][0])->toContain('href="'.route('admin.projects').'"');
});

test('el login es solo de tema claro', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('<meta name="color-scheme" content="light">', false)
        ->assertDontSee('Cambiar a modo', false)
        ->assertDontSee('window.theme', false);
});

test('no quedan estilos de modo oscuro en las vistas ni en el CSS', function () {
    $views = collect(File::allFiles(resource_path('views')))
        ->reject(fn ($file) => $file->getFilename() === 'welcome.blade.php')
        ->filter(fn ($file) => preg_match('/(?<![\w-])dark:/', File::get($file->getPathname())) === 1)
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    $css = File::get(resource_path('css/app.css'));

    expect($views)->toBe([])
        ->and($css)->not->toContain('.dark ')
        // La variante dark debe seguir desactivada para neutralizar los estilos de la paginación del framework.
        ->and($css)->toContain('@custom-variant dark (&:where([data-never-dark], [data-never-dark] *));');
});
