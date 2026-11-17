<?php

namespace App\Jobs;

use App\Support\SchoolPdf\Exporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Super Admin → School Data → Send to Mail, one step: one functionality's
 * PDF written to the run's folder. One job a PDF keeps each well inside the
 * worker's time limit; SchoolDataMailSend, last in the chain, mails them.
 * A PDF that cannot be made is noted and left out, never stopping the rest.
 */
class SchoolDataMailPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 115;

    public int $tries = 2;

    public function __construct(public int $org, public string $key, public string $dir)
    {
    }

    public function handle(): void
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }

        try {
            file_put_contents($this->dir . '/' . $this->key . '.pdf', Exporter::pdf($this->org, $this->key));
        } catch (\Throwable $e) {
            report($e);
            file_put_contents($this->dir . '/' . $this->key . '.failed', $e->getMessage());
        }
    }
}
