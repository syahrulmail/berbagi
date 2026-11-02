<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Konversi gambar unggahan menjadi WebP memakai GD.
 *
 * Prinsip utama: jangan pernah menghilangkan unggahan pengguna. Bila GD/WebP
 * tidak tersedia atau berkas tidak dapat dibaca, berkas asli tetap disimpan
 * seperti perilaku lama.
 */
class ImageWebpService
{
    /** Kualitas WebP (0-100). */
    public const QUALITY = 82;

    /** Sisi terpanjang maksimum (px); 0 = tanpa penskalaan. */
    public const MAX_DIMENSION = 2400;

    /**
     * Batas total piksel yang diproses. Gambar raksasa (mis. panorama/foto
     * 48MP) dapat menghabiskan memori PHP saat didekode GD, jadi berkas
     * seperti itu dibiarkan apa adanya agar upload tidak gagal.
     */
    public const MAX_PIXELS = 40000000;

    /**
     * Simpan gambar unggahan sebagai WebP pada disk.
     *
     * @return string|null  Path relatif berkas tersimpan.
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): ?string
    {
        $path = $this->convertUploadedToTempWebp($file);

        if ($path === null) {
            return $file->store($directory, $disk) ?: null;
        }

        $target = trim($directory, '/') . '/' . $this->randomName();

        try {
            $stream = fopen($path, 'rb');
            $stored = Storage::disk($disk)->put($target, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        } finally {
            @unlink($path);
        }

        if (! $stored) {
            return $file->store($directory, $disk) ?: null;
        }

        return $target;
    }

    /**
     * Konversi berkas yang sudah ada di disk. Berkas lama dihapus bila
     * konversi berhasil.
     *
     * @return string|null  Path relatif baru, atau null bila tidak dikonversi.
     */
    public function convertStored(string $relativePath, string $disk = 'public'): ?string
    {
        $relativePath = ltrim($relativePath, '/');

        if ($relativePath === '' || ! $this->isConvertibleExtension($relativePath)) {
            return null;
        }

        // Sudah WebP: tidak ada yang perlu dikonversi.
        if (strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) === 'webp') {
            return null;
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($relativePath)) {
            return null;
        }

        $source = $storage->path($relativePath);
        $temp = $this->convertFileToTempWebp($source);

        if ($temp === null) {
            return null;
        }

        $target = $this->replaceExtension($relativePath);

        try {
            $stream = fopen($temp, 'rb');
            $stored = $storage->put($target, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        } finally {
            @unlink($temp);
        }

        if (! $stored) {
            return null;
        }

        if ($target !== $relativePath && $storage->exists($relativePath)) {
            $storage->delete($relativePath);
        }

        return $target;
    }

    /**
     * Apakah GD dengan dukungan WebP tersedia.
     */
    public function available(): bool
    {
        return function_exists('imagewebp')
            && function_exists('imagecreatefromstring')
            && function_exists('getimagesize');
    }

    /**
     * Ekstensi yang makna gambar raster dan layak dikonversi.
     */
    public function isConvertibleExtension(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }

    /**
     * Konversi UploadedFile ke berkas WebP sementara.
     */
    protected function convertUploadedToTempWebp(UploadedFile $file): ?string
    {
        if (! $this->available()) {
            return null;
        }

        $path = $file->getRealPath();

        if ($path === false || ! is_file($path)) {
            return null;
        }

        return $this->convertFileToTempWebp($path);
    }

    /**
     * Konversi berkas gambar apa pun ke WebP sementara.
     */
    protected function convertFileToTempWebp(string $path): ?string
    {
        if (! $this->available() || ! is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);

        if (! $info || empty($info[0]) || empty($info[1])) {
            return null;
        }

        if (((int) $info[0] * (int) $info[1]) > self::MAX_PIXELS) {
            return null;
        }

        // GIF animasi tidak dapat ditulis ulang oleh GD tanpa kehilangan
        // animasi. Biarkan berkas asli dipakai agar tidak ada regresi.
        if (($info[2] ?? 0) === IMAGETYPE_GIF && $this->isAnimatedGif($path)) {
            return null;
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        if (! $image) {
            return null;
        }

        if (($info[2] ?? 0) === IMAGETYPE_JPEG) {
            $image = $this->applyOrientation($image, $path);
        }

        $image = $this->normalizeCanvas($image, (int) $info[0], (int) $info[1]);

        $temp = $this->tempWebpPath();

        // Latar transparan untuk mendukung PNG/GIF/WebP beralfa.
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $ok = @imagewebp($image, $temp, self::QUALITY);

        imagedestroy($image);

        if (! $ok || ! is_file($temp) || filesize($temp) === 0) {
            @unlink($temp);

            return null;
        }

        return $temp;
    }

    /**
     * Terapkan orientasi EXIF agar foto ponsel tidak terbalik.
     *
     * @param  \GdImage|resource  $image
     * @return \GdImage|resource
     */
    protected function applyOrientation($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        if ($orientation < 2 || $orientation > 8) {
            return $image;
        }

        $rotated = $image;

        switch ($orientation) {
            case 2:
                imageflip($rotated, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $rotated = imagerotate($image, 180, 0);
                break;
            case 4:
                imageflip($rotated, IMG_FLIP_VERTICAL);
                break;
            case 5:
                $rotated = imagerotate($image, -90, 0);
                imageflip($rotated, IMG_FLIP_HORIZONTAL);
                break;
            case 6:
                $rotated = imagerotate($image, -90, 0);
                break;
            case 7:
                $rotated = imagerotate($image, 90, 0);
                imageflip($rotated, IMG_FLIP_HORIZONTAL);
                break;
            case 8:
                $rotated = imagerotate($image, 90, 0);
                break;
        }

        if ($rotated !== $image) {
            imagedestroy($image);
        }

        return $rotated ?: $image;
    }

    /**
     * Perkecil gambar bila melebihi batas dimensi.
     *
     * @param  \GdImage|resource  $image
     * @return \GdImage|resource
     */
    protected function resizeIfNeeded($image, int $width, int $height)
    {
        $max = (int) self::MAX_DIMENSION;

        if ($max <= 0 || ($width <= $max && $height <= $max)) {
            return $image;
        }

        $scale = min($max / $width, $max / $height);

        return $this->copyToCanvas($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
    }

    /**
     * Normalisasi ke kanvas truecolor (24/32-bit).
     *
     * GD dapat menulis WebP 0 byte untuk gambar palet (GIF/PNG 8-bit) pada
     * sebagian build. Bila perlu penskalaan, kanvas sekaligus menampung hasil
     * resize sehingga tidak ada dua salinan memori besar.
     *
     * @param  \GdImage|resource  $image
     * @return \GdImage|resource
     */
    protected function normalizeCanvas($image, int $width, int $height)
    {
        $max = (int) self::MAX_DIMENSION;

        if ($max > 0 && ($width > $max || $height > $max)) {
            return $this->resizeIfNeeded($image, $width, $height);
        }

        return $this->copyToCanvas($image, $width, $height);
    }

    /**
     * Salin gambar ke kanvas truecolor baru beralfa.
     *
     * @param  \GdImage|resource  $image
     * @return \GdImage|resource
     */
    protected function copyToCanvas($image, int $width, int $height)
    {
        $width = max(1, $width);
        $height = max(1, $height);

        $canvas = imagecreatetruecolor($width, $height);

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        imagedestroy($image);

        return $canvas;
    }

    /**
     * Deteksi GIF animasi (lebih dari satu frame).
     */
    protected function isAnimatedGif(string $path): bool
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return false;
        }

        // NETSCAPE2.0 menandai GIF berulang; hitung juga penanda frame.
        return substr_count($contents, "\x00\x21\xF9\x04") > 1
            || strpos($contents, 'NETSCAPE2.0') !== false;
    }

    /**
     * Ganti ekstensi path relatif menjadi .webp.
     */
    protected function replaceExtension(string $path): string
    {
        $directory = dirname($path);

        return ($directory === '.' ? '' : $directory . '/') . pathinfo($path, PATHINFO_FILENAME) . '.webp';
    }

    /**
     * Nama berkas acak dengan ekstensi .webp.
     */
    protected function randomName(): string
    {
        return Str::random(40) . '.webp';
    }

    /**
     * Path sementara berekstensi .webp (GD menulis berdasarkan ekstensi).
     */
    protected function tempWebpPath(): string
    {
        $base = tempnam(sys_get_temp_dir(), 'webp_');

        if ($base === false) {
            return sys_get_temp_dir() . '/webp_' . Str::random(16) . '.webp';
        }

        @unlink($base);

        return $base . '.webp';
    }
}
