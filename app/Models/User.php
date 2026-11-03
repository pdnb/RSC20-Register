<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'role', 'university_id', 'username', 'email', 'auth0_sub', 'phone',
        'password', 'must_change_password', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** Admins can do everything staff can (check-in). */
    public function isStaff(): bool
    {
        return in_array($this->role, [Role::Admin, Role::Staff], true);
    }

    public function isCoordinator(): bool
    {
        return $this->role === Role::Coordinator;
    }
}
