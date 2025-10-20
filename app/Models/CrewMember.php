<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrewMember extends Model
{
    use HasFactory, SoftDeletes, UsesUuid;

    protected $fillable = [
        'name',
        'local_name',
        'active',
        'phone',
        'id_card_no',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the user who created this crew member
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all trip assignments for this crew member
     */
    public function tripAssignments()
    {
        return $this->hasMany(TripAssignment::class);
    }

    /**
     * Get all payouts for this crew member
     */
    public function weeklyPayouts()
    {
        return $this->hasMany(WeeklyPayout::class);
    }

    /**
     * Get all credits for this crew member
     */
    public function crewCredits()
    {
        return $this->hasMany(CrewCredit::class);
    }

    /**
     * Scope to get only active crew members
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to search crew members
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('local_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}
