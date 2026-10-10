<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'clientId' => $this->client_id,
            'clientName' => $this->client?->name,
            'clientAvatarUrl' => $this->client?->avatar_url,
            'title' => $this->title,
            'description' => $this->description,
            'categoryId' => $this->category_id,
            'categoryName' => $this->category?->name,
            'budgetType' => $this->budget_type,
            'budgetAmount' => (float) $this->budget_amount,
            'deadline' => $this->deadline ? $this->deadline->format('Y-m-d') : null,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'locationName' => $this->location_name,
            'skills' => $this->skills ? $this->skills->pluck('name')->toArray() : [],
            'status' => $this->status,
            'proposalsCount' => $this->relationLoaded('proposals') ? $this->proposals->count() : $this->proposals()->count(),
            'createdAt' => $this->created_at?->toISOString()
        ];
    }
}
