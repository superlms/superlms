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
        Schema::create('time_table_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TimeTable::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            
            // Resource details
            $table->string('resource_name')->nullable();
            $table->string('resource_type')->nullable(); 
            $table->integer('quantity')->default(1);
            $table->text('description')->nullable();
            $table->json('specifications')->nullable(); 
            
            // Status
            $table->boolean('is_available')->default(true);
            $table->text('availability_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_table_resources');
    }
};
