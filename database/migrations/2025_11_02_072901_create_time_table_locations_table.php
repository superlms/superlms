<?php

use App\Models\Calendar\TimeTable;
use App\Models\Organization;
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
        Schema::create('time_table_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TimeTable::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->string('room_number')->nullable();
            $table->string('building')->nullable();
            $table->string('location')->nullable();
            $table->string('address')->nullable();
            $table->string('floor')->nullable();
            
            // Capacity
            $table->integer('capacity')->nullable();
            $table->json('facilities')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_table_locations');
    }
};
