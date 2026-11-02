<?php

namespace App\Console\Commands;

use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--force : Tulis ulang varian yang sudah ada}';

    protected $description = 'Buat varian WebP terkompresi untuk banner, testimoni, logo mitra, dan popup.';

    public function handle(ImageOptimizer $optimizer): int
    {
        $disk = Storage::disk('public');
        $total = 0;

        foreach (array_keys(ImageOptimizer::WIDTHS) as $dir) {
            if (! $disk->exists($dir)) {
                continue;
            }

            foreach ($disk->files($dir) as $file) {
                if (preg_match('#\.(webp|webm)$#i', $file) || preg_match('#-\d+w\.webp$#i', $file)) {
                    continue;
                }

                $variants = $optimizer->optimize($file, (bool) $this->option('force'));

                if ($variants) {
                    $total++;
                    $this->line('ok ' . $file . ' -> ' . implode(', ', $variants));
                }
            }
        }

        $this->info($total . ' gambar diproses.');

        return self::SUCCESS;
    }
}
