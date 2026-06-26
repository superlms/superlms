<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_exam_papers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('standard_id');
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('standard_id')->references('id')->on('standards')->cascadeOnDelete();
            $table->index(['organization_id', 'standard_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_exam_papers');
    }
};
