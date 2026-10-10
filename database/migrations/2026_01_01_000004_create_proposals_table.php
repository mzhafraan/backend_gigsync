<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('freelancer_id')->constrained('users')->onDelete('cascade');
            $table->decimal('bid_amount', 12, 2);
            $table->integer('estimated_days');
            $table->text('cover_letter');
            $table->string('attachment_url', 500)->nullable();
            $table->enum('status', ['PENDING', 'ACCEPTED', 'REJECTED'])->default('PENDING');
            $table->timestamps();

            $table->unique(['job_id', 'freelancer_id']);
            $table->index(['freelancer_id', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('proposals');
    }
};
