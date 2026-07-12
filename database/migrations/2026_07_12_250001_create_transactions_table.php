<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every economic movement of the platform: membership sales, session and
        // event income, rent, practitioner fees, commissions, taxes — anything.
        // Generic on purpose (income or expense), classified by category,
        // subcategory (via category tree), cost center and payment method.
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // income | expense
            $table->unsignedBigInteger('amount'); // minor unit (MoneyCast), always positive
            $table->date('occurred_on');
            $table->string('description')->nullable();

            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();

            // Optional link to what generated it (membership sale, appointment...).
            $table->nullableMorphs('source');

            // Invoicing (generic / white-label): tax_id + tax_condition, not RUC/IVA.
            $table->boolean('invoice_issued')->default(false);
            $table->string('invoice_number')->nullable();
            $table->string('invoice_business_name')->nullable();
            $table->string('invoice_tax_id')->nullable();
            $table->string('invoice_tax_condition')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
