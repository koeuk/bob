<?php

namespace App\Actions\Pages;

use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\User;

/**
 * Creates or updates a CMS page. Both operations stamp `updated_by` and log
 * the change, differing only in the validation rules and log name.
 */
class SavePage
{
    public static function createRules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'unique:pages,slug'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
        ];
    }

    public static function updateRules(Page $page): array
    {
        return [
            'slug' => ['sometimes', 'string', 'max:255', 'unique:pages,slug,'.$page->id],
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:draft,published'],
        ];
    }

    public function create(array $data, User $actor): Page
    {
        $page = Page::create([...$data, 'updated_by' => $actor->id]);

        ActivityLog::record('page.create', $page, null, $page->only(['slug', 'title', 'status']));

        return $page;
    }

    public function update(Page $page, array $data, User $actor): Page
    {
        $before = $page->only(array_keys($data));

        $page->update([...$data, 'updated_by' => $actor->id]);

        ActivityLog::record('page.update', $page, $before, $page->only(array_keys($data)));

        return $page;
    }

    public function delete(Page $page): void
    {
        ActivityLog::record('page.delete', $page, $page->only(['slug', 'title']));

        $page->delete();
    }
}
