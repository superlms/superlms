<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SMS through Bhash SMS (App\Services\SchoolSms): which school has SMS
     * turned on — off until the super admin turns it on — and every SMS sent,
     * one row each. With "welcome" on, a new student or teacher gets their
     * login by SMS only, in place of the welcome email and WhatsApp.
     *
     * The message text is never kept: it carries the password.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sms_school_features')) {
            Schema::create('sms_school_features', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('feature', 30);             // welcome
                $table->boolean('enabled')->default(false);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'feature']);
            });
        }

        if (!Schema::hasTable('sms_messages')) {
            Schema::create('sms_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
                $table->string('feature', 30);             // welcome | credentials
                $table->string('template', 60);            // student_welcome | teacher_welcome
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('student_detail_id')->nullable();
                $table->string('to_phone', 20)->nullable();
                $table->string('provider_message_id', 120)->nullable();
                $table->string('status', 20)->default('sending');   // sending | sent | failed
                $table->text('error')->nullable();
                $table->unsignedSmallInteger('parts')->default(1);  // 160-character parts it went as
                $table->timestamps();

                $table->index(['organization_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_school_features');
    }
};
