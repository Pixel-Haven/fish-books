<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FishType extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'name',
        'default_rate_per_kilo',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'default_rate_per_kilo' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['current_rate'];

    public function getCurrentRateAttribute()
    {
        return $this->getCurrentRate();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rates()
    {
        return $this->hasMany(FishTypeRate::class);
    }

    public function fishPurchases()
    {
        return $this->hasMany(FishPurchase::class);
    }

    public function billLineItems()
    {
        return $this->hasMany(BillLineItem::class);
    }

    /**
     * Get the current effective rate for this fish type on a given date
     */
    public function getCurrentRate($date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        
        return $this->rates()
            ->where('is_active', true)
            ->where(function ($query) use ($date) {
                $query->whereNull('rate_effective_from')
                    ->orWhere('rate_effective_from', '<=', $date);
            })
            ->where(function ($query) use ($date) {
                $query->whereNull('rate_effective_to')
                    ->orWhere('rate_effective_to', '>=', $date);
            })
            ->orderBy('rate_effective_from', 'desc')
            ->first()?->rate_per_kilo ?? $this->default_rate_per_kilo;
    }
}
