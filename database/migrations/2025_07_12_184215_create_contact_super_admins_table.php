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
        Schema::create('contact_super_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->text('topic')->nullable();
            $table->text('admin_query')->nullable();
            $table->string('image')->nullable();
            $table->text('super_admin_text')->nullable();
            $table->boolean('super_admin_reply')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_super_admins');
    }
};
