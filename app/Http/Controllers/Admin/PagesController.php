<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pages\ListPages;
use App\Actions\Pages\SavePage;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PagesController extends Controller
{
    public function index(Request $request, ListPages $listPages): Response
    {
        return Inertia::render('admin/pages/index', [
            'pages' => $listPages->handle($request),
            'filters' => $request->only(['filter']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/pages/edit', [
            'page' => null,
        ]);
    }

    public function store(Request $request, SavePage $savePage): RedirectResponse
    {
        $data = $request->validate(SavePage::createRules());

        $page = $savePage->create($data, $request->user());

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page created.');
    }

    public function edit(Page $page): Response
    {
        $page->load('updatedBy:id,uuid,name');

        return Inertia::render('admin/pages/edit', [
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page, SavePage $savePage): RedirectResponse
    {
        $data = $request->validate(SavePage::updateRules($page));

        $savePage->update($page, $data, $request->user());

        return back()->with('status', 'Page updated.');
    }

    public function destroy(Page $page, SavePage $savePage): RedirectResponse
    {
        $savePage->delete($page);

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
    }
}
