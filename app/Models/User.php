<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    public function reportsReviewed(): HasMany
    {
        return $this->hasMany(Report::class, 'reviewed_by');
    }

    public function reportsAgainst(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function bansIssued(): HasMany
    {
        return $this->hasMany(Ban::class, 'banned_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(FriendRequest::class, 'receiver_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user');
    }

    /**
     * Flat single-role accessor.
     *
     * The `users.role` column is a denormalized mirror of the user's spatie
     * role (spatie remains the source of truth for permissions). Prefer the
     * stored column value; fall back to the spatie role name if the column has
     * not been populated yet, so reads never break.
     */
    public function getRoleAttribute(): ?string
    {
        return $this->attributes['role'] ?? $this->getRoleNames()->first();
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

    /**
     * Numeric privilege level derived from the role hierarchy.
     * super_admin(3) > admin(2) > moderator(1) > user(0).
     */
    public function roleRank(): int
    {
        return match (true) {
            $this->isSuperAdmin() => 3,
            $this->hasRole('admin') => 2,
            $this->hasRole('moderator') => 1,
            default => 0,
        };
    }

    /**
     * Whether this user may manage (edit/ban/delete) the given target.
     * A super_admin may manage anyone; otherwise the actor must strictly
     * outrank the target, so staff can never act on peers or higher roles.
     */
    public function outranks(User $other): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roleRank() > $other->roleRank();
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
