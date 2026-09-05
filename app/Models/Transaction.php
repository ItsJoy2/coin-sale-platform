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
        'order_id',
        'type',
        'amount_mind',
        'amount_usdt',
        'source_user_id',
        'rate_applied',
        'description',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_mind' => 'decimal:8',
            'amount_usdt' => 'decimal:8',
            'rate_applied' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

        protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {

            if (empty($transaction->order_id)) {

                do {
                    $orderId = strtoupper(
                        Str::random(14)
                    );
                } while (
                    self::where('order_id', $orderId)->exists()
                );

                $transaction->order_id = $orderId;
            }
        });
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

    public function isCouponBonus(): bool
    {
        return $this->type === 'coupon_bonus';
    }

    public function isReferralBonus(): bool
    {
        return $this->type === 'referral_bonus';
    }
}
