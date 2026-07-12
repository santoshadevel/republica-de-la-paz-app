<?php

use App\Enums\AccountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Money accounts / cash boxes: where money actually sits (Caja chica,
        // Cuenta Banco 0082...). A transaction moves money in/out of one; a
        // transfer moves it between two.
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->default(AccountType::Cash->value);
            $table->string('account_number')->nullable();
            $table->unsignedBigInteger('opening_balance')->default(0); // minor unit (MoneyCast)
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
