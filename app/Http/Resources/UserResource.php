<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'googleId' => $this->google_id,
            'name' => $this->name,
            'email' => $this->email,
            'activeRole' => $this->active_role,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'hourlyRate' => (float) $this->hourly_rate,
            'avatarUrl' => $this->avatar_url,
            'walletBalance' => (float) $this->wallet_balance,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
