<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'agen_id',
        'program_id',
        'contact_id',
        'amount',
        'donation_date',
        'payment_method',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'donation_date' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function agen()
    {
        return $this->belongsTo(User::class, 'agen_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
