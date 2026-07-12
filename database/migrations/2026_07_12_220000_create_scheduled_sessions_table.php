<?php

use App\Enums\SessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A dated occurrence of a group activity: this is where "who leads the
        // class on day X" lives (practitioner_id per occurrence). See the design
        // note in docs/REQUISITOS.md (plantilla vs ocurrencia).
        Schema::create('scheduled_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            // The facilitator for THIS occurrence (may differ from the default —
            // substitutions). Nullable until assigned.
            $table->foreignId('practitioner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('capacity'); // max seats for this occurrence
            $table->string('status')->default(SessionStatus::Scheduled->value)->index();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['starts_at', 'room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_sessions');
    }
};
