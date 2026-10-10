<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletTransactionResource;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->latest('created_at')
            ->get();

        return $this->successResponse([
            'balance' => (float) $user->wallet_balance,
            'transactions' => WalletTransactionResource::collection($transactions),
        ], 'Informasi dompet berhasil diambil');
    }

    public function topup(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'amount' => ['required', 'numeric', 'min:10000'],
        ]);

        $amount = (float) $request->input('amount');

        $transaction = DB::transaction(function () use ($user, $amount) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            $lockedUser->wallet_balance += $amount;
            $lockedUser->save();

            return WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'TOPUP',
                'amount' => $amount,
                'balance_after' => $lockedUser->wallet_balance,
                'description' => "Pengisian saldo dompet (Top-Up) senilai Rp " . number_format($amount, 0, ',', '.'),
            ]);
        });

        return $this->successResponse([
            'balance' => (float) $user->fresh()->wallet_balance,
            'transaction' => new WalletTransactionResource($transaction),
        ], 'Top-up saldo berhasil dieksekusi', 201);
    }
}
