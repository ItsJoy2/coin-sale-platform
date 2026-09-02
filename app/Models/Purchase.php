<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'invoice_id',
        'payment_address',
        'coupon_code',
        'usdt_amount',
        'payable_usdt',
        'received_usdt',
        'mind_price',
        'mind_amount',
        'bonus_percentage',
        'bonus_mind',
        'total_mind',
        'slot',
        'tx_hash',
        'status',
        'paid_at',
        'completed_at',
        'failure_reason',
    ];

    protected $casts = [
        'usdt_amount' => 'decimal:8',
        'received_usdt' => 'decimal:8',
        'payable_usdt' => 'decimal:8',
        'mind_price' => 'decimal:8',
        'mind_amount' => 'decimal:8',
        'bonus_percentage' => 'decimal:4',
        'bonus_mind' => 'decimal:8',
        'total_mind' => 'decimal:8',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
