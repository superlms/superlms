<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Super Admin → Calendar: each person's own meetings, tasks and events
     * (App\Models\SuperAdmin\SuperAdminCalendarEvent). A row belongs to the
     * user who added it (user_id) and is shown to that user alone, so a
     * sub-super-admin with Calendar has a calendar of their own and never
     * sees the super admin's.
     */
    public function up(): void
    {
        if (Schema::hasTable('super_admin_calendar_events')) {
            return;
        }

        Schema::create('super_admin_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 16)->default('meeting');   // meeting | task | event
            $table->string('title', 191);
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('with_whom', 191)->nullable();      // a meeting's people
            $table->string('location', 255)->nullable();       // a place or a meeting link
            $table->text('notes')->nullable();
            $table->boolean('is_done')->default(false);        // a task ticked off
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_calendar_events');
    }
};
