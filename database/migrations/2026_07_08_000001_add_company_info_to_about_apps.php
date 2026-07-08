<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Basic Information (company name / CIN) moves from the Terms Of Use
 * module to the About App module. Adds the columns and copies the
 * values already stored on the term_and_conditions row so nothing is
 * lost when the Terms Of Use editor stops managing them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('about_apps', function (Blueprint $table) {
            if (!Schema::hasColumn('about_apps', 'company_name')) {
                $table->string('company_name')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('about_apps', 'company_cin')) {
                $table->string('company_cin', 50)->nullable()->after('company_name');
            }
        });

        if (!Schema::hasTable('term_and_conditions')) {
            return;
        }

        $terms = DB::table('term_and_conditions')->orderBy('id')->first();
        if (!$terms) {
            return;
        }

        $about = DB::table('about_apps')->orderBy('id')->first();

        if ($about) {
            $data = [];
            if (empty($about->company_name) && !empty($terms->company_name)) {
                $data['company_name'] = $terms->company_name;
            }
            if (empty($about->company_cin) && !empty($terms->company_cin)) {
                $data['company_cin'] = $terms->company_cin;
            }
            if ($data) {
                DB::table('about_apps')->where('id', $about->id)
                    ->update($data + ['updated_at' => now()]);
            }
        } elseif (!empty($terms->company_name) || !empty($terms->company_cin)) {
            DB::table('about_apps')->insert([
                'heading'      => $terms->platform_name ?: 'SUPERLMS',
                'company_name' => $terms->company_name,
                'company_cin'  => $terms->company_cin,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('about_apps', function (Blueprint $table) {
            if (Schema::hasColumn('about_apps', 'company_cin')) {
                $table->dropColumn('company_cin');
            }
            if (Schema::hasColumn('about_apps', 'company_name')) {
                $table->dropColumn('company_name');
            }
        });
    }
};
