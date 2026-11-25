<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
            $table->dropUnique('users_google_id_unique');

            $table->unique(['email', 'active_role'], 'users_email_role_unique');
            $table->unique(['google_id', 'active_role'], 'users_google_role_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_role_unique');
            $table->dropUnique('users_google_role_unique');

            $table->unique('email', 'users_email_unique');
            $table->unique('google_id', 'users_google_id_unique');
        });
    }
};

