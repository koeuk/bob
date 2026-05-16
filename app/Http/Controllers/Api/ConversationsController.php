<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->with(['participants:id,uuid,name,avatar', 'latestMessage.sender:id,uuid,name,avatar'])
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id)->whereNull('read_at');
            }])
            ->get()
            ->sortByDesc(fn ($c) => optional($c->latestMessage)->created_at)
            ->values();

        return response()->json($conversations);
    }

    public function findOrCreate(Request $request)
    {
        $request->validate(['user_uuid' => 'required|exists:users,uuid']);

        $user = $request->user();
        $other = User::where('uuid', $request->user_uuid)->firstOrFail();

        $myConvIds = DB::table('conversation_user')
            ->where('user_id', $user->id)
            ->pluck('conversation_id');

        $convId = DB::table('conversation_user')
            ->whereIn('conversation_id', $myConvIds)
            ->where('user_id', $other->id)
            ->value('conversation_id');

        if ($convId) {
            $conversation = Conversation::find($convId);
        } else {
            $conversation = Conversation::create([]);
            $conversation->participants()->attach([$user->id, $other->id]);
        }

        return response()->json(['uuid' => $conversation->uuid]);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        abort_if(
            ! $conversation->participants()->where('users.id', $user->id)->exists(),
            403
        );

        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender:id,uuid,name,avatar')
            ->get();

        return response()->json($messages);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        abort_if(
            ! $conversation->participants()->where('users.id', $user->id)->exists(),
            403
        );

        $request->validate([
            'body'      => 'nullable|string|max:2000',
            'images'    => 'nullable|array|max:5',
            'images.*'  => 'image|max:5120',
        ]);

        abort_if(! $request->filled('body') && ! $request->hasFile('images'), 422);

        $paths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $paths[] = $file->store('message-images', 'public');
            }
        }

        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'body'    => $request->body,
            'images'  => $paths ?: null,
        ]);

        $message->load('sender:id,uuid,name,avatar');

        return response()->json($message, 201);
    }

    public function unread(Request $request)
    {
        $user = $request->user();

        $count = \App\Models\Message::whereHas('conversation.participants', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })
        ->where('user_id', '!=', $user->id)
        ->whereNull('read_at')
        ->count();

        return response()->json(['count' => $count]);
    }
}
