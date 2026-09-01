<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    public const STATUS_PROSPECT = 'prospect';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_DONATED = 'donated';
    public const STATUS_CHURNED = 'churned';

    protected $fillable = [
        'name',
        'phone',
        'status',
        'agen_id',
        'branch_id',
        'notes',
    ];

    public function agen()
    {
        return $this->belongsTo(User::class, 'agen_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function whatsappMessages()
    {
        return $this->hasMany(WhatsappMessage::class);
    }

    public function statusLabel(): string
    {
        $labels = [
            self::STATUS_PROSPECT => 'Prospek',
            self::STATUS_CONTACTED => 'Simpan',
            self::STATUS_DONATED => 'Wakif',
            self::STATUS_CHURNED => 'Stop',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
