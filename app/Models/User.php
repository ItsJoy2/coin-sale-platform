<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'wallet_address',
        'password',
        'email',
        'name',
        'address',
        'referral_code',
        'referred_id',
        'mind_balance',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'mind_balance' => 'decimal:8',
        ];
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function referralTransactions()
    {
        return $this->hasMany(Transaction::class, 'source_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
