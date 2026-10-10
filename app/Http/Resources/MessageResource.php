<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chatId' => $this->chat_id,
            'senderId' => $this->sender_id,
            'senderName' => $this->sender?->name,
            'senderAvatarUrl' => $this->sender?->avatar_url,
            'message' => $this->message,
            'attachmentUrl' => $this->attachment_url,
            'isRead' => (bool) $this->is_read,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
