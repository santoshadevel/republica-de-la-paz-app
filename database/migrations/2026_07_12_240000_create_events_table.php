<?php

use App\Enums\EventStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One-off events: workshops, talks, retreats, circles, trainings.
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();       // stored path
            $table->string('location')->nullable();    // free text (may be offsite)
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->unsignedBigInteger('price')->nullable(); // minor unit (MoneyCast)
            $table->unsignedInteger('capacity')->nullable(); // null = unlimited
            $table->string('status')->default(EventStatus::Scheduled->value)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // Facilitators of an event (many-to-many with practitioners).
        Schema::create('event_practitioner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'practitioner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_practitioner');
        Schema::dropIfExists('events');
    }
};
