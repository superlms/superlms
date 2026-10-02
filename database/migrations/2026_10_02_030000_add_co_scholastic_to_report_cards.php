<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The co-scholastic grades chosen when a report card is issued.
 *
 * The card printed "A" for every co-scholastic area in both terms, for every
 * student. The grades are chosen on the issue form now and kept with the card,
 * as {"term1": {"General Studies": "A", …}, "term2": {…}}. A card issued
 * before this has none saved and goes on printing "A", as it always did.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_cards') || Schema::hasColumn('report_cards', 'co_scholastic')) {
            return;
        }

        Schema::table('report_cards', function (Blueprint $table) {
            $table->json('co_scholastic')->nullable()->after('result');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('report_cards') && Schema::hasColumn('report_cards', 'co_scholastic')) {
            Schema::table('report_cards', function (Blueprint $table) {
                $table->dropColumn('co_scholastic');
            });
        }
    }
};
