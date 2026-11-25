<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MilestoneResource;
use App\Models\Contract;
use App\Models\ContractMilestone;
use App\Services\EscrowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MilestoneController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly EscrowService $escrowService
    ) {
    }

    /**
     * 1. Mengambil daftar milestone dalam sebuah Contract.
     * Client dan freelancer yang terlibat boleh melihat.
     */
    public function index(Request $request, string|int $contractId): JsonResponse
    {
        $user = $request->user();

        $contract = Contract::find($contractId);

        if (!$contract) {
            return $this->errorResponse('Kontrak tidak ditemukan.', 404);
        }

        if (
            $contract->client_id !== $user->id &&
            $contract->freelancer_id !== $user->id
        ) {
            return $this->errorResponse(
                'Anda tidak memiliki akses ke kontrak ini.',
                403
            );
        }

        $milestones = $contract->milestones()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->successResponse(
            MilestoneResource::collection($milestones),
            'Daftar milestone berhasil diambil.'
        );
    }

    /**
     * 2. Client membuat milestone baru.
     */
    public function store(Request $request, string|int $contractId): JsonResponse
    {
        $user = $request->user();

        $contract = Contract::find($contractId);

        if (!$contract) {
            return $this->errorResponse('Kontrak tidak ditemukan.', 404);
        }

        if ($contract->client_id !== $user->id) {
            return $this->errorResponse(
                'Hanya client pemilik kontrak yang dapat membuat milestone.',
                403
            );
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $result = DB::transaction(function () use (
            $contractId,
            $user,
            $validated
        ) {
            $lockedContract = Contract::whereKey($contractId)
                ->lockForUpdate()
                ->first();

            if (!$lockedContract) {
                return [
                    'error' => 'Kontrak tidak ditemukan.',
                    'status' => 404,
                ];
            }

            if ($lockedContract->client_id !== $user->id) {
                return [
                    'error' => 'Anda bukan pemilik kontrak ini.',
                    'status' => 403,
                ];
            }

            if (
                $lockedContract->contract_status !== 'ACTIVE' ||
                $lockedContract->escrow_status !== 'HELD'
            ) {
                return [
                    'error' => 'Milestone hanya dapat dibuat pada kontrak aktif dengan escrow berstatus HELD.',
                    'status' => 400,
                ];
            }

            // Hitung total alokasi yang sudah dibuat.
            $allocatedCents = $this->amountToCents(
                $lockedContract->milestones()->sum('amount')
            );

            $newAmountCents = $this->amountToCents(
                $validated['amount']
            );

            $contractCents = $this->amountToCents(
                $lockedContract->agreed_amount
            );

            if ($allocatedCents + $newAmountCents > $contractCents) {
                return [
                    'error' => 'Total alokasi milestone tidak boleh melebihi nilai kontrak.',
                    'status' => 422,
                ];
            }

            $nextSortOrder = (
                (int) ($lockedContract->milestones()->max('sort_order') ?? 0)
            ) + 1;

            $milestone = $lockedContract->milestones()->create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'amount' => $validated['amount'],
                'deadline' => $validated['deadline'] ?? null,
                'status' => 'PENDING',
                'sort_order' => $nextSortOrder,
            ]);

            return [
                'milestone' => $milestone,
            ];
        });

        if (isset($result['error'])) {
            return $this->errorResponse(
                $result['error'],
                $result['status']
            );
        }

        return $this->successResponse(
            new MilestoneResource($result['milestone']),
            'Milestone berhasil dibuat.'
        );
    }

    /**
     * 3. Freelancer memulai pengerjaan milestone.
     */
    public function start(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $result = DB::transaction(function () use ($id, $user) {
            $locked = $this->lockMilestoneAndContract($id);

            if (isset($locked['error'])) {
                return $locked;
            }

            $contract = $locked['contract'];
            $milestone = $locked['milestone'];

            if ($contract->freelancer_id !== $user->id) {
                return [
                    'error' => 'Hanya freelancer yang ditunjuk dapat memulai milestone.',
                    'status' => 403,
                ];
            }

            if (
                $contract->contract_status !== 'ACTIVE' ||
                $contract->escrow_status !== 'HELD'
            ) {
                return [
                    'error' => 'Kontrak tidak aktif atau escrow tidak tersedia.',
                    'status' => 400,
                ];
            }

            if (!in_array($milestone->status, ['PENDING', 'REVISION'])) {
                return [
                    'error' => 'Milestone hanya dapat dimulai jika berstatus PENDING atau REVISION.',
                    'status' => 400,
                ];
            }

            $milestone->update([
                'status' => 'IN_PROGRESS',
                'started_at' => $milestone->started_at ?? now(),
            ]);

            return [
                'milestone' => $milestone->fresh(),
            ];
        });

        if (isset($result['error'])) {
            return $this->errorResponse(
                $result['error'],
                $result['status']
            );
        }

        return $this->successResponse(
            new MilestoneResource($result['milestone']),
            'Milestone berhasil dimulai.'
        );
    }

    /**
     * 4. Freelancer menyerahkan hasil milestone.
     */
    public function submit(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $submissionNotes = $request->input('submissionNotes')
            ?? $request->input('submission_notes');

        $submissionUrl = $request->input('submissionUrl')
            ?? $request->input('submission_url');

        $request->merge([
            'submission_notes' => $submissionNotes,
            'submission_url' => $submissionUrl,
        ]);

        $validated = $request->validate([
            'submission_notes' => ['required', 'string'],
            'submission_url' => ['nullable', 'url', 'max:500'],
        ]);

        $result = DB::transaction(function () use ($id, $user, $validated) {
            $locked = $this->lockMilestoneAndContract($id);

            if (isset($locked['error'])) {
                return $locked;
            }

            $contract = $locked['contract'];
            $milestone = $locked['milestone'];

            if ($contract->freelancer_id !== $user->id) {
                return [
                    'error' => 'Hanya freelancer yang ditunjuk dapat menyerahkan hasil milestone.',
                    'status' => 403,
                ];
            }

            if (
                $contract->contract_status !== 'ACTIVE' ||
                $contract->escrow_status !== 'HELD'
            ) {
                return [
                    'error' => 'Kontrak tidak aktif atau escrow tidak tersedia.',
                    'status' => 400,
                ];
            }

            if (!in_array($milestone->status, ['IN_PROGRESS', 'REVISION'])) {
                return [
                    'error' => 'Milestone harus berstatus IN_PROGRESS atau REVISION sebelum hasil diserahkan.',
                    'status' => 400,
                ];
            }

            $milestone->update([
                'status' => 'UNDER_REVIEW',
                'submission_notes' => $validated['submission_notes'],
                'submission_url' => $validated['submission_url'] ?? null,
                'submitted_at' => now(),
            ]);

            return [
                'milestone' => $milestone->fresh(),
            ];
        });

        if (isset($result['error'])) {
            return $this->errorResponse(
                $result['error'],
                $result['status']
            );
        }

        return $this->successResponse(
            new MilestoneResource($result['milestone']),
            'Hasil milestone berhasil diserahkan dan menunggu peninjauan client.'
        );
    }

    /**
     * 5. Client meminta revisi milestone.
     */
    public function revise(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $revisionNotes = $request->input('revisionNotes')
            ?? $request->input('revision_notes');

        $request->merge([
            'revision_notes' => $revisionNotes,
        ]);

        $validated = $request->validate([
            'revision_notes' => ['required', 'string'],
        ]);

        $result = DB::transaction(function () use ($id, $user, $validated) {
            $locked = $this->lockMilestoneAndContract($id);

            if (isset($locked['error'])) {
                return $locked;
            }

            $contract = $locked['contract'];
            $milestone = $locked['milestone'];

            if ($contract->client_id !== $user->id) {
                return [
                    'error' => 'Hanya client pemilik kontrak dapat meminta revisi.',
                    'status' => 403,
                ];
            }

            if (
                $contract->contract_status !== 'ACTIVE' ||
                $contract->escrow_status !== 'HELD'
            ) {
                return [
                    'error' => 'Kontrak tidak aktif atau escrow tidak tersedia.',
                    'status' => 400,
                ];
            }

            if ($milestone->status !== 'UNDER_REVIEW') {
                return [
                    'error' => 'Revisi hanya dapat diminta ketika milestone berstatus UNDER_REVIEW.',
                    'status' => 400,
                ];
            }

            $milestone->update([
                'status' => 'REVISION',
                'revision_notes' => $validated['revision_notes'],
                'approved_at' => null,
            ]);

            return [
                'milestone' => $milestone->fresh(),
            ];
        });

        if (isset($result['error'])) {
            return $this->errorResponse(
                $result['error'],
                $result['status']
            );
        }

        return $this->successResponse(
            new MilestoneResource($result['milestone']),
            'Permintaan revisi milestone berhasil dikirim.'
        );
    }

    /**
     * 6. Client menyetujui hasil milestone.
     * Escrow dicairkan hanya ketika semua milestone disetujui.
     */
    public function approve(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $result = DB::transaction(function () use ($id, $user) {
            $locked = $this->lockMilestoneAndContract($id);

            if (isset($locked['error'])) {
                return $locked;
            }

            $contract = $locked['contract'];
            $milestone = $locked['milestone'];

            if ($contract->client_id !== $user->id) {
                return [
                    'error' => 'Hanya client pemilik kontrak dapat menyetujui milestone.',
                    'status' => 403,
                ];
            }

            if (
                $contract->contract_status !== 'ACTIVE' ||
                $contract->escrow_status !== 'HELD'
            ) {
                return [
                    'error' => 'Kontrak tidak aktif atau escrow tidak tersedia.',
                    'status' => 400,
                ];
            }

            if ($milestone->status !== 'UNDER_REVIEW') {
                return [
                    'error' => 'Milestone hanya dapat disetujui ketika berstatus UNDER_REVIEW.',
                    'status' => 400,
                ];
            }

            // Periksa apakah masih ada milestone lain yang belum disetujui.
            $hasOtherUnapproved = $contract->milestones()
                ->where('id', '!=', $milestone->id)
                ->where('status', '!=', 'APPROVED')
                ->exists();

            $isFinalMilestone = !$hasOtherUnapproved;

            // Pastikan seluruh nilai kontrak sudah dialokasikan.
            if ($isFinalMilestone) {
                $allocatedCents = $this->amountToCents(
                    $contract->milestones()->sum('amount')
                );

                $contractCents = $this->amountToCents(
                    $contract->agreed_amount
                );

                if ($allocatedCents !== $contractCents) {
                    return [
                        'error' => 'Semua milestone harus memiliki total alokasi yang sama dengan nilai kontrak sebelum pencairan escrow.',
                        'status' => 422,
                    ];
                }
            }

            $milestone->update([
                'status' => 'APPROVED',
                'approved_at' => now(),
            ]);

            // Hanya approval milestone terakhir yang mencairkan escrow.
            if ($isFinalMilestone) {
                $this->escrowService->releaseEscrowToFreelancer($contract);
            }

            return [
                'milestone' => $milestone->fresh(),
                'escrow_released' => $isFinalMilestone,
            ];
        });

        if (isset($result['error'])) {
            return $this->errorResponse(
                $result['error'],
                $result['status']
            );
        }

        $message = $result['escrow_released']
            ? 'Milestone terakhir berhasil disetujui. Seluruh pekerjaan selesai dan dana escrow telah dicairkan ke freelancer.'
            : 'Milestone berhasil disetujui. Menunggu penyelesaian milestone lainnya.';

        return $this->successResponse(
            new MilestoneResource($result['milestone']),
            $message
        );
    }

    /**
     * Mengambil Contract dan Milestone dengan row lock.
     * Method ini dipanggil dari dalam DB::transaction().
     */
    private function lockMilestoneAndContract(string|int $id): array
    {
        $milestone = ContractMilestone::find($id);

        if (!$milestone) {
            return [
                'error' => 'Milestone tidak ditemukan.',
                'status' => 404,
            ];
        }

        // Lock Contract terlebih dahulu agar approval berjalan bergantian.
        $contract = Contract::whereKey($milestone->contract_id)
            ->lockForUpdate()
            ->first();

        if (!$contract) {
            return [
                'error' => 'Kontrak tidak ditemukan.',
                'status' => 404,
            ];
        }

        $lockedMilestone = ContractMilestone::where('contract_id', $contract->id)
            ->whereKey($id)
            ->lockForUpdate()
            ->first();

        if (!$lockedMilestone) {
            return [
                'error' => 'Milestone tidak ditemukan.',
                'status' => 404,
            ];
        }

        return [
            'contract' => $contract,
            'milestone' => $lockedMilestone,
        ];
    }

    /**
     * Mengubah nilai uang menjadi satuan sen untuk perbandingan.
     */
    private function amountToCents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
