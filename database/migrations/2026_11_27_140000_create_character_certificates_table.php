<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Character certificates (चरित्र प्रमाण-पत्र), issued from Certificates & TC
 * (user's ask of 2026-11-27): the book and serial numbers, the years the
 * student studied here, the examination passed with its division, whether
 * they took part in anything unlawful, and the language it is printed in —
 * Hindi or English. Names in Hindi are optional (the record's are used
 * without them).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('character_certificates')) {
            return;
        }

        Schema::create('character_certificates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->index();
            $t->unsignedBigInteger('student_detail_id')->index();
            $t->string('language', 2)->default('hi');          // hi | en
            $t->string('book_no', 50);                          // पुस्तक संख्या
            $t->string('serial_no', 50);                        // क्रम संख्या
            $t->string('gender', 10)->default('male');          // male | female — the wording
            $t->string('student_name_hi')->nullable();
            $t->string('father_name_hi')->nullable();
            $t->string('school_name_hi')->nullable();
            $t->string('school_place_hi')->nullable();
            $t->string('from_year', 4);                         // सन् 20.. से
            $t->string('to_year', 4);                           // 20.. तक
            $t->string('exam_year', 4);
            $t->string('exam_name', 100);                       // e.g. हाईस्कूल / High School
            $t->string('division', 20)->nullable();             // first | second | third
            $t->string('result', 10)->default('passed');        // passed | failed
            $t->boolean('took_part_unlawful')->default(false);  // भाग नहीं लिया है / लिया था
            $t->date('issue_date');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_certificates');
    }
};
