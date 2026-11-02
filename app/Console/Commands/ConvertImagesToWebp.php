<?php

namespace App\Console\Commands;

use App\Models\Achievement;
use App\Models\Banner;
use App\Models\Broadcast;
use App\Models\Donation;
use App\Models\PopupBanner;
use App\Models\Program;
use App\Models\Setting;
use App\Services\ImageWebpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Konversi gambar lama di storage menjadi WebP dan perbarui referensi
 * yang tersimpan di database maupun setting.
 *
 * Jalankan dengan --dry-run terlebih dahulu untuk melihat rencana.
 */
class ConvertImagesToWebp extends Command
{
    protected $signature = 'images:webp
        {--dry-run : Tampilkan rencana tanpa mengubah apa pun}
        {--chunk=200 : Jumlah baris yang diproses per batch}';

    protected $description = 'Konversi gambar lama menjadi WebP dan perbarui referensi di database';

    /** @var ImageWebpService */
    protected $images;

    /** @var bool */
    protected $dryRun = false;

    /** @var array<string,int> */
    protected $stats = [
        'converted' => 0,
        'skipped' => 0,
        'missing' => 0,
        'failed' => 0,
    ];

    /** @var array<string,string|null> Cache hasil konversi per path. */
    protected $cache = [];

    public function handle(ImageWebpService $images): int
    {
        $this->images = $images;
        $this->dryRun = (bool) $this->option('dry-run');

        if (! $images->available()) {
            $this->error('GD dengan dukungan WebP tidak tersedia di server ini.');

            return 1;
        }

        if ($this->dryRun) {
            $this->warn('Mode dry-run: tidak ada berkas atau data yang diubah.');
        }

        $this->convertSingleColumn(Banner::class, 'image');
        $this->convertSingleColumn(Achievement::class, 'image');
        $this->convertSingleColumn(PopupBanner::class, 'image');
        $this->convertSingleColumn(Donation::class, 'payment_proof');
        $this->convertSingleColumn(Program::class, 'image');
        $this->convertProgramMedia();
        $this->convertBroadcastMedia();
        $this->convertProfileSettings();
        $this->convertJsonSetting('home_testimonials', 'photo');
        $this->convertPartnerLogos();

        $this->newLine();
        $this->table(['Status', 'Jumlah'], [
            ['Dikonversi', $this->stats['converted']],
            ['Dilewati (sudah WebP/tidak didukung)', $this->stats['skipped']],
            ['Berkas tidak ditemukan', $this->stats['missing']],
            ['Gagal', $this->stats['failed']],
        ]);

        return $this->stats['failed'] > 0 ? 1 : 0;
    }

    /**
     * Konversi satu kolom model yang menyimpan path gambar tunggal.
     */
    protected function convertSingleColumn(string $modelClass, string $column): void
    {
        $model = new $modelClass();
        $table = $model->getTable();

        $modelClass::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy($model->getKeyName())
            ->chunkById((int) $this->option('chunk'), function ($rows) use ($modelClass, $column, $table) {
                foreach ($rows as $row) {
                    $old = (string) $row->{$column};
                    $new = $this->convert($old);

                    if ($new !== null && $new !== $old) {
                        $this->updateColumn($modelClass, $row->getKey(), $column, $new, $table);
                    }
                }
            });
    }

    /**
     * Konversi galeri media program (kolom JSON media).
     */
    protected function convertProgramMedia(): void
    {
        Program::query()
            ->whereNotNull('media')
            ->orderBy('id')
            ->chunkById((int) $this->option('chunk'), function ($rows) use (&$done) {
                foreach ($rows as $program) {
                    $media = $program->media;

                    if (! is_array($media) || $media === []) {
                        continue;
                    }

                    $changed = false;

                    foreach ($media as $index => $item) {
                        $path = is_array($item) ? (string) ($item['path'] ?? '') : (string) $item;

                        if ($path === '') {
                            continue;
                        }

                        $new = $this->convert($path);

                        if ($new !== null && $new !== $path) {
                            if (is_array($item)) {
                                $media[$index]['path'] = $new;
                            } else {
                                $media[$index] = $new;
                            }
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        if (! $this->dryRun) {
                            $program->update(['media' => $media]);
                        }
                                }
                }
            });
    }

    /**
     * Konversi media broadcast hanya bila bertipe gambar.
     */
    protected function convertBroadcastMedia(): void
    {
        Broadcast::query()
            ->whereNotNull('media_path')
            ->where('media_path', '!=', '')
            ->orderBy('id')
            ->chunkById((int) $this->option('chunk'), function ($rows) use (&$done) {
                foreach ($rows as $broadcast) {
                    if (strtolower((string) $broadcast->media_type) !== 'image') {
                        continue;
                    }

                    $old = (string) $broadcast->media_path;
                    $new = $this->convert($old);

                    if ($new !== null && $new !== $old) {
                        if (! $this->dryRun) {
                            $broadcast->update(['media_path' => $new, 'media_type' => 'image']);
                        }
                    }
                }
            });
    }

    /**
     * Konversi foto & tanda tangan yang tersimpan pada setting profil agen.
     */
    protected function convertProfileSettings(): void
    {
        $keys = Setting::query()
            ->where('key', 'like', 'agent_profile_%')
            ->pluck('key');

        foreach ($keys as $key) {
            $raw = Setting::get($key, '{}');
            $profile = json_decode((string) $raw, true);

            if (! is_array($profile)) {
                continue;
            }

            $changed = false;

            foreach (['photo', 'signature'] as $field) {
                $path = (string) ($profile[$field] ?? '');

                if ($path === '') {
                    continue;
                }

                $new = $this->convert($path);

                if ($new !== null && $new !== $path) {
                    $profile[$field] = $new;
                    $changed = true;
                }
            }

            if ($changed && ! $this->dryRun) {
                Setting::set($key, json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }
    }

    /**
     * Konversi gambar pada setting berformat daftar objek JSON.
     */
    protected function convertJsonSetting(string $settingKey, string $field): void
    {
        $raw = Setting::get($settingKey, '[]');
        $items = json_decode((string) $raw, true);

        if (! is_array($items)) {
            return;
        }

        $changed = false;

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $path = (string) ($item[$field] ?? '');

            if ($path === '') {
                continue;
            }

            $new = $this->convert($path);

            if ($new !== null && $new !== $path) {
                $items[$index][$field] = $new;
                $changed = true;
            }
        }

        if ($changed && ! $this->dryRun) {
            Setting::set($settingKey, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * Konversi logo mitra (daftar path atau objek berisi photo).
     */
    protected function convertPartnerLogos(): void
    {
        $raw = Setting::get('home_partner_logos', '[]');
        $items = json_decode((string) $raw, true);

        if (! is_array($items)) {
            return;
        }

        $changed = false;

        foreach ($items as $index => $item) {
            $path = is_array($item) ? (string) ($item['photo'] ?? '') : (string) $item;

            if ($path === '') {
                continue;
            }

            $new = $this->convert($path);

            if ($new === null || $new === $path) {
                continue;
            }

            if (is_array($item)) {
                $items[$index]['photo'] = $new;
            } else {
                $items[$index] = $new;
            }
            $changed = true;
        }

        if ($changed && ! $this->dryRun) {
            Setting::set('home_partner_logos', json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * Konversi satu path relatif. Mengembalikan path baru atau null.
     */
    protected function convert(string $path): ?string
    {
        if (array_key_exists($path, $this->cache)) {
            return $this->cache[$path];
        }

        if ($path === '' || preg_match('#^(https?://|data:)#i', $path)) {
            $this->stats['skipped']++;

            return $this->cache[$path] = null;
        }

        if (! $this->images->isConvertibleExtension($path)) {
            $this->stats['skipped']++;

            return $this->cache[$path] = null;
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'webp') {
            $this->stats['skipped']++;

            return $this->cache[$path] = null;
        }

        if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            $this->stats['missing']++;

            return $this->cache[$path] = null;
        }

        $this->line(($this->dryRun ? '[dry] ' : '') . 'Konversi: ' . $path);

        if ($this->dryRun) {
            $this->stats['converted']++;

            return $this->cache[$path] = $this->guessWebpPath($path);
        }

        $new = $this->images->convertStored($path);

        if ($new === null) {
            $this->stats['failed']++;

            return $this->cache[$path] = null;
        }

        $this->stats['converted']++;

        return $this->cache[$path] = $new;
    }

    /**
     * Tebakan path WebP untuk mode dry-run.
     */
    protected function guessWebpPath(string $path): string
    {
        $directory = dirname($path);

        return ($directory === '.' ? '' : $directory . '/') . pathinfo($path, PATHINFO_FILENAME) . '.webp';
    }

    /**
     * Update kolom model tanpa memicu observer/updated_at yang tidak perlu.
     */
    protected function updateColumn(string $modelClass, $id, string $column, string $value, string $table): void
    {
        if ($this->dryRun) {
            return;
        }

        DB::table($table)->where((new $modelClass())->getKeyName(), $id)->update([$column => $value]);
    }
}
