<?php

use App\Models\Admin\Exam;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(StudentDetail::class)->default(0);
            $table->foreignIdFor(Exam::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->string('admit_card_number')->unique()->nullable();
            $table->string('student_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('roll_number')->nullable();
            $table->string('exam_roll_number')->nullable();
            $table->foreignIdFor(Standard::class)->default(0);
            $table->foreignIdFor(Section::class)->default(0);
            $table->string('exam_name')->nullable();
            $table->string('academic_year')->nullable();
            $table->json('subjects')->nullable();
            $table->time('reporting_time')->nullable();
            $table->string('exam_center')->nullable();
            $table->string('exam_center_address')->nullable();
            $table->text('instructions')->nullable();
            $table->string('student_photo')->nullable();
            $table->string('student_signature')->nullable();
            $table->string('authorized_signature')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('status')->default('active');
            $table->text('qr_code')->nullable();
            $table->string('seat_number')->nullable();
            $table->string('room_number')->nullable();
            $table->json('allowed_items')->nullable();
            $table->json('prohibited_items')->nullable();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->unsignedBigInteger('updated_by')->default(0);
            $table->index(['admit_card_number']);
            $table->index(['exam_id', 'student_detail_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admit_cards');
    }
};
