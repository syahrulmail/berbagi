<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_AGEN = 'agen';
    public const ROLE_DONATUR = 'donatur';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'slug',
        'email',
        'password',
        'role',
        'branch_id',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class, 'agen_id');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'agen_id');
    }

    public function supervisedBranches()
    {
        return $this->hasMany(Branch::class, 'supervisor_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isAgen(): bool
    {
        return $this->role === self::ROLE_AGEN;
    }

    public function isDonatur(): bool
    {
        return $this->role === self::ROLE_DONATUR;
    }

    public static function uniqueSlug(string $username, $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($username);

        if (empty($base)) {
            $base = 'user-' . strtolower(\Illuminate\Support\Str::random(6));
        }

        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->where('id', '!=', $ignoreId)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    public function roleLabel(): string
    {
        $labels = [
            self::ROLE_ADMIN => 'Admin Super',
            self::ROLE_SUPERVISOR => 'Supervisor',
            self::ROLE_AGEN => 'Agen',
            self::ROLE_DONATUR => 'Donatur',
        ];

        return $labels[$this->role] ?? $this->role;
    }
}
