<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastMessage = $this->messages()->latest('created_at')->first();
        
        return [
            'id' => $this->id,
            'jobId' => $this->job_id,
            'jobTitle' => $this->job?->title,
            'participants' => UserResource::collection($this->users),
            'lastMessage' => $lastMessage ? new MessageResource($lastMessage) : null,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
