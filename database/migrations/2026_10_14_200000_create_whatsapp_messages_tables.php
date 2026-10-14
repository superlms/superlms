<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every WhatsApp message the platform sends, one row each, with whom it is
     * charged to (App\Services\SchoolWhatsApp): the school pays for an absent
     * alert, a fee received and a Send Credentials at the rate on the row; the
     * welcome on adding someone is free. Meta's status webhook moves a row on
     * to delivered / read / failed — a failed message is not charged.
     *
     * And which school has which paid message turned on — off until the
     * super admin turns it on.
     */
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
                $table->string('feature', 30);            // absent_alert | fee_received | credentials | welcome
                $table->string('template', 60);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('student_detail_id')->nullable();
                $table->string('to_phone', 20)->nullable();
                // One message per thing: an absent alert per student and day, a fee message per payment.
                $table->string('dedupe_key', 120)->nullable()->unique();
                $table->string('wa_message_id', 120)->nullable()->index();
                $table->string('status', 20)->default('sent');   // sending | sent | delivered | read | failed
                $table->text('error')->nullable();
                $table->boolean('billable')->default(false);
                $table->decimal('rate', 8, 2)->default(0);
                $table->timestamps();

                $table->index(['organization_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('whatsapp_school_features')) {
            Schema::create('whatsapp_school_features', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('feature', 30);             // absent_alert | fee_received
                $table->boolean('enabled')->default(false);
                $table->timestamps();

                $table->unique(['organization_id', 'feature']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_school_features');
        Schema::dropIfExists('whatsapp_messages');
    }
};
