<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastTarget extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_STOPPED = 'stopped';

    protected $fillable = [
        'broadcast_id',
        'contact_id',
        'provider',
        'status',
        'rendered_message',
        'response',
        'attempts',
        'scheduled_at',
        'sent_at',
        'error',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function broadcast()
    {
        return $this->belongsTo(Broadcast::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
