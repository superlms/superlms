<?php

namespace App\Console\Commands;

use App\Services\FeeAutopay;
use Illuminate\Console\Command;

/**
 * Fee Autopay's round (App\Services\FeeAutopay::run): mandates waiting for
 * approval asked about, installments due in the next two days told to the
 * parent, told debits taken after their 24 hours, debits on their way settled.
 */
class RunFeeAutopay extends Command
{
    protected $signature = 'fees:autopay';

    protected $description = 'Fee Autopay: notify, debit and settle PhonePe UPI AutoPay installments';

    public function handle(): int
    {
        $done = FeeAutopay::run();

        $this->info(sprintf(
            'Autopay — mandates checked: %d, parents told: %d, debits taken: %d, settled: %d',
            $done['synced'], $done['notified'], $done['debited'], $done['settled'],
        ));

        return self::SUCCESS;
    }
}
