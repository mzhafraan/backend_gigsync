<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('restrict');
            $table->foreignId('client_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('freelancer_id')->constrained('users')->onDelete('restrict');
            $table->decimal('agreed_amount', 12, 2);
            $table->enum('escrow_status', ['HELD', 'RELEASED', 'REFUNDED'])->default('HELD');
            $table->enum('contract_status', ['ACTIVE', 'UNDER_REVIEW', 'REVISION', 'COMPLETED', 'CANCELLED'])->default('ACTIVE');
            $table->text('submission_notes')->nullable();
            $table->string('submission_url', 500)->nullable();
            $table->timestamps();

            $table->index(['client_id', 'contract_status']);
            $table->index(['freelancer_id', 'contract_status']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewee_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'reviewer_id']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('set null');
            $table->enum('type', ['TOPUP', 'ESCROW_LOCK', 'ESCROW_RELEASE', 'REFUND', 'WITHDRAWAL']);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->string('description', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('contracts');
    }
};
