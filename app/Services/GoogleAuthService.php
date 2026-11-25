<?php

namespace App\Services;

use Google_Client;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleAuthService
{
    private Google_Client $client;

    public function __construct()
    {
        $this->client = new Google_Client(['client_id' => config('services.google.client_id')]);
    }



    public function authenticate(string $idToken, string $activeRole = 'FREELANCER'): array
    {
        // 1. Toleransi clock skew (perbedaan jam antara HP dan laptop/server)
        \Firebase\JWT\JWT::$leeway = 3600;

        // 1. Verifikasi tanda tangan kriptografis token via public key Google
        $payload = $this->client->verifyIdToken($idToken);

        if (!$payload) {
            throw ValidationException::withMessages([
                'idToken' => ['Google ID Token tidak valid atau telah kedaluwarsa.']
            ]);
        }

        $googleId = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'] ?? 'Google User';
        $picture = $payload['picture'] ?? null;

        $role = in_array($activeRole, ['CLIENT', 'FREELANCER']) ? $activeRole : 'FREELANCER';

        // 2. Temukan pengguna berdasarkan google_id atau email khusus untuk role aplikasi tersebut
        $user = User::where('active_role', $role)
            ->where(function ($query) use ($googleId, $email) {
                $query->where('google_id', $googleId)
                    ->orWhere('email', $email);
            })
            ->first();

        if ($user) {
            if (!$user->google_id) {
                $user->google_id = $googleId;
            }
            if (!$user->avatar_url && $picture) {
                $user->avatar_url = $picture;
            }
            $user->save();
        } else {
            $initialBalance = $role === 'CLIENT' ? 50000000.00 : 0.00;

            // Buat pengguna baru terpisah untuk role aplikasi tersebut
            $user = User::create([
                'google_id' => $googleId,
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(32)),
                'avatar_url' => $picture,
                'active_role' => $role,
                'wallet_balance' => $initialBalance
            ]);
        }

        // 3. Terbitkan token Sanctum
        $token = $user->createToken('android-device')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user
        ];
    }
}
