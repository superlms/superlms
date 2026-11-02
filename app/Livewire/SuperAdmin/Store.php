<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\Store\StoreInvoice;
use App\Models\Store\StoreInvoiceItem;
use App\Models\Store\StoreProduct;
use App\Models\User;
use App\Services\StoreInvoices;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * Super Admin → Store: what SuperLMS sells to schools, and what it bills them.
 *
 *   Store     the products — title, price, MRP, GST, unit, stock, description,
 *             specifications and several images (the first is the cover) —
 *             added, edited, hidden from new invoices and deleted
 *   Invoices  an invoice for a school, its lines picked from the Store or
 *             written out, with an agreement (written, an uploaded document,
 *             or both); downloaded as a PDF, emailed to the school, marked
 *             paid, its agreement marked signed, cancelled. Settings: who
 *             SuperLMS is on an invoice, where to pay, the default terms and
 *             agreement.
 */
class Store extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

    public const TABS = [
        'store'    => 'Store',
        'invoices' => 'Invoices',
    ];

    #[Url(history: true, except: 'store')]
    public string $tab = 'store';

    // ─── Store filters ──────────────────────────────────────────────────────
    public string $search   = '';
    public string $category = '';
    public string $shown    = '';   // '' | active | hidden

    // ─── Product slide-in ───────────────────────────────────────────────────
    public bool   $showProduct  = false;
    public ?int   $productId    = null;
    public string $pTitle       = '';
    public string $pCategory    = '';
    public string $pSku         = '';
    public        $pPrice       = '';
    public        $pMrp         = '';
    public        $pGst         = '18';
    public string $pUnit        = '';
    public        $pStock       = '';
    public string $pDescription = '';
    public array  $pSpecs       = [];   // [['label' => '', 'value' => '']]
    public array  $pImages      = [];   // the saved image URLs, in order
    public array  $pUploads     = [];   // new images, gathered one by one
    public        $pOne         = null; // each image as it arrives — Livewire's S3 uploads take one file at a time
    public bool   $pActive      = true;

    public ?int $deleteProductId = null;

    // ─── Invoice filters ────────────────────────────────────────────────────
    public string $invSearch = '';
    public string $invSchool = '';
    public string $invStatus = '';

    // ─── Invoice slide-in ───────────────────────────────────────────────────
    public bool   $showInvoice        = false;
    public ?int   $invoiceId          = null;
    public string $iOrgId             = '';
    public string $iDate              = '';
    public string $iDue               = '';
    public array  $iItems             = [];
    public        $iDiscount          = '';
    public string $iNotes             = '';
    public bool   $iAgreement         = false;
    public string $iAgreementTitle    = '';
    public string $iAgreementBody     = '';
    public        $iAgreementFile     = null;
    public ?string $iAgreementFileUrl  = null;
    public ?string $iAgreementFileName = null;
    public string $iPick              = '';   // product to add as a line

    // ─── An invoice open to read ────────────────────────────────────────────
    public ?int $viewId = null;

    // ─── Send popup ─────────────────────────────────────────────────────────
    public ?int   $sendId      = null;
    public string $sendTo      = '';
    public string $sendMessage = '';

    // ─── Mark paid popup ────────────────────────────────────────────────────
    public ?int   $paidId        = null;
    public string $paidDate      = '';
    public string $paidMode      = 'UPI';
    public string $paidReference = '';

    public ?int $cancelId        = null;
    public ?int $deleteInvoiceId = null;

    // ─── Settings slide-in ──────────────────────────────────────────────────
    public bool  $showSettings = false;
    public array $settings     = [];

    public function mount(): void
    {
        if (!array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'store';
        }
    }

    private function ready(): bool
    {
        try {
            return Schema::hasTable('store_products') && Schema::hasTable('store_invoices') && Schema::hasTable('store_invoice_items');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
            $this->resetPage();
        }
    }

    public function updated($name): void
    {
        if (in_array($name, ['search', 'category', 'shown', 'invSearch', 'invSchool', 'invStatus'], true)) {
            $this->resetPage();
        }
    }

    // ─── Which schools ──────────────────────────────────────────────────────

    /** The one school a sub super admin is kept to, or null for all. */
    private function onlyOrgId(): ?int
    {
        $user = Auth::user();

        return $user && method_exists($user, 'allowedOrganizationId') ? $user->allowedOrganizationId() : null;
    }

    private function schools()
    {
        return Organization::query()
            ->when($this->onlyOrgId(), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function invoiceQuery()
    {
        return StoreInvoice::query()->when($this->onlyOrgId(), fn ($q, $id) => $q->where('organization_id', $id));
    }

    private function findInvoice(?int $id): ?StoreInvoice
    {
        return $id ? $this->invoiceQuery()->find($id) : null;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Store — products
    // ═══════════════════════════════════════════════════════════════════════

    public function openProduct(?int $id = null): void
    {
        $this->resetValidation();
        $this->resetProductForm();

        if ($id && ($product = StoreProduct::find($id))) {
            $this->productId    = $product->id;
            $this->pTitle       = (string) $product->title;
            $this->pCategory    = (string) $product->category;
            $this->pSku         = (string) $product->sku;
            $this->pPrice       = $this->plain($product->price);
            $this->pMrp         = $product->mrp !== null ? $this->plain($product->mrp) : '';
            $this->pGst         = $this->plain($product->gst_rate);
            $this->pUnit        = (string) $product->unit;
            $this->pStock       = $product->stock !== null ? (string) $product->stock : '';
            $this->pDescription = (string) $product->description;
            $this->pSpecs       = collect($product->specifications ?? [])
                ->map(fn ($s) => ['label' => (string) ($s['label'] ?? ''), 'value' => (string) ($s['value'] ?? '')])
                ->values()->all();
            $this->pImages      = array_values(array_filter((array) $product->images));
            $this->pActive      = (bool) $product->is_active;
        }

        if (empty($this->pSpecs)) {
            $this->pSpecs = [['label' => '', 'value' => '']];
        }

        $this->showProduct = true;
    }

    public function closeProduct(): void
    {
        $this->showProduct = false;
        $this->resetProductForm();
    }

    private function resetProductForm(): void
    {
        $this->productId    = null;
        $this->pTitle       = '';
        $this->pCategory    = '';
        $this->pSku         = '';
        $this->pPrice       = '';
        $this->pMrp         = '';
        $this->pGst         = '18';
        $this->pUnit        = '';
        $this->pStock       = '';
        $this->pDescription = '';
        $this->pSpecs       = [];
        $this->pImages      = [];
        $this->pUploads     = [];
        $this->pOne         = null;
        $this->pActive      = true;
    }

    public function addSpec(): void
    {
        $this->pSpecs[] = ['label' => '', 'value' => ''];
    }

    public function removeSpec(int $i): void
    {
        unset($this->pSpecs[$i]);
        $this->pSpecs = array_values($this->pSpecs);
        if (empty($this->pSpecs)) {
            $this->pSpecs = [['label' => '', 'value' => '']];
        }
    }

    /** Each image picked joins the ones before it (several picked at once arrive one after another). */
    public function updatedPOne(): void
    {
        $this->resetErrorBag(['pUploads', 'pOne']);
        $this->validate(
            ['pOne' => 'image|mimes:jpg,jpeg,png,webp|max:5120'],
            ['pOne.image' => 'Only images (JPG, PNG, WEBP).', 'pOne.mimes' => 'Only images (JPG, PNG, WEBP).', 'pOne.max' => 'Each image can be up to 5 MB.'],
        );

        if (count($this->pImages) + count($this->pUploads) >= StoreProduct::MAX_IMAGES) {
            $this->addError('pUploads', 'A product can have up to ' . StoreProduct::MAX_IMAGES . ' images.');
        } elseif ($this->pOne) {
            $this->pUploads[] = $this->pOne;
        }
        $this->pOne = null;
    }

    public function removeUpload(int $i): void
    {
        unset($this->pUploads[$i]);
        $this->pUploads = array_values($this->pUploads);
    }

    public function removeImage(int $i): void
    {
        unset($this->pImages[$i]);
        $this->pImages = array_values($this->pImages);
    }

    /** Move a saved image one place left (-1) or right (+1); first place is the cover. */
    public function moveImage(int $i, int $step): void
    {
        $j = $i + ($step < 0 ? -1 : 1);
        if (!isset($this->pImages[$i], $this->pImages[$j])) {
            return;
        }
        [$this->pImages[$i], $this->pImages[$j]] = [$this->pImages[$j], $this->pImages[$i]];
    }

    public function makeCover(int $i): void
    {
        if (!isset($this->pImages[$i])) {
            return;
        }
        $url = $this->pImages[$i];
        unset($this->pImages[$i]);
        $this->pImages = array_values(array_merge([$url], $this->pImages));
    }

    public function saveProduct(): void
    {
        if (!$this->ready()) {
            return;
        }

        $this->pSpecs = array_values(array_filter(
            array_map(fn ($s) => ['label' => trim((string) ($s['label'] ?? '')), 'value' => trim((string) ($s['value'] ?? ''))], $this->pSpecs),
            fn ($s) => $s['label'] !== '' || $s['value'] !== '',
        ));

        $this->validate([
            'pTitle'          => 'required|string|max:200',
            'pCategory'       => 'nullable|string|max:100',
            'pSku'            => 'nullable|string|max:100',
            'pPrice'          => 'required|numeric|min:0|max:99999999',
            'pMrp'            => 'nullable|numeric|min:0|max:99999999',
            'pGst'            => 'required|numeric|min:0|max:100',
            'pUnit'           => 'nullable|string|max:30',
            'pStock'          => 'nullable|integer|min:0|max:9999999',
            'pDescription'    => 'nullable|string|max:10000',
            'pSpecs.*.label'  => 'required|string|max:100',
            'pSpecs.*.value'  => 'required|string|max:500',
            'pUploads.*'      => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'pTitle.required'         => 'Give the product a title.',
            'pPrice.required'         => 'Give the price.',
            'pSpecs.*.label.required' => 'Name this specification.',
            'pSpecs.*.value.required' => 'Give this specification a value.',
        ]);

        if (count($this->pImages) + count($this->pUploads) > StoreProduct::MAX_IMAGES) {
            $this->addError('pUploads', 'A product can have up to ' . StoreProduct::MAX_IMAGES . ' images.');
            return;
        }

        $product = $this->productId ? StoreProduct::find($this->productId) : null;
        $before  = $product ? array_values(array_filter((array) $product->images)) : [];

        $images = $this->pImages;
        foreach ($this->pUploads as $upload) {
            $path = $upload->store('store/products', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $images[] = Storage::disk('s3')->url($path);
        }

        $values = [
            'title'          => trim($this->pTitle),
            'category'       => trim($this->pCategory) ?: null,
            'sku'            => trim($this->pSku) ?: null,
            'price'          => round((float) $this->pPrice, 2),
            'mrp'            => $this->pMrp === '' || $this->pMrp === null ? null : round((float) $this->pMrp, 2),
            'gst_rate'       => round((float) $this->pGst, 2),
            'unit'           => trim($this->pUnit) ?: null,
            'stock'          => $this->pStock === '' || $this->pStock === null ? null : (int) $this->pStock,
            'description'    => trim($this->pDescription) ?: null,
            'specifications' => $this->pSpecs ?: null,
            'images'         => array_values($images),
            'is_active'      => $this->pActive,
        ];

        if ($product) {
            $product->update($values);
        } else {
            StoreProduct::create($values + ['created_by' => Auth::id()]);
        }

        // Images taken off the product go from storage too.
        $this->deleteImages(array_diff($before, $images));

        $this->notification()->success($product ? 'Product updated.' : 'Product added to the Store.');
        $this->closeProduct();
    }

    public function toggleActive(int $id): void
    {
        $product = StoreProduct::find($id);
        if ($product) {
            $product->update(['is_active' => !$product->is_active]);
            $this->notification()->success($product->is_active ? 'Product is shown again.' : 'Product hidden — it no longer appears when making an invoice.');
        }
    }

    public function confirmDeleteProduct(int $id): void
    {
        $this->deleteProductId = $id;
    }

    public function deleteProduct(): void
    {
        $product = $this->deleteProductId ? StoreProduct::find($this->deleteProductId) : null;
        $this->deleteProductId = null;

        if (!$product) {
            return;
        }

        $images = (array) $product->images;
        // Invoices keep their lines; they just no longer point at the product.
        StoreInvoiceItem::where('store_product_id', $product->id)->update(['store_product_id' => null]);
        $product->delete();
        $this->deleteImages($images);

        if ($this->productId === $product->id) {
            $this->closeProduct();
        }

        $this->notification()->success('Product deleted.');
    }

    /** Delete Store images from S3 (only ones this page put there). */
    private function deleteImages(array $urls): void
    {
        foreach ($urls as $url) {
            $path = StoreInvoices::storagePath((string) $url);
            if ($path !== '') {
                try {
                    Storage::disk('s3')->delete($path);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Invoices
    // ═══════════════════════════════════════════════════════════════════════

    private function blankLine(): array
    {
        return ['product_id' => null, 'title' => '', 'description' => '', 'quantity' => '1', 'unit' => '', 'unit_price' => '', 'gst_rate' => '18'];
    }

    public function openInvoice(?int $id = null, ?int $orgId = null): void
    {
        if (!$this->ready()) {
            return;
        }

        $this->resetValidation();
        $this->resetInvoiceForm();
        $this->viewId = null;

        $invoice = $this->findInvoice($id);
        if ($invoice) {
            if (!$invoice->canEdit()) {
                $this->notification()->error('A paid or cancelled invoice can\'t be changed.');
                return;
            }
            $this->invoiceId          = $invoice->id;
            $this->iOrgId             = (string) $invoice->organization_id;
            $this->iDate              = $invoice->invoice_date?->toDateString() ?? now()->toDateString();
            $this->iDue               = $invoice->due_date?->toDateString() ?? '';
            $this->iDiscount          = (float) $invoice->discount > 0 ? $this->plain($invoice->discount) : '';
            $this->iNotes             = (string) $invoice->notes;
            $this->iItems             = $invoice->items->map(fn ($i) => [
                'product_id'  => $i->store_product_id,
                'title'       => (string) $i->title,
                'description' => (string) $i->description,
                'quantity'    => $this->plain($i->quantity),
                'unit'        => (string) $i->unit,
                'unit_price'  => $this->plain($i->unit_price),
                'gst_rate'    => $this->plain($i->gst_rate),
            ])->all();
            $this->iAgreement         = $invoice->hasAgreement();
            $this->iAgreementTitle    = (string) ($invoice->agreement_title ?: StoreInvoices::setting('store_agreement_title'));
            $this->iAgreementBody     = (string) $invoice->agreement_body;
            $this->iAgreementFileUrl  = $invoice->agreement_file_url;
            $this->iAgreementFileName = $invoice->agreement_file_name;
        } else {
            $only = $this->onlyOrgId();
            $this->iOrgId = $only ? (string) $only : ($orgId ? (string) $orgId : '');
            $this->iDate  = now()->toDateString();
            $this->iDue   = now()->addDays(15)->toDateString();
            $this->iItems = [];
        }

        if (empty($this->iItems)) {
            $this->iItems = [$this->blankLine()];
        }

        $this->showInvoice = true;
    }

    public function closeInvoice(): void
    {
        $this->showInvoice = false;
        $this->resetInvoiceForm();
    }

    private function resetInvoiceForm(): void
    {
        $this->invoiceId          = null;
        $this->iOrgId             = '';
        $this->iDate              = '';
        $this->iDue               = '';
        $this->iItems             = [];
        $this->iDiscount          = '';
        $this->iNotes             = '';
        $this->iAgreement         = false;
        $this->iAgreementTitle    = '';
        $this->iAgreementBody     = '';
        $this->iAgreementFile     = null;
        $this->iAgreementFileUrl  = null;
        $this->iAgreementFileName = null;
        $this->iPick              = '';
    }

    /** Turning the agreement on fills in the default one. */
    public function updatedIAgreement($on): void
    {
        if ($on && trim($this->iAgreementBody) === '' && !$this->iAgreementFileUrl) {
            $this->iAgreementTitle = StoreInvoices::setting('store_agreement_title') ?: 'Agreement';
            $this->iAgreementBody  = StoreInvoices::setting('store_agreement_body');
        }
    }

    /** A product picked from the Store becomes a line (or adds one to its line). */
    public function updatedIPick($id): void
    {
        $product = $id !== '' ? StoreProduct::find((int) $id) : null;
        $this->iPick = '';
        if (!$product) {
            return;
        }

        foreach ($this->iItems as $i => $line) {
            if ((int) ($line['product_id'] ?? 0) === $product->id) {
                $this->iItems[$i]['quantity'] = $this->plain((float) $line['quantity'] + 1);
                return;
            }
        }

        $line = [
            'product_id'  => $product->id,
            'title'       => (string) $product->title,
            'description' => (string) ($product->sku ? 'SKU ' . $product->sku : ''),
            'quantity'    => '1',
            'unit'        => (string) $product->unit,
            'unit_price'  => $this->plain($product->price),
            'gst_rate'    => $this->plain($product->gst_rate),
        ];

        // An empty first line is replaced rather than left above it.
        $last = count($this->iItems) - 1;
        if ($last >= 0 && trim((string) $this->iItems[$last]['title']) === '' && (string) $this->iItems[$last]['unit_price'] === '') {
            $this->iItems[$last] = $line;
        } else {
            $this->iItems[] = $line;
        }
    }

    public function addLine(): void
    {
        $this->iItems[] = $this->blankLine();
    }

    public function removeLine(int $i): void
    {
        unset($this->iItems[$i]);
        $this->iItems = array_values($this->iItems);
        if (empty($this->iItems)) {
            $this->iItems = [$this->blankLine()];
        }
    }

    public function removeAgreementFile(): void
    {
        $this->iAgreementFile     = null;
        $this->iAgreementFileUrl  = null;
        $this->iAgreementFileName = null;
    }

    public function saveInvoice(bool $andSend = false): void
    {
        if (!$this->ready()) {
            return;
        }

        $this->iItems = array_values(array_filter($this->iItems, fn ($l) => trim((string) ($l['title'] ?? '')) !== '' || (string) ($l['unit_price'] ?? '') !== ''));
        if (empty($this->iItems)) {
            $this->iItems = [$this->blankLine()];
        }

        $this->validate([
            'iOrgId'              => 'required|integer|exists:organizations,id',
            'iDate'               => 'required|date',
            'iDue'                => 'nullable|date|after_or_equal:iDate',
            'iItems'              => 'required|array|min:1|max:100',
            'iItems.*.title'      => 'required|string|max:200',
            'iItems.*.description'=> 'nullable|string|max:500',
            'iItems.*.quantity'   => 'required|numeric|min:0.01|max:9999999',
            'iItems.*.unit'       => 'nullable|string|max:30',
            'iItems.*.unit_price' => 'required|numeric|min:0|max:99999999',
            'iItems.*.gst_rate'   => 'required|numeric|min:0|max:100',
            'iDiscount'           => 'nullable|numeric|min:0|max:99999999',
            'iNotes'              => 'nullable|string|max:5000',
            'iAgreementTitle'     => 'nullable|string|max:200',
            'iAgreementBody'      => 'nullable|string|max:50000',
            'iAgreementFile'      => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ], [
            'iOrgId.required'              => 'Pick the school.',
            'iDue.after_or_equal'          => 'The due day can\'t be before the invoice day.',
            'iItems.*.title.required'      => 'Name the item.',
            'iItems.*.quantity.required'   => 'Quantity?',
            'iItems.*.quantity.min'        => 'Quantity?',
            'iItems.*.unit_price.required' => 'Rate?',
            'iAgreementFile.mimes'         => 'The agreement can be a PDF, Word document or an image.',
            'iAgreementFile.max'           => 'The agreement document can be up to 10 MB.',
        ]);

        $orgId = (int) $this->iOrgId;
        if (($only = $this->onlyOrgId()) && $only !== $orgId) {
            $this->addError('iOrgId', 'You can only bill your own school.');
            return;
        }

        if ($this->iAgreement && trim($this->iAgreementBody) === '' && !$this->iAgreementFile && !$this->iAgreementFileUrl) {
            $this->addError('iAgreementBody', 'Write the agreement or attach its document — or turn the agreement off.');
            return;
        }

        $invoice = $this->invoiceId ? $this->findInvoice($this->invoiceId) : null;
        if ($invoice && !$invoice->canEdit()) {
            $this->notification()->error('A paid or cancelled invoice can\'t be changed.');
            return;
        }

        $sums = StoreInvoices::totals(array_map(fn ($l) => [
            'quantity' => $l['quantity'], 'unit_price' => $l['unit_price'], 'gst_rate' => $l['gst_rate'],
        ], $this->iItems), $this->iDiscount);

        // The agreement document: a new one replaces the old.
        $fileUrl  = $this->iAgreement ? $this->iAgreementFileUrl : null;
        $fileName = $this->iAgreement ? $this->iAgreementFileName : null;
        if ($this->iAgreement && $this->iAgreementFile) {
            $path = $this->iAgreementFile->store('store/agreements', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $fileUrl  = Storage::disk('s3')->url($path);
            $fileName = mb_substr((string) $this->iAgreementFile->getClientOriginalName(), 0, 255);
        }
        $oldFile = $invoice?->agreement_file_url;

        $org    = Organization::find($orgId);
        $values = [
            'organization_id'     => $orgId,
            'invoice_date'        => $this->iDate,
            'due_date'            => $this->iDue ?: null,
            'bill_to'             => StoreInvoices::billTo($org),
            'subtotal'            => $sums['subtotal'],
            'tax_total'           => $sums['tax_total'],
            'discount'            => $sums['discount'],
            'total'               => $sums['total'],
            'notes'               => trim($this->iNotes) ?: null,
            'agreement_title'     => $this->iAgreement ? (trim($this->iAgreementTitle) ?: 'Agreement') : null,
            'agreement_body'      => $this->iAgreement ? (trim($this->iAgreementBody) ?: null) : null,
            'agreement_file_url'  => $fileUrl,
            'agreement_file_name' => $fileUrl ? $fileName : null,
        ];
        if (!$this->iAgreement && $invoice) {
            $values['agreement_signed_at'] = null;
        }

        $invoice = DB::transaction(function () use ($invoice, $values, $sums) {
            if ($invoice) {
                $invoice->update($values);
                $invoice->items()->delete();
            } else {
                $invoice = $this->createWithNumber($values + ['status' => 'draft', 'created_by' => Auth::id()]);
            }

            foreach ($sums['lines'] as $i => $line) {
                $src = $this->iItems[$i];
                $invoice->items()->create([
                    'store_product_id' => !empty($src['product_id']) && StoreProduct::whereKey((int) $src['product_id'])->exists() ? (int) $src['product_id'] : null,
                    'title'            => trim((string) $src['title']),
                    'description'      => trim((string) ($src['description'] ?? '')) ?: null,
                    'quantity'         => round((float) $src['quantity'], 2),
                    'unit'             => trim((string) ($src['unit'] ?? '')) ?: null,
                    'unit_price'       => round((float) $src['unit_price'], 2),
                    'gst_rate'         => round((float) $src['gst_rate'], 2),
                    'amount'           => $line['amount'],
                    'tax'              => $line['tax'],
                    'total'            => $line['total'],
                    'sort_order'       => $i,
                ]);
            }

            return $invoice;
        });

        if ($oldFile && $oldFile !== $fileUrl) {
            $this->deleteImages([$oldFile]);
        }

        $this->notification()->success($this->invoiceId ? 'Invoice ' . $invoice->number . ' updated.' : 'Invoice ' . $invoice->number . ' made.');
        $this->closeInvoice();

        if ($andSend) {
            $this->openSend($invoice->id);
        } else {
            $this->viewId = $invoice->id;
        }
    }

    /** A new invoice with the next number — tried again if two are made at once. */
    private function createWithNumber(array $values): StoreInvoice
    {
        $date = \Carbon\Carbon::parse($values['invoice_date']);

        for ($try = 0; ; $try++) {
            try {
                return DB::transaction(fn () => StoreInvoice::create($values + ['number' => StoreInvoices::nextNumber($date)]));
            } catch (\Illuminate\Database\QueryException $e) {
                if ($try >= 3) {
                    throw $e;
                }
            }
        }
    }

    // ─── An invoice: read, download, send, paid, signed, cancel, delete ────

    public function openView(int $id): void
    {
        $this->viewId = $this->findInvoice($id)?->id;
    }

    public function closeView(): void
    {
        $this->viewId = null;
    }

    public function downloadPdf(int $id)
    {
        $invoice = $this->findInvoice($id);
        if (!$invoice) {
            return null;
        }

        [$bytes, $fileName] = StoreInvoices::pdf($invoice);

        return response()->streamDownload(fn () => print($bytes), $fileName, ['Content-Type' => 'application/pdf']);
    }

    public function openSend(int $id): void
    {
        $invoice = $this->findInvoice($id);
        if (!$invoice || $invoice->status === 'cancelled') {
            return;
        }

        $this->resetValidation();
        $emails = array_filter([
            $invoice->organization?->email,
            User::where('organization_id', $invoice->organization_id)->where('role', 'admin')->orderBy('id')->value('email'),
        ]);
        $this->sendId      = $invoice->id;
        $this->sendTo      = $invoice->sent_to ?: implode(', ', array_unique(array_map('strtolower', $emails)));
        $this->sendMessage = '';
    }

    public function closeSend(): void
    {
        $this->sendId = null;
        $this->sendTo = '';
        $this->sendMessage = '';
    }

    public function send(): void
    {
        $invoice = $this->findInvoice($this->sendId);
        if (!$invoice || $invoice->status === 'cancelled') {
            $this->closeSend();
            return;
        }

        $emails = array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', $this->sendTo) ?: [])));
        $bad    = array_filter($emails, fn ($e) => !filter_var($e, FILTER_VALIDATE_EMAIL));

        if (empty($emails)) {
            $this->addError('sendTo', 'Give at least one email address.');
            return;
        }
        if ($bad) {
            $this->addError('sendTo', 'Not an email address: ' . implode(', ', $bad));
            return;
        }
        $this->validate(['sendMessage' => 'nullable|string|max:3000']);

        try {
            StoreInvoices::send($invoice, $emails, $this->sendMessage);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('sendTo', 'The email could not go: ' . $e->getMessage());
            return;
        }

        $this->notification()->success('Invoice ' . $invoice->number . ' emailed to ' . implode(', ', $emails) . '.');
        $this->closeSend();
        $this->viewId = $invoice->id;
    }

    public function openPaid(int $id): void
    {
        $invoice = $this->findInvoice($id);
        if (!$invoice || !$invoice->canEdit()) {
            return;
        }

        $this->resetValidation();
        $this->paidId        = $invoice->id;
        $this->paidDate      = now()->toDateString();
        $this->paidMode      = 'UPI';
        $this->paidReference = '';
    }

    public function closePaid(): void
    {
        $this->paidId = null;
    }

    public function markPaid(): void
    {
        $invoice = $this->findInvoice($this->paidId);
        if (!$invoice || !$invoice->canEdit()) {
            $this->closePaid();
            return;
        }

        $this->validate([
            'paidDate'      => 'required|date',
            'paidMode'      => 'required|in:' . implode(',', StoreInvoices::PAYMENT_MODES),
            'paidReference' => 'nullable|string|max:255',
        ]);

        $invoice->update([
            'status'            => 'paid',
            'paid_at'           => $this->paidDate,
            'payment_mode'      => $this->paidMode,
            'payment_reference' => trim($this->paidReference) ?: null,
        ]);

        $this->notification()->success('Invoice ' . $invoice->number . ' marked paid.');
        $this->closePaid();
    }

    /** A paid invoice back to unpaid (a payment marked by mistake). */
    public function markUnpaid(int $id): void
    {
        $invoice = $this->findInvoice($id);
        if ($invoice && $invoice->status === 'paid') {
            $invoice->update([
                'status'            => $invoice->sent_at ? 'sent' : 'draft',
                'paid_at'           => null,
                'payment_mode'      => null,
                'payment_reference' => null,
            ]);
            $this->notification()->success('Invoice ' . $invoice->number . ' is unpaid again.');
        }
    }

    public function toggleSigned(int $id): void
    {
        $invoice = $this->findInvoice($id);
        if ($invoice && $invoice->hasAgreement()) {
            $invoice->update(['agreement_signed_at' => $invoice->agreement_signed_at ? null : now()]);
            $this->notification()->success($invoice->agreement_signed_at ? 'Agreement marked signed.' : 'Agreement marked not signed.');
        }
    }

    public function confirmCancel(int $id): void
    {
        $this->cancelId = $this->findInvoice($id)?->id;
    }

    public function cancelInvoice(): void
    {
        $invoice = $this->findInvoice($this->cancelId);
        $this->cancelId = null;
        if ($invoice && $invoice->status !== 'paid') {
            $invoice->update(['status' => 'cancelled']);
            $this->notification()->success('Invoice ' . $invoice->number . ' cancelled.');
        }
    }

    public function confirmDeleteInvoice(int $id): void
    {
        $invoice = $this->findInvoice($id);
        $this->deleteInvoiceId = $invoice && $invoice->status === 'draft' ? $invoice->id : null;
    }

    /** Only a draft (never sent) is deleted; one that went out is cancelled instead. */
    public function deleteInvoice(): void
    {
        $invoice = $this->findInvoice($this->deleteInvoiceId);
        $this->deleteInvoiceId = null;
        if (!$invoice || $invoice->status !== 'draft') {
            return;
        }

        $file = $invoice->agreement_file_url;
        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->delete();
        });
        if ($file) {
            $this->deleteImages([$file]);
        }
        if ($this->viewId === $invoice->id) {
            $this->viewId = null;
        }

        $this->notification()->success('Draft ' . $invoice->number . ' deleted.');
    }

    // ─── Settings ───────────────────────────────────────────────────────────

    public function openSettings(): void
    {
        $this->resetValidation();
        $this->settings = [];
        foreach (array_keys(StoreInvoices::SETTINGS) as $key) {
            $this->settings[$key] = StoreInvoices::setting($key);
        }
        $this->showSettings = true;
    }

    public function closeSettings(): void
    {
        $this->showSettings = false;
    }

    public function saveSettings(): void
    {
        $this->settings['store_seller_gstin'] = strtoupper(trim((string) ($this->settings['store_seller_gstin'] ?? '')));

        $this->validate([
            'settings.store_seller_name'     => 'required|string|max:150',
            'settings.store_seller_address'  => 'nullable|string|max:500',
            'settings.store_seller_gstin'    => ['nullable', 'string', 'regex:/^[0-9A-Z]{15}$/'],
            'settings.store_seller_phone'    => 'nullable|string|max:50',
            'settings.store_seller_email'    => 'nullable|email|max:150',
            'settings.store_payment_details' => 'nullable|string|max:1000',
            'settings.store_invoice_terms'   => 'nullable|string|max:2000',
            'settings.store_invoice_prefix'  => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9-]+$/'],
            'settings.store_agreement_title' => 'nullable|string|max:200',
            'settings.store_agreement_body'  => 'nullable|string|max:50000',
        ], [
            'settings.store_seller_name.required'  => 'Give the name invoices go out under.',
            'settings.store_seller_gstin.regex'    => 'A GSTIN is 15 letters and digits, e.g. 09ABCDE1234F1Z5.',
            'settings.store_invoice_prefix.regex'  => 'Letters, digits and - only.',
        ]);

        foreach (array_keys(StoreInvoices::SETTINGS) as $key) {
            StoreInvoices::saveSetting($key, $this->settings[$key] ?? '');
        }

        $this->notification()->success('Invoice settings saved.');
        $this->showSettings = false;
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /** A number as a form shows it: 1200 not 1200.00, 12.5 not 12.50. */
    private function plain($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    }

    public function render()
    {
        $ready   = $this->ready();
        $schools = $this->schools();
        $data    = [
            'ready'      => $ready,
            'schools'    => $schools,
            'products'   => null,
            'categories' => collect(),
            'counts'     => ['products' => 0, 'active' => 0, 'invoices' => 0, 'billed' => 0, 'paid' => 0, 'due' => 0, 'drafts' => 0, 'overdue' => 0],
            'invoices'   => null,
            'view'       => null,
            'formSums'   => null,
            'pickable'   => collect(),
        ];

        if (!$ready) {
            return view('livewire.super-admin.store', $data);
        }

        $data['categories'] = StoreProduct::whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category');
        $data['counts']['products'] = StoreProduct::count();
        $data['counts']['active']   = StoreProduct::where('is_active', true)->count();

        $live = $this->invoiceQuery()->where('status', '!=', 'cancelled');
        $data['counts']['invoices'] = (clone $live)->count();
        $data['counts']['billed']   = (float) (clone $live)->sum('total');
        $data['counts']['paid']     = (float) (clone $live)->where('status', 'paid')->sum('total');
        $data['counts']['due']      = (float) (clone $live)->whereIn('status', ['draft', 'sent'])->sum('total');
        $data['counts']['drafts']   = (clone $live)->where('status', 'draft')->count();
        $data['counts']['overdue']  = (clone $live)->whereIn('status', ['draft', 'sent'])->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString())->count();

        if ($this->tab === 'store') {
            $term = trim($this->search);
            $data['products'] = StoreProduct::query()
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")->orWhere('category', 'like', "%{$term}%")))
                ->when($this->category !== '', fn ($q) => $q->where('category', $this->category))
                ->when($this->shown === 'active', fn ($q) => $q->where('is_active', true))
                ->when($this->shown === 'hidden', fn ($q) => $q->where('is_active', false))
                ->orderByDesc('is_active')->orderBy('title')
                ->paginate(24);
        } else {
            $term = trim($this->invSearch);
            $data['invoices'] = $this->invoiceQuery()
                ->with('organization:id,name')
                ->withCount('items')
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('number', 'like', "%{$term}%")
                    ->orWhereHas('organization', fn ($o) => $o->where('name', 'like', "%{$term}%"))))
                ->when($this->invSchool !== '', fn ($q) => $q->where('organization_id', (int) $this->invSchool))
                ->when($this->invStatus === 'overdue', fn ($q) => $q->whereIn('status', ['draft', 'sent'])->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString()))
                ->when($this->invStatus !== '' && $this->invStatus !== 'overdue', fn ($q) => $q->where('status', $this->invStatus))
                ->orderByDesc('invoice_date')->orderByDesc('id')
                ->paginate(20);
        }

        if ($this->viewId) {
            $data['view'] = $this->findInvoice($this->viewId)?->load('items', 'organization');
            if (!$data['view']) {
                $this->viewId = null;
            }
        }

        if ($this->showInvoice) {
            $data['formSums'] = StoreInvoices::totals(array_map(fn ($l) => [
                'quantity' => is_numeric($l['quantity'] ?? null) ? $l['quantity'] : 0,
                'unit_price' => is_numeric($l['unit_price'] ?? null) ? $l['unit_price'] : 0,
                'gst_rate' => is_numeric($l['gst_rate'] ?? null) ? $l['gst_rate'] : 0,
            ], $this->iItems), is_numeric($this->iDiscount) ? $this->iDiscount : 0);
            $data['pickable'] = StoreProduct::where('is_active', true)->orderBy('title')->get(['id', 'title', 'price', 'gst_rate', 'unit', 'images']);
        }

        return view('livewire.super-admin.store', $data);
    }
}
