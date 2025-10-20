<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, UsesUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is an owner
     */
    public function isOwner(): bool
    {
        return $this->role === 'OWNER';
    }

    /**
     * Check if user is a manager
     */
    public function isManager(): bool
    {
        return $this->role === 'MANAGER';
    }

    /**
     * Trips created by this user
     */
    public function trips()
    {
        return $this->hasMany(Trip::class, 'created_by');
    }

    /**
     * Expenses created by this user
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    /**
     * Crew members created by this user
     */
    public function crewMembers()
    {
        return $this->hasMany(CrewMember::class, 'created_by');
    }

    /**
     * Vessels created by this user
     */
    public function vessels()
    {
        return $this->hasMany(Vessel::class, 'created_by');
    }
}
