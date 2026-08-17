<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'image',
        'url',
        'label_color',
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
        if (\Illuminate\Support\Str::startsWith($img, ['http://', 'https://', '/', 'data:'])) {
            return $img;
        }
        return asset('storage/' . ltrim($img, '/'));
    }
}
