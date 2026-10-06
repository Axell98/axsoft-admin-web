<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Limita el atributo style a lo que produce el editor: color de texto y de fondo en <span>
 * y alineación (text-align) en párrafos y celdas de tabla.
 *
 * Cualquier otra propiedad (position, url(), expression(), etc.) o un valor que no sea
 * un color hexadecimal o rgb()/rgba() (o una alineación conocida) se descarta.
 * Si no queda nada válido, se quita el atributo.
 */
class ColorStyleAttributeSanitizer implements AttributeSanitizerInterface
{
    private const ALLOWED_PROPERTIES = ['color', 'background-color'];

    private const ALIGNMENTS = ['left', 'center', 'right', 'justify'];

    private const HEX = '/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i';

    private const RGB = '/^rgba?\(\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*(?:,\s*(?:0|1|0?\.\d+|1\.0+)\s*)?\)$/i';

    public function getSupportedElements(): ?array
    {
        return ['span', 'p', 'th', 'td'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['style'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $declarations = [];

        foreach (explode(';', $value) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $color] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);

            if ($element === 'span') {
                if (in_array($property, self::ALLOWED_PROPERTIES, true) && $this->isColor($color)) {
                    $declarations[$property] = $color;
                }
            } elseif ($property === 'text-align' && in_array(strtolower($color), self::ALIGNMENTS, true)) {
                $declarations[$property] = strtolower($color);
            }
        }

        if ($declarations === []) {
            return null;
        }

        return implode('; ', array_map(fn (string $property, string $value) => "{$property}: {$value}", array_keys($declarations), $declarations)).';';
    }

    private function isColor(string $value): bool
    {
        return preg_match(self::HEX, $value) === 1 || preg_match(self::RGB, $value) === 1;
    }
}
