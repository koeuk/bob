<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes uploaded files on the `public` disk that no database row references.
 *
 * These accumulated because replaced avatars/covers were never removed: the old
 * path was deleted using the model accessor, which returns a `/storage/...`
 * URL rather than a disk path, so the delete silently matched nothing.
 *
 * Dry run by default — pass --force to actually delete.
 */
class PruneOrphanedUploads extends Command
{
    protected $signature = 'uploads:prune
                            {--force : Actually delete the files (otherwise only reports)}
                            {--dir=* : Limit to specific directories, e.g. --dir=avatars}';

    protected $description = 'Find (and optionally delete) uploaded files no longer referenced by any record';

    /** Directories on the public disk that hold user uploads. */
    private const UPLOAD_DIRS = ['avatars', 'covers', 'posts', 'branding', 'message-images'];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $dirs = $this->option('dir') ?: self::UPLOAD_DIRS;

        $referenced = $this->referencedPaths();
        $this->info(sprintf('%d referenced upload(s) found in the database.', $referenced->count()));

        $orphans = collect();
        foreach ($dirs as $dir) {
            if (! $disk->directoryExists($dir)) {
                continue;
            }

            foreach ($disk->files($dir) as $file) {
                if (! $referenced->has($file)) {
                    $orphans->push($file);
                }
            }
        }

        if ($orphans->isEmpty()) {
            $this->info('No orphaned uploads. Nothing to do.');

            return self::SUCCESS;
        }

        $bytes = $orphans->sum(fn ($f) => $disk->size($f));
        $this->warn(sprintf(
            '%d orphaned file(s), %s.',
            $orphans->count(),
            $this->humanBytes($bytes),
        ));

        foreach ($orphans->take(20) as $file) {
            $this->line("  {$file}");
        }
        if ($orphans->count() > 20) {
            $this->line(sprintf('  … and %d more', $orphans->count() - 20));
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('Dry run — nothing deleted. Re-run with --force to delete these files.');

            return self::SUCCESS;
        }

        // Delete individually and re-check: a bulk delete reports success even
        // when the file survives (e.g. a root-owned directory the current user
        // cannot write to), which would give false confidence.
        $deleted = 0;
        $freed = 0;
        $failed = [];

        foreach ($orphans as $file) {
            $size = $disk->size($file);
            $disk->delete($file);

            if ($disk->exists($file)) {
                $failed[] = $file;

                continue;
            }

            $deleted++;
            $freed += $size;
        }

        $this->info(sprintf('Deleted %d file(s), freeing %s.', $deleted, $this->humanBytes($freed)));

        if ($failed !== []) {
            $this->newLine();
            $this->error(sprintf('%d file(s) could NOT be deleted:', count($failed)));
            foreach ($failed as $file) {
                $this->line("  {$file}");
            }
            $this->comment('Usually a permissions problem — check the owner of the containing directory:');
            $this->comment('  ls -la storage/app/public && sudo chown -R $USER storage/app/public');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Every upload path currently referenced by a record, keyed for O(1) lookup.
     * Raw column values are used so model accessors cannot rewrite them to URLs.
     */
    private function referencedPaths(): \Illuminate\Support\Collection
    {
        $paths = collect();

        User::query()->select(['avatar', 'cover'])->cursor()->each(function ($user) use ($paths) {
            $paths->push($user->getRawOriginal('avatar'), $user->getRawOriginal('cover'));
        });

        Post::query()->select(['image', 'images'])->cursor()->each(function ($post) use ($paths) {
            $paths->push($post->getRawOriginal('image'));
            foreach ((array) $post->images as $image) {
                $paths->push($image);
            }
        });

        Message::query()->select(['images'])->cursor()->each(function ($message) use ($paths) {
            foreach ((array) $message->images as $image) {
                $paths->push($image);
            }
        });

        $paths->push(Setting::get('app_logo'));

        return $paths
            ->filter()
            ->map(fn ($p) => ltrim(preg_replace('#^/?storage/#', '', $p), '/'))
            ->flip();
    }

    private function humanBytes(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024 || $unit === 'GB') {
                return round($bytes, 1).' '.$unit;
            }
            $bytes /= 1024;
        }

        return $bytes.' B';
    }
}
