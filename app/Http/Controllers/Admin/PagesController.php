<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Page::query()->with('updatedBy:id,uuid,name');

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        return response()->json(
            $query->latest('updated_at')->paginate($request->integer('per_page', 25))
        );
    }

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

        return response()->json(['page' => $page], 201);
    }

    public function show(Page $page): JsonResponse
    {
        $page->load('updatedBy:id,uuid,name');

        return response()->json(['page' => $page]);
    }

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

        return response()->json(['page' => $page]);
    }

    public function destroy(Page $page): JsonResponse
    {
        ActivityLog::record('page.delete', $page, $page->only(['slug', 'title']));
        $page->delete();

        return response()->json(['message' => 'Page deleted.']);
    }
}
