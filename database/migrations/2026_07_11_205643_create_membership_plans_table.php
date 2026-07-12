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
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // stable programmatic reference
            $table->text('description')->nullable();
            // Price stored as an integer in the currency's minor unit (see MoneyCast).
            $table->unsignedBigInteger('price')->default(0);
            // Flexible behaviour bag: practice credits, unlimited flag, validity days,
            // cancellation windows, etc. Keeps plan rules data-driven, not hardcoded.
            $table->json('rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
