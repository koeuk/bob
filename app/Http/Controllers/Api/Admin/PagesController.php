<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Admin: Pages
 *
 * Requires `moderator`, `admin`, or `super_admin` role.
 */
class PagesController extends Controller
{
    /**
     * List pages
     *
     * @queryParam filter[status] string One of: `draft`, `published`. Example: published
     * @queryParam filter[title] string Partial match on title. Example: about
     * @queryParam filter[slug] string Partial match on slug. Example: about
     * @queryParam sort string One of: `title`, `slug`, `updated_at`, `status`, `-updated_at`. Example: -updated_at
     * @queryParam per_page int Results per page (default 25). Example: 25
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "data": [{ "id": 1, "uuid": "...", "slug": "about", "title": "About", "status": "published" }],
     *   "total": 5
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $pages = QueryBuilder::for(Page::class)
            ->with('updatedBy:id,uuid,name')
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::partial('title'),
                AllowedFilter::partial('slug'),
            ])
            ->allowedSorts(['title', 'slug', 'updated_at', 'status'])
            ->defaultSort('-updated_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return response()->json($pages);
    }

    /**
     * Create page
     *
     * @bodyParam slug string required Unique URL slug (max 255 chars). Example: about-us
     * @bodyParam title string required Page title (max 255 chars). Example: About Us
     * @bodyParam body string required Page content (HTML/Markdown). Example: <p>We are...</p>
     * @bodyParam status string required One of: `draft`, `published`. Example: draft
     *
     * @response 201 { "id": 6, "uuid": "...", "slug": "about-us", "title": "About Us", "status": "draft" }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:pages,slug'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $page = Page::create([...$data, 'updated_by' => $request->user()->id]);

        ActivityLog::record('page.create', $page, null, $page->only(['slug', 'title', 'status']));

        return response()->json($page, 201);
    }

    /**
     * Update page
     *
     * @urlParam page string required Page UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam slug string URL slug (must be unique). Example: about-us
     * @bodyParam title string Page title. Example: About Us
     * @bodyParam body string Page content. Example: <p>Updated content.</p>
     * @bodyParam status string One of: `draft`, `published`. Example: published
     *
     * @response 200 { "id": 1, "slug": "about-us", "title": "About Us", "status": "published" }
     */
    public function update(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['sometimes', 'string', 'max:255', 'unique:pages,slug,'.$page->id],
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:draft,published'],
        ]);

        $before = $page->only(array_keys($data));
        $page->update([...$data, 'updated_by' => $request->user()->id]);

        ActivityLog::record('page.update', $page, $before, $page->only(array_keys($data)));

        return response()->json($page->fresh());
    }

    /**
     * Delete page
     *
     * @urlParam page string required Page UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "message": "Page deleted." }
     */
    public function destroy(Page $page): JsonResponse
    {
        ActivityLog::record('page.delete', $page, $page->only(['slug', 'title']));
        $page->delete();

        return response()->json(['message' => 'Page deleted.']);
    }
}
