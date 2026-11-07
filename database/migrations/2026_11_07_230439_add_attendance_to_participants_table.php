<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            // conference | board_game | both — board game only has no fee, food or shirt
            $table->string('attendance', 20)->default('conference')->index()->after('section');
            $table->string('food_type', 50)->nullable()->change();
            $table->string('shirt_size', 5)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropIndex(['attendance']);
            $table->dropColumn('attendance');
            $table->string('food_type', 50)->nullable(false)->change();
            $table->string('shirt_size', 5)->nullable(false)->change();
        });
    }
};
