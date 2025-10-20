<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class FishTypeRate extends Model
{
    use UsesUuid;
    protected $fillable = [
        'fish_type_id',
        'rate_per_kilo',
        'rate_effective_from',
        'rate_effective_to',
        'is_active',
    ];

    protected $casts = [
        'rate_per_kilo' => 'decimal:2',
        'rate_effective_from' => 'date',
        'rate_effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function fishType()
    {
        return $this->belongsTo(FishType::class);
    }

    /**
     * Scope to get only active rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get rates effective on a specific date
     */
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('rate_effective_from')
              ->orWhere('rate_effective_from', '<=', $date);
        })
        ->where(function ($q) use ($date) {
            $q->whereNull('rate_effective_to')
              ->orWhere('rate_effective_to', '>=', $date);
        });
    }
}
