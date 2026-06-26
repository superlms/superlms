<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per dynamic marketing page (Why Us, Services, Careers,
     * Become an Executive, Blogs, FAQs). All page content lives in the
     * `metadata` JSON column, keyed by slug, and is editable from the
     * super-admin panel.
     */
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('metadata')->nullable();
            $table->date('last_updated')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_pages');
    }
};
