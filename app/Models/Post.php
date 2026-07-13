<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'user_id',
        'body',
        'status',
        'image',
        'images',
        'feeling',
        'visibility',
        'shared_post_id',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    /**
     * Whether the given user may view this post.
     * Hidden posts and private posts are only visible to their author.
     */
    public function isVisibleTo(?User $user): bool
    {
        if ($this->user_id === $user?->id) {
            return true;
        }

        return $this->status !== 'hidden' && $this->visibility !== 'private';
    }

    private function normalizeStorageUrl(?string $value): ?string
    {
        if (! $value) return null;
        if (str_starts_with($value, 'http')) {
            $path = preg_replace('#^https?://[^/]+/storage/#', '', $value);
            return Storage::url($path);
        }
        return Storage::url($value);
    }

    public function getImageAttribute(?string $value): ?string
    {
        return $this->normalizeStorageUrl($value);
    }

    public function getImagesAttribute(mixed $value): ?array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (! is_array($decoded)) return null;
        return array_map(fn ($v) => $this->normalizeStorageUrl($v), $decoded);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sharedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'shared_post_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Post::class, 'shared_post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
