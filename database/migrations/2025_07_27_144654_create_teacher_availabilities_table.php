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
        Schema::create('teacher_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TeacherDetail::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->integer('day_of_week')->default(0); // 1-7 (Monday-Sunday)
            $table->time('available_from')->nullable();
            $table->time('available_to')->nullable();
            $table->boolean('is_available')->default(true);
            $table->text('unavailability_reason')->nullable();
            // $table->unique(['teacher_detail_id', 'day_of_week']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_availabilities');
    }
};
