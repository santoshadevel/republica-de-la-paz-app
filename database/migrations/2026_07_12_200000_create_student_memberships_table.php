<?php

use App\Enums\MembershipStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A membership/pass a student actually bought. Plan rules are snapshotted
        // here so future catalog changes never alter past sales (see
        // docs/MODULO_MEMBRESIAS.md).
        Schema::create('student_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot of the plan at sale time.
            $table->unsignedInteger('credits_total')->nullable(); // null = unlimited
            $table->boolean('is_unlimited')->default(false);
            $table->unsignedBigInteger('price_paid')->default(0); // minor unit (MoneyCast)
            $table->string('currency_code', 3);

            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->default(MembershipStatus::Active->value)->index();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_memberships');
    }
};
