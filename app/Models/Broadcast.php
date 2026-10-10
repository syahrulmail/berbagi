<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    use HasFactory;

    public const MECHANISM_AUTO = 'auto';
    public const MECHANISM_LIMIT = 'limit';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'name',
        'message_template',
        'media_path',
        'media_type',
        'target_branch_id',
        'target_agen_id',
        'target_statuses',
        'target_followups',
        'mechanism',
        'stop_at',
        'limit_count',
        'schedule_type',
        'scheduled_at',
        'interval_min',
        'interval_max',
        'provider',
        'status',
        'total',
        'sent',
        'failed',
        'replies',
        'last_tick_at',
    ];

    protected $casts = [
        'target_statuses' => 'array',
        'target_followups' => 'array',
        'stop_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'last_tick_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targets()
    {
        return $this->hasMany(BroadcastTarget::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_RUNNING, self::STATUS_PAUSED], true);
    }

    public function statusLabel(): string
    {
        $labels = [
            self::STATUS_QUEUED => 'Menunggu',
            self::STATUS_RUNNING => 'Berjalan',
            self::STATUS_PAUSED => 'Dijeda',
            self::STATUS_STOPPED => 'Dihentikan',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_FAILED => 'Gagal',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
