<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'goal_amount',
        'image',
        'is_active',
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function campaignTags()
    {
        return $this->belongsToMany(CampaignTag::class, 'campaign_tag_program');
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function totalCollected()
    {
        return $this->donations()->sum('amount');
    }

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
