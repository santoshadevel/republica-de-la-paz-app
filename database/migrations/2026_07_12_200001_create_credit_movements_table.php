<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Signed ledger of every change to a membership's practice balance.
        // Balance = SUM(amount). Keeps the history auditable (sale, consumption,
        // refund, manual adjustment, expiration).
        Schema::create('credit_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_membership_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->integer('amount'); // signed: +grant, -consume
            $table->string('reason')->nullable();
            // Filled in Fase 5 when bookings exist (links a movement to its booking).
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            // Who performed a manual adjustment (nullable for system movements).
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_movements');
    }
};
