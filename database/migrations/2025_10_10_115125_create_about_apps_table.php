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
        Schema::create('about_apps', function (Blueprint $table) {
            $table->id();
            $table->string('heading')->nullable();
            $table->text('sub_heading')->nullable();
            $table->json('content')->nullable();
            $table->string('logo')->nullable();
            $table->json('contact_details')->nullable();
            $table->text('address')->nullable();
            $table->json('core_team')->nullable();
            $table->json('social_media')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_apps');
    }
};
