<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Http\Resources\ProposalResource;
use App\Models\Job;
use App\Models\Proposal;
use App\Services\EscrowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProposalController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly EscrowService $escrowService)
    {
    }

    public function store(Request $request, string|int $jobId): JsonResponse
    {
        $user = $request->user();

        $job = Job::find($jobId);
        if (!$job) {
            return $this->errorResponse('Pekerjaan tidak ditemukan', 404);
        }

        if ($job->status !== 'OPEN') {
            return $this->errorResponse('Lowongan pekerjaan ini sudah tidak menerima proposal.', 400);
        }

        if ($job->client_id === $user->id) {
            return $this->errorResponse('Anda tidak dapat mengajukan proposal pada pekerjaan Anda sendiri.', 400);
        }

        // Check if already applied
        $existing = Proposal::where('job_id', $job->id)
            ->where('freelancer_id', $user->id)
            ->first();

        if ($existing) {
            return $this->errorResponse('Anda sudah mengajukan proposal untuk pekerjaan ini.', 400);
        }

        // Normalize camelCase to snake_case
        $bidAmount = $request->input('bidAmount') ?? $request->input('bid_amount');
        $estimatedDays = $request->input('estimatedDays') ?? $request->input('estimated_days');
        $coverLetter = $request->input('coverLetter') ?? $request->input('cover_letter');

        $request->merge([
            'bid_amount' => $bidAmount,
            'estimated_days' => $estimatedDays,
            'cover_letter' => $coverLetter,
        ]);

        $request->validate([
            'bid_amount' => ['required', 'numeric', 'min:0'],
            'estimated_days' => ['required', 'integer', 'min:1'],
            'cover_letter' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // 5MB
        ]);

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = 'proposal_' . $job->id . '_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('proposals', $filename, 'public');
            $attachmentUrl = url(Storage::url($path));
        }

        $proposal = Proposal::create([
            'job_id' => $job->id,
            'freelancer_id' => $user->id,
            'bid_amount' => $bidAmount,
            'estimated_days' => $estimatedDays,
            'cover_letter' => $coverLetter,
            'attachment_url' => $attachmentUrl,
            'status' => 'PENDING',
        ]);

        $proposal->load(['job', 'freelancer']);

        return $this->successResponse(
            new ProposalResource($proposal),
            'Proposal penawaran berhasil diajukan',
            201
        );
    }

    public function jobProposals(Request $request, string|int $jobId): JsonResponse
    {
        $user = $request->user();

        $job = Job::find($jobId);
        if (!$job) {
            return $this->errorResponse('Pekerjaan tidak ditemukan', 404);
        }

        if ($job->client_id !== $user->id) {
            return $this->errorResponse('Anda tidak memiliki hak akses melihat pelamar lowongan ini.', 403);
        }

        $proposals = Proposal::where('job_id', $job->id)
            ->with(['job', 'freelancer'])
            ->latest()
            ->get();

        return $this->successResponse(
            ProposalResource::collection($proposals),
            'Daftar proposal pelamar berhasil diambil'
        );
    }

    public function myProposals(Request $request): JsonResponse
    {
        $user = $request->user();

        $proposals = Proposal::where('freelancer_id', $user->id)
            ->with(['job', 'freelancer'])
            ->latest()
            ->get();

        return $this->successResponse(
            ProposalResource::collection($proposals),
            'Daftar proposal saya berhasil diambil'
        );
    }

    public function updateStatus(Request $request, string|int $id): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'status' => ['required', Rule::in(['ACCEPTED', 'REJECTED'])],
        ]);

        $proposal = Proposal::with(['job', 'freelancer'])->find($id);
        if (!$proposal) {
            return $this->errorResponse('Proposal tidak ditemukan', 404);
        }

        if ($proposal->job->client_id !== $user->id) {
            return $this->errorResponse('Hanya pemilik pekerjaan yang dapat mengubah status proposal.', 403);
        }

        if ($proposal->status !== 'PENDING') {
            return $this->errorResponse("Proposal sudah berstatus {$proposal->status} dan tidak dapat diubah lagi.", 400);
        }

        $newStatus = $request->input('status');

        if ($newStatus === 'ACCEPTED') {
            try {
                $contract = $this->escrowService->acceptProposalAndHoldEscrow($proposal, $user);
                $contract->load(['job', 'client', 'freelancer']);

                return $this->successResponse([
                    'proposal' => new ProposalResource($proposal->fresh()),
                    'contract' => new ContractResource($contract),
                ], 'Proposal berhasil diterima dan dana escrow telah dikunci.');
            } catch (\Exception $e) {
                return $this->errorResponse($e->getMessage(), 400);
            }
        } else {
            $proposal->update(['status' => 'REJECTED']);

            return $this->successResponse(
                new ProposalResource($proposal->fresh()),
                'Proposal telah ditolak.'
            );
        }
    }
}
