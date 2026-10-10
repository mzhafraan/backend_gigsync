<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->string('title', 255);
            $table->text('description');
            $table->enum('budget_type', ['FIXED', 'HOURLY'])->default('FIXED');
            $table->decimal('budget_amount', 12, 2);
            $table->date('deadline');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_name', 255)->nullable();
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'COMPLETED', 'CLOSED'])->default('OPEN');
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index('deadline');
        });

        Schema::create('job_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->unsignedInteger('skill_id');
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
            $table->unique(['job_id', 'skill_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('job_skills');
        Schema::dropIfExists('jobs');
    }
};
