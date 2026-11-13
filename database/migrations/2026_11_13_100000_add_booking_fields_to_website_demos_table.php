<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A website demo request books its own meeting (App\Http\Controllers\DemoBookingController):
     * the thank-you carries a private link (booking_token) where the person picks
     * a free time; the meeting then gets its Meet link and goes on the Calendar.
     * Super Admin → Calendar → Queries lists them with their status.
     *
     * status: pending (no time picked yet) | booked | completed | cancelled.
     * messages: what was sent and how it went, [{type, email, whatsapp, at}, …].
     */
    public function up(): void
    {
        if (!Schema::hasTable('website_demos')) {
            return;
        }

        Schema::table('website_demos', function (Blueprint $table) {
            if (!Schema::hasColumn('website_demos', 'status')) {
                $table->string('status', 16)->default('pending')->index();
            }
            if (!Schema::hasColumn('website_demos', 'booking_token')) {
                $table->string('booking_token', 64)->nullable()->unique();
            }
            if (!Schema::hasColumn('website_demos', 'meeting_at')) {
                $table->dateTime('meeting_at')->nullable()->index();
            }
            if (!Schema::hasColumn('website_demos', 'meeting_minutes')) {
                $table->unsignedSmallInteger('meeting_minutes')->nullable();
            }
            if (!Schema::hasColumn('website_demos', 'meet_link')) {
                $table->string('meet_link', 255)->nullable();
            }
            if (!Schema::hasColumn('website_demos', 'meet_provider')) {
                $table->string('meet_provider', 16)->nullable();   // google | jitsi
            }
            if (!Schema::hasColumn('website_demos', 'google_event_id')) {
                $table->string('google_event_id', 255)->nullable();
            }
            if (!Schema::hasColumn('website_demos', 'calendar_event_id')) {
                $table->unsignedBigInteger('calendar_event_id')->nullable();
            }
            if (!Schema::hasColumn('website_demos', 'booked_at')) {
                $table->timestamp('booked_at')->nullable();
            }
            if (!Schema::hasColumn('website_demos', 'messages')) {
                $table->json('messages')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('website_demos')) {
            return;
        }

        Schema::table('website_demos', function (Blueprint $table) {
            if (Schema::hasColumn('website_demos', 'booking_token')) {
                $table->dropUnique(['booking_token']);
            }
            foreach (['status', 'meeting_at'] as $indexed) {
                if (Schema::hasColumn('website_demos', $indexed)) {
                    $table->dropIndex([$indexed]);
                }
            }
        });

        Schema::table('website_demos', function (Blueprint $table) {
            foreach (['status', 'booking_token', 'meeting_at', 'meeting_minutes', 'meet_link', 'meet_provider',
                'google_event_id', 'calendar_event_id', 'booked_at', 'messages'] as $col) {
                if (Schema::hasColumn('website_demos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
