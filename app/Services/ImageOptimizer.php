<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImageOptimizer
{
    /**
     * Lebar maksimum varian per direktori penyimpanan.
     */
    public const WIDTHS = [
        'banners'       => 720,
        'testimonials'  => 320,
        'popup-banners' => 900,
        'partner-logos' => 480,
        'achievements'  => 320,
    ];

    public const QUALITY = 80;

    /**
     * Buat (bila belum ada) varian WebP terkompresi untuk satu berkas.
     *
     * @return string[] path relatif varian yang tersedia
     */
    public function optimize(string $path, bool $force = false): array
    {
        $path = ltrim($path, '/');

        if ($path === '' || preg_match('#^(https?://|data:)#i', $path)) {
            return [];
        }

        $width = $this->widthFor($path);

        if ($width === null) {
            return [];
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return [];
        }

        $variant = $this->variantPath($path, $width);

        if (! $force && $disk->exists($variant)) {
            return [$variant];
        }

        $source = $disk->path($path);
        $target = $disk->path($variant);

        if (! is_file($source) || ! function_exists('imagewebp')) {
            return [];
        }

        $info = @getimagesize($source);

        if (! $info || empty($info[0]) || empty($info[1])) {
            return [];
        }

        $image = @imagecreatefromstring((string) file_get_contents($source));

        if (! $image) {
            return [];
        }

        $widthPx = (int) $info[0];
        $heightPx = (int) $info[1];

        if ($widthPx > $width) {
            $heightPx = max(1, (int) round($heightPx * ($width / $widthPx)));
            $widthPx = $width;
        }

        $canvas = imagecreatetruecolor($widthPx, $heightPx);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $widthPx, $heightPx, (int) $info[0], (int) $info[1]);

        if (! is_dir(dirname($target))) {
            @mkdir(dirname($target), 0755, true);
        }

        $ok = imagewebp($canvas, $target, self::QUALITY);

        imagedestroy($canvas);
        imagedestroy($image);

        if (! $ok || ! is_file($target)) {
            return [];
        }

        // Jangan pakai varian bila ukurannya tidak lebih kecil dari aslinya
        // (mis. logo yang sudah WebP kecil).
        if (filesize($target) >= filesize($source)) {
            @unlink($target);

            return [];
        }

        return [$variant];
    }

    public function widthFor(string $path): ?int
    {
        $dir = strtok($path, '/');

        return self::WIDTHS[$dir] ?? null;
    }

    public function variantPath(string $path, int $width): string
    {
        $path = ltrim($path, '/');
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $base = $ext !== '' ? substr($path, 0, - (strlen($ext) + 1)) : $path;

        return $base . '-' . $width . 'w.webp';
    }
}
