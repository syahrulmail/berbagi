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
