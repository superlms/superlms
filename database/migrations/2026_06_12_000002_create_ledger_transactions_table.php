<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manual ledger entries only. Automatic credits (fee payments) and
     * automatic expenses (salaries) are read live from their own tables and
     * merged at display/export time — they are NOT duplicated here, so there
     * is never any double-counting or sync drift.
     */
    public function up(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->default(0)->index();
            $table->enum('type', ['credit', 'expense'])->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('txn_date')->index();
            $table->string('party')->nullable();   // the "By" field — payer / payee
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'txn_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
