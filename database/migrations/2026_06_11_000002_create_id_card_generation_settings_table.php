<?php

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per organization + person-type ID-card generation settings.
     *
     * Once a type's cards are issued for the first time, `auto_enabled` is
     * turned on. From the next day a scheduled command generates cards for any
     * newly-added persons of that type who don't yet have an active card,
     * reusing the stored `expiry_date`.
     */
    public function up(): void
    {
        Schema::create('id_card_generation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->default(0);
            $table->string('type'); // student | teacher | employee
            $table->boolean('auto_enabled')->default(false);
            $table->date('expiry_date')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_card_generation_settings');
    }
};
