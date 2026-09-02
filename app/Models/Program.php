<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'WAP' => 'Quran',
        'WAFP' => 'Air Bersih',
        'TCIT' => 'Listrik',
        'WP' => 'Produktif',
        'WKD' => 'Dakwah',
        'IB' => 'Belajar',
        'SK' => 'Kemanusiaan',
        'IS' => 'Infaq',
    ];

    public const CATEGORY_DEFAULT = 'WAP';

    protected $fillable = [
        'name',
        'slug',
        'program_category',
        'category',
        'description',
        'goal_amount',
        'image',
        'video_url',
        'media',
        'show_goal',
        'is_active',
        'terkumpul_publik',
        'suka',
        'klik',
        'suka_riil',
        'klik_riil',
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'media' => 'array',
        'show_goal' => 'boolean',
        'is_active' => 'boolean',
        'terkumpul_publik' => 'decimal:2',
    ];

    public function campaignTags()
    {
        return $this->belongsToMany(CampaignTag::class, 'campaign_tag_program');
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function donationItems()
    {
        return $this->hasMany(DonationItem::class);
    }

    public function totalCollected()
    {
        return $this->donationItems()->sum('amount');
    }

    /**
     * Angka 'Terkumpul' yang ditampilkan publik = input manual terkumpul_publik.
     * Donasi riil TIDAK ikut menambah angka publik ini.
     */
    public function getPublicCollectedAttribute()
    {
        return (float) $this->terkumpul_publik;
    }

    public function getTotalSukaAttribute()
    {
        return (int) $this->suka + (int) $this->suka_riil;
    }

    public function getTotalKlikAttribute()
    {
        return (int) $this->klik + (int) $this->klik_riil;
    }

    public function getCategoryLabelAttribute()
    {
        return self::CATEGORIES[$this->program_category] ?? ($this->program_category ?: '');
    }

    public function getMediaItemsAttribute()
    {
        $media = is_array($this->media) ? $this->media : [];

        $items = array_map(function ($m) {
            $path = (string) ($m['path'] ?? '');

            return [
                'path'  => $path,
                'order' => (int) ($m['order'] ?? 0),
                'url'   => asset_photo_url($path),
            ];
        }, $media);

        usort($items, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return $items;
    }

    public function getImageUrlAttribute()
    {
        // Sampul program diambil dari gambar prioritas pertama (prioritas 0).
        $items = $this->media_items;

        if (count($items)) {
            return $items[0]['url'];
        }

        // Fallback ke field lama (image) untuk program sebelum galeri media.
        $img = $this->image;

        if (!$img) {
            return '';
        }

        return asset_photo_url($img);
    }

    public function getVideoIdAttribute()
    {
        $url = (string) $this->video_url;

        if ($url === '') {
            return '';
        }

        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([\w-]{11})/i', $url, $m)) {
            return $m[1];
        }

        return '';
    }

    public function getVideoEmbedUrlAttribute()
    {
        return $this->video_id !== '' ? 'https://www.youtube-nocookie.com/embed/' . $this->video_id : '';
    }

    public function getMediaSlidesAttribute()
    {
        $slides = [];

        foreach ($this->media_items as $item) {
            $slides[] = [
                'type' => 'image',
                'url'  => $item['url'],
            ];
        }

        $embed = $this->video_embed_url;

        if ($embed !== '') {
            $slides[] = [
                'type'  => 'video',
                'url'   => $embed,
                'thumb' => $this->video_id !== '' ? 'https://i.ytimg.com/vi/' . $this->video_id . '/hqdefault.jpg' : '',
            ];
        }

        return $slides;
    }
}
