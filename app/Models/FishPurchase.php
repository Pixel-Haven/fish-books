<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class FishPurchase extends Model
{
    use UsesUuid;
    protected $fillable = [
        'trip_id',
        'fish_type_id',
        'kilos',
        'rate_per_kilo',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'kilos' => 'decimal:2',
        'rate_per_kilo' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function fishType()
    {
        return $this->belongsTo(FishType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
