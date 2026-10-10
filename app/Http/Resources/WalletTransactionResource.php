<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'contractId' => $this->contract_id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'balanceAfter' => (float) $this->balance_after,
            'description' => $this->description,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
