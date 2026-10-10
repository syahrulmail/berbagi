<?php

namespace App\Services;

/**
 * Merender template pesan WhatsApp.
 *
 * Mendukung dua sintaks:
 *  - Placeholder: [nama] / [nama_kontak] / [nomor] / [nama_agen] (case-insensitive).
 *  - Pilihan acak: {Halo|Hai|Assalamualaikum} dipilih acak satu.
 */
class TemplateRenderer
{
    /**
     * @param  array<string,string>  $vars
     */
    public function render(string $template, array $vars = []): string
    {
        $text = $template;

        foreach ($vars as $key => $value) {
            $text = preg_replace(
                '/\[' . preg_quote((string) $key, '/') . '\]/i',
                str_replace('$', '\\$', (string) $value),
                $text
            );
        }

        return preg_replace_callback('/\{([^{}]+)\}/', function ($matches) {
            $options = explode('|', $matches[1]);

            if (count($options) <= 1) {
                return $matches[0];
            }

            return trim($options[array_rand($options)]);
        }, $text);
    }

    /**
     * Status apakah template mengandung placeholder yang belum terisi.
     */
    public function hasUnresolvedPlaceholder(string $template): bool
    {
        return (bool) preg_match('/\[[a-z_]+\]/i', $template);
    }
}
