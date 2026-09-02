<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get actual typed value
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {

            'integer' => (int) $this->value,

            'decimal' => (float) $this->value,

            'boolean' => filter_var(
                $this->value,
                FILTER_VALIDATE_BOOLEAN
            ),

            'json' => json_decode(
                $this->value,
                true
            ),

            default => $this->value,
        };
    }
}
