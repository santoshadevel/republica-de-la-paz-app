<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Which account the money entered / left.
            $table->foreignId('account_id')->nullable()->after('payment_method_id')
                ->constrained()->nullOnDelete();
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            // Default account money paid with this method lands in (e.g. cash →
            // Caja chica, transfer → a bank account). Auto-routes transactions.
            $table->foreignId('default_account_id')->nullable()->after('name')
                ->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_account_id');
        });
    }
};
