<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Pages\ListPages;
use App\Actions\Pages\SavePage;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     *   "data": [{ "id": 1, "uuid": "...", "slug": "about-us", "title": "About Us", "status": "published" }],
     *   "total": 6
     * }
     */
    public function index(Request $request, ListPages $listPages): JsonResponse
    {
        return response()->json($listPages->handle($request));
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
    public function store(Request $request, SavePage $savePage): JsonResponse
    {
        $data = $request->validate(SavePage::createRules());

        return response()->json($savePage->create($data, $request->user()), 201);
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
    public function update(Request $request, Page $page, SavePage $savePage): JsonResponse
    {
        $data = $request->validate(SavePage::updateRules($page));

        $savePage->update($page, $data, $request->user());

        return response()->json($page->fresh());
    }

    /**
     * Delete page
     *
     * @urlParam page string required Page UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "message": "Page deleted." }
     */
    public function destroy(Page $page, SavePage $savePage): JsonResponse
    {
        $savePage->delete($page);

        return response()->json(['message' => 'Page deleted.']);
    }
}
