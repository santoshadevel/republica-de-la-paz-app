<?php

use App\Enums\BookingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A student's reservation for a scheduled group session. The credit it
        // consumed (if any) is linked so cancellations can refund the right one.
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheduled_session_id')->constrained()->cascadeOnDelete();
            // Which membership paid for this booking (null for unlimited/manual).
            $table->foreignId('student_membership_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default(BookingStatus::Booked->value)->index();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['scheduled_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
