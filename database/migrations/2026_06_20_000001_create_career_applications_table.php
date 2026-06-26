<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_applications', function (Blueprint $table) {
            $table->id();
            $table->string('job_role')->nullable();   // position they applied for
            $table->string('full_name');
            $table->string('email');
            $table->string('mobile');
            $table->text('address')->nullable();
            $table->string('qualification')->nullable();
            $table->text('description')->nullable();
            $table->string('document_path')->nullable(); // S3 key for resume / CV
            $table->string('status')->default('new');    // new | reviewed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_applications');
    }
};
