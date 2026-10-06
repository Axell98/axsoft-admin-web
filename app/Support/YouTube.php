<?php

namespace App\Support;

/**
 * Utilidades para videos de YouTube. Nunca se guarda HTML del usuario:
 * solo el identificador de 11 caracteres, con el que se arma la URL de incrustación.
 */
class YouTube
{
    private const ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

    /**
     * Extrae el ID de un enlace de YouTube (watch, youtu.be, embed, shorts, live)
     * o del atributo src de un código <iframe>. Devuelve null si no es válido.
     */
    public static function extractId(string $input): ?string
    {
        $input = trim($input);

        if (stripos($input, '<iframe') !== false) {
            if (! preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/is', $input, $matches)) {
                return null;
            }

            $input = html_entity_decode(trim($matches[2]));
        }

        $parts = parse_url(str_contains($input, '://') ? $input : 'https://'.ltrim($input, '/'));

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = (string) preg_replace('/^(www\.|m\.)/', '', strtolower($parts['host']));
        $path = $parts['path'] ?? '';
        $id = null;

        if ($host === 'youtu.be') {
            $id = explode('/', ltrim($path, '/'))[0];
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = $query['v'] ?? null;
            } elseif (preg_match('#^/(?:embed|shorts|live|v)/([^/?]+)#', $path, $matches)) {
                $id = $matches[1];
            }
        }

        return is_string($id) && preg_match(self::ID_PATTERN, $id) ? $id : null;
    }

    public static function embedUrl(string $id): string
    {
        return "https://www.youtube.com/embed/{$id}";
    }

    public static function thumbnailUrl(string $id): string
    {
        return "https://img.youtube.com/vi/{$id}/hqdefault.jpg";
    }
}
