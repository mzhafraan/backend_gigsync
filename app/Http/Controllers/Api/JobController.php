<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Models\Skill;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Job::with(['client', 'category', 'skills'])
            ->withCount('proposals');

        // Filter search keyword
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%");
            });
        }

        // Filter category_id or categoryId
        $categoryId = $request->input('category_id') ?? $request->input('categoryId');
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Filter status (default to OPEN unless specified)
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'OPEN');
        }

        $jobs = $query->latest()->get();

        return $this->successResponse(
            JobResource::collection($jobs),
            'Daftar lowongan pekerjaan berhasil diambil'
        );
    }

    public function show(string|int $id): JsonResponse
    {
        $job = Job::with(['client', 'category', 'skills'])
            ->withCount('proposals')
            ->find($id);

        if (!$job) {
            return $this->errorResponse('Pekerjaan tidak ditemukan', 404);
        }

        return $this->successResponse(
            new JobResource($job),
            'Detail lowongan pekerjaan berhasil diambil'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Normalize camelCase to snake_case inputs
        $categoryId = $request->input('categoryId') ?? $request->input('category_id');
        $budgetType = $request->input('budgetType') ?? $request->input('budget_type') ?? 'FIXED';
        $budgetAmount = $request->input('budgetAmount') ?? $request->input('budget_amount');
        $locationName = $request->input('locationName') ?? $request->input('location_name');

        $request->merge([
            'category_id' => $categoryId,
            'budget_type' => $budgetType,
            'budget_amount' => $budgetAmount,
            'location_name' => $locationName,
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'budget_type' => ['required', Rule::in(['FIXED', 'HOURLY'])],
            'budget_amount' => ['required', 'numeric', 'min:0'],
            'deadline' => ['required', 'date'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'array'],
        ]);

        $job = Job::create([
            'client_id' => $user->id,
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'budget_type' => $validated['budget_type'],
            'budget_amount' => $validated['budget_amount'],
            'deadline' => $validated['deadline'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'location_name' => $validated['location_name'] ?? null,
            'status' => 'OPEN',
        ]);

        if (!empty($validated['skills'])) {
            $skillIds = [];
            foreach ($validated['skills'] as $skillItem) {
                if (is_numeric($skillItem)) {
                    $skillIds[] = (int) $skillItem;
                } else {
                    $skill = Skill::firstOrCreate(['name' => trim($skillItem)]);
                    $skillIds[] = $skill->id;
                }
            }
            $job->skills()->sync($skillIds);
        }

        $job->load(['client', 'category', 'skills']);

        return $this->successResponse(
            new JobResource($job),
            'Lowongan pekerjaan berhasil dibuat',
            201
        );
    }

    public function clientJobs(Request $request): JsonResponse
    {
        $user = $request->user();

        $jobs = Job::where('client_id', $user->id)
            ->with(['client', 'category', 'skills'])
            ->withCount('proposals')
            ->latest()
            ->get();

        return $this->successResponse(
            JobResource::collection($jobs),
            'Daftar lowongan milik klien berhasil diambil'
        );
    }
}
