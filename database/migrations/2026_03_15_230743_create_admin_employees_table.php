<?php

use App\Models\Organization;
use App\Models\Teacher\TeacherDetail;
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
        Schema::create('admin_employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(Organization::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(TeacherDetail::class)->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('designation')->nullable();
            $table->enum('type', ['teacher', 'management', 'employee', 'driver']);
            $table->decimal('salary', 10, 2)->default(0);
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_holder_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('joining_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_employees');
    }
};
