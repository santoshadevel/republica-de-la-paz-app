<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Generic fiscal id (RUC in PY, VAT elsewhere). Optional; uniqueness,
            // if needed, is validated in code — white-label friendly.
            $table->string('tax_id')->nullable()->after('identity_number');
            // How the student found the center (marketing channel). Free-form so
            // each brand can use its own channels; not a hardcoded enum.
            $table->string('acquisition_source')->nullable()->after('birth_date');
            // What the student wants from their practice (CRM context).
            $table->text('goals')->nullable()->after('acquisition_source');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['tax_id', 'acquisition_source', 'goals']);
        });
    }
};
