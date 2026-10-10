<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\SkillResource;
use App\Models\Category;
use App\Models\Skill;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategorySkillController extends Controller
{
    use ApiResponse;

    public function categories(): JsonResponse
    {
        $categories = Category::all();
        return $this->successResponse(
            CategoryResource::collection($categories),
            'Daftar kategori berhasil diambil'
        );
    }

    public function skills(): JsonResponse
    {
        $skills = Skill::all();
        return $this->successResponse(
            SkillResource::collection($skills),
            'Daftar keahlian berhasil diambil'
        );
    }
}
