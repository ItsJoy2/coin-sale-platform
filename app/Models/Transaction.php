<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'purchase_id',
        'type',
        'amount_mind',
        'amount_usdt',
        'source_user_id',
        'rate_applied',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_mind' => 'decimal:8',
            'amount_usdt' => 'decimal:2',
            'rate_applied' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function sourceUser()
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    public function isPurchase(): bool
    {
        return $this->type === 'purchase';
    }

    public function isTierBonus(): bool
    {
        return $this->type === 'tier_bonus';
    }

    public function isCouponBonus(): bool
    {
        return $this->type === 'coupon_bonus';
    }

    public function isReferralBonus(): bool
    {
        return $this->type === 'referral_bonus';
    }
}
