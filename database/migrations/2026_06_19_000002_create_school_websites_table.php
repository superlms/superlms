<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One public website per organization, built from a template (Kider) and
     * served on the school's own custom domain. All editable content lives in
     * the `content` JSON column; `theme` holds colours, `pages` the enabled
     * page slugs.
     */
    public function up(): void
    {
        Schema::create('school_websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('domain')->nullable()->unique();   // e.g. mykidsschool.com
            $table->string('template')->default('kider');
            $table->json('theme')->nullable();                // { preset, primary, light, dark }
            $table->json('pages')->nullable();                // ['home','about','classes','team','contact',...]
            $table->json('content')->nullable();              // all editable text/images/lists
            $table->boolean('status')->default(false);        // published / live
            $table->timestamps();
        });

        // Enquiries submitted from a school's public website contact form.
        Schema::create('school_website_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_website_enquiries');
        Schema::dropIfExists('school_websites');
    }
};
