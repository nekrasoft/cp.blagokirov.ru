<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_READONLY_ADMIN = 'readonly_admin';

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
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    public static function adminRoleOptions(): array
    {
        return [
            self::ROLE_ADMIN => 'Администратор',
            self::ROLE_READONLY_ADMIN => 'Админ только чтение',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && $this->canReadAdminPanel();
    }

    public function canReadAdminPanel(): bool
    {
        return $this->is_active !== false
            && in_array($this->role ?? self::ROLE_ADMIN, [
                self::ROLE_ADMIN,
                self::ROLE_READONLY_ADMIN,
            ], true);
    }

    public function canWriteAdminPanel(): bool
    {
        return $this->is_active !== false
            && ($this->role ?? self::ROLE_ADMIN) === self::ROLE_ADMIN;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
