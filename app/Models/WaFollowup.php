<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaFollowup extends Model
{
    use HasFactory;

    public const SOURCE_HOME = 'home';
    public const SOURCE_PROGRAM = 'program';
    public const SOURCE_AGENT = 'agent';

    protected $fillable = [
        'agen_id',
        'program_id',
        'phone',
        'source',
        'ip_address',
        'user_agent',
    ];

    public function agen()
    {
        return $this->belongsTo(User::class, 'agen_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
