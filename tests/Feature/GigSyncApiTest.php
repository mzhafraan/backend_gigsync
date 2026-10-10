<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GigSyncApiTest extends TestCase
{
    public function test_user_can_register_with_camel_case_payload(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Freelancer',
            'email' => 'testfreelancer@telkomuniversity.ac.id',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi akun berhasil',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'activeRole',
                        'walletBalance',
                    ],
                ],
            ]);
    }

    public function test_user_can_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'freelancer@gigsync.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'email', 'name', 'activeRole'],
                ],
            ]);
    }

    public function test_can_list_categories_and_skills(): void
    {
        $catResponse = $this->getJson('/api/v1/categories');
        $catResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $skillResponse = $this->getJson('/api/v1/skills');
        $skillResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_browse_jobs_feed(): void
    {
        $response = $this->getJson('/api/v1/jobs');
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'clientId',
                        'clientName',
                        'categoryName',
                        'budgetType',
                        'budgetAmount',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_complete_escrow_and_contract_lifecycle(): void
    {
        $client = User::where('email', 'client@gigsync.com')->first();
        $freelancer = User::where('email', 'freelancer@gigsync.com')->first();
        $category = Category::first();

        // 1. Client creates a job
        $jobResponse = $this->actingAs($client, 'sanctum')->postJson('/api/v1/jobs', [
            'title' => 'Tubes Pemrograman Mobile',
            'description' => 'Membangun aplikasi Android dengan arsitektur MVI',
            'categoryId' => $category->id,
            'budgetType' => 'FIXED',
            'budgetAmount' => 2000000,
            'deadline' => now()->addDays(14)->toDateString(),
            'locationName' => 'Gedung Tokong Nanas (TULT)',
            'skills' => ['Android', 'Kotlin', 'Jetpack Compose'],
        ]);

        $jobResponse->assertStatus(201);
        $jobId = $jobResponse->json('data.id');

        // 2. Freelancer submits proposal
        $proposalResponse = $this->actingAs($freelancer, 'sanctum')->postJson("/api/v1/jobs/{$jobId}/proposals", [
            'bidAmount' => 2000000,
            'estimatedDays' => 10,
            'coverLetter' => 'Saya siap menyelesaikan tugas tepat waktu sesuai spesifikasi.',
        ]);

        $proposalResponse->assertStatus(201);
        $proposalId = $proposalResponse->json('data.id');

        // 3. Client accepts proposal (Escrow HELD)
        $initialClientBalance = $client->fresh()->wallet_balance;
        $acceptResponse = $this->actingAs($client, 'sanctum')->putJson("/api/v1/proposals/{$proposalId}/status", [
            'status' => 'ACCEPTED',
        ]);

        $acceptResponse->assertStatus(200);
        $contractId = $acceptResponse->json('data.contract.id');

        // Verify client balance decreased by bidAmount
        $this->assertEquals($initialClientBalance - 2000000, $client->fresh()->wallet_balance);

        // 4. Freelancer submits deliverables (UNDER_REVIEW)
        $submitResponse = $this->actingAs($freelancer, 'sanctum')->postJson("/api/v1/contracts/{$contractId}/submit", [
            'submissionNotes' => 'Repositori GitHub dan APK telah selesai.',
            'submissionUrl' => 'https://github.com/d3if/tubes-mobile',
        ]);

        $submitResponse->assertStatus(200)
            ->assertJsonPath('data.contractStatus', 'UNDER_REVIEW');

        // 5. Client approves contract (Escrow RELEASED, 10% platform fee)
        $initialFreelancerBalance = $freelancer->fresh()->wallet_balance;
        $approveResponse = $this->actingAs($client, 'sanctum')->postJson("/api/v1/contracts/{$contractId}/approve");

        $approveResponse->assertStatus(200)
            ->assertJsonPath('data.contractStatus', 'COMPLETED')
            ->assertJsonPath('data.escrowStatus', 'RELEASED');

        // Agreed amount: 2.000.000, fee 10% = 200.000, net = 1.800.000
        $this->assertEquals($initialFreelancerBalance + 1800000, $freelancer->fresh()->wallet_balance);

        // 6. Client submits review
        $reviewResponse = $this->actingAs($client, 'sanctum')->postJson("/api/v1/contracts/{$contractId}/reviews", [
            'rating' => 5,
            'comment' => 'Pengerjaan sangat cepat dan rapi!',
        ]);

        $reviewResponse->assertStatus(201)
            ->assertJsonPath('data.rating', 5);
    }
}
