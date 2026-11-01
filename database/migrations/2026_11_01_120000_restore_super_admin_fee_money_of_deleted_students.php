<?php

use App\Support\SuperAdminFeeAllocator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * A Per Student fee's money that went with deleted students: the
     * database's cascade dropped their payment rows, so Fees showed less
     * collected than was received (₹14,860 of a ₹15,000 payment). What the
     * receipts add up to less what the rows hold goes onto the students owing
     * now, in the order Amount Received pays them (SuperAdminFeeAllocator).
     * From now on a deleted student's money moves on by itself.
     */
    public function up(): void
    {
        try {
            $restored = SuperAdminFeeAllocator::restoreLost();
            if ($restored) {
                Log::info('Super Admin fees: money of deleted students placed again', ['by_fee' => $restored]);
            }
        } catch (\Throwable $e) {
            // Never stops a deploy; the totals stay as they were.
            report($e);
        }
    }

    public function down(): void
    {
        // The money stays where it was placed.
    }
};
