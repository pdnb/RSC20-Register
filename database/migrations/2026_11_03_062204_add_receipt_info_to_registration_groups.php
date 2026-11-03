<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shared receipt info per group; a participant's own receipt_* columns become an optional override
     * (receipt_name NULL = use the group's info).
     */
    public function up(): void
    {
        Schema::table('registration_groups', function (Blueprint $table) {
            $table->string('receipt_name', 200)->nullable()->after('pdpa_accepted_at');
            $table->text('receipt_address')->nullable()->after('receipt_name');
            $table->text('receipt_tax_id')->nullable()->after('receipt_address'); // encrypted cast
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->string('receipt_name')->nullable()->change();
            $table->text('receipt_address')->nullable()->change();
            $table->text('receipt_tax_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('receipt_name')->nullable(false)->change();
            $table->text('receipt_address')->nullable(false)->change();
            $table->text('receipt_tax_id')->nullable(false)->change();
        });

        Schema::table('registration_groups', function (Blueprint $table) {
            $table->dropColumn(['receipt_name', 'receipt_address', 'receipt_tax_id']);
        });
    }
};
