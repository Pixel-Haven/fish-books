<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes, UsesUuid;

    protected $fillable = [
        'vessel_id',
        'weekly_sheet_id',
        'date',
        'day_of_week',
        'is_fishing_day',
        'status',
        'total_sales',
        'balance',
        'net_total',
        'owner_share',
        'crew_share',
        'notes',
        'created_by',
        'closed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'is_fishing_day' => 'boolean',
        'total_sales' => 'decimal:2',
        'balance' => 'decimal:2',
        'net_total' => 'decimal:2',
        'owner_share' => 'decimal:2',
        'crew_share' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function weeklySheet()
    {
        return $this->belongsTo(WeeklySheet::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fishPurchases()
    {
        return $this->hasMany(FishPurchase::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function tripAssignments()
    {
        return $this->hasMany(TripAssignment::class);
    }

    /**
     * Scope to get only ongoing trips
     */
    public function scopeOngoing($query)
    {
        return $query->where('status', 'ONGOING');
    }

    /**
     * Scope to get only closed trips
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'CLOSED');
    }

    /**
     * Scope to get only draft trips
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'DRAFT');
    }

    /**
     * Scope to filter trips by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Check if trip is closed
     */
    public function isClosed(): bool
    {
        return $this->status === 'CLOSED';
    }

    /**
     * Check if trip is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['DRAFT', 'ONGOING']);
    }
}
