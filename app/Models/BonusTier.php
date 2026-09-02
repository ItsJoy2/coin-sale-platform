<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_amount_usd',
        'max_amount_usd',
        'bonus_percentage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_amount_usd' => 'decimal:2',
            'max_amount_usd' => 'decimal:2',
            'bonus_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
