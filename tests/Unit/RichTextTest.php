<?php

use App\Support\RichText;

test('conserva el formato basico permitido', function () {
    $html = '<div>Hola <strong>mundo</strong> <em>cursiva</em> <del>tachado</del></div><ul><li>uno</li></ul><ol><li>dos</li></ol>';

    expect(RichText::clean($html))->toBe($html);
});

test('elimina scripts, iframes, imagenes y eventos', function (string $dirty, string $expectedNot) {
    $clean = RichText::clean("<div>Texto seguro</div>{$dirty}");

    expect($clean)->toContain('Texto seguro')
        ->and($clean)->not->toContain($expectedNot);
})->with([
    'script' => ['<script>alert(1)</script>', 'script'],
    'iframe' => ['<iframe src="https://evil.com"></iframe>', 'iframe'],
    'imagen con evento' => ['<img src="x" onerror="alert(1)">', 'onerror'],
    'estilo' => ['<style>body{display:none}</style>', 'style'],
    'formulario' => ['<form action="https://evil.com"><input name="x"></form>', 'form'],
    'atributo de evento' => ['<div onclick="alert(1)">clic</div>', 'onclick'],
    'estilo en linea' => ['<div style="position:fixed">x</div>', 'style='],
]);

test('los enlaces solo permiten http, https y mailto y se abren de forma segura', function () {
    $clean = RichText::clean('<div><a href="https://sitio.com" onclick="x()">ok</a> <a href="javascript:alert(1)">mal</a> <a href="data:text/html,x">data</a> <a href="/relativo">rel</a> <a href="mailto:a@b.com">mail</a></div>');

    expect($clean)->toContain('href="https://sitio.com"')
        ->toContain('rel="noopener noreferrer nofollow"')
        ->toContain('target="_blank"')
        ->toContain('href="mailto:a&#64;b.com"')
        ->not->toContain('javascript:')
        ->not->toContain('data:text')
        ->not->toContain('/relativo')
        ->not->toContain('onclick');
});

test('devuelve null cuando no hay contenido visible', function (?string $html) {
    expect(RichText::clean($html))->toBeNull();
})->with([
    'null' => [null],
    'vacio' => [''],
    'espacios' => ['   '],
    'div vacio' => ['<div></div>'],
    'salto de linea' => ['<div><br></div>'],
    'nbsp' => ['<div>&nbsp;</div>'],
    'solo script' => ['<script>alert(1)</script>'],
]);

test('plain devuelve texto sin etiquetas ni espacios repetidos', function () {
    expect(RichText::plain('<div>Hola <strong>mundo</strong></div><div>otra&nbsp;linea</div>'))->toBe('Hola mundo otra linea')
        ->and(RichText::plain('<p>uno</p><p>dos</p>'))->toBe('uno dos')
        ->and(RichText::plain('línea<br>dos'))->toBe('línea dos')
        ->and(RichText::plain(null))->toBe('');
});

test('plain recorta el texto largo con puntos suspensivos', function () {
    expect(RichText::plain('<p>'.str_repeat('a', 50).'</p>', 10))->toBe(str_repeat('a', 10).'…')
        ->and(RichText::plain('<p>corto</p>', 10))->toBe('corto');
});

// --- Colores ------------------------------------------------------------------------

test('conserva el color de texto y de fondo en span', function (string $style, string $expected) {
    $clean = RichText::clean("<div>Hola <span style=\"{$style}\">mundo</span></div>");

    expect($clean)->toContain("<span style=\"{$expected}\">mundo</span>");
})->with([
    'color hex' => ['color: #dc2626;', 'color: #dc2626;'],
    'color hex corto' => ['color:#f00', 'color: #f00;'],
    'fondo hex' => ['background-color: #fef08a;', 'background-color: #fef08a;'],
    'ambos' => ['color: #ffffff; background-color: #2563eb;', 'color: #ffffff; background-color: #2563eb;'],
    'rgb (lo que genera el navegador)' => ['color: rgb(220, 38, 38);', 'color: rgb(220, 38, 38);'],
    'rgba' => ['background-color: rgba(250, 204, 21, 0.5);', 'background-color: rgba(250, 204, 21, 0.5);'],
    'mayusculas' => ['COLOR: #DC2626', 'color: #DC2626;'],
]);

test('descarta cualquier estilo que no sea un color permitido', function (string $style) {
    $clean = RichText::clean("<div><span style=\"{$style}\">texto</span></div>");

    expect($clean)->toBe('<div><span>texto</span></div>');
})->with([
    'posicion' => ['position: fixed; top: 0; left: 0'],
    'url en el fondo' => ['background-color: url(https://evil.com/x.png)'],
    'imagen de fondo' => ['background-image: url(https://evil.com/x.png)'],
    'expression' => ['color: expression(alert(1))'],
    'javascript' => ['color: javascript:alert(1)'],
    'color con nombre' => ['color: red'],
    'color hex invalido' => ['color: #gggggg'],
    'rgb fuera de rango' => ['color: rgb(300, 0, 0)'],
    'inyeccion de css' => ['color: #fff} body{display:none'],
    'display none' => ['display: none'],
    'tamaño de fuente' => ['font-size: 100px'],
    'vacio' => [''],
]);

test('si mezcla propiedades validas e invalidas conserva solo las validas', function () {
    $clean = RichText::clean('<div><span style="position: fixed; color: #dc2626; font-size: 99px; background-color: url(x)">texto</span></div>');

    expect($clean)->toBe('<div><span style="color: #dc2626;">texto</span></div>');
});

test('el atributo style solo se permite en span', function () {
    $clean = RichText::clean('<div style="color: #dc2626">uno</div><strong style="color: #dc2626">dos</strong><p style="background-color: #fff">tres</p><a href="https://sitio.com" style="color: #dc2626">cuatro</a>');

    expect($clean)->not->toContain('style=');
});

test('el color se conserva junto al resto del formato', function () {
    $html = '<div><strong><span style="color: #dc2626;">negrita roja</span></strong> <em><span style="background-color: #fef08a;">cursiva resaltada</span></em></div>';

    expect(RichText::clean($html))->toBe($html);
});

test('plain ignora los estilos de color', function () {
    expect(RichText::plain('<div>Hola <span style="color: #dc2626;">mundo</span></div>'))->toBe('Hola mundo');
});

test('conserva la alineacion de parrafos y celdas', function (string $alignment) {
    $html = "<p style=\"text-align: {$alignment};\">texto</p>";

    expect(RichText::clean($html))->toBe($html);
})->with(['left', 'center', 'right', 'justify']);

test('descarta alineaciones y estilos no permitidos en parrafos', function () {
    $clean = RichText::clean('<p style="text-align: expression(alert(1)); position: fixed">uno</p><p style="text-align: center; color: red">dos</p>');

    expect($clean)->toBe('<p>uno</p><p style="text-align: center;">dos</p>');
});

test('conserva las tablas con su encabezado, celdas combinadas y alineacion', function () {
    $html = '<table><tbody><tr><th colspan="2" style="text-align: center;"><p>Titulo</p></th></tr><tr><td><p>uno</p></td><td rowspan="2"><p>dos</p></td></tr></tbody></table>';

    expect(RichText::clean($html))->toBe($html);
});

test('las tablas no conservan atributos ni elementos peligrosos', function () {
    $clean = RichText::clean('<table onclick="x()" style="position:fixed"><colgroup><col style="min-width: 25px"></colgroup><tbody><tr><td onmouseover="x()" data-colwidth="100"><script>alert(1)</script>celda</td></tr></tbody></table>');

    expect($clean)->toContain('celda')
        ->and($clean)->not->toContain('onclick')
        ->and($clean)->not->toContain('onmouseover')
        ->and($clean)->not->toContain('colgroup')
        ->and($clean)->not->toContain('data-colwidth')
        ->and($clean)->not->toContain('script')
        ->and($clean)->not->toContain('style=');
});

test('plain separa el texto de las celdas de una tabla', function () {
    expect(RichText::plain('<table><tbody><tr><td><p>uno</p></td><td><p>dos</p></td></tr></tbody></table>'))->toBe('uno dos');
});
