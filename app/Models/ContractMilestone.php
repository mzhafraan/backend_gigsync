<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractMilestone extends Model
{
    /**
     * Kolom yang boleh diisi melalui mass assignment.
     */
    protected $fillable = [
        'contract_id',
        'title',
        'description',
        'amount',
        'deadline',
        'status',
        'submission_notes',
        'submission_url',
        'revision_notes',
        'sort_order',
        'started_at',
        'submitted_at',
        'approved_at',
    ];

    /**
     * Konversi tipe data otomatis oleh Laravel.
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'deadline' => 'date:Y-m-d',
            'sort_order' => 'integer',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Setiap milestone dimiliki oleh satu Contract.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
}
