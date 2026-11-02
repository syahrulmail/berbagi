<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Cache seluruh setting dalam satu query per request. Halaman publik
     * memanggil Setting::get belasan kali; tanpa ini tiap panggilan
     * menambah satu round-trip ke database.
     */
    protected static ?array $memo = null;

    public static function get(string $key, $default = null)
    {
        if (static::$memo === null) {
            static::$memo = static::query()->pluck('value', 'key')->all();
        }

        return array_key_exists($key, static::$memo) ? static::$memo[$key] : $default;
    }

    public static function flushMemo(): void
    {
        static::$memo = null;
    }

    public static function set(string $key, $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        static::flushMemo();
    }
}
