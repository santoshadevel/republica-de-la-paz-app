<?php

use App\Enums\MembershipOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A student's request to buy a pass. Falls into the system as "pending"
        // and staff approve it manually (→ SellMembership); no payment gateway yet.
        Schema::create('membership_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(MembershipOrderStatus::Pending->value)->index();
            $table->unsignedBigInteger('price'); // snapshot in minor units
            $table->foreignId('student_membership_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_orders');
    }
};
