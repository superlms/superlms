<?php

use App\Services\IdCardService;
use App\Support\AcademicYear;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ID cards already made are brought in line with how cards are made from now
 * on (the school's ask, 2026-10-01):
 * - every card runs to the end of the session, 31 March 2027, and so does
 *   each school's auto-generation setting (the nightly run's expiry);
 * - every card's QR opens superlms.in (IdCardService::siteQr) instead of the
 *   card's own details.
 * Written straight to the tables, so nobody is sent a notification for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $end = AcademicYear::end()->toDateString();
        $qr  = app(IdCardService::class)->siteQr();

        foreach (['student_id_cards', 'teacher_id_cards', 'employee_id_cards'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $set = ['expiry_date' => $end];
            if ($qr !== null && Schema::hasColumn($table, 'qr_code')) {
                $set['qr_code'] = $qr;
            }
            DB::table($table)->update($set);
        }

        if (Schema::hasTable('id_card_generation_settings')) {
            DB::table('id_card_generation_settings')->update(['expiry_date' => $end]);
        }
    }

    public function down(): void
    {
        // The earlier expiry dates and QR contents were not kept.
    }
};
