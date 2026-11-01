<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Messaging\MessagingCampaign;
use App\Models\Messaging\MessagingWalletTransaction;
use App\Models\Organization;
use App\Services\MessagingWallet;
use App\Services\SchoolEmail;
use App\Services\SchoolSms;
use App\Services\SchoolWhatsApp;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * Super Admin → Messaging: what SuperLMS gives the schools to message with,
 * and how they pay for it.
 *
 *   Wallets   every school's wallet — balance, this month's recharges and
 *             messages — with Add funds / Take out, and its statement
 *   Requests  money schools say they paid in (More → Messaging → Add funds),
 *             to approve or reject
 *   WhatsApp  a school's paid messages on / off (Absent alert, Fee received,
 *             Send Credentials, Notice) and its analytics — what was the
 *             school view's WhatsApp tab
 *   SMS       a school's Welcome by SMS on / off and what went — what was the
 *             school view's SMS tab
 *   Email     a school's email messaging on / off and the emails it sent
 *   Sent      the messages schools sent to many, and how far they got
 *   Settings  the rates, Prepaid, and where schools pay
 */
class Messaging extends Component
{
    use WireUiActions, WithPagination;
    use \App\Livewire\Concerns\HandlesWhatsAppAnalytics;

    public const TABS = [
        'wallets'  => 'Wallets',
        'requests' => 'Requests',
        'whatsapp' => 'WhatsApp',
        'sms'      => 'SMS',
        'email'    => 'Email',
        'sent'     => 'Sent',
        'settings' => 'Settings',
    ];

    #[Url(history: true, except: 'wallets')]
    public string $tab = 'wallets';

    /** The school the WhatsApp / SMS / Email / Sent tabs are on. */
    #[Url(except: '')]
    public string $school = '';

    // ─── Wallets ────────────────────────────────────────────────────────────
    public string $search     = '';
    public string $walletSort = 'name';   // name | balance_asc | balance_desc | used
    public ?int   $statementOrgId = null;

    // ─── Add funds / Take out slide-in ──────────────────────────────────────
    public bool   $showFunds      = false;
    public string $fundsOrgId     = '';
    public string $fundsType      = 'credit';   // credit | debit
    public        $fundsAmount    = '';
    public string $fundsMode      = 'UPI';
    public string $fundsReference = '';
    public string $fundsNote      = '';
    public string $fundsDate      = '';
    public bool   $fundsInLedger  = true;

    // ─── Requests ───────────────────────────────────────────────────────────
    public ?int   $rejectId     = null;
    public string $rejectReason = '';

    // ─── Settings ───────────────────────────────────────────────────────────
    public        $setWhatsappRate   = '';
    public        $setEmailRate      = '';
    public bool   $setPrepaid        = false;
    public string $setPaymentDetails = '';

    public function mount(): void
    {
        if (!array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'wallets';
        }
        if ($this->school !== '' && !$this->mayOpen((int) $this->school)) {
            $this->school = '';
        }
        // A sub super admin kept to one school has it picked already.
        if ($this->school === '' && ($only = Auth::user()?->allowedOrganizationId())) {
            $this->school = (string) $only;
        }
        $this->loadSettings();
    }

    // ─── Which schools ──────────────────────────────────────────────────────

    /** The one school a sub super admin is kept to, or null for all. */
    private function onlyOrgId(): ?int
    {
        $user = Auth::user();

        return $user && method_exists($user, 'allowedOrganizationId') ? $user->allowedOrganizationId() : null;
    }

    private function mayOpen(int $orgId): bool
    {
        $only = $this->onlyOrgId();

        return $orgId > 0 && ($only === null || $only === $orgId) && Organization::whereKey($orgId)->exists();
    }

    public function schoolOptions()
    {
        return Organization::query()
            ->when($this->onlyOrgId(), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
            $this->resetPage();
        }
    }

    public function updatedSchool(): void
    {
        if ($this->school !== '' && !$this->mayOpen((int) $this->school)) {
            $this->school = '';
        }
        $this->resetWhatsAppFilters();
        $this->resetPage();
    }

    /** Open a school's WhatsApp / SMS / Email / Sent tab. */
    public function openSchool(int $orgId, string $tab = 'whatsapp'): void
    {
        if (!$this->mayOpen($orgId)) {
            return;
        }
        $this->school = (string) $orgId;
        $this->resetWhatsAppFilters();
        $this->setTab($tab);
    }

    protected function whatsAppOrgId(): ?int
    {
        $id = (int) $this->school;

        return $id > 0 && $this->mayOpen($id) ? $id : null;
    }

    // ─── WhatsApp, SMS and Email switches ───────────────────────────────────

    public function toggleWhatsApp(string $feature): void
    {
        $orgId = $this->whatsAppOrgId();
        if (!$orgId || !SchoolWhatsApp::tracked() || !(SchoolWhatsApp::FEATURES[$feature][2] ?? false)) {
            return;
        }

        $on = !SchoolWhatsApp::enabled($orgId, $feature);
        SchoolWhatsApp::setEnabled($orgId, $feature, $on);

        $label = $feature === 'credentials' ? 'Send Credentials' : SchoolWhatsApp::FEATURES[$feature][0];
        $this->notification()->success($label . ($on ? ' is on' : ' is off') . ' for ' . Organization::whereKey($orgId)->value('name') . '.');
    }

    public function toggleSms(): void
    {
        $orgId = $this->whatsAppOrgId();
        if (!$orgId || !SchoolSms::tracked()) {
            return;
        }

        $on = !SchoolSms::enabled($orgId, 'welcome');
        SchoolSms::setEnabled($orgId, 'welcome', $on, Auth::id());

        $this->notification()->success('Welcome by SMS ' . ($on ? 'is on' : 'is off') . ' for ' . Organization::whereKey($orgId)->value('name') . '.');
    }

    public function smsSummary(): ?array
    {
        $orgId = $this->whatsAppOrgId();

        return $orgId && SchoolSms::tracked() ? SchoolSms::summary($orgId) : null;
    }

    public function toggleEmail(): void
    {
        $orgId = $this->whatsAppOrgId();
        if (!$orgId || !SchoolEmail::tracked()) {
            return;
        }

        $on = !SchoolEmail::enabled($orgId);
        SchoolEmail::setEnabled($orgId, $on);

        $this->notification()->success('Email messaging ' . ($on ? 'is on' : 'is off') . ' for ' . Organization::whereKey($orgId)->value('name') . '.');
    }

    public function emailSummary(): ?array
    {
        $orgId = $this->whatsAppOrgId();

        return $orgId && SchoolEmail::tracked() ? SchoolEmail::summary($orgId) : null;
    }

    // ─── Wallet statement ───────────────────────────────────────────────────

    public function openStatement(int $orgId): void
    {
        if ($this->mayOpen($orgId)) {
            $this->statementOrgId = $orgId;
        }
    }

    public function closeStatement(): void
    {
        $this->statementOrgId = null;
    }

    // ─── Add funds / Take out ───────────────────────────────────────────────

    public function openFunds($orgId = null, string $type = 'credit'): void
    {
        $this->resetValidation();
        $this->statementOrgId = null;
        $this->fundsOrgId     = $orgId && $this->mayOpen((int) $orgId) ? (string) $orgId : ($this->onlyOrgId() ? (string) $this->onlyOrgId() : '');
        $this->fundsType      = $type === 'debit' ? 'debit' : 'credit';
        $this->fundsAmount    = '';
        $this->fundsMode      = $this->fundsType === 'debit' ? 'Other' : 'UPI';
        $this->fundsReference = '';
        $this->fundsNote      = '';
        $this->fundsDate      = now()->toDateString();
        $this->fundsInLedger  = $this->fundsType === 'credit';
        $this->showFunds      = true;
    }

    public function closeFunds(): void
    {
        $this->showFunds = false;
    }

    public function updatedFundsType(): void
    {
        $this->fundsInLedger = $this->fundsType === 'credit' && $this->fundsMode !== 'Free credit';
    }

    /** A free credit is not money received. */
    public function updatedFundsMode(): void
    {
        $this->fundsInLedger = $this->fundsType === 'credit' && $this->fundsMode !== 'Free credit';
    }

    public function saveFunds(): void
    {
        $this->validate([
            'fundsOrgId'     => 'required|integer',
            'fundsType'      => 'required|in:credit,debit',
            'fundsAmount'    => 'required|numeric|min:1|max:10000000',
            'fundsMode'      => 'nullable|string|max:40',
            'fundsReference' => 'nullable|string|max:120',
            'fundsNote'      => 'nullable|string|max:500',
            'fundsDate'      => 'required|date',
        ], [], [
            'fundsOrgId' => 'school', 'fundsAmount' => 'amount', 'fundsMode' => 'mode',
            'fundsReference' => 'reference', 'fundsNote' => 'note', 'fundsDate' => 'date',
        ]);

        $orgId = (int) $this->fundsOrgId;
        if (!$this->mayOpen($orgId)) {
            $this->addError('fundsOrgId', 'Pick a school.');
            return;
        }

        MessagingWallet::adjust(
            $orgId,
            $this->fundsType,
            (float) $this->fundsAmount,
            $this->fundsMode,
            trim($this->fundsReference),
            trim($this->fundsNote),
            Auth::id(),
            $this->fundsInLedger,
            $this->fundsDate,
        );

        $this->showFunds = false;
        $this->notification()->success('₹' . number_format((float) $this->fundsAmount, 2)
            . ($this->fundsType === 'debit' ? ' taken out of ' : ' added to ')
            . Organization::whereKey($orgId)->value('name') . "'s wallet. Balance: ₹" . number_format(MessagingWallet::balance($orgId), 2) . '.');
    }

    // ─── Requests ───────────────────────────────────────────────────────────

    private function findRequest(int $id): ?MessagingWalletTransaction
    {
        $txn = MessagingWalletTransaction::pending()->find($id);

        return $txn && $this->mayOpen((int) $txn->organization_id) ? $txn : null;
    }

    public function approveRequest(int $id): void
    {
        if (!$txn = $this->findRequest($id)) {
            return;
        }

        MessagingWallet::decide($txn, true, Auth::id());
        $this->notification()->success('₹' . number_format((float) $txn->amount, 2) . ' added to '
            . ($txn->organization?->name ?? 'the school') . "'s wallet.");
    }

    public function openReject(int $id): void
    {
        $this->rejectId     = $this->findRequest($id)?->id;
        $this->rejectReason = '';
    }

    public function closeReject(): void
    {
        $this->rejectId = null;
    }

    public function confirmReject(): void
    {
        $this->validate(['rejectReason' => 'required|string|max:255'], [], ['rejectReason' => 'reason']);
        if (!$this->rejectId || !$txn = $this->findRequest($this->rejectId)) {
            $this->rejectId = null;
            return;
        }

        MessagingWallet::decide($txn, false, Auth::id(), trim($this->rejectReason));
        $this->rejectId = null;
        $this->notification()->success('The request was rejected.');
    }

    // ─── Settings ───────────────────────────────────────────────────────────

    private function loadSettings(): void
    {
        $this->setWhatsappRate   = (string) MessagingWallet::whatsappRate();
        $this->setEmailRate      = (string) MessagingWallet::emailRate();
        $this->setPrepaid        = MessagingWallet::prepaid();
        $this->setPaymentDetails = MessagingWallet::paymentDetails();
    }

    public function saveSettings(): void
    {
        if ($this->onlyOrgId()) {
            return;   // the rates are everyone's
        }

        $this->validate([
            'setWhatsappRate'   => 'required|numeric|min:0|max:100',
            'setEmailRate'      => 'required|numeric|min:0|max:100',
            'setPaymentDetails' => 'nullable|string|max:2000',
        ], [], [
            'setWhatsappRate' => 'WhatsApp rate', 'setEmailRate' => 'email rate', 'setPaymentDetails' => 'payment details',
        ]);

        MessagingWallet::setSetting('whatsapp_rate', (string) round((float) $this->setWhatsappRate, 2));
        MessagingWallet::setSetting('email_rate', (string) round((float) $this->setEmailRate, 2));
        MessagingWallet::setSetting('prepaid', $this->setPrepaid ? '1' : '0');
        MessagingWallet::setSetting('payment_details', trim($this->setPaymentDetails));
        $this->loadSettings();

        $this->notification()->success('Messaging settings saved.');
    }

    // ─── Render ─────────────────────────────────────────────────────────────

    public function render()
    {
        $tracked = MessagingWallet::tracked();
        $only    = $this->onlyOrgId();
        $schools = $this->schoolOptions();
        $data    = ['tracked' => $tracked, 'schools' => $schools];

        $wallets = $tracked ? MessagingWallet::all($only ? [$only] : null) : [];
        $data['totals'] = [
            'balance'  => round(array_sum(array_column($wallets, 'balance')), 2),
            'topped'   => round(array_sum(array_column($wallets, 'topped_month')), 2),
            'used'     => round(array_sum(array_column($wallets, 'used_month')), 2),
            'pending'  => $tracked ? MessagingWalletTransaction::pending()->when($only, fn ($q) => $q->where('organization_id', $only))->count() : 0,
        ];

        if ($this->tab === 'wallets') {
            $needle = mb_strtolower(trim($this->search));
            $blank  = ['funded' => 0.0, 'used' => 0.0, 'balance' => 0.0, 'used_month' => 0.0, 'topped_month' => 0.0, 'pending' => 0, 'last_topup' => null];
            $rows   = $schools
                ->filter(fn ($s) => $needle === '' || str_contains(mb_strtolower($s->name), $needle))
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name] + ($wallets[$s->id] ?? $blank));
            $rows = match ($this->walletSort) {
                'balance_asc'  => $rows->sortBy('balance'),
                'balance_desc' => $rows->sortByDesc('balance'),
                'used'         => $rows->sortByDesc('used_month'),
                default        => $rows,
            };
            $data['walletRows'] = $rows->values();
        }

        if ($this->tab === 'requests' && $tracked) {
            $base = fn () => MessagingWalletTransaction::with(['organization:id,name', 'creator:id,name'])
                ->where('source', 'school')
                ->when($only, fn ($q) => $q->where('organization_id', $only));
            $data['pendingRequests'] = $base()->pending()->latest('id')->get();
            $data['decidedRequests'] = $base()->whereIn('status', ['approved', 'rejected'])->latest('decided_at')->limit(30)->get();
        }

        if ($this->tab === 'sent' && $tracked) {
            $data['campaigns'] = MessagingCampaign::with(['organization:id,name', 'creator:id,name'])
                ->when($this->school !== '', fn ($q) => $q->where('organization_id', (int) $this->school))
                ->when($only, fn ($q) => $q->where('organization_id', $only))
                ->latest('id')
                ->paginate(25);
        }

        if ($this->statementOrgId && $tracked) {
            $data['statement'] = [
                'school'  => $schools->firstWhere('id', $this->statementOrgId)?->name ?? 'School',
                'wallet'  => $wallets[$this->statementOrgId] ?? null,
                'balance' => MessagingWallet::balance($this->statementOrgId),
                'rows'    => MessagingWallet::statement($this->statementOrgId),
            ];
        }

        $data['rates'] = ['whatsapp' => SchoolWhatsApp::rate(), 'email' => MessagingWallet::emailRate()];
        $data['canSettings'] = $only === null;

        return view('livewire.super-admin.messaging', $data);
    }
}
