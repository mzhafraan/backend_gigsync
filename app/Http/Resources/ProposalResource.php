<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jobId' => $this->job_id,
            'jobTitle' => $this->job?->title,
            'freelancerId' => $this->freelancer_id,
            'freelancerName' => $this->freelancer?->name,
            'freelancerAvatarUrl' => $this->freelancer?->avatar_url,
            'freelancerHeadline' => $this->freelancer?->headline,
            'bidAmount' => (float) $this->bid_amount,
            'estimatedDays' => (int) $this->estimated_days,
            'coverLetter' => $this->cover_letter,
            'attachmentUrl' => $this->attachment_url,
            'status' => $this->status,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
