<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Services\EscrowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly EscrowService $escrowService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $contracts = Contract::where(function ($q) use ($user) {
            $q->where('client_id', $user->id)
              ->orWhere('freelancer_id', $user->id);
        })
        ->with(['job', 'client', 'freelancer'])
        ->latest()
        ->get();

        return $this->successResponse(
            ContractResource::collection($contracts),
            'Daftar kontrak berhasil diambil'
        );
    }

    public function show(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $contract = Contract::with(['job', 'client', 'freelancer'])->find($id);
        if (!$contract) {
            return $this->errorResponse('Kontrak tidak ditemukan', 404);
        }

        if ($contract->client_id !== $user->id && $contract->freelancer_id !== $user->id) {
            return $this->errorResponse('Anda tidak memiliki akses ke kontrak ini.', 403);
        }

        return $this->successResponse(
            new ContractResource($contract),
            'Detail kontrak berhasil diambil'
        );
    }

    public function submit(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $contract = Contract::with(['job', 'client', 'freelancer'])->find($id);
        if (!$contract) {
            return $this->errorResponse('Kontrak tidak ditemukan', 404);
        }

        if ($contract->freelancer_id !== $user->id) {
            return $this->errorResponse('Hanya freelancer yang ditunjuk yang dapat menyerahkan hasil kerja.', 403);
        }

        if (!in_array($contract->contract_status, ['ACTIVE', 'REVISION'])) {
            return $this->errorResponse("Hasil kerja tidak dapat diserahkan saat status kontrak {$contract->contract_status}.", 400);
        }

        $submissionNotes = $request->input('submissionNotes') ?? $request->input('submission_notes');
        $submissionUrl = $request->input('submissionUrl') ?? $request->input('submission_url');

        $request->merge([
            'submission_notes' => $submissionNotes,
            'submission_url' => $submissionUrl,
        ]);

        $request->validate([
            'submission_notes' => ['required', 'string'],
            'submission_url' => ['nullable', 'url', 'max:500'],
        ]);

        $contract->update([
            'contract_status' => 'UNDER_REVIEW',
            'submission_notes' => $submissionNotes,
            'submission_url' => $submissionUrl,
        ]);

        return $this->successResponse(
            new ContractResource($contract->fresh(['job', 'client', 'freelancer'])),
            'Hasil kerja berhasil diserahkan dan menunggu peninjauan dari klien.'
        );
    }

    public function approve(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $contract = Contract::with(['job', 'client', 'freelancer'])->find($id);
        if (!$contract) {
            return $this->errorResponse('Kontrak tidak ditemukan', 404);
        }

        if ($contract->client_id !== $user->id) {
            return $this->errorResponse('Hanya klien yang dapat menyetujui hasil kerja kontrak ini.', 403);
        }

        if ($contract->contract_status === 'COMPLETED') {
            return $this->errorResponse('Kontrak ini sudah diselesaikan sebelumnya.', 400);
        }

        try {
            $this->escrowService->releaseEscrowToFreelancer($contract);

            return $this->successResponse(
                new ContractResource($contract->fresh(['job', 'client', 'freelancer'])),
                'Pekerjaan berhasil disetujui dan dana escrow telah dicairkan ke freelancer.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function revise(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $contract = Contract::with(['job', 'client', 'freelancer'])->find($id);
        if (!$contract) {
            return $this->errorResponse('Kontrak tidak ditemukan', 404);
        }

        if ($contract->client_id !== $user->id) {
            return $this->errorResponse('Hanya klien yang dapat meminta revisi hasil kerja.', 403);
        }

        if ($contract->contract_status !== 'UNDER_REVIEW') {
            return $this->errorResponse('Revisi hanya dapat diajukan saat kontrak berstatus UNDER_REVIEW.', 400);
        }

        $revisionNotes = $request->input('revisionNotes') ?? $request->input('revision_notes');
        $request->merge(['revision_notes' => $revisionNotes]);

        $request->validate([
            'revision_notes' => ['required', 'string'],
        ]);

        $contract->update([
            'contract_status' => 'REVISION',
            'submission_notes' => "Revisi Diminta: " . $revisionNotes,
        ]);

        return $this->successResponse(
            new ContractResource($contract->fresh(['job', 'client', 'freelancer'])),
            'Permintaan revisi berhasil dikirim ke freelancer.'
        );
    }
}
