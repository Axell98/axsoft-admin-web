<?php

use App\Livewire\Admin\FileManager;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Crea un archivo ya guardado (en disco y en base de datos).
 */
function storedFile(string $name = 'plano.pdf', ?MediaFolder $folder = null, int $size = 1024): MediaFile
{
    $extension = pathinfo($name, PATHINFO_EXTENSION);
    $path = 'files/2026/10/'.fake()->unique()->lexify('??????????').'.'.$extension;
    Storage::disk('public')->put($path, 'contenido');

    return MediaFile::create([
        'media_folder_id' => $folder?->id,
        'name' => $name,
        'path' => $path,
        'extension' => $extension,
        'mime_type' => 'application/octet-stream',
        'size' => $size,
    ]);
}

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

// --- Acceso -----------------------------------------------------------------

test('la pagina requiere autenticacion', function () {
    auth()->logout();

    $this->get(route('admin.files'))->assertRedirect('/login');
});

test('la pagina carga mostrando carpetas y archivos', function () {
    $folder = MediaFolder::create(['name' => 'Proyectos']);
    storedFile('memoria.pdf', $folder);

    $this->get(route('admin.files'))
        ->assertOk()
        ->assertSeeLivewire(FileManager::class)
        ->assertSee('Proyectos')
        ->assertSee('memoria.pdf');
});

test('muestra un estado vacio cuando no hay archivos', function () {
    Livewire::test(FileManager::class)->assertSee('Aún no hay archivos');
});

// --- Carpetas ---------------------------------------------------------------

test('se puede crear una carpeta y queda seleccionada', function () {
    Livewire::test(FileManager::class)
        ->call('openFolderModal')
        ->set('folderName', 'Banners')
        ->call('saveFolder')
        ->assertHasNoErrors()
        ->assertSet('showFolderModal', false)
        ->assertSet('folderId', MediaFolder::first()->id)
        ->assertDispatched('notify');

    expect(MediaFolder::pluck('name')->all())->toBe(['Banners']);
});

test('valida el nombre de la carpeta', function () {
    MediaFolder::create(['name' => 'Banners']);

    Livewire::test(FileManager::class)
        ->call('openFolderModal')
        ->set('folderName', '')
        ->call('saveFolder')
        ->assertHasErrors(['folderName' => 'required'])
        ->set('folderName', 'Banners')
        ->call('saveFolder')
        ->assertHasErrors(['folderName' => 'unique'])
        ->set('folderName', str_repeat('a', 61))
        ->call('saveFolder')
        ->assertHasErrors(['folderName' => 'max']);

    expect(MediaFolder::count())->toBe(1);
});

test('se puede renombrar una carpeta conservando su nombre sin error de duplicado', function () {
    $folder = MediaFolder::create(['name' => 'Fotos']);

    Livewire::test(FileManager::class)
        ->call('openFolderModal', $folder->id)
        ->assertSet('folderName', 'Fotos')
        ->call('saveFolder')
        ->assertHasNoErrors()
        ->call('openFolderModal', $folder->id)
        ->set('folderName', 'Imágenes')
        ->call('saveFolder')
        ->assertHasNoErrors();

    expect($folder->refresh()->name)->toBe('Imágenes');
});

test('al eliminar una carpeta se eliminan sus archivos del disco', function () {
    $folder = MediaFolder::create(['name' => 'Temporal']);
    $inside = storedFile('uno.pdf', $folder);
    $outside = storedFile('otro.pdf');

    Livewire::test(FileManager::class)
        ->call('selectFolder', $folder->id)
        ->call('deleteFolder', $folder->id)
        ->assertSet('folderId', null);

    expect(MediaFolder::count())->toBe(0)
        ->and(MediaFile::pluck('id')->all())->toBe([$outside->id]);

    Storage::disk('public')->assertMissing($inside->path);
    Storage::disk('public')->assertExists($outside->path);
});

// --- Listado ----------------------------------------------------------------

test('filtra los archivos por carpeta', function () {
    $folder = MediaFolder::create(['name' => 'Fotos']);
    storedFile('en-carpeta.jpg', $folder);
    storedFile('suelto.pdf');

    Livewire::test(FileManager::class)
        ->assertSee('en-carpeta.jpg')
        ->assertSee('suelto.pdf')
        ->call('selectFolder', $folder->id)
        ->assertSee('en-carpeta.jpg')
        ->assertDontSee('suelto.pdf');
});

test('busca archivos por nombre', function () {
    storedFile('catalogo.pdf');
    storedFile('fachada.jpg');

    Livewire::test(FileManager::class)
        ->set('search', 'fach')
        ->assertSee('fachada.jpg')
        ->assertDontSee('catalogo.pdf')
        ->set('search', 'inexistente')
        ->assertSee('Sin resultados');
});

test('la busqueda trata los comodines de SQL como texto literal', function () {
    storedFile('informe_final.pdf');
    storedFile('informeXfinal.pdf');

    Livewire::test(FileManager::class)
        ->set('search', 'informe_final')
        ->assertSee('informe_final.pdf')
        ->assertDontSee('informeXfinal.pdf');
});

test('pagina el listado', function () {
    foreach (range(1, 17) as $i) {
        storedFile(sprintf('archivo-%02d.pdf', $i));
    }

    $component = Livewire::test(FileManager::class);

    expect($component->viewData('files')->total())->toBe(17)
        ->and($component->viewData('files')->count())->toBe(15);
});

// --- Subida -----------------------------------------------------------------

test('guarda un archivo subido en la carpeta elegida', function () {
    $folder = MediaFolder::create(['name' => 'Docs']);

    Livewire::test(FileManager::class)
        ->set('uploadFolder', (string) $folder->id)
        ->set('upload', UploadedFile::fake()->create('Memoria Descriptiva.pdf', 200, 'application/pdf'))
        ->call('storeUpload')
        ->assertReturned(['ok' => true])
        ->assertSet('upload', null);

    $file = MediaFile::first();

    expect($file->name)->toBe('Memoria_Descriptiva.pdf')
        ->and($file->extension)->toBe('pdf')
        ->and($file->media_folder_id)->toBe($folder->id)
        ->and($file->size)->toBeGreaterThan(0)
        ->and($file->path)->toStartWith('files/'.now()->format('Y/m').'/')
        ->and($file->path)->not->toContain('Memoria');

    Storage::disk('public')->assertExists($file->path);
});

test('sin carpeta elegida el archivo queda sin carpeta', function () {
    Livewire::test(FileManager::class)
        ->set('upload', UploadedFile::fake()->image('foto.jpg'))
        ->call('storeUpload')
        ->assertReturned(['ok' => true]);

    expect(MediaFile::first()->media_folder_id)->toBeNull();
});

test('acepta los tipos de documentos y multimedia permitidos', function (string $name, string $category) {
    Livewire::test(FileManager::class)
        ->set('upload', UploadedFile::fake()->create($name, 50))
        ->call('storeUpload')
        ->assertReturned(['ok' => true]);

    expect(MediaFile::first()->category())->toBe($category);
})->with([
    ['plano.pdf', 'pdf'],
    ['informe.docx', 'word'],
    ['presupuesto.xlsx', 'excel'],
    ['datos.csv', 'excel'],
    ['presentacion.pptx', 'powerpoint'],
    ['recorrido.mp4', 'video'],
    ['logo.webp', 'image'],
    ['planos.zip', 'archive'],
    ['notas.txt', 'text'],
]);

test('rechaza extensiones no permitidas', function (string $name) {
    Livewire::test(FileManager::class)
        ->set('upload', UploadedFile::fake()->create($name, 10))
        ->call('storeUpload')
        ->assertReturned(fn (array $result) => $result['ok'] === false && str_contains($result['error'], 'no permitido'));

    expect(MediaFile::count())->toBe(0);
})->with(['shell.php', 'pagina.html', 'icono.svg', 'virus.exe', 'sin-extension', 'doble.php.jpg.php']);

test('rechaza archivos que superan el tamaño maximo', function () {
    $tooBigKb = MediaFile::maxUploadKb() + 1;

    Livewire::test(FileManager::class)
        ->set('upload', UploadedFile::fake()->create('enorme.pdf', $tooBigKb, 'application/pdf'))
        ->call('storeUpload')
        ->assertReturned(fn (array $result) => $result['ok'] === false && str_contains($result['error'], 'tamaño máximo'));

    expect(MediaFile::count())->toBe(0);
});

test('devuelve error si no hay archivo para guardar', function () {
    Livewire::test(FileManager::class)
        ->call('storeUpload')
        ->assertReturned(['ok' => false, 'error' => 'No se recibió el archivo.']);
});

test('limpia el nombre del archivo subido', function () {
    Livewire::test(FileManager::class)
        ->set('upload', UploadedFile::fake()->create('..\\..\\carpeta\\plano.pdf', 10))
        ->call('storeUpload')
        ->assertReturned(['ok' => true]);

    expect(MediaFile::first()->name)->toBe('plano.pdf');
});

// --- Nombres de archivo -------------------------------------------------------

test('normaliza el nombre: espacios a guion bajo y sin caracteres especiales', function (string $original, string $expected) {
    Livewire::test(FileManager::class)
        ->set('upload', UploadedFile::fake()->create($original, 10))
        ->call('storeUpload')
        ->assertReturned(['ok' => true]);

    expect(MediaFile::first()->name)->toBe($expected);
})->with([
    'espacios' => ['Memoria Descriptiva.pdf', 'Memoria_Descriptiva.pdf'],
    'espacios repetidos y simbolos' => ['Plano   (final) #2.PDF', 'Plano_final_2.pdf'],
    'tildes y eñe' => ['Diseño núcleo.jpg', 'Diseno_nucleo.jpg'],
    'puntos intermedios' => ['informe.v2.final.pdf', 'informe_v2_final.pdf'],
    'simbolos pegados' => ['a&b=c+d.xlsx', 'abcd.xlsx'],
    'guiones y bordes' => ['  --foto--  .png', 'foto.png'],
    'solo simbolos' => ['@@@.pdf', 'archivo.pdf'],
    'mayusculas en extension' => ['LOGO.PNG', 'LOGO.png'],
]);

// --- Mover archivos -----------------------------------------------------------

test('se puede mover un archivo a otra carpeta', function () {
    $origin = MediaFolder::create(['name' => 'Origen']);
    $target = MediaFolder::create(['name' => 'Destino']);
    $file = storedFile('plano.pdf', $origin);

    Livewire::test(FileManager::class)
        ->call('openMoveModal', $file->id)
        ->assertSet('showMoveModal', true)
        ->assertSet('moveIds', [$file->id])
        ->set('moveTo', (string) $target->id)
        ->call('moveFiles')
        ->assertHasNoErrors()
        ->assertSet('showMoveModal', false)
        ->assertDispatched('notify');

    expect($file->refresh()->media_folder_id)->toBe($target->id);
    Storage::disk('public')->assertExists($file->path);
});

test('se puede mover un archivo a sin carpeta', function () {
    $folder = MediaFolder::create(['name' => 'Origen']);
    $file = storedFile('plano.pdf', $folder);

    Livewire::test(FileManager::class)
        ->call('openMoveModal', $file->id)
        ->set('moveTo', '')
        ->call('moveFiles')
        ->assertHasNoErrors();

    expect($file->refresh()->media_folder_id)->toBeNull();
});

test('se pueden mover varios archivos seleccionados', function () {
    $target = MediaFolder::create(['name' => 'Destino']);
    $a = storedFile('a.pdf');
    $b = storedFile('b.pdf');
    $stay = storedFile('c.pdf');

    Livewire::test(FileManager::class)
        ->set('selected', [(string) $a->id, (string) $b->id])
        ->call('openMoveModal')
        ->assertSet('moveIds', [$a->id, $b->id])
        ->set('moveTo', (string) $target->id)
        ->call('moveFiles')
        ->assertSet('selected', []);

    expect($a->refresh()->media_folder_id)->toBe($target->id)
        ->and($b->refresh()->media_folder_id)->toBe($target->id)
        ->and($stay->refresh()->media_folder_id)->toBeNull();
});

test('no abre el modal de mover si no hay archivos seleccionados', function () {
    Livewire::test(FileManager::class)
        ->call('openMoveModal')
        ->assertSet('showMoveModal', false);
});

test('no permite mover a una carpeta que no existe', function () {
    $file = storedFile();

    Livewire::test(FileManager::class)
        ->call('openMoveModal', $file->id)
        ->set('moveTo', '9999')
        ->call('moveFiles')
        ->assertHasErrors(['moveTo' => 'exists'])
        ->assertSet('showMoveModal', true);

    expect($file->refresh()->media_folder_id)->toBeNull();
});

// --- Eliminar y descargar -----------------------------------------------------

test('eliminar un archivo borra tambien el fichero del disco', function () {
    $file = storedFile();

    Livewire::test(FileManager::class)->call('deleteFile', $file->id);

    expect(MediaFile::count())->toBe(0);
    Storage::disk('public')->assertMissing($file->path);
});

test('se pueden eliminar varios archivos a la vez', function () {
    $a = storedFile('a.pdf');
    $b = storedFile('b.pdf');
    $keep = storedFile('c.pdf');

    Livewire::test(FileManager::class)
        ->set('selected', [(string) $a->id, (string) $b->id])
        ->call('deleteSelected')
        ->assertSet('selected', []);

    expect(MediaFile::pluck('id')->all())->toBe([$keep->id]);
    Storage::disk('public')->assertMissing($a->path);
    Storage::disk('public')->assertExists($keep->path);
});

test('la descarga entrega el archivo con su nombre original', function () {
    $file = storedFile('Memoria Final.pdf');

    $this->get(route('admin.files.download', $file))
        ->assertOk()
        ->assertDownload('Memoria Final.pdf');
});

test('la descarga devuelve 404 si el fichero ya no existe en disco', function () {
    $file = storedFile();
    Storage::disk('public')->delete($file->path);

    $this->get(route('admin.files.download', $file))->assertNotFound();
});

test('la descarga requiere autenticacion', function () {
    $file = storedFile();
    auth()->logout();

    $this->get(route('admin.files.download', $file))->assertRedirect('/login');
});

// --- Modelo -------------------------------------------------------------------

test('el limite de subida nunca supera la configuracion ni los limites de PHP', function () {
    config(['files.max_size_kb' => 1]);
    expect(MediaFile::maxUploadKb())->toBe(1);

    config(['files.max_size_kb' => 99_999_999]);
    $php = min(
        (int) ini_get('upload_max_filesize'),
        (int) ini_get('post_max_size'),
    );
    expect(MediaFile::maxUploadKb())->toBeLessThanOrEqual($php * 1024);
});
