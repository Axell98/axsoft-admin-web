<?php

use App\Support\YouTube;

test('extrae el id de distintos formatos de enlace de YouTube', function (string $input) {
    expect(YouTube::extractId($input))->toBe('dQw4w9WgXcQ');
})->with([
    'watch' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'watch con parametros extra' => 'https://www.youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=10s',
    'sin protocolo' => 'youtube.com/watch?v=dQw4w9WgXcQ',
    'movil' => 'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
    'youtu.be' => 'https://youtu.be/dQw4w9WgXcQ',
    'youtu.be con tiempo' => 'https://youtu.be/dQw4w9WgXcQ?t=42',
    'embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
    'nocookie' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
    'shorts' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'live' => 'https://www.youtube.com/live/dQw4w9WgXcQ',
    'iframe completo' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ?si=abc" title="YouTube video player" frameborder="0" allowfullscreen></iframe>',
    'iframe con comillas simples' => "<iframe src='https://www.youtube.com/embed/dQw4w9WgXcQ'></iframe>",
    'con espacios alrededor' => '   https://youtu.be/dQw4w9WgXcQ  ',
]);

test('rechaza enlaces que no son de YouTube o no son validos', function (string $input) {
    expect(YouTube::extractId($input))->toBeNull();
})->with([
    'vacio' => '',
    'texto' => 'hola mundo',
    'id suelto' => 'dQw4w9WgXcQ',
    'otro sitio' => 'https://vimeo.com/123456789',
    'dominio parecido' => 'https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ',
    'subdominio falso' => 'https://evil-youtube.com/watch?v=dQw4w9WgXcQ',
    'id muy corto' => 'https://www.youtube.com/watch?v=abc',
    'id con caracteres raros' => 'https://www.youtube.com/watch?v=dQw4w9WgXc<',
    'sin id' => 'https://www.youtube.com/watch',
    'canal' => 'https://www.youtube.com/@canal',
    'iframe sin src' => '<iframe width="560"></iframe>',
    'iframe de otro sitio' => '<iframe src="https://evil.com/embed/dQw4w9WgXcQ"></iframe>',
]);

test('arma las urls de incrustacion y miniatura solo con el id', function () {
    expect(YouTube::embedUrl('dQw4w9WgXcQ'))->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->and(YouTube::thumbnailUrl('dQw4w9WgXcQ'))->toBe('https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg');
});
