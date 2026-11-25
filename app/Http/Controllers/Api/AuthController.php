<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly GoogleAuthService $googleAuthService) {}

    public function register(Request $request): JsonResponse
    {
        $passwordConfirmation = $request->input('passwordConfirmation') ?? $request->input('password_confirmation');
        $activeRole = $request->input('activeRole') ?? $request->input('active_role') ?? 'FREELANCER';
        $request->merge([
            'password_confirmation' => $passwordConfirmation,
            'active_role' => $activeRole,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(fn ($query) => $query->where('active_role', $activeRole)),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'active_role' => ['required', 'string', Rule::in(['CLIENT', 'FREELANCER'])],
        ]);

        $initialBalance = $validated['active_role'] === 'CLIENT' ? 50000000.00 : 0.00;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'active_role' => $validated['active_role'],
            'wallet_balance' => $initialBalance,
        ]);

        $token = $user->createToken('android-device')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Registrasi akun berhasil', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $activeRole = $request->input('activeRole') ?? $request->input('active_role');

        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $query = User::where('email', $validated['email']);
        if ($activeRole && in_array($activeRole, ['CLIENT', 'FREELANCER'])) {
            $query->where('active_role', $activeRole);
        }
        $user = $query->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            $roleLabel = $activeRole ? " ({$activeRole})" : '';
            return $this->errorResponse("Kredensial login{$roleLabel} tidak cocok dengan data kami.", 401);
        }

        $token = $user->createToken('android-device')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Login akun berhasil');
    }

    public function google(Request $request): JsonResponse
    {
        $idToken = $request->input('idToken') ?? $request->input('id_token');
        $activeRole = $request->input('activeRole') ?? $request->input('active_role') ?? 'FREELANCER';

        if (!$idToken) {
            return $this->errorResponse('Parameter idToken wajib disertakan.', 422);
        }

        try {
            $authData = $this->googleAuthService->authenticate($idToken, $activeRole);
            return $this->successResponse([
                'token' => $authData['token'],
                'user' => new UserResource($authData['user']),
            ], 'Autentikasi Google berhasil');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal melakukan autentikasi Google: ' . $e->getMessage(), 400);
        }
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UserResource($request->user()),
            'Data profil pengguna berhasil diambil'
        );
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'hourlyRate' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'avatar' => ['nullable', 'image', 'max:5120'], // 5MB max
        ]);

        if ($request->has('name')) {
            $user->name = $request->input('name');
        }
        if ($request->has('headline')) {
            $user->headline = $request->input('headline');
        }
        if ($request->has('bio')) {
            $user->bio = $request->input('bio');
        }
        $hourlyRate = $request->input('hourlyRate') ?? $request->input('hourly_rate');
        if ($hourlyRate !== null) {
            $user->hourly_rate = (float) $hourlyRate;
        }

        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $avatarFile->getClientOriginalExtension();
            $path = 'avatars/' . $filename;

            // Resize & compress image using Intervention Image
            $img = Image::read($avatarFile->getPathname());
            $img->cover(400, 400);

            Storage::disk('public')->put($path, (string) $img->encode());
            $user->avatar_url = url(Storage::url($path));
        }

        $user->save();

        return $this->successResponse(
            new UserResource($user),
            'Profil berhasil diperbarui'
        );
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $fcmToken = $request->input('fcmToken') ?? $request->input('fcm_token');
        $request->merge(['fcm_token' => $fcmToken]);

        $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        $user = $request->user();
        $user->fcm_token = $fcmToken;
        $user->save();

        return $this->successResponse([
            'fcm_token' => $user->fcm_token,
        ], 'FCM token berhasil diperbarui');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Berhasil keluar (logout)');
    }
}
