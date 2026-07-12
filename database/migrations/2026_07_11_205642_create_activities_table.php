<?php

use App\Enums\ActivityType;
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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default(ActivityType::GroupClass->value);
            $table->text('description')->nullable();
            $table->unsignedInteger('default_duration_minutes')->nullable();
            $table->string('color', 7)->nullable(); // hex color for the calendar UI
            // Default room where this activity usually takes place (optional).
            $table->foreignId('default_room_id')->nullable()->constrained('rooms')->nullOnDelete();
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
        Schema::dropIfExists('activities');
    }
};
