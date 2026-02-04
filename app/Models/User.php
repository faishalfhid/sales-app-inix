<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
    // Helper methods untuk role
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDirektur(): bool
    {
        return $this->role === 'direktur';
    }

    public function isGeneralManager(): bool
    {
        return $this->role === 'general_manager';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function canApprove(): bool
    {
        return in_array($this->role, ['direktur', 'general_manager']);
    }

    // Mendapatkan label role
    public function getRoleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Administrator',
            'direktur' => 'Direktur',
            'general_manager' => 'General Manager',
            'staff' => 'Staff',
            default => 'Unknown',
        };
    }

    // Available roles
    public static function getRoles(): array
    {
        return [
            'admin' => 'Administrator',
            'direktur' => 'Direktur',
            'general_manager' => 'General Manager',
            'staff' => 'Staff',
        ];
    }
}
