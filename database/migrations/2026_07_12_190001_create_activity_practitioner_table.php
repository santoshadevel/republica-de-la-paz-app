<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which activities each practitioner leads (their specialties). Drives the
        // landing "Referentes" list, individual-session responsible professional,
        // and per-service fee settlement later.
        Schema::create('activity_practitioner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'practitioner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_practitioner');
    }
};
