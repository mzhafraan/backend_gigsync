<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'google_id',
        'name',
        'email',
        'password',
        'active_role',
        'headline',
        'bio',
        'hourly_rate',
        'avatar_url',
        'wallet_balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'hourly_rate' => 'float',
            'wallet_balance' => 'float',
        ];
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'client_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'freelancer_id');
    }

    public function clientContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'client_id');
    }

    public function freelancerContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'freelancer_id');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'user_id');
    }

    public function chatParticipants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
}
