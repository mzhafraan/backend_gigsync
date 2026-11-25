<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MilestoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contractId' => $this->contract_id,

            // Informasi milestone
            'title' => $this->title,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'deadline' => $this->deadline?->format('Y-m-d'),

            // Status pengerjaan
            'status' => $this->status,
            'sortOrder' => $this->sort_order,

            // Pengumpulan hasil pekerjaan
            'submissionNotes' => $this->submission_notes,
            'submissionUrl' => $this->submission_url,
            'revisionNotes' => $this->revision_notes,

            // Waktu aktivitas
            'startedAt' => $this->started_at?->toISOString(),
            'submittedAt' => $this->submitted_at?->toISOString(),
            'approvedAt' => $this->approved_at?->toISOString(),

            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
