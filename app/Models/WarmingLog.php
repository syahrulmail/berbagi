<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarmingLog extends Model
{
    use HasFactory;

    public const DIRECTION_OUT = 'out';
    public const DIRECTION_IN = 'in';

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'from_phone',
        'to_phone',
        'message',
        'provider',
        'direction',
        'response',
    ];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
