<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use SoftDeletes, UsesUuid;

    protected $fillable = [
        'trip_id',
        'bill_type',
        'bill_no',
        'vendor',
        'description',
        'amount',
        'bill_date',
        'payment_status',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bill_date' => 'date',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function lineItems()
    {
        return $this->hasMany(BillLineItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get bills by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('bill_type', $type);
    }

    /**
     * Scope to get paid bills
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'PAID');
    }

    /**
     * Scope to get unpaid bills
     */
    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'UNPAID');
    }
}
