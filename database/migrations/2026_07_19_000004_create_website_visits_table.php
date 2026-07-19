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
        if (Schema::hasTable('website_visits')) {
            return;
        }

        Schema::create('website_visits', function (Blueprint $table) {
            $table->id();
            $table->string('path')->index();          // e.g. /web/about
            $table->string('page')->nullable();        // friendly label, e.g. "About"
            $table->string('visitor_id', 64)->index(); // hashed IP+UA (privacy-friendly)
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_visits');
    }
};
