<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * User's ask of 2026-11-25. An admission sent from a school's website
     * lands in the admin's Admissions too, with every answer of its form
     * (`details`) and where it came from (`source` = 'website'). And every
     * site gets the new Boards page (the Class 9–12 students' login): a site
     * with a page list of its own has it added; one with every page on (no
     * list) needs nothing.
     */
    public function up(): void
    {
        if (Schema::hasTable('admission_enquiries')) {
            Schema::table('admission_enquiries', function (Blueprint $table) {
                if (! Schema::hasColumn('admission_enquiries', 'source')) {
                    $table->string('source', 20)->nullable()->after('status');
                }
                if (! Schema::hasColumn('admission_enquiries', 'details')) {
                    $table->json('details')->nullable()->after('documents');
                }
            });
        }

        if (Schema::hasTable('school_websites')) {
            DB::table('school_websites')->whereNotNull('pages')->orderBy('id')->get(['id', 'pages'])
                ->each(function ($row) {
                    $pages = json_decode((string) $row->pages, true);
                    if (! is_array($pages) || in_array('boards', $pages, true)) {
                        return;
                    }
                    $pages[] = 'boards';
                    DB::table('school_websites')->where('id', $row->id)->update(['pages' => json_encode(array_values($pages))]);
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admission_enquiries')) {
            Schema::table('admission_enquiries', function (Blueprint $table) {
                foreach (['source', 'details'] as $col) {
                    if (Schema::hasColumn('admission_enquiries', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('school_websites')) {
            DB::table('school_websites')->whereNotNull('pages')->orderBy('id')->get(['id', 'pages'])
                ->each(function ($row) {
                    $pages = json_decode((string) $row->pages, true);
                    if (is_array($pages) && in_array('boards', $pages, true)) {
                        $pages = array_values(array_diff($pages, ['boards']));
                        DB::table('school_websites')->where('id', $row->id)->update(['pages' => json_encode($pages)]);
                    }
                });
        }
    }
};
