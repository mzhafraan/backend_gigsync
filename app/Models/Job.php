<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'category_id',
        'title',
        'description',
        'budget_type',
        'budget_amount',
        'deadline',
        'latitude',
        'longitude',
        'location_name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'budget_amount' => 'float',
            'deadline' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skills');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'job_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'job_id');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'job_id');
    }
}
