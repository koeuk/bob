<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'avatar',
        'cover',
        'last_active_at',
        'password',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getAvatarAttribute(?string $value): ?string
    {
        if (! $value) return null;
        if (str_starts_with($value, 'http')) {
            $path = preg_replace('#^https?://[^/]+/storage/#', '', $value);
            return Storage::url($path);
        }
        return Storage::url($value);
    }

    public function getCoverAttribute(?string $value): ?string
    {
        if (! $value) return null;
        if (str_starts_with($value, 'http')) {
            $path = preg_replace('#^https?://[^/]+/storage/#', '', $value);
            return Storage::url($path);
        }
        return Storage::url($value);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function bans(): HasMany
    {
        return $this->hasMany(Ban::class);
    }

    public function reportsFiled(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'admin_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user');
    }

    /**
     * Backward-compatible single-role accessor.
     *
     * The role system is backed by spatie/laravel-permission (a user can hold
     * one role here), but the API/frontend still expect a flat `user.role`
     * string. This exposes the user's role name. NOTE: not in $appends to avoid
     * an N+1 when serializing many users (e.g. post authors in the feed) —
     * controllers that need it eager-load `roles` and ->append('role').
     */
    public function getRoleAttribute(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    public function isModerator(): bool
    {
        return $this->hasAnyRole(['moderator', 'admin', 'super_admin']);
    }

    public function isBanned(): bool
    {
        return $this->bans()
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
