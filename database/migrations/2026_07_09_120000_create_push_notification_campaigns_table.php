<?php

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
        Schema::create('push_notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('audience_scope');   // all | organization
            $table->string('audience_role');    // students | teachers | both
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('screen')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('device_count')->default(0);
            $table->boolean('delivered')->default(false);
            $table->foreignIdFor(User::class, 'sent_by')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_campaigns');
    }
};
