<?php

use App\Livewire\Admin\ProjectForm;
use App\Models\MediaFile;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function formMedia(string $name): MediaFile
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

/**
 * Crea un proyecto desde el formulario con los campos dados.
 *
 * @param  array<string, mixed>  $fields
 */
function createProject(array $fields = []): Project
{
    $component = Livewire::test(ProjectForm::class)->set('title', $fields['title'] ?? 'Edificio Multifamiliar');

    unset($fields['title']);

    foreach ($fields as $name => $value) {
        $component->set($name, $value);
    }

    $component->call('save')->assertHasNoErrors();

    return Project::latest('id')->firstOrFail();
}

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

// --- Acceso -----------------------------------------------------------------------

test('las paginas de crear y editar requieren autenticacion', function () {
    $project = Project::create(['title' => 'Casa', 'slug' => 'casa']);
    auth()->logout();

    $this->get(route('admin.projects.create'))->assertRedirect('/login');
    $this->get(route('admin.projects.edit', $project))->assertRedirect('/login');
});

test('la pagina de crear carga el formulario', function () {
    $this->get(route('admin.projects.create'))
        ->assertOk()
        ->assertSeeLivewire(ProjectForm::class)
        ->assertSee('Nuevo proyecto')
        ->assertSee('Fases del proyecto')
        ->assertSee('Video del proyecto');
});

test('la pagina de editar carga los datos del proyecto', function () {
    $project = Project::create(['title' => 'Torre Norte', 'slug' => 'torre-norte', 'location' => 'Miraflores', 'project_category_id' => ProjectCategory::create(['name' => 'Comercial', 'slug' => 'comercial'])->id, 'execution_percentage' => 60, 'year' => 2023]);

    $this->get(route('admin.projects.edit', $project))
        ->assertOk()
        ->assertSee('Editar proyecto')
        ->assertSee('Torre Norte')
        ->assertSee('torre-norte');

    Livewire::test(ProjectForm::class, ['project' => $project])
        ->assertSet('projectId', $project->id)
        ->assertSet('title', 'Torre Norte')
        ->assertSet('location', 'Miraflores')
        ->assertSet('executionPercentage', '60')
        ->assertSet('year', '2023');
});

test('editar un proyecto inexistente da 404', function () {
    $this->get('/admin/proyectos/9999/editar')->assertNotFound();
});

// --- Crear ------------------------------------------------------------------------

test('solo el titulo es obligatorio', function () {
    Livewire::test(ProjectForm::class)
        ->call('save')
        ->assertHasErrors(['title' => 'required']);

    expect(Project::count())->toBe(0);

    $project = createProject(['title' => 'Mínimo']);

    expect($project->location)->toBeNull()
        ->and($project->project_category_id)->toBeNull()
        ->and($project->execution_percentage)->toBeNull()
        ->and($project->year)->toBeNull()
        ->and($project->description)->toBeNull()
        ->and($project->video_type)->toBeNull()
        ->and($project->is_published)->toBeTrue();
});

test('crea el proyecto con todos sus datos y vuelve al listado', function () {
    $category = ProjectCategory::create(['name' => 'Residencial', 'slug' => 'residencial']);

    $component = Livewire::test(ProjectForm::class)
        ->set('title', 'Diseño Edificio Multifamiliar')
        ->set('description', '<div>Desarrollo y modelado <strong>3D</strong>.</div>')
        ->set('location', 'San Borja, Lima')
        ->set('categoryId', (string) $category->id)
        ->set('executionPercentage', '100')
        ->set('year', '2020')
        ->set('isPublished', false)
        ->call('save')
        ->assertHasNoErrors();

    $project = Project::first();

    $component->assertRedirect(route('admin.projects'));

    expect(session('notify'))->toBe('Proyecto creado correctamente');

    expect($project->title)->toBe('Diseño Edificio Multifamiliar')
        ->and($project->description)->toBe('<div>Desarrollo y modelado <strong>3D</strong>.</div>')
        ->and($project->location)->toBe('San Borja, Lima')
        ->and($project->category->name)->toBe('Residencial')
        ->and($project->execution_percentage)->toBe(100)
        ->and($project->year)->toBe(2020)
        ->and($project->is_published)->toBeFalse();
});

// --- Slug -------------------------------------------------------------------------

test('genera el slug a partir del titulo', function () {
    expect(createProject(['title' => 'Diseño Edificio Multifamiliar'])->slug)->toBe('diseno-edificio-multifamiliar');
});

test('los slugs generados nunca se repiten', function () {
    $first = createProject(['title' => 'Casa Playa']);
    $second = createProject(['title' => 'Casa Playa']);
    $third = createProject(['title' => 'Casa  Playa!']);

    expect($first->slug)->toBe('casa-playa')
        ->and($second->slug)->toBe('casa-playa-2')
        ->and($third->slug)->toBe('casa-playa-3');
});

test('usa el slug escrito por el usuario normalizado', function () {
    expect(createProject(['title' => 'Casa', 'slug' => '  Mi Proyecto Nuevo! 2026 '])->slug)->toBe('mi-proyecto-nuevo-2026');
});

test('no permite un slug ya usado por otro proyecto', function () {
    Project::create(['title' => 'Otro', 'slug' => 'mi-slug']);

    Livewire::test(ProjectForm::class)
        ->set('title', 'Casa')
        ->set('slug', 'Mi Slug')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);

    expect(Project::count())->toBe(1);
});

test('rechaza un slug sin letras ni numeros', function () {
    Livewire::test(ProjectForm::class)
        ->set('title', 'Casa')
        ->set('slug', '!!! ???')
        ->call('save')
        ->assertHasErrors('slug');
});

test('al editar se conserva el slug propio y se puede cambiar', function () {
    $project = createProject(['title' => 'Casa Playa']);

    $component = Livewire::test(ProjectForm::class, ['project' => $project])
        ->assertSet('slug', 'casa-playa')
        ->set('title', 'Casa de Playa Norte')
        ->call('save')
        ->assertHasNoErrors();

    expect($project->refresh()->slug)->toBe('casa-playa');

    $component->set('slug', 'casa-norte')->call('save')->assertHasNoErrors();

    expect($project->refresh()->slug)->toBe('casa-norte');
});

test('al editar con el slug vacio se regenera desde el titulo sin chocar consigo mismo', function () {
    $project = createProject(['title' => 'Casa Playa']);

    Livewire::test(ProjectForm::class, ['project' => $project])
        ->set('slug', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($project->refresh()->slug)->toBe('casa-playa');
});

// --- Validaciones -----------------------------------------------------------------

test('valida los campos del proyecto', function () {
    Livewire::test(ProjectForm::class)
        ->set('title', str_repeat('a', 201))
        ->set('location', str_repeat('a', 151))
        ->set('categoryId', '999999')
        ->set('executionPercentage', '101')
        ->set('year', '1800')
        ->call('save')
        ->assertHasErrors(['title' => 'max', 'location' => 'max', 'categoryId' => 'exists', 'executionPercentage' => 'between', 'year' => 'between']);
});

test('valida el porcentaje de ejecucion', function (string $value, bool $valid) {
    $component = Livewire::test(ProjectForm::class)->set('title', 'Casa')->set('executionPercentage', $value)->call('save');

    $valid ? $component->assertHasNoErrors() : $component->assertHasErrors('executionPercentage');
})->with([
    'cero' => ['0', true],
    'medio' => ['80', true],
    'cien' => ['100', true],
    'negativo' => ['-1', false],
    'mayor a cien' => ['101', false],
    'decimal' => ['50.5', false],
    'texto' => ['abc', false],
]);

test('valida el año', function (string $value, bool $valid) {
    $component = Livewire::test(ProjectForm::class)->set('title', 'Casa')->set('year', $value)->call('save');

    $valid ? $component->assertHasNoErrors() : $component->assertHasErrors('year');
})->with([
    'actual' => [(string) now()->year, true],
    'antiguo' => ['1990', true],
    'muy antiguo' => ['1899', false],
    'muy futuro' => ['2999', false],
    'texto' => ['dos mil', false],
]);

// --- Texto enriquecido ------------------------------------------------------------

test('limpia el html de la descripcion', function () {
    $project = createProject([
        'description' => '<div>Texto <strong>seguro</strong><script>alert(1)</script> <a href="javascript:alert(1)" onclick="x()">enlace</a><img src=x onerror=alert(1)></div>',
    ]);

    expect($project->description)->toContain('<strong>seguro</strong>')
        ->not->toContain('script')
        ->not->toContain('javascript:')
        ->not->toContain('onclick')
        ->not->toContain('<img');
});

test('una descripcion sin contenido visible se guarda vacia', function () {
    expect(createProject(['description' => '<div><br></div>'])->description)->toBeNull();
});

// --- Fases ------------------------------------------------------------------------

test('el selector se abre desde el formulario con el contexto correcto', function () {
    $file = formMedia('a.jpg');

    Livewire::test(ProjectForm::class)
        ->set('phases', [['uid' => 'u1', 'id' => null, 'type' => 'file', 'media_file_id' => $file->id, 'url' => null, 'title' => null, 'content' => null]])
        ->call('choosePhaseImages')
        ->assertDispatched('open-media-picker', context: 'phase', mode: 'image', multiple: true, external: ['image'], used: [$file->id]);

    Livewire::test(ProjectForm::class)
        ->call('chooseVideo')
        ->assertDispatched('open-media-picker', context: 'video', mode: 'video', multiple: false, external: ['youtube']);
});

test('agrega fases desde archivos y links de imagen', function () {
    $file = formMedia('render.jpg');

    Livewire::test(ProjectForm::class)
        ->dispatch('media-picked', context: 'phase', items: [
            ['type' => 'file', 'media_file_id' => $file->id],
            ['type' => 'image_url', 'url' => 'https://sitio.com/otra.jpg'],
        ])
        ->assertCount('phases', 2)
        ->assertSee('render.jpg');
});

test('ignora elementos manipulados al agregar fases', function () {
    $pdf = formMedia('plano.pdf');
    $video = formMedia('video.mp4');

    Livewire::test(ProjectForm::class)
        ->dispatch('media-picked', context: 'phase', items: [
            ['type' => 'file', 'media_file_id' => $pdf->id],
            ['type' => 'file', 'media_file_id' => $video->id],
            ['type' => 'file', 'media_file_id' => 9999],
            ['type' => 'file', 'media_file_id' => 'abc'],
            ['type' => 'image_url', 'url' => 'javascript:alert(1)'],
            ['type' => 'image_url', 'url' => 'data:image/png;base64,AAAA'],
            ['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ'],
            ['type' => 'otro'],
            [],
        ])
        ->assertCount('phases', 0);
});

test('ignora eventos de un contexto desconocido', function () {
    $file = formMedia('a.jpg');

    Livewire::test(ProjectForm::class)
        ->dispatch('media-picked', context: 'banner', items: [['type' => 'file', 'media_file_id' => $file->id]])
        ->assertCount('phases', 0)
        ->assertSet('videoType', null);
});

test('cada fase guarda su titulo y contenido y mantiene el orden', function () {
    $a = formMedia('a.jpg');
    $b = formMedia('b.jpg');

    $component = Livewire::test(ProjectForm::class)
        ->set('title', 'Edificio')
        ->dispatch('media-picked', context: 'phase', items: [
            ['type' => 'file', 'media_file_id' => $a->id],
            ['type' => 'file', 'media_file_id' => $b->id],
        ])
        ->set('phases.0.title', 'Fase 01: Concepción')
        ->set('phases.0.content', '<div>Modelado <strong>3D</strong></div>')
        ->set('phases.1.title', 'Fase 02: Construcción')
        ->set('phases.1.content', '<div><br></div>')
        ->call('save')
        ->assertHasNoErrors();

    $phases = Project::first()->phases;

    expect($phases)->toHaveCount(2)
        ->and($phases[0]->media_file_id)->toBe($a->id)
        ->and($phases[0]->title)->toBe('Fase 01: Concepción')
        ->and($phases[0]->content)->toBe('<div>Modelado <strong>3D</strong></div>')
        ->and($phases[0]->position)->toBe(0)
        ->and($phases[1]->media_file_id)->toBe($b->id)
        ->and($phases[1]->content)->toBeNull()
        ->and($phases[1]->position)->toBe(1);
});

test('limpia el html del contenido de las fases', function () {
    $a = formMedia('a.jpg');

    Livewire::test(ProjectForm::class)
        ->set('title', 'Edificio')
        ->dispatch('media-picked', context: 'phase', items: [['type' => 'file', 'media_file_id' => $a->id]])
        ->set('phases.0.content', '<div>Hola<script>alert(1)</script><iframe src="https://evil.com"></iframe></div>')
        ->call('save');

    $content = Project::first()->phases->first()->content;

    expect($content)->toBe('<div>Hola</div>');
});

test('se pueden reordenar y quitar fases', function () {
    $a = formMedia('a.jpg');
    $b = formMedia('b.jpg');
    $c = formMedia('c.jpg');

    $component = Livewire::test(ProjectForm::class)
        ->set('title', 'Edificio')
        ->dispatch('media-picked', context: 'phase', items: [
            ['type' => 'file', 'media_file_id' => $a->id],
            ['type' => 'file', 'media_file_id' => $b->id],
            ['type' => 'file', 'media_file_id' => $c->id],
        ]);

    $uids = array_column($component->get('phases'), 'uid');

    $component
        ->call('movePhase', $uids[2], 'up')     // a, c, b
        ->call('movePhase', $uids[0], 'down')   // c, a, b
        ->call('movePhase', $uids[1], 'up')     // c, b, a
        ->call('removePhase', $uids[0])         // c, b
        ->call('save');

    expect(Project::first()->phases->pluck('media_file_id')->all())->toBe([$c->id, $b->id]);
});

test('mover mas alla de los extremos no hace nada', function () {
    $a = formMedia('a.jpg');

    $component = Livewire::test(ProjectForm::class)
        ->dispatch('media-picked', context: 'phase', items: [['type' => 'file', 'media_file_id' => $a->id]]);

    $uid = $component->get('phases')[0]['uid'];

    $component->call('movePhase', $uid, 'up')->call('movePhase', $uid, 'down')->assertCount('phases', 1);
});

test('al guardar de nuevo actualiza las fases existentes sin duplicarlas', function () {
    $a = formMedia('a.jpg');

    $component = Livewire::test(ProjectForm::class)
        ->set('title', 'Edificio')
        ->dispatch('media-picked', context: 'phase', items: [['type' => 'file', 'media_file_id' => $a->id]]);

    $component->call('save');

    $project = Project::first();

    Livewire::test(ProjectForm::class, ['project' => $project])
        ->set('phases.0.title', 'Nuevo título')
        ->call('save')
        ->call('save');

    expect(ProjectPhase::count())->toBe(1)
        ->and(ProjectPhase::first()->title)->toBe('Nuevo título');
});

test('no se pueden superar las 30 fases', function () {
    $items = collect(range(1, 31))->map(fn ($i) => ['type' => 'image_url', 'url' => "https://sitio.com/{$i}.jpg"])->all();

    Livewire::test(ProjectForm::class)
        ->dispatch('media-picked', context: 'phase', items: $items)
        ->assertCount('phases', 30);
});

test('rechaza datos de fases manipulados al guardar', function () {
    $pdf = formMedia('plano.pdf');

    Livewire::test(ProjectForm::class)
        ->set('title', 'Casa')
        ->set('phases', [['uid' => 'x', 'id' => null, 'type' => 'file', 'media_file_id' => $pdf->id, 'url' => null, 'title' => null, 'content' => null]])
        ->call('save')
        ->assertHasErrors('phases');

    Livewire::test(ProjectForm::class)
        ->set('title', 'Casa')
        ->set('phases', [['uid' => 'x', 'id' => null, 'type' => 'image_url', 'media_file_id' => null, 'url' => 'javascript:alert(1)', 'title' => str_repeat('a', 201), 'content' => null]])
        ->call('save')
        ->assertHasErrors(['phases.0.url', 'phases.0.title']);

    expect(Project::count())->toBe(0);
});

test('no se pueden modificar fases de otro proyecto con un id manipulado', function () {
    $a = formMedia('a.jpg');
    $other = Project::create(['title' => 'Otro', 'slug' => 'otro']);
    $foreign = $other->phases()->create(['type' => 'file', 'media_file_id' => $a->id, 'title' => 'Original', 'position' => 0]);

    Livewire::test(ProjectForm::class)
        ->set('title', 'Mío')
        ->set('phases', [['uid' => 'x', 'id' => $foreign->id, 'type' => 'file', 'media_file_id' => $a->id, 'url' => null, 'title' => 'Hackeado', 'content' => null]])
        ->call('save');

    expect($foreign->refresh()->title)->toBe('Original')
        ->and($foreign->project_id)->toBe($other->id);
});

// --- Video ------------------------------------------------------------------------

test('se puede elegir un video del gestor de archivos', function () {
    $video = formMedia('recorrido.mp4');

    Livewire::test(ProjectForm::class)
        ->set('title', 'Casa')
        ->dispatch('media-picked', context: 'video', items: [['type' => 'file', 'media_file_id' => $video->id]])
        ->assertSet('videoType', 'file')
        ->call('save');

    $project = Project::first();

    expect($project->video_type)->toBe('file')
        ->and($project->video_media_file_id)->toBe($video->id)
        ->and($project->video_youtube_id)->toBeNull()
        ->and($project->videoKind())->toBe('video')
        ->and($project->videoUrl())->toBe(Storage::disk('public')->url('files/2026/10/recorrido.mp4'));
});

test('se puede usar un video de YouTube', function () {
    Livewire::test(ProjectForm::class)
        ->set('title', 'Casa')
        ->dispatch('media-picked', context: 'video', items: [['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']])
        ->call('save');

    $project = Project::first();

    expect($project->video_type)->toBe('youtube')
        ->and($project->video_youtube_id)->toBe('dQw4w9WgXcQ')
        ->and($project->video_media_file_id)->toBeNull()
        ->and($project->videoEmbedUrl())->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ');
});

test('cambiar de tipo de video limpia el anterior', function () {
    $video = formMedia('a.mp4');

    $project = createProject(['title' => 'Casa']);

    Livewire::test(ProjectForm::class, ['project' => $project])
        ->dispatch('media-picked', context: 'video', items: [['type' => 'file', 'media_file_id' => $video->id]])
        ->call('save')
        ->dispatch('media-picked', context: 'video', items: [['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']])
        ->call('save');

    $project->refresh();

    expect($project->video_type)->toBe('youtube')
        ->and($project->video_media_file_id)->toBeNull();
});

test('se puede quitar el video', function () {
    $project = createProject(['title' => 'Casa']);

    Livewire::test(ProjectForm::class, ['project' => $project])
        ->dispatch('media-picked', context: 'video', items: [['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ']])
        ->call('save')
        ->call('removeVideo')
        ->call('save');

    $project->refresh();

    expect($project->video_type)->toBeNull()
        ->and($project->video_youtube_id)->toBeNull()
        ->and($project->videoKind())->toBeNull();
});

test('ignora videos invalidos al elegirlos', function () {
    $image = formMedia('foto.jpg');

    Livewire::test(ProjectForm::class)
        ->dispatch('media-picked', context: 'video', items: [['type' => 'file', 'media_file_id' => $image->id]])
        ->assertSet('videoType', null)
        ->dispatch('media-picked', context: 'video', items: [['type' => 'youtube', 'youtube_id' => 'corto']])
        ->assertSet('videoType', null)
        ->dispatch('media-picked', context: 'video', items: [['type' => 'youtube', 'youtube_id' => '<script>alert1']])
        ->assertSet('videoType', null);
});

test('rechaza un video manipulado al guardar', function () {
    $image = formMedia('foto.jpg');

    Livewire::test(ProjectForm::class)
        ->set('title', 'Casa')
        ->set('videoType', 'file')
        ->set('videoMediaFileId', $image->id)
        ->call('save')
        ->assertHasErrors('videoMediaFileId');

    Livewire::test(ProjectForm::class)
        ->set('title', 'Casa')
        ->set('videoType', 'youtube')
        ->set('videoYoutubeId', 'no-valido')
        ->call('save')
        ->assertHasErrors('videoYoutubeId');

    expect(Project::count())->toBe(0);
});

// --- Edición ----------------------------------------------------------------------

test('edita un proyecto existente y se queda en la pagina', function () {
    $video = formMedia('a.mp4');
    $a = formMedia('a.jpg');

    $project = createProject([
        'title' => 'Casa Playa',
        'location' => 'Asia',
    ]);

    Livewire::test(ProjectForm::class, ['project' => $project])
        ->set('title', 'Casa Playa Norte')
        ->set('location', 'Punta Hermosa')
        ->set('executionPercentage', '45')
        ->dispatch('media-picked', context: 'phase', items: [['type' => 'file', 'media_file_id' => $a->id]])
        ->dispatch('media-picked', context: 'video', items: [['type' => 'file', 'media_file_id' => $video->id]])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.projects'));

    expect(session('notify'))->toBe('Proyecto guardado correctamente');

    $project->refresh();

    expect($project->title)->toBe('Casa Playa Norte')
        ->and($project->location)->toBe('Punta Hermosa')
        ->and($project->execution_percentage)->toBe(45)
        ->and($project->phases)->toHaveCount(1)
        ->and($project->video_media_file_id)->toBe($video->id)
        ->and(Project::count())->toBe(1);
});

test('el formulario de edicion carga las fases y el video guardados', function () {
    $a = formMedia('a.jpg');
    $video = formMedia('v.mp4');

    $project = Project::create(['title' => 'Casa', 'slug' => 'casa', 'video_type' => 'file', 'video_media_file_id' => $video->id]);
    $project->phases()->create(['type' => 'file', 'media_file_id' => $a->id, 'title' => 'Fase 1', 'content' => '<div>Texto</div>', 'position' => 0]);

    Livewire::test(ProjectForm::class, ['project' => $project])
        ->assertCount('phases', 1)
        ->assertSet('phases.0.title', 'Fase 1')
        ->assertSet('phases.0.content', '<div>Texto</div>')
        ->assertSet('videoType', 'file')
        ->assertSet('videoMediaFileId', $video->id)
        ->assertSee('v.mp4');
});

test('si se elimina un archivo del gestor la fase queda sin imagen pero conserva su texto', function () {
    $a = formMedia('a.jpg');
    $project = Project::create(['title' => 'Casa', 'slug' => 'casa']);
    $project->phases()->create(['type' => 'file', 'media_file_id' => $a->id, 'title' => 'Fase 1', 'content' => '<div>Texto</div>', 'position' => 0]);

    $a->delete();

    $phase = $project->phases()->first();

    expect($phase->media_file_id)->toBeNull()
        ->and($phase->title)->toBe('Fase 1')
        ->and($phase->imageUrl())->toBeNull();

    Livewire::test(ProjectForm::class, ['project' => $project])->assertSee('Archivo no disponible');
});

test('al eliminar el video del gestor el proyecto queda sin video', function () {
    $video = formMedia('v.mp4');
    $project = Project::create(['title' => 'Casa', 'slug' => 'casa', 'video_type' => 'file', 'video_media_file_id' => $video->id]);

    $video->delete();

    expect($project->refresh()->videoKind())->toBeNull();
});

// --- Colores del editor -------------------------------------------------------------

test('guarda el color de texto, el fondo y el subrayado de la descripcion y de las fases', function () {
    $a = formMedia('a.jpg');

    $description = '<div>Texto <span style="color: rgb(220, 38, 38);">rojo</span> y <span style="background-color: rgb(254, 240, 138);">resaltado</span> con <u>subrayado</u>.</div>';
    $content = '<div><strong><span style="color: #2563eb;">Fase azul</span></strong></div>';

    Livewire::test(ProjectForm::class)
        ->set('title', 'Casa')
        ->set('description', $description)
        ->dispatch('media-picked', context: 'phase', items: [['type' => 'file', 'media_file_id' => $a->id]])
        ->set('phases.0.content', $content)
        ->call('save')
        ->assertHasNoErrors();

    $project = Project::first();

    expect($project->description)->toBe($description)
        ->and($project->phases->first()->content)->toBe($content);
});

test('los estilos peligrosos de la descripcion y de las fases se eliminan al guardar', function () {
    $a = formMedia('a.jpg');

    Livewire::test(ProjectForm::class)
        ->set('title', 'Casa')
        ->set('description', '<div><span style="position: fixed; top: 0; color: #dc2626; background-image: url(https://evil.com/x.png)">texto</span></div>')
        ->dispatch('media-picked', context: 'phase', items: [['type' => 'file', 'media_file_id' => $a->id]])
        ->set('phases.0.content', '<div style="display:none"><span style="color: expression(alert(1))">x</span></div>')
        ->call('save');

    $project = Project::first();

    expect($project->description)->toBe('<div><span style="color: #dc2626;">texto</span></div>')
        ->and($project->phases->first()->content)->toBe('<div><span>x</span></div>');
});

test('el formulario no contiene formularios anidados (el boton de abajo debe seguir dentro)', function () {
    $html = Livewire::test(ProjectForm::class)->html();

    expect(substr_count($html, '<form'))->toBe(1)
        ->and(substr_count($html, '</form>'))->toBe(1)
        ->and(substr_count($html, 'type="submit"'))->toBe(2);
});

// --- Categorías ---------------------------------------------------------------------

test('el select muestra las categorias y la elegida queda guardada', function () {
    $residencial = ProjectCategory::create(['name' => 'Residencial', 'slug' => 'residencial']);
    ProjectCategory::create(['name' => 'Comercial', 'slug' => 'comercial']);

    Livewire::test(ProjectForm::class)
        ->assertSeeInOrder(['Sin categoría', 'Comercial', 'Residencial']);

    $project = createProject(['categoryId' => (string) $residencial->id]);

    Livewire::test(ProjectForm::class, ['project' => $project])->assertSet('categoryId', (string) $residencial->id);
});

test('se puede quitar la categoria de un proyecto', function () {
    $category = ProjectCategory::create(['name' => 'Residencial', 'slug' => 'residencial']);
    $project = createProject(['categoryId' => (string) $category->id]);

    Livewire::test(ProjectForm::class, ['project' => $project])->set('categoryId', '')->call('save')->assertHasNoErrors();

    expect($project->refresh()->project_category_id)->toBeNull();
});

test('rechaza una categoria que no existe al guardar', function () {
    Livewire::test(ProjectForm::class)->set('title', 'Casa')->set('categoryId', '12345')->call('save')->assertHasErrors(['categoryId' => 'exists']);

    expect(Project::count())->toBe(0);
});
