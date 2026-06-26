<?php

use App\Models\Organization;
use App\Models\Teacher\TeacherDetail;
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
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TeacherDetail::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->date('attendance_date')->nullable();
            $table->boolean('status')->default(0);
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('marked_by')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};
