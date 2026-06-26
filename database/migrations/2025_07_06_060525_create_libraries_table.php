<?php

use App\Models\Organization;
use App\Models\User;
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
        Schema::create('libraries', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->year('publication_year')->nullable();
            $table->string('isbn')->nullable()->unique();
            $table->string('edition')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('language')->default('English');
            $table->integer('pages')->default(0);
            $table->string('cover_image')->nullable();
            $table->string('file_path')->nullable();
            $table->string('type')->nullable(); //, ['book', 'journal', 'thesis', 'ebook', 'other'])->default('book');
            $table->string('availability')->nullable(); //, ['available', 'checked_out', 'lost', 'reserved'])->default('available');
            $table->foreignIdFor(User::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('libraries');
    }
};
