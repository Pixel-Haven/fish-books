<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeeklySheet extends Model
{
    use SoftDeletes, UsesUuid;

    protected $fillable = [
        'vessel_id',
        'week_start',
        'week_end',
        'label',
        'description',
        'status',
        'total_sales',
        'total_expenses',
        'total_weekly_payout',
        'owner_share',
        'crew_share',
        'processed_at',
        'created_by',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'total_sales' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_weekly_payout' => 'decimal:2',
        'owner_share' => 'decimal:2',
        'crew_share' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function weeklyExpenses()
    {
        return $this->hasMany(WeeklyExpense::class);
    }

    public function crewCredits()
    {
        return $this->hasMany(CrewCredit::class);
    }

    public function weeklyPayouts()
    {
        return $this->hasMany(WeeklyPayout::class);
    }

    /**
     * Scope to get only finalized sheets
     */
    public function scopeFinalized($query)
    {
        return $query->where('status', 'FINALIZED');
    }

    /**
     * Scope to get only draft sheets
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'DRAFT');
    }

    /**
     * Check if sheet is finalized
     */
    public function isFinalized(): bool
    {
        return in_array($this->status, ['FINALIZED', 'PAID']);
    }

    /**
     * Check if sheet is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['DRAFT', 'READY_FOR_APPROVAL']);
    }
}
