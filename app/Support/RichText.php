<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Limpia el HTML que produce el editor de texto enriquecido.
 *
 * Solo se conservan negrita, cursiva, subrayado, tachado, color de texto y de
 * fondo, alineación, listas, tablas, saltos de línea y enlaces http(s)/mailto. Todo lo demás
 * (scripts, otros estilos, eventos, imágenes, iframes...) se elimina antes de guardar.
 */
class RichText
{
    private const MAX_INPUT_LENGTH = 100_000;

    /**
     * Devuelve el HTML limpio, o null si no queda contenido visible.
     */
    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = trim(self::sanitizer()->sanitize($html));

        return self::plain($clean) === '' ? null : $clean;
    }

    /**
     * Texto sin etiquetas, con espacios normalizados. Con $limit lo recorta añadiendo «…».
     */
    public static function plain(?string $html, ?int $limit = null): string
    {
        if ($html === null) {
            return '';
        }

        // Los bloques y saltos de línea se convierten en espacio para no pegar palabras.
        $text = (string) preg_replace('/<\/(p|div|li|th|td|tr)>|<br\s*\/?>/i', ' ', $html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $text));

        if ($limit !== null && mb_strlen($text) > $limit) {
            return rtrim(mb_substr($text, 0, $limit)).'…';
        }

        return $text;
    }

    private static function sanitizer(): HtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig)
            ->allowElement('p', ['style'])
            ->allowElement('div')
            ->allowElement('table')
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th', ['style', 'colspan', 'rowspan'])
            ->allowElement('td', ['style', 'colspan', 'rowspan'])
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('del')
            ->allowElement('s')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('span', ['style'])
            ->allowElement('a', ['href'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks(false)
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->forceAttribute('a', 'target', '_blank')
            ->withAttributeSanitizer(new ColorStyleAttributeSanitizer)
            ->withMaxInputLength(self::MAX_INPUT_LENGTH);

        return new HtmlSanitizer($config);
    }
}
