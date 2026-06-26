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
        Schema::create('term_and_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('platform_logo')->nullable();
            $table->string('platform_name')->nullable();
            $table->string('company_name')->nullable();
            $table->text('company_cin')->nullable();
            $table->json('metadata')->nullable();
            $table->date('last_updated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_and_conditions');
    }
};
