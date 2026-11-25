
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_milestones', function (Blueprint $table) {
            $table->id();

            // Relasi ke kontrak utama
            $table->foreignId('contract_id')
                ->constrained('contracts')
                ->cascadeOnDelete();

            // Informasi milestone
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('deadline')->nullable();

            // Status pengerjaan
            $table->enum('status', [
                'PENDING',
                'IN_PROGRESS',
                'UNDER_REVIEW',
                'REVISION',
                'APPROVED',
            ])->default('PENDING');

            // Data pengumpulan pekerjaan
            $table->text('submission_notes')->nullable();
            $table->string('submission_url', 500)->nullable();
            $table->text('revision_notes')->nullable();

            // Urutan milestone dalam satu kontrak
            $table->unsignedInteger('sort_order')->default(0);

            // Waktu aktivitas
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // Index untuk pencarian milestone
            $table->index(['contract_id', 'status']);
            $table->index(['contract_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_milestones');
    }
};
