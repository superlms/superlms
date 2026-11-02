<?php

namespace App\Models\Store;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * An invoice SuperLMS sends a school for what it buys (Super Admin → Store →
 * Invoices), with an agreement when there is one — written out, an uploaded
 * document, or both.
 */
class StoreInvoice extends Model
{
    public const STATUSES = [
        'draft'     => 'Draft',
        'sent'      => 'Sent',
        'paid'      => 'Paid',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'number',
        'organization_id',
        'invoice_date',
        'due_date',
        'status',
        'bill_to',
        'subtotal',
        'tax_total',
        'discount',
        'total',
        'notes',
        'agreement_title',
        'agreement_body',
        'agreement_file_url',
        'agreement_file_name',
        'agreement_signed_at',
        'sent_at',
        'sent_to',
        'paid_at',
        'payment_mode',
        'payment_reference',
        'created_by',
    ];

    protected $casts = [
        'invoice_date'        => 'date',
        'due_date'            => 'date',
        'paid_at'             => 'date',
        'sent_at'             => 'datetime',
        'agreement_signed_at' => 'datetime',
        'bill_to'             => 'array',
        'subtotal'            => 'decimal:2',
        'tax_total'           => 'decimal:2',
        'discount'            => 'decimal:2',
        'total'               => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(StoreInvoiceItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function hasAgreement(): bool
    {
        return trim((string) $this->agreement_body) !== '' || (string) $this->agreement_file_url !== '';
    }

    /** Past its due day and not paid (or cancelled). */
    public function isOverdue(): bool
    {
        return in_array($this->status, ['draft', 'sent'], true)
            && $this->due_date
            && $this->due_date->lt(now()->startOfDay());
    }

    public function canEdit(): bool
    {
        return !in_array($this->status, ['paid', 'cancelled'], true);
    }

    /** The link the school opens from its email to see / download the PDF — signed, no sign-in. */
    public function publicPdfUrl(): string
    {
        return URL::signedRoute('store.invoice.pdf', ['invoice' => $this->id]);
    }

    /** The school's name as billed (the school's own name if it was not kept). */
    public function schoolName(): string
    {
        return (string) ($this->bill_to['name'] ?? $this->organization?->name ?? 'School');
    }
}
