<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When set, an admin has let the coordinator edit existing participants even after payment or close.
     */
    public function up(): void
    {
        Schema::table('registration_groups', function (Blueprint $table) {
            $table->timestamp('edit_unlocked_at')->nullable()->after('reject_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_groups', function (Blueprint $table) {
            $table->dropColumn('edit_unlocked_at');
        });
    }
};
