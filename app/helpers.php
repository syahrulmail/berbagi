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
