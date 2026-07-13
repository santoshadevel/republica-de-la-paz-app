<?php

use App\Enums\FeeType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How each practitioner is paid, per activity (or a default when activity
        // is null). Drives the monthly honorarium liquidation. See REQUISITOS 4.9.
        Schema::create('fee_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_id')->constrained()->cascadeOnDelete();
            // Specific activity/specialty this rule applies to; null = default.
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default(FeeType::FixedPerSession->value);
            $table->unsignedBigInteger('fixed_amount')->nullable();   // minor unit (MoneyCast)
            $table->unsignedTinyInteger('percentage')->nullable();    // 0..100
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_schemes');
    }
};
