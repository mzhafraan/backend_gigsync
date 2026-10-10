<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatResource;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $chats = Chat::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->with(['users', 'job'])
        ->latest('updated_at')
        ->get();

        return $this->successResponse(
            ChatResource::collection($chats),
            'Daftar percakapan berhasil diambil'
        );
    }

    public function startChat(Request $request): JsonResponse
    {
        $user = $request->user();

        $recipientId = $request->input('recipientId') ?? $request->input('recipient_id');
        $jobId = $request->input('jobId') ?? $request->input('job_id');

        $request->merge([
            'recipient_id' => $recipientId,
            'job_id' => $jobId,
        ]);

        $request->validate([
            'recipient_id' => ['required', 'exists:users,id', 'different:user_id'],
            'job_id' => ['nullable', 'exists:jobs,id'],
        ]);

        // Find existing chat between these 2 users (optionally for same job)
        $chat = Chat::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->whereHas('participants', function ($q) use ($recipientId) {
            $q->where('user_id', $recipientId);
        });

        if ($jobId) {
            $chat->where('job_id', $jobId);
        }

        $existingChat = $chat->first();

        if ($existingChat) {
            $existingChat->load(['users', 'job']);
            return $this->successResponse(
                new ChatResource($existingChat),
                'Ruang percakapan ditemukan'
            );
        }

        // Create new chat
        $newChat = Chat::create([
            'job_id' => $jobId,
        ]);

        ChatParticipant::create([
            'chat_id' => $newChat->id,
            'user_id' => $user->id,
        ]);

        ChatParticipant::create([
            'chat_id' => $newChat->id,
            'user_id' => $recipientId,
        ]);

        $newChat->load(['users', 'job']);

        return $this->successResponse(
            new ChatResource($newChat),
            'Ruang obrolan baru berhasil dibuat',
            201
        );
    }

    public function messages(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $chat = Chat::find($id);
        if (!$chat) {
            return $this->errorResponse('Ruang obrolan tidak ditemukan', 404);
        }

        $isParticipant = ChatParticipant::where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isParticipant) {
            return $this->errorResponse('Anda bukan peserta ruang obrolan ini.', 403);
        }

        $messages = Message::where('chat_id', $chat->id)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponse(
            MessageResource::collection($messages),
            'Daftar pesan berhasil diambil'
        );
    }

    public function sendMessage(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $chat = Chat::find($id);
        if (!$chat) {
            return $this->errorResponse('Ruang obrolan tidak ditemukan', 404);
        }

        $isParticipant = ChatParticipant::where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isParticipant) {
            return $this->errorResponse('Anda bukan peserta ruang obrolan ini.', 403);
        }

        $request->validate([
            'message' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = 'chat_' . $chat->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('chat_attachments', $filename, 'public');
            $attachmentUrl = url(Storage::url($path));
        }

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $user->id,
            'message' => $request->input('message'),
            'attachment_url' => $attachmentUrl,
            'is_read' => false,
        ]);

        $chat->touch(); // Update updated_at timestamp

        $message->load('sender');

        return $this->successResponse(
            new MessageResource($message),
            'Pesan berhasil dikirim',
            201
        );
    }

    public function markAsRead(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $chat = Chat::find($id);
        if (!$chat) {
            return $this->errorResponse('Ruang obrolan tidak ditemukan', 404);
        }

        // Mark incoming messages as read
        Message::where('chat_id', $chat->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        ChatParticipant::where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        return $this->successResponse(null, 'Pesan telah ditandai dibaca');
    }
}
