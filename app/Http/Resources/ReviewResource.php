<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contractId' => $this->contract_id,
            'reviewerId' => $this->reviewer_id,
            'reviewerName' => $this->reviewer?->name,
            'reviewerAvatarUrl' => $this->reviewer?->avatar_url,
            'revieweeId' => $this->reviewee_id,
            'revieweeName' => $this->reviewee?->name,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
