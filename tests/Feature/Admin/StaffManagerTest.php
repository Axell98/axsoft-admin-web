<?php

use App\Livewire\Admin\StaffManager;
use App\Models\MediaFile;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function staffImage(string $name = 'carlos.jpg'): MediaFile
{
    $extension = pathinfo($name, PATHINFO_EXTENSION);

    return MediaFile::create([
        'name' => $name,
        'path' => 'files/2026/10/'.fake()->unique()->lexify('??????????').'.'.$extension,
        'extension' => $extension,
        'mime_type' => 'application/octet-stream',
        'size' => 1024,
    ]);
}

function makeStaff(array $attributes = []): StaffMember
{
    return StaffMember::create(array_merge([
        'name' => 'Carlos Ruiz',
        'role' => 'Arquitecto',
        'sort_order' => 1,
    ], $attributes));
}

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

// --- Acceso ------------------------------------------------------------------------

test('la pagina requiere autenticacion', function () {
    auth()->logout();

    $this->get(route('admin.staff'))->assertRedirect('/login');
});

test('muestra un estado vacio sin integrantes', function () {
    $this->get(route('admin.staff'))
        ->assertOk()
        ->assertSeeLivewire(StaffManager::class)
        ->assertSee('Aún no hay integrantes');
});

test('el menu incluye el staff', function () {
    expect(collect(config('menu'))->pluck('route'))->toContain('admin.staff');

    $this->get(route('dashboard'))->assertSee(route('admin.staff'));
});

test('lista a los integrantes por orden y luego por antiguedad', function () {
    makeStaff(['name' => 'Tercero', 'sort_order' => 3]);
    makeStaff(['name' => 'Primero', 'sort_order' => 1]);
    makeStaff(['name' => 'Segundo A', 'sort_order' => 2]);
    makeStaff(['name' => 'Segundo B', 'sort_order' => 2, 'role' => null]);

    Livewire::test(StaffManager::class)
        ->assertSeeInOrder(['Primero', 'Segundo A', 'Segundo B', 'Tercero'])
        ->assertSee('Sin cargo');
});

test('el orden se aplica antes de paginar', function () {
    foreach (range(1, 26) as $i) {
        makeStaff(['name' => sprintf('Persona %02d', $i), 'sort_order' => 27 - $i]);
    }

    $component = Livewire::test(StaffManager::class);

    expect($component->viewData('members'))->toHaveCount(24)
        ->and($component->viewData('members')->first()->name)->toBe('Persona 26');
});

// --- Registrar -------------------------------------------------------------------------

test('el orden sugerido es el siguiente al mayor existente', function () {
    Livewire::test(StaffManager::class)->call('create')->assertSet('sortOrder', '1');

    makeStaff(['sort_order' => 7]);

    Livewire::test(StaffManager::class)->call('create')->assertSet('sortOrder', '8')->assertSee('Registrar integrante');
});

test('registra un integrante con todos sus datos', function () {
    $image = staffImage();

    Livewire::test(StaffManager::class)
        ->call('create')
        ->set('name', '  Carlos Ruiz ')
        ->set('role', ' Arquitecto principal ')
        ->set('sortOrder', '3')
        ->dispatch('media-picked', context: 'staff', items: [['type' => 'file', 'media_file_id' => $image->id]])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false)
        ->assertDispatched('notify');

    $member = StaffMember::firstOrFail();

    expect($member->name)->toBe('Carlos Ruiz')
        ->and($member->role)->toBe('Arquitecto principal')
        ->and($member->sort_order)->toBe(3)
        ->and($member->image_type)->toBe('file')
        ->and($member->imageUrl())->toBe(Storage::disk('public')->url($image->path));
});

test('solo el nombre y el orden son obligatorios', function () {
    Livewire::test(StaffManager::class)->call('create')->set('name', 'Luis')->call('save')->assertHasNoErrors();

    $member = StaffMember::firstOrFail();

    expect($member->role)->toBeNull()->and($member->imageUrl())->toBeNull();
});

test('valida los campos', function (array $values, array $errors) {
    $component = Livewire::test(StaffManager::class)->call('create')->set('name', 'Luis');

    foreach ($values as $field => $value) {
        $component->set($field, $value);
    }

    $component->call('save')->assertHasErrors($errors);

    expect(StaffMember::count())->toBe(0);
})->with([
    'sin nombre' => [['name' => '  '], ['name' => 'required']],
    'nombre largo' => [['name' => str_repeat('a', 121)], ['name' => 'max']],
    'cargo largo' => [['role' => str_repeat('a', 121)], ['role' => 'max']],
    'orden vacio' => [['sortOrder' => ''], ['sortOrder' => 'required']],
    'orden texto' => [['sortOrder' => 'abc'], ['sortOrder' => 'integer']],
    'orden decimal' => [['sortOrder' => '1.5'], ['sortOrder' => 'integer']],
    'orden negativo' => [['sortOrder' => '-1'], ['sortOrder' => 'between']],
    'orden enorme' => [['sortOrder' => '10000'], ['sortOrder' => 'between']],
]);

test('acepta el orden cero y el maximo', function (string $order) {
    Livewire::test(StaffManager::class)->call('create')->set('name', 'Luis')->set('sortOrder', $order)->call('save')->assertHasNoErrors();

    expect(StaffMember::first()->sort_order)->toBe((int) $order);
})->with(['0', '9999']);

// --- Foto ----------------------------------------------------------------------------

test('el boton de foto abre el selector solo con imagenes', function () {
    Livewire::test(StaffManager::class)
        ->call('create')
        ->call('chooseImage')
        ->assertDispatched('open-media-picker', context: 'staff', mode: 'image', multiple: false, external: ['image'], used: []);
});

test('acepta una foto por link y la reemplaza al elegir otra', function () {
    $image = staffImage();

    Livewire::test(StaffManager::class)
        ->call('create')
        ->dispatch('media-picked', context: 'staff', items: [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg']])
        ->assertSet('imageType', 'image_url')
        ->dispatch('media-picked', context: 'staff', items: [['type' => 'file', 'media_file_id' => $image->id]])
        ->assertSet('imageType', 'file')
        ->assertSet('mediaFileId', $image->id)
        ->assertSet('externalUrl', null);
});

test('ignora fotos invalidas o de otro selector', function (array $item, string $context) {
    Livewire::test(StaffManager::class)
        ->call('create')
        ->dispatch('media-picked', context: $context, items: [value($item)])
        ->assertSet('imageType', null);
})->with([
    'otro selector' => [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg'], 'testimonial'],
    'video' => [fn () => ['type' => 'file', 'media_file_id' => staffImage('clip.mp4')->id], 'staff'],
    'documento' => [fn () => ['type' => 'file', 'media_file_id' => staffImage('doc.pdf')->id], 'staff'],
    'archivo inexistente' => [['type' => 'file', 'media_file_id' => 99999], 'staff'],
    'url peligrosa' => [['type' => 'image_url', 'url' => 'javascript:alert(1)'], 'staff'],
]);

test('rechaza una foto manipulada al guardar', function (string $type, ?int $fileId, ?string $url) {
    Livewire::test(StaffManager::class)
        ->call('create')
        ->set('name', 'Luis')
        ->set('imageType', $type)
        ->set('mediaFileId', $fileId)
        ->set('externalUrl', $url)
        ->call('save')
        ->assertHasErrors('imageType');

    expect(StaffMember::count())->toBe(0);
})->with([
    'archivo inexistente' => ['file', 99999, null],
    'url invalida' => ['image_url', null, 'ftp://sitio.com/a.jpg'],
    'tipo desconocido' => ['otro', null, null],
]);

test('se puede quitar la foto', function () {
    $member = makeStaff(['image_type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg']);

    Livewire::test(StaffManager::class)
        ->call('edit', $member->id)
        ->call('removeImage')
        ->call('save')
        ->assertHasNoErrors();

    expect($member->refresh()->image_type)->toBeNull()->and($member->external_url)->toBeNull();
});

test('al eliminar el archivo del gestor el integrante se conserva sin foto', function () {
    $image = staffImage();
    $member = makeStaff(['image_type' => 'file', 'media_file_id' => $image->id]);

    $image->delete();

    expect(StaffMember::count())->toBe(1)->and($member->refresh()->imageUrl())->toBeNull();
});

// --- Actualizar y eliminar ----------------------------------------------------------------

test('carga los datos al editar y los actualiza', function () {
    $member = makeStaff();

    Livewire::test(StaffManager::class)
        ->call('edit', $member->id)
        ->assertSet('name', 'Carlos Ruiz')
        ->assertSet('role', 'Arquitecto')
        ->assertSet('sortOrder', '1')
        ->assertSee('Guardar cambios')
        ->set('name', 'Carlos R. Soto')
        ->set('role', '')
        ->set('sortOrder', '5')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editingId', null);

    $member->refresh();

    expect(StaffMember::count())->toBe(1)
        ->and($member->name)->toBe('Carlos R. Soto')
        ->and($member->role)->toBeNull()
        ->and($member->sort_order)->toBe(5);
});

test('cancelar no cambia nada y deja el formulario limpio', function () {
    $member = makeStaff();

    Livewire::test(StaffManager::class)
        ->call('edit', $member->id)
        ->set('name', 'Otro')
        ->call('closeForm')
        ->assertSet('showForm', false)
        ->assertSet('name', '');

    expect($member->refresh()->name)->toBe('Carlos Ruiz');
});

test('se puede eliminar un integrante', function () {
    $member = makeStaff();
    $other = makeStaff(['name' => 'Otra persona', 'sort_order' => 2]);

    Livewire::test(StaffManager::class)
        ->call('delete', $member->id)
        ->assertDispatched('notify')
        ->assertDontSee('Carlos Ruiz')
        ->assertSee('Otra persona');

    expect(StaffMember::pluck('id')->all())->toBe([$other->id]);
});

test('editar o eliminar un integrante que no existe devuelve 404', function () {
    Livewire::test(StaffManager::class)->call('edit', 999)->assertNotFound();
    Livewire::test(StaffManager::class)->call('delete', 999)->assertNotFound();
});

test('el texto se escapa y no ejecuta html', function () {
    makeStaff(['name' => '<script>alert(1)</script>', 'role' => '<b>Jefe</b>']);

    Livewire::test(StaffManager::class)
        ->assertDontSeeHtml('<script>alert(1)</script>')
        ->assertDontSeeHtml('<b>Jefe</b>');
});
