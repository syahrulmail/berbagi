<?php

if (! function_exists('assetv')) {
    /**
     * asset() dengan versi query dari mtime file, untuk
     * menghindari CSS/JS lama di-cache browser/CDN.
     *
     * @param  string  $path
     * @return string
     */
    function assetv($path)
    {
        $v = @filemtime(public_path($path));

        return asset($path) . ($v ? '?v=' . $v : '');
    }
}

if (! function_exists('asset_photo_url')) {
    /**
     * Ubah path foto (relatif ke storage/public) menjadi URL publik.
     * Path absolut/URL sudah jadi dikembalikan apa adanya.
     */
    function asset_photo_url($path)
    {
        if (empty($path)) {
            return '';
        }

        if (preg_match('#^(https?://|/|data:)#i', $path)) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}

if (! function_exists('terbilang_id')) {
    /**
     * Ubah angka menjadi terbilang bahasa Indonesia (mis. 1500000 => "satu juta lima ratus ribu").
     */
    function terbilang_id($number)
    {
        $number = (int) round((float) $number);

        if ($number < 0) {
            return 'minus ' . terbilang_id(abs($number));
        }

        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($number < 12) {
            return $satuan[$number];
        }

        if ($number < 20) {
            return terbilang_id($number - 10) . ' belas';
        }

        if ($number < 100) {
            $sisa = $number % 10;

            return terbilang_id((int) ($number / 10)) . ' puluh' . ($sisa ? ' ' . terbilang_id($sisa) : '');
        }

        if ($number < 200) {
            $sisa = $number % 100;

            return 'seratus' . ($sisa ? ' ' . terbilang_id($sisa) : '');
        }

        if ($number < 1000) {
            $sisa = $number % 100;

            return terbilang_id((int) ($number / 100)) . ' ratus' . ($sisa ? ' ' . terbilang_id($sisa) : '');
        }

        if ($number < 2000) {
            $sisa = $number % 1000;

            return 'seribu' . ($sisa ? ' ' . terbilang_id($sisa) : '');
        }

        if ($number < 1000000) {
            $sisa = $number % 1000;

            return terbilang_id((int) ($number / 1000)) . ' ribu' . ($sisa ? ' ' . terbilang_id($sisa) : '');
        }

        if ($number < 1000000000) {
            $sisa = $number % 1000000;

            return terbilang_id((int) ($number / 1000000)) . ' juta' . ($sisa ? ' ' . terbilang_id($sisa) : '');
        }

        if ($number < 1000000000000) {
            $sisa = $number % 1000000000;

            return terbilang_id((int) ($number / 1000000000)) . ' miliar' . ($sisa ? ' ' . terbilang_id($sisa) : '');
        }

        $sisa = $number % 1000000000000;

        return terbilang_id((int) ($number / 1000000000000)) . ' triliun' . ($sisa ? ' ' . terbilang_id($sisa) : '');
    }
}

if (! function_exists('mask_phone')) {
    /**
     * Sembunyikan 4 digit terakhir nomor telepon dengan tanda bintang,
     * mempertahankan format asli (spasi, tanda hubung).
     */
    function mask_phone($phone)
    {
        $phone = (string) $phone;

        if ($phone === '') {
            return '';
        }

        $chars = preg_split('//u', $phone, -1, PREG_SPLIT_NO_EMPTY);

        if ($chars === false) {
            return $phone;
        }

        $masked = 0;

        for ($i = count($chars) - 1; $i >= 0 && $masked < 4; $i--) {
            if (ctype_digit($chars[$i])) {
                $chars[$i] = '*';
                $masked++;
            }
        }

        return implode('', $chars);
    }
}

if (! function_exists('kwitansi_wa_url')) {
    /**
     * URL WhatsApp (wa.me) untuk mengirim ringkasan kwitansi donasi ke nomor
     * donatur. Kosong bila donatur tidak memiliki nomor telepon.
     */
    function kwitansi_wa_url($donation)
    {
        $contact = $donation->contact;
        $phone = $contact ? (string) $contact->phone : '';

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits !== '' && $digits[0] === '0') {
            $digits = '62' . substr($digits, 1);
        }

        if ($digits === '') {
            return '';
        }

        $wakif = $contact ? (string) $contact->name : (string) ($donation->donor_info ?: '');
        $total = (float) $donation->amount;
        $date = $donation->donation_date ? $donation->donation_date->format('d/m/Y') : '-';

        $lines = [
            '*Kwitansi Donasi - BWA*',
            '',
            'Nama : ' . ($wakif !== '' ? $wakif : '-'),
            'Tanggal : ' . $date,
            'Total : Rp ' . number_format($total, 0, ',', '.'),
            'Terbilang : ' . ucfirst(trim(terbilang_id($total))) . ' Rupiah',
            '',
            'Terima kasih atas wakaf/sedekah Anda.',
        ];

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode(implode("\n", $lines));
    }
}
