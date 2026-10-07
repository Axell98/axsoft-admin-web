<?php

use App\Livewire\Admin\CategoryManager;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\User;
use Database\Seeders\ProjectCategorySeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function makeCategory(string $name): ProjectCategory
{
    return ProjectCategory::create(['name' => $name, 'slug' => Str::slug($name)]);
}

test('la pagina requiere autenticacion', function () {
    auth()->logout();

    $this->get(route('admin.categories'))->assertRedirect('/login');
});

test('muestra un estado vacio sin categorias', function () {
    $this->get(route('admin.categories'))
        ->assertOk()
        ->assertSeeLivewire(CategoryManager::class)
        ->assertSee('Aún no hay categorías');
});

test('las categorias no aparecen en el menu del cliente', function () {
    expect(collect(config('menu'))->pluck('route'))->not->toContain('admin.categories');

    $this->get(route('dashboard'))->assertOk()->assertDontSee(route('admin.categories'));
});

test('el sembrador crea las categorias iniciales sin duplicarlas', function () {
    makeCategory('Residencial');

    $this->seed(ProjectCategorySeeder::class);
    $this->seed(ProjectCategorySeeder::class);

    expect(ProjectCategory::orderBy('name')->pluck('name')->all())->toBe(['Comercial', 'Oficina', 'Residencial']);
});

test('lista las categorias con su cantidad de proyectos', function () {
    $category = makeCategory('Residencial');
    makeCategory('Comercial');
    Project::create(['title' => 'Casa', 'slug' => 'casa', 'project_category_id' => $category->id]);
    Project::create(['title' => 'Casa 2', 'slug' => 'casa-2', 'project_category_id' => $category->id]);

    Livewire::test(CategoryManager::class)
        ->assertSeeInOrder(['Comercial', '0 proyectos', 'Residencial', '2 proyectos']);
});

test('crea una categoria con su slug', function () {
    Livewire::test(CategoryManager::class)
        ->set('newName', '  Obras Públicas ')
        ->call('add')
        ->assertHasNoErrors()
        ->assertSet('newName', '')
        ->assertDispatched('notify');

    $category = ProjectCategory::firstOrFail();

    expect($category->name)->toBe('Obras Públicas')->and($category->slug)->toBe('obras-publicas');
});

test('valida el nombre al crear', function (string $name, string $message) {
    makeCategory('Residencial');

    Livewire::test(CategoryManager::class)
        ->set('newName', $name)
        ->call('add')
        ->assertHasErrors(['newName'])
        ->assertSee($message);

    expect(ProjectCategory::count())->toBe(1);
})->with([
    'vacio' => ['   ', 'Escribe el nombre'],
    'muy largo' => [str_repeat('a', 101), 'no puede superar'],
    'solo simbolos' => ['***', 'solo puede contener letras y números'],
    'repetido' => ['residencial', 'Ya existe una categoría'],
    'repetido con tildes' => ['RESIDÉNCIAL', 'Ya existe una categoría'],
]);

test('cambia el nombre y el slug de una categoria', function () {
    $category = makeCategory('Residencial');

    Livewire::test(CategoryManager::class)
        ->call('startEdit', $category->id)
        ->assertSet('editingId', $category->id)
        ->assertSet('editingName', 'Residencial')
        ->set('editingName', 'Vivienda')
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSet('editingId', null)
        ->assertDispatched('notify');

    expect($category->refresh()->name)->toBe('Vivienda')->and($category->slug)->toBe('vivienda');
});

test('al editar se puede conservar el mismo nombre cambiando solo mayusculas', function () {
    $category = makeCategory('Residencial');

    Livewire::test(CategoryManager::class)
        ->call('startEdit', $category->id)
        ->set('editingName', 'RESIDENCIAL')
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($category->refresh()->name)->toBe('RESIDENCIAL');
});

test('no permite renombrar a una categoria que ya existe', function () {
    $residencial = makeCategory('Residencial');
    makeCategory('Comercial');

    Livewire::test(CategoryManager::class)
        ->call('startEdit', $residencial->id)
        ->set('editingName', 'Comercial')
        ->call('saveEdit')
        ->assertHasErrors(['editingName']);

    expect($residencial->refresh()->name)->toBe('Residencial');
});

test('cancelar la edicion no cambia nada', function () {
    $category = makeCategory('Residencial');

    Livewire::test(CategoryManager::class)
        ->call('startEdit', $category->id)
        ->set('editingName', 'Otro')
        ->call('cancelEdit')
        ->assertSet('editingId', null)
        ->assertSet('editingName', '');

    expect($category->refresh()->name)->toBe('Residencial');
});

test('al eliminar una categoria sus proyectos quedan sin categoria', function () {
    $category = makeCategory('Residencial');
    $project = Project::create(['title' => 'Casa', 'slug' => 'casa', 'project_category_id' => $category->id]);

    Livewire::test(CategoryManager::class)->call('delete', $category->id)->assertDispatched('notify');

    expect(ProjectCategory::count())->toBe(0)
        ->and(Project::count())->toBe(1)
        ->and($project->refresh()->project_category_id)->toBeNull();
});

test('eliminar una categoria que no existe devuelve 404', function () {
    Livewire::test(CategoryManager::class)->call('delete', 999)->assertNotFound();
});
