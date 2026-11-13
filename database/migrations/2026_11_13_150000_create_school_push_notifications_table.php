<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A school's own push notifications (admin → More → Push Notification): sent
 * by its admin to its students and teachers — all of them, a class or a
 * section, or people picked by name. Kept apart from the Super Admin's
 * push_notification_campaigns. user_ids are the users it went to, so a
 * delete can take it out of exactly their notification bells.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_push_notifications')) {
            return;
        }

        Schema::create('school_push_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->string('title');
            $table->text('body');
            $table->string('audience', 20);              // students | teachers | everyone | selected
            $table->unsignedBigInteger('standard_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('audience_label')->nullable(); // "Class 6 · Section A", "Rahul Sharma, Priya Verma" …
            $table->json('user_ids')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('device_count')->default(0);
            $table->unsignedInteger('web_count')->default(0);
            $table->json('device_breakdown')->nullable();
            $table->boolean('delivered')->default(false);
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_push_notifications');
    }
};
