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

    /**
     * Reduce any stored/submitted image reference to a bare disk path.
     *
     * Clients echo back the *display* URL (e.g. `/storage/posts/x.jpg`) when
     * they keep existing images, so writes must be normalised or the path gets
     * re-prefixed on every round-trip (`/storage//storage/posts/x.jpg`).
     */
    public static function toStoragePath(?string $value): ?string
    {
        if (! $value) return null;

        $path = preg_replace('#^https?://[^/]+#', '', $value);

        // Strip every leading `storage/` segment so rows already corrupted with
        // a doubled prefix normalise back to a clean path. Anchor on the LAST
        // occurrence so a sub-path deployment (APP_URL=https://host/app) also
        // reduces correctly instead of leaving `app/storage/...` behind.
        if (($pos = strrpos($path, '/storage/')) !== false) {
            $path = substr($path, $pos + strlen('/storage/'));
        }

        $path = ltrim(preg_replace('#^(?:/*storage/+)+#', '', $path), '/');

        // Reject traversal: keep_images is client-supplied, and `..` segments
        // would let a caller pin arbitrary disk paths into the row.
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    private function normalizeStorageUrl(?string $value): ?string
    {
        // Normalising first makes reads self-healing for rows already written
        // with a doubled `/storage/` prefix.
        $path = self::toStoragePath($value);

        return $path ? Storage::url($path) : null;
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
