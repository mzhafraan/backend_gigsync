<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EscrowService
{
    /**
     * Menerima proposal & mengunci dana escrow Klien ke penampungan
     */
    public function acceptProposalAndHoldEscrow(Proposal $proposal, User $client): Contract
    {
        return DB::transaction(function () use ($proposal, $client) {
            // 1. Lock baris akun klien secara pesimistik agar saldo tidak bisa dipakai ganda (double-spent)
            $lockedClient = User::where('id', $client->id)->lockForUpdate()->first();

            $agreedAmount = $proposal->bid_amount;

            if ($lockedClient->wallet_balance < $agreedAmount) {
                throw new HttpException(400, 'Saldo dompet tidak mencukupi untuk mendepositkan dana escrow.');
            }

            // 2. Kurangi saldo aktif klien
            $lockedClient->wallet_balance -= $agreedAmount;
            $lockedClient->save();

            // 3. Ubah status proposal
            $proposal->update(['status' => 'ACCEPTED']);

            // 4. Buat kontrak baru
            $contract = Contract::create([
                'job_id' => $proposal->job_id,
                'client_id' => $lockedClient->id,
                'freelancer_id' => $proposal->freelancer_id,
                'agreed_amount' => $agreedAmount,
                'escrow_status' => 'HELD',
                'contract_status' => 'ACTIVE'
            ]);

            // 5. Catat mutasi penahanan saldo
            WalletTransaction::create([
                'user_id' => $lockedClient->id,
                'contract_id' => $contract->id,
                'type' => 'ESCROW_LOCK',
                'amount' => -$agreedAmount,
                'balance_after' => $lockedClient->wallet_balance,
                'description' => "Penguncian dana escrow untuk kontrak #{$contract->id}"
            ]);

            // 6. Update status pekerjaan menjadi IN_PROGRESS
            $proposal->job->update(['status' => 'IN_PROGRESS']);

            return $contract;
        });
    }

    /**
     * Menyetujui hasil kerja & mencairkan dana escrow ke Freelancer (Potong Platform Fee 10%)
     */
    public function releaseEscrowToFreelancer(Contract $contract): void
    {
        DB::transaction(function () use ($contract) {
            $lockedFreelancer = User::where('id', $contract->freelancer_id)->lockForUpdate()->first();

            $platformFee = $contract->agreed_amount * 0.10; // Potongan 10%
            $netEarnings = $contract->agreed_amount - $platformFee;

            // Tambah saldo aktif freelancer
            $lockedFreelancer->wallet_balance += $netEarnings;
            $lockedFreelancer->save();

            // Ubah status kontrak & escrow
            $contract->update([
                'escrow_status' => 'RELEASED',
                'contract_status' => 'COMPLETED'
            ]);

            // Catat mutasi pencairan saldo
            WalletTransaction::create([
                'user_id' => $lockedFreelancer->id,
                'contract_id' => $contract->id,
                'type' => 'ESCROW_RELEASE',
                'amount' => $netEarnings,
                'balance_after' => $lockedFreelancer->wallet_balance,
                'description' => "Pencairan hasil kerja kontrak #{$contract->id} (Fee platform 10%)"
            ]);

            // Ubah status pekerjaan menjadi COMPLETED
            $contract->job->update(['status' => 'COMPLETED']);
        });
    }

    /**
     * Mengembalikan dana escrow ke Klien (Refund)
     */
    public function refundEscrowToClient(Contract $contract): void
    {
        DB::transaction(function () use ($contract) {
            $lockedClient = User::where('id', $contract->client_id)->lockForUpdate()->first();

            $lockedClient->wallet_balance += $contract->agreed_amount;
            $lockedClient->save();

            $contract->update([
                'escrow_status' => 'REFUNDED',
                'contract_status' => 'CANCELLED'
            ]);

            WalletTransaction::create([
                'user_id' => $lockedClient->id,
                'contract_id' => $contract->id,
                'type' => 'REFUND',
                'amount' => $contract->agreed_amount,
                'balance_after' => $lockedClient->wallet_balance,
                'description' => "Pengembalian dana escrow untuk kontrak #{$contract->id}"
            ]);

            $contract->job->update(['status' => 'OPEN']);
        });
    }
}
