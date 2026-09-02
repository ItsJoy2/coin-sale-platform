<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_percentage',
        'extra_bonus_percentage',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
            'extra_bonus_percentage' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (
            $this->expires_at &&
            $this->expires_at->isPast()
        ) {
            return false;
        }

        if (
            $this->max_uses !== null &&
            $this->used_count >= $this->max_uses
        ) {
            return false;
        }

        return true;
    }
}
