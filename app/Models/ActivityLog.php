<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'before',
        'after',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public static function record(string $action, ?object $target = null, ?array $before = null, ?array $after = null): self
    {
        $request = request();

        return self::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
