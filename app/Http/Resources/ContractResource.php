<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jobId' => $this->job_id,
            'jobTitle' => $this->job?->title,
            'clientId' => $this->client_id,
            'clientName' => $this->client?->name,
            'freelancerId' => $this->freelancer_id,
            'freelancerName' => $this->freelancer?->name,
            'agreedAmount' => (float) $this->agreed_amount,
            'escrowStatus' => $this->escrow_status,
            'contractStatus' => $this->contract_status,
            'submissionNotes' => $this->submission_notes,
            'submissionUrl' => $this->submission_url,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
