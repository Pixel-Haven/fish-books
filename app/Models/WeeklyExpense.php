<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class WeeklyExpense extends Model
{
    use UsesUuid;
    protected $fillable = [
        'weekly_sheet_id',
        'category',
        'amount',
        'description',
        'distributed_amount',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'distributed_amount' => 'decimal:2',
    ];

    public function weeklySheet()
    {
        return $this->belongsTo(WeeklySheet::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only approved expenses
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    /**
     * Scope to get only pending expenses
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }
}
