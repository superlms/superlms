<?php

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
        Schema::create('website_demos', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('school_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email');
            $table->string('city')->nullable();
            $table->string('no_of_students')->nullable();
            $table->string('role')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_demos');
    }
};
