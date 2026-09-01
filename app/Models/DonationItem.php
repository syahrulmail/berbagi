<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'donation_id',
        'program_id',
        'program_category',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
