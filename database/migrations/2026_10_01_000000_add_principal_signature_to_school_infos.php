<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a school's principal signature will be kept (an image URL or storage
 * path). Nothing sets it yet — the school settings have no place to upload it
 * — but the ID card already prints it above "Principal" as soon as it is set
 * (IdCardService::cardViewData → principal_sign).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_infos') && !Schema::hasColumn('school_infos', 'principal_signature')) {
            Schema::table('school_infos', function (Blueprint $table) {
                $table->string('principal_signature')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('school_infos', 'principal_signature')) {
            Schema::table('school_infos', function (Blueprint $table) {
                $table->dropColumn('principal_signature');
            });
        }
    }
};
