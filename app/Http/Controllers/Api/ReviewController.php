<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Contract;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    public function store(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $contract = Contract::with(['job', 'client', 'freelancer'])->find($id);
        if (!$contract) {
            return $this->errorResponse('Kontrak tidak ditemukan', 404);
        }

        if ($contract->contract_status !== 'COMPLETED') {
            return $this->errorResponse('Ulasan hanya dapat diberikan setelah kontrak selesai (COMPLETED).', 400);
        }

        if ($contract->client_id !== $user->id && $contract->freelancer_id !== $user->id) {
            return $this->errorResponse('Anda bukan peserta dari kontrak ini.', 403);
        }

        $revieweeId = ($contract->client_id === $user->id) ? $contract->freelancer_id : $contract->client_id;

        // Check if already reviewed
        $existing = Review::where('contract_id', $contract->id)
            ->where('reviewer_id', $user->id)
            ->first();

        if ($existing) {
            return $this->errorResponse('Anda sudah memberikan ulasan untuk kontrak ini.', 400);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = Review::create([
            'contract_id' => $contract->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $revieweeId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        $review->load(['reviewer', 'reviewee', 'contract']);

        return $this->successResponse(
            new ReviewResource($review),
            'Ulasan berhasil dikirimkan.',
            201
        );
    }

    public function contractReviews(string|int $id): JsonResponse
    {
        $reviews = Review::where('contract_id', $id)
            ->with(['reviewer', 'reviewee'])
            ->get();

        return $this->successResponse(
            ReviewResource::collection($reviews),
            'Daftar ulasan kontrak berhasil diambil'
        );
    }

    public function userReviews(string|int $userId): JsonResponse
    {
        $reviews = Review::where('reviewee_id', $userId)
            ->with(['reviewer', 'reviewee'])
            ->latest()
            ->get();

        return $this->successResponse(
            ReviewResource::collection($reviews),
            'Daftar ulasan pengguna berhasil diambil'
        );
    }
}
