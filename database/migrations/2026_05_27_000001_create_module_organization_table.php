<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('module_organization')) {
            return;
        }

        Schema::create('module_organization', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->string('module_key', 64);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_organization');
    }
};
