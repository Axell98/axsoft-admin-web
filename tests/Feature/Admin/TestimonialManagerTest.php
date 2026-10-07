<?php

use App\Livewire\Admin\TestimonialManager;
use App\Models\MediaFile;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function testimonialImage(string $name = 'maria.jpg'): MediaFile
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

function makeTestimonial(array $attributes = []): Testimonial
{
    return Testimonial::create(array_merge([
        'name' => 'María Fernández',
        'role' => 'Gerente',
        'content' => 'Excelente trabajo.',
    ], $attributes));
}

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

// --- Acceso ------------------------------------------------------------------------

test('la pagina requiere autenticacion', function () {
    auth()->logout();

    $this->get(route('admin.testimonials'))->assertRedirect('/login');
});

test('muestra un estado vacio sin testimonios', function () {
    $this->get(route('admin.testimonials'))
        ->assertOk()
        ->assertSeeLivewire(TestimonialManager::class)
        ->assertSee('Aún no hay testimonios');
});

test('el menu incluye los testimonios', function () {
    expect(collect(config('menu'))->pluck('route'))->toContain('admin.testimonials');

    $this->get(route('dashboard'))->assertSee(route('admin.testimonials'));
});

test('lista los testimonios con sus datos', function () {
    makeTestimonial(['name' => 'Carlos Ruiz', 'role' => 'Arquitecto', 'content' => 'Superaron mis expectativas.']);
    makeTestimonial(['name' => 'Ana Torres', 'role' => null]);

    Livewire::test(TestimonialManager::class)
        ->assertSee('Carlos Ruiz')
        ->assertSee('Arquitecto')
        ->assertSee('Superaron mis expectativas.')
        ->assertSee('Ana Torres')
        ->assertSee('Sin cargo');
});

test('los testimonios se paginan', function () {
    foreach (range(1, 14) as $i) {
        makeTestimonial(['name' => sprintf('Cliente %02d', $i)]);
    }

    $component = Livewire::test(TestimonialManager::class);

    expect($component->viewData('testimonials'))->toHaveCount(12)
        ->and($component->viewData('total'))->toBe(14);
});

// --- Registrar -------------------------------------------------------------------------

test('abre un formulario vacio para registrar', function () {
    Livewire::test(TestimonialManager::class)
        ->call('create')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSet('isPublished', true)
        ->assertSee('Registrar testimonio');
});

test('registra un testimonio con todos sus datos', function () {
    $image = testimonialImage();

    Livewire::test(TestimonialManager::class)
        ->call('create')
        ->set('name', '  María Fernández ')
        ->set('role', ' Gerente de Inmobiliaria Sol ')
        ->set('content', "Muy profesionales.\nLo recomiendo.")
        ->dispatch('media-picked', context: 'testimonial', items: [['type' => 'file', 'media_file_id' => $image->id]])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false)
        ->assertDispatched('notify');

    $testimonial = Testimonial::firstOrFail();

    expect($testimonial->name)->toBe('María Fernández')
        ->and($testimonial->role)->toBe('Gerente de Inmobiliaria Sol')
        ->and($testimonial->content)->toBe("Muy profesionales.\nLo recomiendo.")
        ->and($testimonial->image_type)->toBe('file')
        ->and($testimonial->media_file_id)->toBe($image->id)
        ->and($testimonial->imageUrl())->toBe(Storage::disk('public')->url($image->path))
        ->and($testimonial->is_published)->toBeTrue();
});

test('solo el nombre y el testimonio son obligatorios', function () {
    Livewire::test(TestimonialManager::class)
        ->call('create')
        ->set('name', 'Luis')
        ->set('content', 'Todo bien.')
        ->call('save')
        ->assertHasNoErrors();

    $testimonial = Testimonial::firstOrFail();

    expect($testimonial->role)->toBeNull()
        ->and($testimonial->image_type)->toBeNull()
        ->and($testimonial->imageUrl())->toBeNull();
});

test('valida los campos', function () {
    Livewire::test(TestimonialManager::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'content' => 'required']);

    Livewire::test(TestimonialManager::class)
        ->call('create')
        ->set('name', str_repeat('a', 121))
        ->set('role', str_repeat('a', 121))
        ->set('content', str_repeat('a', 1501))
        ->call('save')
        ->assertHasErrors(['name' => 'max', 'role' => 'max', 'content' => 'max']);

    expect(Testimonial::count())->toBe(0);
});

test('un testimonio se puede registrar oculto', function () {
    Livewire::test(TestimonialManager::class)
        ->call('create')
        ->set('name', 'Luis')
        ->set('content', 'Todo bien.')
        ->set('isPublished', false)
        ->call('save');

    expect(Testimonial::first()->is_published)->toBeFalse();
});

// --- Imagen ----------------------------------------------------------------------------

test('el boton de imagen abre el selector solo con imagenes', function () {
    Livewire::test(TestimonialManager::class)
        ->call('create')
        ->call('chooseImage')
        ->assertDispatched('open-media-picker', context: 'testimonial', mode: 'image', multiple: false, external: ['image'], used: []);
});

test('acepta una imagen por link y la reemplaza al elegir otra', function () {
    $image = testimonialImage();

    Livewire::test(TestimonialManager::class)
        ->call('create')
        ->dispatch('media-picked', context: 'testimonial', items: [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg']])
        ->assertSet('imageType', 'image_url')
        ->assertSet('externalUrl', 'https://sitio.com/a.jpg')
        ->dispatch('media-picked', context: 'testimonial', items: [['type' => 'file', 'media_file_id' => $image->id]])
        ->assertSet('imageType', 'file')
        ->assertSet('mediaFileId', $image->id)
        ->assertSet('externalUrl', null);
});

test('ignora imagenes invalidas o de otro selector', function (array $item, string $context) {
    Livewire::test(TestimonialManager::class)
        ->call('create')
        ->dispatch('media-picked', context: $context, items: [value($item)])
        ->assertSet('imageType', null);
})->with([
    'otro selector' => [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg'], 'phase'],
    'video' => [fn () => ['type' => 'file', 'media_file_id' => testimonialImage('clip.mp4')->id], 'testimonial'],
    'documento' => [fn () => ['type' => 'file', 'media_file_id' => testimonialImage('doc.pdf')->id], 'testimonial'],
    'archivo inexistente' => [['type' => 'file', 'media_file_id' => 99999], 'testimonial'],
    'url peligrosa' => [['type' => 'image_url', 'url' => 'javascript:alert(1)'], 'testimonial'],
    'youtube' => [['type' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ'], 'testimonial'],
]);

test('ignora lo elegido cuando el formulario esta cerrado', function () {
    Livewire::test(TestimonialManager::class)
        ->dispatch('media-picked', context: 'testimonial', items: [['type' => 'image_url', 'url' => 'https://sitio.com/a.jpg']])
        ->assertSet('imageType', null);
});

test('se puede quitar la imagen', function () {
    $testimonial = makeTestimonial(['image_type' => 'image_url', 'external_url' => 'https://sitio.com/a.jpg']);

    Livewire::test(TestimonialManager::class)
        ->call('edit', $testimonial->id)
        ->assertSet('imageType', 'image_url')
        ->call('removeImage')
        ->assertSet('imageType', null)
        ->call('save')
        ->assertHasNoErrors();

    expect($testimonial->refresh()->image_type)->toBeNull()->and($testimonial->external_url)->toBeNull();
});

test('rechaza una imagen manipulada al guardar', function (string $type, ?int $fileId, ?string $url) {
    Livewire::test(TestimonialManager::class)
        ->call('create')
        ->set('name', 'Luis')
        ->set('content', 'Todo bien.')
        ->set('imageType', $type)
        ->set('mediaFileId', $fileId)
        ->set('externalUrl', $url)
        ->call('save')
        ->assertHasErrors('imageType');

    expect(Testimonial::count())->toBe(0);
})->with([
    'archivo inexistente' => ['file', 99999, null],
    'url invalida' => ['image_url', null, 'ftp://sitio.com/a.jpg'],
    'tipo desconocido' => ['otro', null, null],
]);

test('al eliminar el archivo del gestor el testimonio se conserva sin imagen', function () {
    $image = testimonialImage();
    $testimonial = makeTestimonial(['image_type' => 'file', 'media_file_id' => $image->id]);

    $image->delete();

    $testimonial->refresh();

    expect(Testimonial::count())->toBe(1)
        ->and($testimonial->media_file_id)->toBeNull()
        ->and($testimonial->imageUrl())->toBeNull();

    Livewire::test(TestimonialManager::class)->assertSee('María Fernández');
});

// --- Actualizar ---------------------------------------------------------------------------

test('carga los datos al editar y los actualiza', function () {
    $testimonial = makeTestimonial();

    Livewire::test(TestimonialManager::class)
        ->call('edit', $testimonial->id)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $testimonial->id)
        ->assertSet('name', 'María Fernández')
        ->assertSet('role', 'Gerente')
        ->assertSet('content', 'Excelente trabajo.')
        ->assertSee('Guardar cambios')
        ->set('name', 'María F. Rojas')
        ->set('role', '')
        ->set('content', 'Increíble.')
        ->set('isPublished', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false)
        ->assertSet('editingId', null);

    $testimonial->refresh();

    expect(Testimonial::count())->toBe(1)
        ->and($testimonial->name)->toBe('María F. Rojas')
        ->and($testimonial->role)->toBeNull()
        ->and($testimonial->content)->toBe('Increíble.')
        ->and($testimonial->is_published)->toBeFalse();
});

test('cancelar no cambia nada y deja el formulario limpio', function () {
    $testimonial = makeTestimonial();

    Livewire::test(TestimonialManager::class)
        ->call('edit', $testimonial->id)
        ->set('name', 'Otro')
        ->call('closeForm')
        ->assertSet('showForm', false)
        ->assertSet('name', '')
        ->call('create')
        ->assertSet('name', '')
        ->assertSet('editingId', null);

    expect($testimonial->refresh()->name)->toBe('María Fernández');
});

test('editar un testimonio que no existe devuelve 404', function () {
    Livewire::test(TestimonialManager::class)->call('edit', 999)->assertNotFound();
});

// --- Ocultar y eliminar ----------------------------------------------------------------

test('se puede ocultar y volver a mostrar un testimonio', function () {
    $testimonial = makeTestimonial();

    Livewire::test(TestimonialManager::class)
        ->call('toggleVisibility', $testimonial->id)
        ->assertDispatched('notify')
        ->assertSee('Oculto');

    expect($testimonial->refresh()->is_published)->toBeFalse();

    Livewire::test(TestimonialManager::class)->call('toggleVisibility', $testimonial->id);

    expect($testimonial->refresh()->is_published)->toBeTrue();
});

test('se puede eliminar un testimonio', function () {
    $testimonial = makeTestimonial();
    $other = makeTestimonial(['name' => 'Otro cliente']);

    Livewire::test(TestimonialManager::class)
        ->call('delete', $testimonial->id)
        ->assertDispatched('notify')
        ->assertDontSee('María Fernández')
        ->assertSee('Otro cliente');

    expect(Testimonial::pluck('id')->all())->toBe([$other->id]);
});

test('eliminar el testimonio que se esta editando cierra el formulario', function () {
    $testimonial = makeTestimonial();

    Livewire::test(TestimonialManager::class)
        ->call('edit', $testimonial->id)
        ->call('delete', $testimonial->id)
        ->assertSet('showForm', false)
        ->assertSet('editingId', null);
});

test('eliminar un testimonio que no existe devuelve 404', function () {
    Livewire::test(TestimonialManager::class)->call('delete', 999)->assertNotFound();
});

test('el texto del testimonio se escapa y no ejecuta html', function () {
    makeTestimonial(['name' => '<b>Hack</b>', 'content' => '<script>alert(1)</script>']);

    Livewire::test(TestimonialManager::class)
        ->assertDontSeeHtml('<script>alert(1)</script>')
        ->assertDontSeeHtml('<b>Hack</b>');
});
