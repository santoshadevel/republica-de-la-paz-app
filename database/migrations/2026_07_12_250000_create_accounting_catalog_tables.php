<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Accounting categories, hierarchical: a top-level category (e.g.
        // "Honorarios") has subcategories (e.g. "Profesores"). Each belongs to
        // income or expense.
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // income | expense
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'parent_id']);
        });

        // Business units a movement can be assigned to (Yoga, Terapias, etc.).
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // How money moved (cash, transfer, POS, card...).
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('categories');
    }
};
