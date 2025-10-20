<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class CrewCredit extends Model
{
    use UsesUuid;
    protected $fillable = [
        'weekly_sheet_id',
        'crew_member_id',
        'amount',
        'description',
        'credit_date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credit_date' => 'datetime',
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
}
