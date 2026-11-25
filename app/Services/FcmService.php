<?php

namespace App\Services;

use App\Models\User;
use Google_Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private ?string $credentialsPath;
    private ?string $projectId = null;

    public function __construct()
    {
        $this->credentialsPath = base_path('firebase-credentials.json');
        if (file_exists($this->credentialsPath)) {
            $json = json_decode(file_get_contents($this->credentialsPath), true);
            $this->projectId = $json['project_id'] ?? null;
        }
    }

    /**
     * Dapatkan Google OAuth2 Access Token untuk FCM HTTP v1
     */
    private function getAccessToken(): ?string
    {
        if (!file_exists($this->credentialsPath)) {
            Log::warning('FCM: firebase-credentials.json tidak ditemukan di base_path.');
            return null;
        }

        try {
            $client = new Google_Client();
            $client->setAuthConfig($this->credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $tokenData = $client->fetchAccessTokenWithAssertion();
            return $tokenData['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::error('FCM: Gagal mengambil access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Kirim push notification ke perangkat target via token FCM
     */
    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty($fcmToken)) {
            return false;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken || !$this->projectId) {
            Log::warning('FCM: Pengiriman dibatalkan, accessToken atau projectId tidak tersedia.');
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        // Pastikan semua nilai pada key 'data' bertipe string sesuai spesifikasi FCM
        $stringData = [];
        foreach ($data as $key => $val) {
            $stringData[(string) $key] = (string) $val;
        }
        $stringData['title'] = $title;
        $stringData['body'] = $body;

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'gigsync_realtime_updates',
                        'default_sound' => true,
                        'default_vibrate_timings' => true,
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                Log::info("FCM: Notifikasi berhasil dikirim ke token {$fcmToken}. Response: " . $response->body());
                return true;
            } else {
                Log::warning("FCM: Gagal mengirim notifikasi HTTP {$response->status()}: " . $response->body());
                return false;
            }
        } catch (\Throwable $e) {
            Log::error('FCM: Exception saat request FCM v1: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim notifikasi ke user tertentu berdasarkan ID atau instance User
     */
    public function sendToUser(User|int $user, string $title, string $body, array $data = []): bool
    {
        $targetUser = is_int($user) ? User::find($user) : $user;

        if (!$targetUser) {
            Log::warning("FCM: Target user id {$user} tidak ditemukan.");
            return false;
        }

        if (empty($targetUser->fcm_token)) {
            Log::info("FCM: User {$targetUser->id} ({$targetUser->email}) belum memiliki fcm_token terdaftar.");
            return false;
        }

        return $this->sendToToken($targetUser->fcm_token, $title, $body, $data);
    }
}
