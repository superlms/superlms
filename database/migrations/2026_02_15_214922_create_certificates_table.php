<?php

use App\Models\Organization;
use App\Models\Student\StudentDetail;
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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
             $table->foreignIdFor(StudentDetail::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->enum('type', ['achievement', 'participation']);
            $table->string('event_name');
            $table->string('issued_by');         
            $table->string('issued_by_designation')->nullable(); 
            $table->text('description')->nullable();
            $table->date('issued_date');
            $table->string('certificate_no')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
