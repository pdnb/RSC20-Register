<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // executive | staff | student
            $table->string('name_th');
            $table->decimal('fee', 10, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('registration_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->unique()->constrained();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method', 20)->nullable(); // transfer | onsite
            $table->string('payment_status', 20)->default('pending')->index();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->string('slip_path')->nullable();
            $table->string('slip_reference', 100)->nullable();
            $table->dateTime('transferred_at')->nullable();
            $table->timestamp('slip_submitted_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('onsite_received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('onsite_received_at')->nullable();
            $table->timestamp('pdpa_accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique(); // RSC20-0001
            $table->foreignId('registration_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_type_id')->constrained();
            $table->string('section', 20)->index(); // executive | member
            $table->string('position');
            $table->string('title', 50);
            $table->string('full_name');
            $table->string('mobile_phone', 10);
            $table->text('national_id');
            $table->string('national_id_hash', 64)->unique();
            $table->string('food_type', 50);
            $table->string('food_allergy')->nullable();
            $table->string('shirt_size', 5);
            $table->string('badge_photo_path')->nullable();
            $table->decimal('fee_amount', 10, 2);
            $table->string('receipt_name');
            $table->text('receipt_address');
            $table->text('receipt_tax_id');
            $table->string('receipt_no', 50)->nullable();
            $table->ulid('qr_token')->unique();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('finance_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submission_no', 30)->unique();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->json('filters')->nullable();
            $table->unsignedInteger('item_count');
            $table->decimal('total_amount', 12, 2);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('finance_submission_participant', function (Blueprint $table) {
            $table->foreignId('finance_submission_id')->constrained()->cascadeOnDelete();
            // a participant can be submitted to the finance office only once
            $table->foreignId('participant_id')->unique()->constrained()->cascadeOnDelete();
            $table->primary(['finance_submission_id', 'participant_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50)->index();
            $table->nullableMorphs('subject');
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('finance_submission_participant');
        Schema::dropIfExists('finance_submissions');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('registration_groups');
        Schema::dropIfExists('participant_types');
    }
};
