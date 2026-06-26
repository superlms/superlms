<?php

use App\Models\Admin\SchoolInfo;
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
        Schema::create('school_management_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(SchoolInfo::class)->default(0);
            $table->string('name')->nullable();
            $table->string('designation')->nullable();
            $table->string('photo_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_management_teams');
    }
};
