<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executive_applications', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('mobile');
            $table->text('address')->nullable();
            $table->string('qualification')->nullable();
            $table->text('description')->nullable();
            $table->string('document_path')->nullable(); // S3 key for resume / ID
            $table->string('status')->default('new');     // new | contacted | approved | rejected
            $table->text('admin_remark')->nullable();      // super-admin's note for the status
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_applications');
    }
};
