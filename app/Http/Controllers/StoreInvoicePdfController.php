<?php

namespace App\Http\Controllers;

use App\Models\Store\StoreInvoice;
use App\Services\StoreInvoices;

/**
 * The invoice PDF a school opens from its email (Super Admin → Store →
 * Invoices → Send). The link is signed, so it needs no sign-in and can't be
 * guessed; a cancelled invoice is not shown.
 */
class StoreInvoicePdfController extends Controller
{
    public function __invoke(StoreInvoice $invoice)
    {
        abort_if($invoice->status === 'cancelled', 410, 'This invoice was cancelled.');

        [$bytes, $fileName] = StoreInvoices::pdf($invoice);

        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }
}
