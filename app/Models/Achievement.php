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
}
