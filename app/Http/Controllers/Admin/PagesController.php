<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PagesController extends Controller
{
    public function index(Request $request): Response
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

        return Inertia::render('admin/pages/index', [
            'pages' => $pages,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/pages/edit', [
            'page' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:pages,slug'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $page = Page::create([...$data, 'updated_by' => $request->user()->id]);

        ActivityLog::record('page.create', $page, null, $page->only(['slug', 'title', 'status']));

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page created.');
    }

    public function edit(Page $page): Response
    {
        $page->load('updatedBy:id,uuid,name');

        return Inertia::render('admin/pages/edit', [
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
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

        return back()->with('status', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        ActivityLog::record('page.delete', $page, $page->only(['slug', 'title']));
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
    }
}
