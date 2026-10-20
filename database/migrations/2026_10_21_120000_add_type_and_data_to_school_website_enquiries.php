<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A school website's enquiry keeps which form it came from — Contact Us,
 * Query, Appointment, Career or Online Admission — and every field the
 * visitor filled in, as its label and answer, so the admin's Website
 * Enquiries lists them by type and shows each one's own fields.
 *
 * The enquiries already in arrive under their form's name as the subject
 * ("Appointment — 25 Oct 2026"); they are given that form's type.
 */
return new class extends Migration
{
    private const FROM_SUBJECT = [
        'admission'   => 'Online Admission%',
        'career'      => 'Career%',
        'appointment' => 'Appointment%',
        'query'       => 'Query%',
        'contact'     => 'Contact%',
    ];

    public function up(): void
    {
        Schema::table('school_website_enquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('school_website_enquiries', 'type')) {
                $table->string('type', 20)->nullable()->after('organization_id');
                $table->index(['organization_id', 'type']);
            }
            if (! Schema::hasColumn('school_website_enquiries', 'data')) {
                $table->json('data')->nullable()->after('message');
            }
        });

        foreach (self::FROM_SUBJECT as $type => $like) {
            DB::table('school_website_enquiries')->whereNull('type')->where('subject', 'like', $like)->update(['type' => $type]);
        }
    }

    public function down(): void
    {
        Schema::table('school_website_enquiries', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'type']);
            $table->dropColumn(['type', 'data']);
        });
    }
};
