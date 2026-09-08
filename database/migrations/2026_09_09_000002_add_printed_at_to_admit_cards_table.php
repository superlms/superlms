<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When an admit card was last printed.
 *
 * Cards are issued in batches — 20 today on attendance, 30 more next week — and
 * the second print run must only produce the cards that haven't come out of the
 * printer yet. NULL means "never printed".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admit_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('admit_cards', 'printed_at')) {
                $table->timestamp('printed_at')->nullable()->after('status')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('admit_cards', function (Blueprint $table) {
            if (Schema::hasColumn('admit_cards', 'printed_at')) {
                $table->dropColumn('printed_at');
            }
        });
    }
};
