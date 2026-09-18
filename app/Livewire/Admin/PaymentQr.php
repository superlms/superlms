<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Fee\FeePaymentRequest;
use App\Models\Admin\Fee\PaymentQrCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

/**
 * Fees → Payment QR: the school's own UPI QR, which students see in the app's
 * Fees screen and pay on. The money goes straight to the school's account; what
 * they report (UTR / screenshot) waits in Fees → QR Payments to be checked.
 *
 * One QR per school. The image is kept on S3 by its key, public — it is meant
 * to be shown.
 */
class PaymentQr extends Component
{
    use WireUiActions, WithFileUploads;

    public $qrImage = null;           // a new upload, not yet saved
    public string $upiId = '';
    public string $payeeName = '';
    public string $instructions = '';
    public bool $isActive = true;

    protected $messages = [
        'qrImage.required' => 'Upload the QR image.',
        'qrImage.image'    => 'The QR must be an image.',
        'qrImage.mimes'    => 'The QR must be a JPG, PNG or WebP image.',
        'qrImage.max'      => 'The QR image must be 2 MB or smaller.',
        'upiId.regex'      => 'Enter a UPI ID like school@okaxis.',
    ];

    public function mount(): void
    {
        $this->fillFromSaved();
    }

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    private function fillFromSaved(): void
    {
        $qr = PaymentQrCode::forOrg($this->orgId());

        $this->qrImage      = null;
        $this->upiId        = (string) ($qr->upi_id ?? '');
        $this->payeeName    = (string) ($qr->payee_name ?? (Auth::user()->organization->name ?? ''));
        $this->instructions = (string) ($qr->instructions ?? '');
        $this->isActive     = $qr ? (bool) $qr->is_active : true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $orgId = $this->orgId();
        $qr    = PaymentQrCode::forOrg($orgId);

        $this->upiId        = trim($this->upiId);
        $this->payeeName    = trim($this->payeeName);
        $this->instructions = trim($this->instructions);

        $this->validate([
            'qrImage'      => [$qr ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'upiId'        => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9.\-_]{2,256}@[A-Za-z][A-Za-z0-9.\-]{1,63}$/'],
            'payeeName'    => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:500'],
        ]);

        $old  = $qr?->qr_path;
        $path = $old;

        if ($this->qrImage) {
            $path = $this->qrImage->store("fees/qr/{$orgId}", 's3');
            if (!$path) {
                $this->notification()->error('Upload failed', 'Could not upload the QR image. Please try again.');
                return;
            }
            Storage::disk('s3')->setVisibility($path, 'public');
        }

        PaymentQrCode::updateOrCreate(
            ['organization_id' => $orgId],
            [
                'qr_path'      => $path,
                'upi_id'       => $this->upiId ?: null,
                'payee_name'   => $this->payeeName ?: null,
                'instructions' => $this->instructions ?: null,
                'is_active'    => $this->isActive,
                'updated_by'   => Auth::id(),
            ]
        );

        // The replaced image, once the new one is saved in its place.
        if ($old && $old !== $path) {
            $this->deleteFile($old);
        }

        $this->notification()->success(
            $qr ? 'Payment QR updated' : 'Payment QR added',
            $this->isActive ? 'Students can now pay fees on it from the app.' : 'It stays hidden from students until you switch it on.'
        );
        $this->fillFromSaved();
    }

    /** Show or hide the saved QR in the app, without touching anything else. */
    public function toggleActive(): void
    {
        $qr = PaymentQrCode::forOrg($this->orgId());
        if (!$qr) {
            $this->isActive = !$this->isActive;
            return;
        }

        $qr->update(['is_active' => !$qr->is_active, 'updated_by' => Auth::id()]);
        $this->isActive = (bool) $qr->is_active;

        $this->notification()->success(
            $qr->is_active ? 'QR switched on' : 'QR switched off',
            $qr->is_active ? 'Students can pay on it from the app again.' : 'Students no longer see it in the app.'
        );
    }

    public function clearUpload(): void
    {
        $this->qrImage = null;
        $this->resetValidation('qrImage');
    }

    public function confirmRemove(): void
    {
        $this->dialog()->confirm([
            'title'       => 'Remove the payment QR?',
            'description' => 'Students will no longer be able to pay on it from the app. Payments already sent stay in QR Payments.',
            'icon'        => 'error',
            'accept'      => ['label' => 'Yes, remove', 'method' => 'removeQr'],
            'reject'      => ['label' => 'Cancel'],
        ]);
    }

    public function removeQr(): void
    {
        $qr = PaymentQrCode::forOrg($this->orgId());
        if (!$qr) {
            return;
        }

        $path = $qr->qr_path;
        $qr->delete();
        $this->deleteFile($path);

        $this->notification()->success('Payment QR removed');
        $this->fillFromSaved();
    }

    private function deleteFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        try {
            $key = str_starts_with($path, 'http') ? ltrim((string) parse_url($path, PHP_URL_PATH), '/') : $path;
            Storage::disk('s3')->delete($key);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render()
    {
        $orgId = $this->orgId();
        $qr    = PaymentQrCode::forOrg($orgId);

        $preview = null;
        if ($this->qrImage) {
            try {
                $preview = $this->qrImage->temporaryUrl();
            } catch (\Throwable $e) {
                $preview = null;
            }
        }

        $pending = FeePaymentRequest::where('organization_id', $orgId)
            ->where('status', FeePaymentRequest::STATUS_PENDING);

        return view('livewire.admin.payment-qr', [
            'qr'            => $qr,
            'savedUrl'      => $qr?->imageUrl(),
            'previewUrl'    => $preview,
            'pendingCount'  => (clone $pending)->count(),
            'pendingAmount' => (float) (clone $pending)->sum('amount'),
        ]);
    }
}
