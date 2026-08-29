<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'icon',
        'image',
        'color',
        'value',
        'label',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute()
    {
        $img = $this->image;
        if (!$img) {
            return '';
        }
        if (Str::startsWith($img, ['http://', 'https://', '/', 'data:'])) {
            return $img;
        }
        return asset('storage/' . ltrim($img, '/'));
    }

    public function numericParts(): array
    {
        $value = trim((string) $this->value);
        $prefix = '';
        $suffix = '';
        $number = null;
        $decimals = 0;

        if (preg_match('/\d[\d.,]*/', $value, $m)) {
            $token = $m[0];
            $pos = strpos($value, $token);
            $prefix = trim(substr($value, 0, $pos));
            $suffix = trim(substr($value, $pos + strlen($token)));

            $hasComma = strpos($token, ',') !== false;
            $hasDot = strpos($token, '.') !== false;

            if ($hasComma && $hasDot) {
                $decimal = substr($token, strrpos($token, ',') + 1);
                $decimals = strlen($decimal);
                $whole = str_replace(',', '', $token);
                $whole = str_replace('.', '', $whole);
                $number = (float) ($whole . '.' . $decimal);
            } elseif ($hasComma) {
                $decimal = substr($token, strpos($token, ',') + 1);
                $decimals = strlen($decimal);
                $number = (float) str_replace(',', '.', $token);
            } else {
                $number = (float) str_replace('.', '', $token);
            }
        }

        return compact('prefix', 'number', 'decimals', 'suffix');
    }
}
