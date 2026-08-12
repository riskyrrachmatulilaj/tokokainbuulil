<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public const ROLE_ADMIN = 'admin';
    public const ROLE_KASIR = 'kasir';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKasir(): bool
    {
        return $this->role === self::ROLE_KASIR;
    }

    public function createdDebts()
    {
        return $this->hasMany(Debt::class, 'created_by');
    }

    public function createdInstallments()
    {
        return $this->hasMany(Installment::class, 'created_by');
    }

    public function createdCollectivePayments()
    {
        return $this->hasMany(CollectivePayment::class, 'created_by');
    }

    public function createdReceivables()
    {
        return $this->hasMany(Receivable::class, 'created_by');
    }

    public function createdReceivableInstallments()
    {
        return $this->hasMany(ReceivableInstallment::class, 'created_by');
    }

    public function createdReceivableCollectivePayments()
    {
        return $this->hasMany(ReceivableCollectivePayment::class, 'created_by');
    }

    public function createdSales()
    {
        return $this->hasMany(Sale::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            app(\App\Services\ActivityLogService::class)->log(
                'Pengguna',
                'create',
                "Membuat akun pengguna baru '{$user->name}' ({$user->email})",
                $user,
                ['name' => $user->name, 'email' => $user->email, 'role' => $user->role]
            );
        });

        static::updated(function (User $user) {
            app(\App\Services\ActivityLogService::class)->log(
                'Pengguna',
                'update',
                "Mengubah data akun pengguna '{$user->name}'",
                $user
            );
        });

        static::deleted(function (User $user) {
            app(\App\Services\ActivityLogService::class)->log(
                'Pengguna',
                'delete',
                "Menghapus akun pengguna '{$user->name}' ({$user->email})",
                $user,
                ['name' => $user->name, 'email' => $user->email]
            );
        });
    }
}
