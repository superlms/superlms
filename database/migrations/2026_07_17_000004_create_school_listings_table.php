<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_listings', function (Blueprint $table) {
            $table->id();
            $table->string('location')->index();     // entered first; used for filtering
            $table->string('logo')->nullable();       // public S3 URL
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('classes')->nullable();    // e.g. "Nursery – 12th"
            $table->unsignedInteger('no_of_students')->nullable();
            $table->decimal('avg_fee', 12, 2)->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_listings');
    }
};
