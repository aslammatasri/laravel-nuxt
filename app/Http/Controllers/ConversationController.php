<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProductApplication;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    // GET /api/conversations
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::with([
                'product:id,name,images,image',
                'brand:id,name,avatar',
                'creator:id,name,avatar',
            ])
            ->where(fn ($q) => $q->where('brand_id', $userId)->orWhere('creator_id', $userId))
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at')])
            ->orderByRaw('last_message_at is null')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(function ($c) use ($userId) {
                $last = $c->messages()->latest('id')->first(['body', 'sender_id', 'created_at']);
                $other = $c->brand_id === $userId ? $c->creator : $c->brand;

                return [
                    'id'           => $c->id,
                    'product'      => ['id' => $c->product->id, 'name' => $c->product->name, 'image' => $c->product->images[0] ?? null],
                    'other'        => ['id' => $other->id, 'name' => $other->name, 'avatar' => $other->avatar],
                    'unread_count' => $c->unread_count,
                    'last_message' => $last ? [
                        'body'       => $last->body,
                        'mine'       => $last->sender_id === $userId,
                        'created_at' => $last->created_at,
                    ] : null,
                    'last_message_at' => $c->last_message_at,
                ];
            });

        return response()->json(['data' => $conversations]);
    }

    // GET /api/conversations/unread-count
    public function unreadCount(Request $request)
    {
        $userId = $request->user()->id;

        $count = Message::whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->whereHas('conversation', fn ($q) => $q
                ->where('brand_id', $userId)->orWhere('creator_id', $userId))
            ->count();

        return response()->json(['count' => $count]);
    }

    // POST /api/conversations  { application_id } — open (or create) the chat for an approved application
    public function store(Request $request)
    {
        $data = $request->validate(['application_id' => 'required|integer']);
        $userId = $request->user()->id;

        $application = ProductApplication::with('product')->find($data['application_id']);

        if (! $application || $application->status !== ApplicationStatus::APPROVED) {
            return response()->json(['message' => 'Chat is only available for approved applications'], 422);
        }
        if ($application->creator_id !== $userId && $application->product->brand_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation = Conversation::firstOrCreate(
            ['application_id' => $application->id],
            [
                'product_id' => $application->product_id,
                'brand_id'   => $application->product->brand_id,
                'creator_id' => $application->creator_id,
            ],
        );

        return response()->json(['id' => $conversation->id], 201);
    }

    // GET /api/conversations/{id}/messages?after={messageId}
    public function messages(int $id, Request $request)
    {
        $conversation = $this->findForUser($id, $request);

        $messages = $conversation->messages()
            ->when($request->integer('after'), fn ($q, $after) => $q->where('id', '>', $after))
            ->orderBy('id')
            ->limit(200)
            ->get(['id', 'sender_id', 'body', 'read_at', 'created_at']);

        return response()->json(['data' => $messages]);
    }

    // POST /api/conversations/{id}/messages  { body }
    public function send(int $id, Request $request)
    {
        $conversation = $this->findForUser($id, $request);
        $data = $request->validate(['body' => 'required|string|max:2000']);

        $body = trim($data['body']);
        if ($body === '') {
            return response()->json(['message' => 'Message cannot be empty'], 422);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body'      => $body,
        ]);
        $conversation->update(['last_message_at' => $message->created_at]);

        return response()->json($message->only(['id', 'sender_id', 'body', 'read_at', 'created_at']), 201);
    }

    // PATCH /api/conversations/{id}/read
    public function markRead(int $id, Request $request)
    {
        $conversation = $this->findForUser($id, $request);

        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }

    private function findForUser(int $id, Request $request): Conversation
    {
        $conversation = Conversation::findOrFail($id);

        abort_unless($conversation->hasParticipant($request->user()->id), 403, 'Unauthorized');

        return $conversation;
    }
}
