<?php

namespace App\Support;

use App\Models\MediaFile;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Miniaturas de las imágenes del gestor de archivos, para que las vistas previas del
 * panel no descarguen el archivo original (que puede pesar varios MB).
 *
 * Se guardan en el mismo disco, en «thumbs/», con la misma ruta del original:
 * files/2026/10/abc.jpg → thumbs/2026/10/abc.webp (o .jpg si el servidor no genera WebP).
 * Usa la extensión GD de PHP; si no está disponible, o la imagen no se puede procesar,
 * nunca falla: devuelve null y el panel muestra el original.
 */
class Thumbnail
{
    /** Lado mayor de la miniatura, en píxeles. */
    public const MAX_SIDE = 480;

    private const QUALITY = 80;

    private const DIRECTORY = 'thumbs';

    /** Formatos en los que se puede haber guardado una miniatura. */
    private const EXTENSIONS = ['webp', 'jpg'];

    /** Las imágenes con más píxeles que esto no se procesan. */
    private const MAX_PIXELS = 50_000_000;

    /** Bytes de memoria que GD necesita por píxel al abrir una imagen (con margen). */
    private const BYTES_PER_PIXEL = 6;

    /** Memoria que se deja libre para el resto de la petición. */
    private const MEMORY_HEADROOM = 16 * 1024 * 1024;

    /**
     * Ruta donde va la miniatura de un archivo. Null si no es una imagen o el servidor no puede crearlas.
     */
    public static function path(MediaFile $file): ?string
    {
        $extension = self::extension();

        if ($extension === null || ! $file->isImage()) {
            return null;
        }

        return self::basePath($file).'.'.$extension;
    }

    /**
     * Ruta de la miniatura, creándola si aún no existe. Null si no se puede crear.
     */
    public static function ensure(MediaFile $file): ?string
    {
        $path = self::path($file);

        if ($path === null) {
            return null;
        }

        return Storage::disk(MediaFile::DISK)->exists($path) ? $path : self::generate($file);
    }

    /**
     * Crea (o vuelve a crear) la miniatura y devuelve su ruta. Nunca lanza errores:
     * si algo falla lo registra en el log y devuelve null.
     */
    public static function generate(MediaFile $file): ?string
    {
        try {
            return self::create($file);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Borra la miniatura de un archivo, en cualquiera de sus formatos.
     */
    public static function delete(MediaFile $file): void
    {
        $base = self::basePath($file);

        Storage::disk(MediaFile::DISK)->delete(
            array_map(fn (string $extension) => $base.'.'.$extension, self::EXTENSIONS),
        );
    }

    private static function create(MediaFile $file): ?string
    {
        $path = self::path($file);
        $disk = Storage::disk(MediaFile::DISK);

        if ($path === null || ! $disk->exists($file->path)) {
            return null;
        }

        $source = $disk->path($file->path);
        $info = @getimagesize($source);

        if ($info === false) {
            return null;
        }

        [$width, $height, $type] = $info;

        if ($width < 1 || $height < 1 || ! self::fitsInMemory($width, $height)) {
            return null;
        }

        $image = self::read($source, $type);

        if ($image === null) {
            return null;
        }

        $ratio = min(1, self::MAX_SIDE / max($width, $height));
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $thumb instanceof GdImage) {
            return null;
        }

        $webp = str_ends_with($path, '.webp');

        if ($webp) {
            // WebP conserva la transparencia de los PNG y GIF.
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            imagefilledrectangle($thumb, 0, 0, $targetWidth - 1, $targetHeight - 1, (int) imagecolorallocatealpha($thumb, 255, 255, 255, 127));
        } else {
            // JPG no tiene transparencia: el fondo queda blanco.
            imagefilledrectangle($thumb, 0, 0, $targetWidth - 1, $targetHeight - 1, (int) imagecolorallocate($thumb, 255, 255, 255));
        }

        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        unset($image);

        $thumb = self::orient($thumb, $source, $type);

        ob_start();

        try {
            $encoded = $webp ? imagewebp($thumb, null, self::QUALITY) : imagejpeg($thumb, null, self::QUALITY);
        } finally {
            $data = (string) ob_get_clean();
        }

        if (! $encoded || $data === '') {
            return null;
        }

        return $disk->put($path, $data) ? $path : null;
    }

    private static function read(string $source, int $type): ?GdImage
    {
        $image = match (true) {
            $type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg') => @imagecreatefromjpeg($source),
            $type === IMAGETYPE_PNG && function_exists('imagecreatefrompng') => @imagecreatefrompng($source),
            $type === IMAGETYPE_GIF && function_exists('imagecreatefromgif') => @imagecreatefromgif($source),
            $type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp') => @imagecreatefromwebp($source),
            default => false,
        };

        return $image instanceof GdImage ? $image : null;
    }

    /**
     * Las fotos de celular suelen venir giradas y con la orientación real en los datos EXIF.
     * El navegador las endereza al mostrarlas; aquí se endereza la miniatura para que coincida.
     */
    private static function orient(GdImage $thumb, string $source, int $type): GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data') || ! function_exists('imagerotate')) {
            return $thumb;
        }

        $exif = @exif_read_data($source);

        // imagerotate gira en sentido antihorario.
        $angle = match (is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $thumb;
        }

        $rotated = imagerotate($thumb, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $thumb;
    }

    /**
     * Formato de salida según lo que soporte el servidor. Null si no tiene GD.
     */
    private static function extension(): ?string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagecopyresampled')) {
            return null;
        }

        if (function_exists('imagewebp')) {
            return 'webp';
        }

        return function_exists('imagejpeg') ? 'jpg' : null;
    }

    /**
     * Ruta de la miniatura sin extensión: files/2026/10/abc.jpg → thumbs/2026/10/abc.
     */
    private static function basePath(MediaFile $file): string
    {
        $relative = (string) preg_replace('#^files/#', '', $file->path);

        return self::DIRECTORY.'/'.((string) preg_replace('/\.[^.\/]+$/', '', $relative));
    }

    /**
     * Abrir una imagen grande con GD puede agotar la memoria de PHP, y ese error no se puede
     * capturar. Por eso se calcula antes si cabe; si no cabe, no se genera la miniatura.
     */
    private static function fitsInMemory(int $width, int $height): bool
    {
        $pixels = $width * $height;

        if ($pixels > self::MAX_PIXELS) {
            return false;
        }

        $limit = self::memoryLimit();

        return $limit <= 0
            || memory_get_usage(true) + $pixels * self::BYTES_PER_PIXEL + self::MEMORY_HEADROOM <= $limit;
    }

    /**
     * memory_limit de PHP en bytes. Cero o negativo significa «sin límite».
     */
    private static function memoryLimit(): int
    {
        $value = trim((string) ini_get('memory_limit'));
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
