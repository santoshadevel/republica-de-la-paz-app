<?php

use App\Enums\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Individual sessions (acompañamientos). The agenda is managed by admin:
        // a row is an available slot, a booked appointment, or a blocked time.
        // Individual sessions are paid per session (they do NOT consume membership
        // credits); a late cancellation (< window) charges a fee.
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete(); // specialty
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default(AppointmentStatus::Available->value)->index();

            $table->unsignedBigInteger('price')->nullable();            // minor unit (MoneyCast)
            $table->unsignedBigInteger('cancellation_fee')->nullable(); // charged on late cancel
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['practitioner_id', 'starts_at']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
