<?php

use App\Models\Admin\DriverDetail;
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
        Schema::create('transportations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->default(0);
            $table->string('route_name');
            $table->foreignIdFor(DriverDetail::class)->default(0);
            $table->string('pickup_location')->nullable();
            $table->string('drop_location')->nullable();
            $table->json('stops')->nullable();
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->unsignedSmallInteger('capacity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transportations');
    }
};
