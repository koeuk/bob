<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasUuids;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'key',
        'value',
        'group',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, mixed $value, string $group = 'general'): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'updated_by' => auth()->id()],
        );
    }

    /**
     * App branding (name + logo URL), resolved in a single query.
     * Falls back to config('app.name') when no custom name is set.
     *
     * @return array{name: string, logo: ?string, logo_path: ?string}
     */
    public static function branding(): array
    {
        $rows = self::whereIn('key', ['app_name', 'app_logo'])->get()->keyBy('key');

        $name = $rows->get('app_name')?->value;
        $path = $rows->get('app_logo')?->value;

        return [
            'name' => $name ?: config('app.name'),
            'logo' => $path ? Storage::url($path) : null,
            'logo_path' => $path ?: null,
        ];
    }
}
