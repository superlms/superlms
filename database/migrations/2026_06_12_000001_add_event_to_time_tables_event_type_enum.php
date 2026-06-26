<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The admin Add Event form offers an "Event" type, but the
     * time_tables.event_type enum never included 'event' — MySQL
     * (strict mode) rejected the INSERT, so saving failed.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE time_tables MODIFY COLUMN event_type ENUM('class','lab','meeting','seminar','workshop','sports','exam','holiday','conference','event','other') NOT NULL DEFAULT 'class'");
    }

    public function down(): void
    {
        // Re-map 'event' rows before shrinking the enum, or the ALTER fails.
        DB::table('time_tables')->where('event_type', 'event')->update(['event_type' => 'other']);

        DB::statement("ALTER TABLE time_tables MODIFY COLUMN event_type ENUM('class','lab','meeting','seminar','workshop','sports','exam','holiday','conference','other') NOT NULL DEFAULT 'class'");
    }
};
