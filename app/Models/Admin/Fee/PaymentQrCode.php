<?php

namespace App\Models\Admin\Fee;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * The school's own UPI QR that students pay fees on from the app. One per
 * school; the image lives on S3 (public — it is meant to be shown) by its key.
 */
class PaymentQrCode extends Model
{
    protected $table = 'payment_qr_codes';

    protected $fillable = [
        'organization_id',
        'qr_path',
        'upi_id',
        'payee_name',
        'instructions',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function forOrg(int $orgId): ?self
    {
        return self::where('organization_id', $orgId)->first();
    }

    /** The school's QR when it is switched on, else null. */
    public static function activeForOrg(int $orgId): ?self
    {
        return self::where('organization_id', $orgId)->where('is_active', true)->first();
    }

    public function imageUrl(): ?string
    {
        if (!$this->qr_path) {
            return null;
        }

        return str_starts_with($this->qr_path, 'http')
            ? $this->qr_path
            : Storage::disk('s3')->url($this->qr_path);
    }
}
