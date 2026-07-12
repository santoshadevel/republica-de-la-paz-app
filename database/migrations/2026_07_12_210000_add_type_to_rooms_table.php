<?php

use App\Enums\RoomType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // A room can be a physical space or a virtual (online) one.
            $table->string('type')->default(RoomType::Physical->value)->after('name');
            // Meeting link for virtual rooms (null for physical ones).
            $table->string('meeting_url')->nullable()->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['type', 'meeting_url']);
        });
    }
};
