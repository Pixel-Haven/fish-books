<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class TripAssignment extends Model
{
    use UsesUuid;
    protected $fillable = [
        'trip_id',
        'crew_member_id',
        'role',
        'helper_ratio',
        'kilos_assigned',
        'created_by',
    ];

    protected $casts = [
        'helper_ratio' => 'decimal:2',
        'kilos_assigned' => 'decimal:2',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
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
     * Get the weight/units for this role assignment
     */
    public function getWeight(): float
    {
        return match ($this->role) {
            'DIVING' => 1.0,
            'BAITING', 'FISHING', 'CHUMMER', 'HELPER' => 0.5,
            'SPECIAL' => 0.5 * ($this->helper_ratio ?? 1.0),
            default => 0.5,
        };
    }

    /**
     * Check if this is a diving role
     */
    public function isDiving(): bool
    {
        return $this->role === 'DIVING';
    }

    /**
     * Get kilos assigned for baseline calculations (baiting and fishing only)
     */
    public function getBaselineKilos(): float
    {
        return in_array($this->role, ['BAITING', 'FISHING']) ? 4.0 : 0.0;
    }
}
