<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategorySkillController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Autentikasi Publik
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/google', [AuthController::class, 'google']);

    // Data Master
    Route::get('/categories', [CategorySkillController::class, 'categories']);
    Route::get('/skills', [CategorySkillController::class, 'skills']);

    // Lowongan Kerja Publik
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{id}', [JobController::class, 'show']);

    // Ulasan Publik
    Route::get('/contracts/{id}/reviews', [ReviewController::class, 'contractReviews']);
    Route::get('/users/{id}/reviews', [ReviewController::class, 'userReviews']);

    // Rute Terproteksi (Bearer Token Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        // User & Profil
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/users/active-role', [AuthController::class, 'switchRole']);
        Route::put('/users/profile', [AuthController::class, 'updateProfile']);

        // Modul Pekerjaan
        Route::post('/jobs', [JobController::class, 'store']);
        Route::get('/client/jobs', [JobController::class, 'clientJobs']);

        // Modul Proposal
        Route::post('/jobs/{jobId}/proposals', [ProposalController::class, 'store']);
        Route::get('/jobs/{jobId}/proposals', [ProposalController::class, 'jobProposals']);
        Route::get('/proposals/my', [ProposalController::class, 'myProposals']);
        Route::put('/proposals/{id}/status', [ProposalController::class, 'updateStatus']);

        // Modul Kontrak & Escrow
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::get('/contracts/{id}', [ContractController::class, 'show']);
        Route::post('/contracts/{id}/submit', [ContractController::class, 'submit']);
        Route::post('/contracts/{id}/approve', [ContractController::class, 'approve']);
        Route::post('/contracts/{id}/revise', [ContractController::class, 'revise']);

        // Modul Ulasan
        Route::post('/contracts/{id}/reviews', [ReviewController::class, 'store']);

        // Modul Dompet
        Route::get('/wallet', [WalletController::class, 'index']);
        Route::post('/wallet/topup', [WalletController::class, 'topup']);

        // Modul Obrolan (Chat)
        Route::get('/chats', [ChatController::class, 'index']);
        Route::post('/chats', [ChatController::class, 'startChat']);
        Route::get('/chats/{id}/messages', [ChatController::class, 'messages']);
        Route::post('/chats/{id}/messages', [ChatController::class, 'sendMessage']);
        Route::put('/chats/{id}/read', [ChatController::class, 'markAsRead']);
    });
});
