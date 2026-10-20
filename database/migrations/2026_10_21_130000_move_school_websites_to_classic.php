<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Playful (kider) template is gone (user's ask of 2026-10-20): every
     * school website is on Classic CBSE, and a new one starts there. A site's
     * pages, content and colours are kept as they are — both templates read
     * the same Website Data.
     */
    public function up(): void
    {
        DB::table('school_websites')->where('template', '!=', 'classic')->orWhereNull('template')
            ->update(['template' => 'classic']);

        Schema::table('school_websites', function (Blueprint $table) {
            $table->string('template')->default('classic')->change();
        });
    }

    public function down(): void
    {
        Schema::table('school_websites', function (Blueprint $table) {
            $table->string('template')->default('kider')->change();
        });
    }
};
