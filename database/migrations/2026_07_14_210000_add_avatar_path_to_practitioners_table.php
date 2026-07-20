<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portrait shown on the public landing ("Ciudadanos de la República").
 *
 * It lives on `practitioners` rather than `users` because a practitioner may
 * have no login account at all (`practitioners.user_id` is nullable), and
 * hanging the photo off the account would leave those without a portrait.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practitioners', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('practitioners', function (Blueprint $table): void {
            $table->dropColumn('avatar_path');
        });
    }
};
