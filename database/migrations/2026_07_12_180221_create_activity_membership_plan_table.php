<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Specific activities explicitly included by a plan (in addition to any
        // activity types listed in the plan's rules.included_types bag).
        Schema::create('activity_membership_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['membership_plan_id', 'activity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_membership_plan');
    }
};
