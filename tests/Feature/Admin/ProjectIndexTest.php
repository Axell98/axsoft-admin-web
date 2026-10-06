<?php

use App\Livewire\Admin\ProjectIndex;
use App\Models\MediaFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function indexProject(string $title, array $attributes = []): Project
{
    return Project::create(array_merge([
        'title' => $title,
        'slug' => Str::slug($title),
        'is_published' => true,
    ], $attributes));
}

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

test('la pagina requiere autenticacion', function () {
    auth()->logout();

    $this->get(route('admin.projects'))->assertRedirect('/login');
});

test('muestra un estado vacio sin proyectos', function () {
    $this->get(route('admin.projects'))
        ->assertOk()
        ->assertSeeLivewire(ProjectIndex::class)
        ->assertSee('Aún no hay proyectos');
});

test('lista los proyectos con sus datos principales', function () {
    indexProject('Edificio Multifamiliar', ['category' => 'Residencial', 'location' => 'San Borja, Lima', 'year' => 2024, 'execution_percentage' => 80]);

    Livewire::test(ProjectIndex::class)
        ->assertSee('Edificio Multifamiliar')
        ->assertSee('Residencial')
        ->assertSee('San Borja, Lima')
        ->assertSee('2024')
        ->assertSee('80%');
});

test('muestra la imagen de la primera fase como portada', function () {
    $project = indexProject('Casa');
    $file = MediaFile::create(['name' => 'portada.jpg', 'path' => 'files/portada.jpg', 'extension' => 'jpg', 'size' => 1]);
    $project->phases()->create(['type' => 'file', 'media_file_id' => $file->id, 'position' => 0]);

    Livewire::test(ProjectIndex::class)->assertSeeHtml(Storage::disk('public')->url('files/portada.jpg'));
});

test('marca los proyectos ocultos', function () {
    indexProject('Proyecto oculto', ['is_published' => false]);

    Livewire::test(ProjectIndex::class)->assertSee('Oculto');
});

test('busca por titulo, categoria y locacion', function () {
    indexProject('Torre Norte', ['category' => 'Comercial', 'location' => 'Miraflores']);
    indexProject('Casa Sur', ['category' => 'Residencial', 'location' => 'Surco']);

    Livewire::test(ProjectIndex::class)
        ->set('search', 'torre')->assertSee('Torre Norte')->assertDontSee('Casa Sur')
        ->set('search', 'residencial')->assertSee('Casa Sur')->assertDontSee('Torre Norte')
        ->set('search', 'miraflores')->assertSee('Torre Norte')->assertDontSee('Casa Sur')
        ->set('search', 'nada')->assertSee('Sin resultados');
});

test('la busqueda trata los comodines como texto literal', function () {
    indexProject('Plan_A');
    indexProject('PlanXA');

    Livewire::test(ProjectIndex::class)
        ->set('search', 'Plan_A')
        ->assertSee('Plan_A')
        ->assertDontSee('PlanXA');
});

test('pagina los proyectos', function () {
    foreach (range(1, 14) as $i) {
        indexProject(sprintf('Proyecto %02d', $i));
    }

    $component = Livewire::test(ProjectIndex::class);

    expect($component->viewData('projects')->total())->toBe(14)
        ->and($component->viewData('projects')->count())->toBe(12);
});

test('se puede cambiar la visibilidad de un proyecto', function () {
    $project = indexProject('Casa');

    Livewire::test(ProjectIndex::class)
        ->call('toggleVisibility', $project->id)
        ->assertDispatched('notify');

    expect($project->refresh()->is_published)->toBeFalse();

    Livewire::test(ProjectIndex::class)->call('toggleVisibility', $project->id);

    expect($project->refresh()->is_published)->toBeTrue();
});

test('se puede eliminar un proyecto junto con sus fases', function () {
    $project = indexProject('Casa');
    $project->phases()->create(['type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg', 'position' => 0]);

    Livewire::test(ProjectIndex::class)->call('delete', $project->id)->assertDispatched('notify');

    expect(Project::count())->toBe(0)
        ->and(DB::table('project_phases')->count())->toBe(0);
});

test('eliminar un proyecto no borra los archivos del gestor', function () {
    $project = indexProject('Casa');
    $file = MediaFile::create(['name' => 'a.jpg', 'path' => 'files/a.jpg', 'extension' => 'jpg', 'size' => 1]);
    Storage::disk('public')->put('files/a.jpg', 'x');
    $project->phases()->create(['type' => 'file', 'media_file_id' => $file->id, 'position' => 0]);

    Livewire::test(ProjectIndex::class)->call('delete', $project->id);

    expect(MediaFile::count())->toBe(1);
    Storage::disk('public')->assertExists('files/a.jpg');
});
