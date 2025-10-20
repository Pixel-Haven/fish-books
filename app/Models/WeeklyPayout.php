<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class WeeklyPayout extends Model
{
    use UsesUuid;
    protected $fillable = [
        'weekly_sheet_id',
        'crew_member_id',
        'base_amount',
        'credit_deduction',
        'final_amount',
        'is_paid',
        'paid_at',
        'payment_reference',
        'created_by',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'credit_deduction' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function weeklySheet()
    {
        return $this->belongsTo(WeeklySheet::class);
    }

    public function crewMember()
    {
        return $this->belongsTo(CrewMember::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only paid payouts
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope to get only unpaid payouts
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(string $reference = null): void
    {
        $this->update([
            'is_paid' => true,
            'paid_at' => now(),
            'payment_reference' => $reference,
        ]);
    }
}
