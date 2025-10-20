<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vessel extends Model
{
    use HasFactory, SoftDeletes, UsesUuid;

    protected $fillable = [
        'name',
        'registration_no',
        'capacity',
        'home_island',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function weeklySheets()
    {
        return $this->hasMany(WeeklySheet::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('registration_no', 'like', "%{$search}%")
              ->orWhere('home_island', 'like', "%{$search}%");
        });
    }
}
