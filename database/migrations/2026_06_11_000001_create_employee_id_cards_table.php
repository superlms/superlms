<?php

use App\Models\Admin\AdminEmployee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_id_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AdminEmployee::class)->default(0);
            $table->foreignIdFor(User::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->string('card_number')->unique()->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('active');
            $table->text('qr_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_id_cards');
    }
};
