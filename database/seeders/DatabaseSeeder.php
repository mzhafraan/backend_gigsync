<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Skill;
use App\Models\Job;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Kategori
        $categories = [
            ['name' => 'Mobile Development', 'slug' => 'mobile-dev', 'icon_name' => 'phone_android'],
            ['name' => 'Web Development', 'slug' => 'web-dev', 'icon_name' => 'language'],
            ['name' => 'UI/UX Design', 'slug' => 'ui-ux', 'icon_name' => 'palette'],
            ['name' => 'Machine Learning & AI', 'slug' => 'ai-ml', 'icon_name' => 'memory'],
            ['name' => 'Content & Copywriting', 'slug' => 'writing', 'icon_name' => 'edit'],
        ];
        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // 2. Skill Tags
        $skills = ['Android', 'Kotlin', 'Jetpack Compose', 'Laravel', 'Flutter', 'Figma', 'MySQL', 'Python', 'React'];
        foreach ($skills as $skill) {
            Skill::firstOrCreate(['name' => $skill]);
        }

        // 3. User Demo Klien & Freelancer
        $client = User::firstOrCreate(['email' => 'client@gigsync.com'], [
            'name' => 'PT Telkom Digital Solusindo',
            'password' => Hash::make('Password123!'),
            'active_role' => 'CLIENT',
            'headline' => 'Enterprise Mobile Client',
            'bio' => 'Perusahaan inkubator produk digital kampus.',
            'wallet_balance' => 20000000.00,
            'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400'
        ]);

        $freelancer = User::firstOrCreate(['email' => 'freelancer@gigsync.com'], [
            'name' => 'Zhafran Mufadhdhol',
            'password' => Hash::make('Password123!'),
            'active_role' => 'FREELANCER',
            'headline' => 'Senior Native Android & Cloud Engineer',
            'bio' => 'Mahasiswa tingkat akhir Telkom University dengan keahlian Kotlin Multiplatform.',
            'hourly_rate' => 175000.00,
            'wallet_balance' => 1500000.00,
            'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400'
        ]);

        // 4. Job Dummy
        $catMobile = Category::where('slug', 'mobile-dev')->first();
        $job = Job::firstOrCreate(['title' => 'Pengembangan Fitur Peta Android GigSync'], [
            'client_id' => $client->id,
            'category_id' => $catMobile->id,
            'description' => 'Membangun integrasi Google Maps/OSM pada modul Post Job sesuai rubrik praktikum.',
            'budget_type' => 'FIXED',
            'budget_amount' => 4500000.00,
            'deadline' => now()->addDays(20)->toDateString(),
            'latitude' => -6.9740279,
            'longitude' => 107.6303481,
            'location_name' => 'Telkom University Landmark Tower (TULT)',
            'status' => 'OPEN'
        ]);

        $job->skills()->sync([1, 2, 3]); // Android, Kotlin, Compose
    }
}
