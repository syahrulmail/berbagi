<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'city',
        'supervisor_id',
        'target_amount',
        'is_active',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function agents()
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function totalDonation($year = null, $month = null)
    {
        $query = $this->donations()->where('donation_date', '<=', now()->endOfDay());

        if ($year) {
            $query->whereYear('donation_date', $year);
        }
        if ($month) {
            $query->whereMonth('donation_date', $month);
        }

        return $query->sum('amount');
    }
}
