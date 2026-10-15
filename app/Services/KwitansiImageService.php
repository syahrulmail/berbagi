<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Facades\Storage;

/**
 * Membuat gambar (PNG) kwitansi donasi memakai GD, agar dapat dilampirkan
 * pada pesan WhatsApp. Rasio menyerupai setengah kertas A4 portrait.
 */
class KwitansiImageService
{
    /** Lebar kanvas (px). */
    protected const WIDTH = 1000;

    /** Tinggi kanvas (px) — rasio ± 1/2 A4 portrait (± 1,414). */
    protected const HEIGHT = 1414;

    protected const MARGIN = 28;

    /** Versi tata letak — naikkan agar gambar lama di-regenerate ulang. */
    protected const VERSION = 2;

    /** @var string */
    protected $font;

    /** @var string */
    protected $fontBold;

    /** @var string  Logo BWA (di public/images). */
    protected $logo;

    /** @var string  Stempel BWA (di public/images). */
    protected $stamp;

    protected $green = [47, 122, 82];
    protected $greenDark = [36, 92, 62];
    protected $ink = [34, 34, 34];
    protected $white = [255, 255, 255];
    protected $muted = [110, 110, 110];
    protected $line = [51, 51, 51];
    protected $soft = [242, 247, 244];

    public function __construct()
    {
        $this->font = resource_path('fonts/DejaVuSans.ttf');
        $this->fontBold = resource_path('fonts/DejaVuSans-Bold.ttf');
        $this->logo = public_path('images/logo-bwa.png');
        $this->stamp = public_path('images/stamp-bwa.png');
    }

    /**
     * Render kwitansi menjadi PNG pada disk "public".
     *
     * @return string|null  Path relatif (mis. "kwitansi/xxx.png") atau null bila gagal.
     */
    public function render(Donation $donation): ?string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagettftext')
            || ! is_file($this->font) || ! is_file($this->fontBold)) {
            return null;
        }

        $data = $this->data($donation);
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        imagefilledrectangle($img, 0, 0, self::WIDTH, self::HEIGHT, $this->color($img, $this->white));

        $this->drawFrame($img);
        $this->drawHeader($img);

        $this->drawLeftColumn($img, $data, self::MARGIN + 18, 688 - 16);
        $this->drawRightColumn($img, $data, 704, self::WIDTH - self::MARGIN - 18);
        $this->drawSignature($img, $data);

        $path = $this->store($donation, $img);
        imagedestroy($img);

        return $path;
    }

    /**
     * Susun data kwitansi dari model donasi.
     */
    protected function data(Donation $donation): array
    {
        $donation->load(['contact', 'items.program', 'program', 'agen']);

        $contact = $donation->contact;
        $wakifName = $contact ? (string) $contact->name : (string) ($donation->donor_info ?: '');
        $phone = $contact ? (string) $contact->phone : '';

        $items = [];
        foreach ($donation->items as $item) {
            $items[] = [
                'label' => $item->program ? $item->program->category_label : ($item->program_category ?: ''),
                'amount' => (float) $item->amount,
            ];
        }
        if (empty($items)) {
            $items[] = [
                'label' => $donation->program ? $donation->program->category_label : '',
                'amount' => (float) $donation->amount,
            ];
        }

        $total = (float) $donation->amount;

        return [
            'wakifName' => $wakifName,
            'pendaftar' => (string) ($donation->donor_info ?: ''),
            'phoneMasked' => mask_phone($phone),
            'items' => $items,
            'total' => $total,
            'terbilang' => ucfirst(trim(terbilang_id($total))) . ' Rupiah',
            'paymentMethod' => $this->paymentMethodLabel($donation->payment_method),
            'paymentDate' => $donation->payment_date ? $donation->payment_date->format('d F Y') : '-',
            'donationDate' => $donation->donation_date ? $donation->donation_date->format('d F Y') : '-',
            'signaturePath' => $this->signaturePath($donation->agen),
        ];
    }

    /**
     * Path filesystem tanda tangan agen (bila ada).
     */
    protected function signaturePath($agen): string
    {
        $rel = agent_signature_path($agen);
        if ($rel === '' || ! Storage::disk('public')->exists($rel)) {
            return '';
        }

        return Storage::disk('public')->path($rel);
    }

    protected function drawFrame($img): void
    {
        $m = self::MARGIN;
        $this->rect($img, $m, $m, self::WIDTH - $m, self::HEIGHT - $m, $this->line, false, 2);
    }

    protected function drawHeader($img): void
    {
        $m = self::MARGIN;
        $x1 = $m + 2;
        $y1 = $m + 2;
        $x2 = self::WIDTH - $m - 2;
        $height = 120;
        $y2 = $y1 + $height;

        imagefilledrectangle($img, $x1, $y1, $x2, $y2, $this->color($img, $this->green));

        // Logo BWA langsung di atas header hijau (PNG transparan).
        $logo = $this->loadImage($this->logo);
        if ($logo) {
            $bh = 76;
            $lw0 = imagesx($logo);
            $lh0 = imagesy($logo);
            $lw = (int) round($bh * $lw0 / max(1, $lh0));
            imagecopyresampled($img, $logo, $x1 + 20, $y1 + (int) round(($height - $bh) / 2), 0, 0, $lw, $bh, $lw0, $lh0);
            imagedestroy($logo);
        } else {
            $this->text($img, 30, $x1 + 22, $y1 + 62, 'BWA', $this->white, true);
            $this->text($img, 9.5, $x1 + 22, $y1 + 84, 'Inovasi Wakaf', $this->white, false);
        }

        $addrX = $x2 - 16;
        $addrY = $y1 + 34;
        foreach ([
            'Jl. Tebet Timur Dalam I, No. 1',
            'Tebet - Jakarta Selatan 12820',
            'Telp. (021) 8350084 - Fax (021) 83796421',
            'admin@wakafalquran.org - www.wakafalquran.org',
        ] as $line) {
            $this->text($img, 9, $addrX, $addrY, $line, $this->white, false, 'right', $x2 - 16);
            $addrY += 19;
        }

        $this->text($img, 21, $x1 + 210, $y1 + 78, 'FORMULIR WAKAF', $this->white, true, 'center', $x2 - 320);
    }

    protected function drawLeftColumn($img, array $data, $x, $right): void
    {
        $width = $right - $x;
        $y = 214;

        $y = $this->wrapped($img, 11, $x, $y, $width,
            'Saya amanahkan wakaf/sedekah Saya kepada Badan Wakaf Al Qur\'an sebagai berikut :',
            $this->ink, false, 26) + 22;

        $rows = [
            ['Nama Wakif / Donatur *', $data['wakifName']],
            ['Nama Pendaftar *', $data['pendaftar']],
            ['Alamat Surat', ''],
            ['Kota / Kabupaten', ''],
            ['No. Handphone', $data['phoneMasked']],
            ['email', ''],
        ];

        $labelW = 232;
        $boxX = $x + $labelW + 12;
        $boxH = 40;
        foreach ($rows as $row) {
            $boxTop = $y;
            $boxBottom = $y + $boxH;
            $this->text($img, 11, $x, $boxBottom - 14, $row[0], $this->ink);
            $this->rect($img, $boxX, $boxTop, $right, $boxBottom, $this->line, false, 1);
            if ($row[1] !== '') {
                $this->text($img, 11, $boxX + 10, $boxBottom - 14, $row[1], $this->ink);
            }
            $y = $boxBottom + 14;
        }

        $y += 14;
        $y = $this->drawItemsTable($img, $data, $x, $right, $y);

        $y += 34;
        $this->text($img, 11, $x, $y, 'Syarat & Ketentuan :', $this->ink, true);
        $y += 26;
        $syarat = [
            'Dana wakaf/sedekah tersebut diatas sudah termasuk : biaya produksi, distribusi, pembinaan, pengembangan dan operasional.',
            'Konfirmasi dana wakaf/sedekah kepada Anda, kami lakukan dalam waktu maksimal 3 (tiga) hari setelah bukti setoran wakaf/sedekah terverifikasi oleh Bagian keuangan kami.',
            'Apabila proyek wakaf/sedekah pilihan anda ternyata telah terpenuhi, maka dengan ini anda menyerahkan kepada kami (BWA) untuk memanfaatkan dana anda dalam pembiayaan proyek wakaf/sedekah BWA lainnya.',
        ];
        foreach ($syarat as $i => $text) {
            $this->text($img, 9.5, $x, $y, ($i + 1) . '.', $this->ink);
            $y = $this->wrapped($img, 9.5, $x + 22, $y, $width - 22, $text, $this->ink, false, 17) + 8;
        }
    }

    protected function drawItemsTable($img, array $data, $x, $right, $top): float
    {
        $width = $right - $x;
        $cols = [0.26, 0.19, 0.13, 0.19, 0.23];
        $colX = [];
        $cursor = $x;
        foreach ($cols as $ratio) {
            $colX[] = $cursor;
            $cursor += (int) round($width * $ratio);
        }
        $colX[] = $right;

        $headerH = 44;
        $rowH = 44;

        imagefilledrectangle($img, $x, $top, $right, $top + $headerH, $this->color($img, $this->green));
        $headers = ['Jenis Donasi', 'Kode', 'Banyak', 'Nilai (Rp)', 'Jumlah (Rp)'];
        for ($i = 0; $i < 5; $i++) {
            $this->text($img, 10, $colX[$i] + 8, $top + $headerH - 16, $headers[$i], $this->white, true);
        }

        $y = $top + $headerH;
        $count = 0;
        foreach ($data['items'] as $item) {
            if ($count > 0) {
                imageline($img, $x, $y, $right, $y, $this->color($img, $this->line));
            }
            $this->text($img, 10, $colX[0] + 8, $y + $rowH - 16, $this->ellipsize($img, 10, $item['label'], $width * $cols[0] - 16), $this->ink);
            $this->text($img, 10, $colX[4], $y + $rowH - 16, number_format($item['amount'], 0, ',', '.'), $this->ink, false, 'right', $right - 8);
            $y += $rowH;
            $count++;
        }
        for ($i = $count; $i < 3; $i++) {
            imageline($img, $x, $y, $right, $y, $this->color($img, $this->line));
            $y += $rowH;
        }

        $totalTop = $y;
        $totalBottom = $y + $rowH;

        // Isi baris total, lalu gambar garis tabel di atasnya.
        imagefilledrectangle($img, $x, $totalTop, $right, $totalBottom, $this->color($img, $this->soft));

        $this->rect($img, $x, $top, $right, $totalBottom, $this->line, false, 1);
        for ($i = 1; $i < 5; $i++) {
            imageline($img, $colX[$i], $top, $colX[$i], $totalTop, $this->color($img, $this->line));
        }
        imageline($img, $x, $top + $headerH, $right, $top + $headerH, $this->color($img, $this->line));
        imageline($img, $x, $totalTop, $right, $totalTop, $this->color($img, $this->line));

        $this->text($img, 10, $colX[0] + 8, $totalBottom - 16, 'Total: ' . $data['terbilang'], $this->ink, true);
        $this->text($img, 10, $colX[4], $totalBottom - 16, number_format($data['total'], 0, ',', '.'), $this->ink, true, 'right', $right - 8);

        return $totalBottom;
    }

    protected function drawRightColumn($img, array $data, $x, $right): void
    {
        $width = $right - $x;
        $y = 232;

        $logo = $this->loadImage($this->logo);
        if ($logo) {
            $lw = 126;
            $lw0 = imagesx($logo);
            $lh0 = imagesy($logo);
            $lh = (int) round($lw * $lh0 / max(1, $lw0));
            $lx = $x + (int) round(($width - $lw) / 2);
            imagecopyresampled($img, $logo, $lx, 214, 0, 0, $lw, $lh, $lw0, $lh0);
            imagedestroy($logo);
            $y = 214 + $lh + 22;
        } else {
            $this->text($img, 46, $x, $y, 'BWA', $this->green, true, 'center', $right);
            $y += 26;
            $this->text($img, 11, $x, $y, 'Inovasi Wakaf', $this->green, false, 'center', $right);
            $y += 40;
        }

        $y = $this->wrapped($img, 9.5, $x, $y, $width,
            '"Wakaf yang Anda tunaikan tak hanya berbuah pahala yang mengalir abadi, namun akan membangkitkan saudara kita meraih impiannya"',
            $this->muted, false, 17) + 28;

        $this->text($img, 9.5, $x, $y, 'Diisi oleh petugas', $this->muted);
        $y += 34;
        $this->text($img, 11, $x, $y, 'Sudah terima uang sebesar :', $this->ink);
        $y += 14;

        $boxBottom = $y + 54;
        $this->rect($img, $x, $y, $right, $boxBottom, $this->line, false, 1);
        $this->text($img, 15, $x, $boxBottom - 18, 'Rp ' . number_format($data['total'], 0, ',', '.'), $this->ink, true, 'right', $right - 10);
        $y = $boxBottom + 30;

        $this->text($img, 11, $x, $y, 'terbilang :', $this->ink);
        $y += 28;
        $y = $this->wrapped($img, 10, $x, $y, $width, $data['terbilang'], $this->ink, false, 17) + 22;

        $this->text($img, 9.5, $x, $y, 'Tanggal Pembayaran : ' . $data['paymentDate'], $this->muted);
        $y += 22;
        $this->text($img, 9.5, $x, $y, 'Metode Pembayaran : ' . $data['paymentMethod'], $this->muted);
        $y += 36;

        $this->text($img, 11, $x, $y, 'dari :', $this->ink);
        $y += 24;
        imageline($img, $x, $y, $right, $y, $this->color($img, $this->muted));
        $this->text($img, 10, $x + 2, $y - 6, $data['wakifName'], $this->ink);
        $y += 40;

        $this->text($img, 9.5, $x, $y, 'Tanggal : ' . $data['donationDate'], $this->ink);
    }

    /**
     * Tanda tangan agen + stempel BWA di kanan bawah kwitansi.
     * Stempel digeser ke kiri agar menimpa bagian kanan tanda tangan.
     */
    protected function drawSignature($img, array $data): void
    {
        $right = 906;

        $sigTop = 1255;
        $sig = $this->loadImage($data['signaturePath'] ?? '');
        if ($sig) {
            $sw = imagesx($sig);
            $sh = imagesy($sig);
            $w = 200;
            $h = (int) round($w * $sh / max(1, $sw));
            if ($h > 108) {
                $h = 108;
                $w = (int) round($h * $sw / max(1, $sh));
            }
            $sigTop = 1314 - $h;
            imagecopyresampled($img, $sig, $right - $w, $sigTop, 0, 0, $w, $h, $sw, $sh);
            imagedestroy($sig);
        }

        $stamp = $this->loadImage($this->stamp);
        if ($stamp) {
            $sw = imagesx($stamp);
            $sh = imagesy($stamp);
            $w = 152;
            $h = (int) round($w * $sh / max(1, $sw));
            $bottom = $sigTop + 30;
            imagecopyresampled($img, $stamp, $right - 30 - $w, $bottom - $h, 0, 0, $w, $h, $sw, $sh);
            imagedestroy($stamp);
        }
    }

    /**
     * Muat gambar (png/jpg/webp) sebagai resource GD dengan dukungan alpha.
     */
    protected function loadImage($path)
    {
        if (! $path || ! is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        switch ($info[2]) {
            case IMAGETYPE_PNG:
                $im = @imagecreatefrompng($path);
                break;
            case IMAGETYPE_JPEG:
                $im = @imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_WEBP:
                $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null;
                break;
            default:
                $im = null;
        }

        if (! $im) {
            return null;
        }

        imagealphablending($im, true);
        imagesavealpha($im, true);

        return $im;
    }

    /**
     * Pastikan gambar kwitansi tersedia, render hanya bila belum ada.
     *
     * @return string|null  Path relatif pada disk "public".
     */
    public function ensure(Donation $donation): ?string
    {
        $rel = $this->pathFor($donation);
        if (Storage::disk('public')->exists($rel)) {
            return $rel;
        }

        return $this->render($donation);
    }

    /**
     * Path relatif gambar (deterministik) untuk donasi.
     */
    protected function pathFor(Donation $donation): string
    {
        $hash = substr(md5(self::VERSION . '|' . $donation->id . '|' . $donation->amount . '|' . (string) $donation->updated_at), 0, 10);

        return 'kwitansi/kwitansi-' . $donation->id . '-' . $hash . '.png';
    }

    /**
     * Simpan gambar ke disk public (root: public/storage) agar dapat disajikan web.
     */
    protected function store(Donation $donation, $img): ?string
    {
        $rel = $this->pathFor($donation);

        ob_start();
        $saved = imagepng($img);
        $binary = ob_get_clean();

        if (! $saved || $binary === false || $binary === '') {
            return null;
        }

        if (! Storage::disk('public')->put($rel, $binary)) {
            return null;
        }

        return $rel;
    }

    protected function paymentMethodLabel($method): string
    {
        $map = [
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'e-wallet' => 'E-Wallet',
        ];

        return $map[$method] ?? '-';
    }

    /* =====================================================
     | UTIL GAMBAR
     | ===================================================== */

    protected function color($img, array $rgb)
    {
        return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    }

    protected function font(bool $bold): string
    {
        return $bold ? $this->fontBold : $this->font;
    }

    protected function textWidth($img, $size, string $text, bool $bold): float
    {
        if ($text === '') {
            return 0;
        }

        $bbox = imagettfbbox($size, 0, $this->font($bold), $text);

        return abs($bbox[2] - $bbox[0]);
    }

    protected function text($img, $size, $x, $baseline, string $text, array $color, bool $bold = false, string $align = 'left', $right = null): void
    {
        if ($text === '') {
            return;
        }

        $col = $this->color($img, $color);
        $width = $this->textWidth($img, $size, $text, $bold);

        if ($align === 'center' && $right !== null) {
            $x = $x + (($right - $x - $width) / 2);
        } elseif ($align === 'right' && $right !== null) {
            $x = $right - $width;
        }

        imagettftext($img, $size, 0, (int) round($x), (int) round($baseline), $col, $this->font($bold), $text);
    }

    protected function wrapped($img, $size, $x, $baseline, $maxWidth, string $text, array $color, bool $bold = false, $lineHeight = 13): float
    {
        foreach ($this->wrap($img, $size, $text, $bold, $maxWidth) as $line) {
            $this->text($img, $size, $x, $baseline, $line, $color, $bold);
            $baseline += $lineHeight;
        }

        return $baseline;
    }

    protected function wrap($img, $size, string $text, bool $bold, $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text));
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $try = $current === '' ? $word : $current . ' ' . $word;
            if ($current === '' || $this->textWidth($img, $size, $try, $bold) <= $maxWidth) {
                $current = $try;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }

    protected function ellipsize($img, $size, string $text, $maxWidth): string
    {
        if ($this->textWidth($img, $size, $text, false) <= $maxWidth) {
            return $text;
        }

        while ($text !== '' && $this->textWidth($img, $size, $text . '...', false) > $maxWidth) {
            $text = mb_substr($text, 0, mb_strlen($text) - 1);
        }

        return $text . '...';
    }

    protected function rect($img, $x1, $y1, $x2, $y2, array $color, bool $filled, $thickness = 1): void
    {
        $col = $this->color($img, $color);
        if ($filled) {
            imagefilledrectangle($img, $x1, $y1, $x2, $y2, $col);

            return;
        }

        imagesetthickness($img, $thickness);
        imagerectangle($img, $x1, $y1, $x2, $y2, $col);
        imagesetthickness($img, 1);
    }
}
