<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recurring weekly availability blocks per practitioner (when they are
        // willing to teach / see clients). day_of_week is ISO (1=Mon .. 7=Sun).
        Schema::create('practitioner_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1..7 (ISO-8601)
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['practitioner_id', 'day_of_week']);
        });

        // Date-specific overrides: a closed day (is_available=false, no times) or
        // special hours (is_available=true with a time block). Overrides the
        // weekly schedule for that date.
        Schema::create('practitioner_availability_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_available')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['practitioner_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_availability_exceptions');
        Schema::dropIfExists('practitioner_availabilities');
    }
};
