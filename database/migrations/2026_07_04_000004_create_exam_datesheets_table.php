<?php

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_datesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->default(0)->index();
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('standard_id')->index();
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('exam_datesheet_papers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_datesheet_id')->index();
            $table->unsignedBigInteger('subject_id')->index();
            $table->date('exam_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedTinyInteger('shift')->default(1); // 1 = Shift 1, 2 = Shift 2
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_datesheet_papers');
        Schema::dropIfExists('exam_datesheets');
    }
};
