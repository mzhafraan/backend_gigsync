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

    public function authenticate(string $idToken): array
    {
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

        // 2. Temukan pengguna berdasarkan google_id atau email
        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
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
            // Buat pengguna baru jika belum terdaftar
            $user = User::create([
                'google_id' => $googleId,
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(32)),
                'avatar_url' => $picture,
                'active_role' => 'FREELANCER',
                'wallet_balance' => 0.00
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
