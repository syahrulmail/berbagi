<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignTag extends Model
{
    use HasFactory;

    public const DEFAULT_TAG_SLUGS = ['bantuan-ummat', 'program-dai', 'wakaf-al-quran', 'wakaf-mushaf'];

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'campaign_tag_program');
    }
}
