<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Internal movement of money between two accounts. Not income nor expense
        // (does not affect the result), just relocates funds.
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('to_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('amount'); // minor unit (MoneyCast)
            $table->date('occurred_on');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('occurred_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
