<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * The schools' payment QR images move under admin/ on S3.
 *
 * The media bucket reads out only admin/, super-admin/, superadmin/ and
 * website/ without a signature; everything else answers Access Denied. The QR
 * was saved under fees/qr/, and it is shown by its plain URL — so it was a
 * broken image on the panel's Payment QR page and in the student app's Fees.
 *
 * Each saved QR is copied to admin/fees/qr/… and the row points at the copy;
 * the old object goes only once the row is saved, so a copy that fails leaves
 * the QR exactly as it was.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_qr_codes')) {
            return;
        }

        $rows = DB::table('payment_qr_codes')
            ->where('qr_path', 'like', 'fees/qr/%')
            ->get(['id', 'qr_path']);

        if ($rows->isEmpty()) {
            return;
        }

        $disk = Storage::disk('s3');

        foreach ($rows as $row) {
            $old = $row->qr_path;
            $new = 'admin/' . $old;

            try {
                if (!$disk->exists($new) && !$disk->copy($old, $new)) {
                    logger()->warning('Payment QR image could not be moved under admin/', ['path' => $old]);
                    continue;
                }
            } catch (\Throwable $e) {
                report($e);
                continue;
            }

            DB::table('payment_qr_codes')->where('id', $row->id)->update(['qr_path' => $new]);

            try {
                $disk->delete($old);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function down(): void
    {
        // The images are readable where they now are; putting them back under
        // fees/qr/ would only make them Access Denied again.
    }
};
