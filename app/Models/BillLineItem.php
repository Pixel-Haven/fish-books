<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class BillLineItem extends Model
{
    use UsesUuid;
    protected $fillable = [
        'bill_id',
        'fish_type_id',
        'quantity',
        'price_per_kilo',
        'line_total',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price_per_kilo' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
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
