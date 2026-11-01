<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Super Admin's own Ledger entries made by hand — a credit or an
     * expense (App\Services\SuperAdminLedgerService). Fees received from the
     * schools and salaries paid are read from their own tables, never copied.
     */
    public function up(): void
    {
        if (Schema::hasTable('super_admin_ledger_transactions')) {
            return;
        }

        Schema::create('super_admin_ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);                    // credit | expense
            $table->decimal('amount', 12, 2);
            $table->date('txn_date');
            $table->string('party')->nullable();           // From
            $table->string('party_to')->nullable();        // To (expense) / Collected by (credit)
            $table->string('mode', 50)->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['type', 'txn_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_ledger_transactions');
    }
};
