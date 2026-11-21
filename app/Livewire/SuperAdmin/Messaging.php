<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Messaging\MessagingCampaign;
use App\Models\Messaging\MessagingPack;
use App\Models\Messaging\MessagingPayment;
use App\Models\Organization;
use App\Services\MessagePacks;
use App\Services\MessagingWallet;
use App\Services\SchoolEmail;
use App\Services\SchoolSms;
use App\Services\SchoolWhatsApp;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * Super Admin → Messaging: the WhatsApp, SMS and email messages SuperLMS
 * gives the schools, how many each school has, and how they pay for them —
 * in the Home's analytics look (2026-11-21), a chip a tab:
 *
 *   Overview  messages left, sent this month, money in and to collect; sent
 *             by month, by channel, the top schools, who is running low,
 *             the latest payments
 *   Schools   every school's messages left on each channel, this month's, what
 *             it owes — Give Messages, Record Payment, its detail
 *   Requests  messages schools bought (More → Messaging → Buy Messages), to
 *             approve or reject
 *   Packs     every pack given or bought, with how far it is paid
 *   Payments  who owes what, and every payment
 *   WhatsApp  every school's WhatsApp messages; a school's switches and
 *   SMS       analytics (what was the school view's WhatsApp / SMS tab)
 *   Email
 *   Sent      the messages schools sent to many, and how far they got
 *   Settings  the rate a message on each channel, Prepaid, where schools pay
 */
class Messaging extends Component
{
    use WireUiActions, WithPagination;
    use \App\Livewire\Concerns\HandlesWhatsAppAnalytics;

    public const TABS = [
        'overview' => 'Overview',
        'schools'  => 'Schools',
        'requests' => 'Requests',
        'packs'    => 'Packs',
        'payments' => 'Payments',
        'whatsapp' => 'WhatsApp',
        'sms'      => 'SMS',
        'email'    => 'Email',
        'sent'     => 'Sent',
        'settings' => 'Settings',
    ];

    #[Url(history: true, except: 'overview')]
    public string $tab = 'overview';

    /** The school the WhatsApp / SMS / Email / Packs / Payments / Sent tabs are on ('' = every school). */
    #[Url(except: '')]
    public string $school = '';

    // ─── Schools ────────────────────────────────────────────────────────────
    public string $search = '';
    public string $sort   = 'name';   // name | left | used | due
    public string $show   = '';       // '' | low | due | none

    // ─── Packs ──────────────────────────────────────────────────────────────
    public string $packChannel = '';
    public string $packState   = '';  // '' | paid | part | due | free | take

    // ─── A school's messages (slide-in) ─────────────────────────────────────
    public ?int $detailOrgId = null;

    // ─── Give Messages / Take Back (slide-in) ───────────────────────────────
    public bool   $showGive   = false;
    public string $gType      = 'give';      // give | take
    public string $gOrgId     = '';
    public string $gChannel   = 'whatsapp';
    public        $gMessages  = 1000;
    public        $gRate      = '';
    public string $gPayment   = 'paid';      // paid | due | free
    public string $gMode      = 'UPI';
    public string $gReference = '';
    public string $gDate      = '';
    public string $gNote      = '';

    // ─── Record Payment (slide-in) ──────────────────────────────────────────
    public bool   $showPay    = false;
    public string $pOrgId     = '';
    public string $pPackId    = '';
    public        $pAmount    = '';
    public string $pMode      = 'UPI';
    public string $pReference = '';
    public string $pDate      = '';
    public string $pNote      = '';

    // ─── Requests ───────────────────────────────────────────────────────────
    public ?int   $rejectId     = null;
    public string $rejectReason = '';

    // ─── Settings ───────────────────────────────────────────────────────────
    public        $setWhatsappRate   = '';
    public        $setSmsRate        = '';
    public        $setEmailRate      = '';
    public bool   $setPrepaid        = false;
    public string $setUpiId          = '';
    public string $setUpiName        = '';
    public string $setPaymentDetails = '';

    public function mount(): void
    {
        if (!array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'overview';
        }
        if ($this->school !== '' && !$this->mayOpen((int) $this->school)) {
            $this->school = '';
        }
        // A sub super admin kept to one school has it picked already.
        if ($this->school === '' && ($only = $this->onlyOrgId())) {
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
            ->get(['id', 'name', 'logo']);
    }

    private function schoolName(int $orgId): string
    {
        return (string) (Organization::whereKey($orgId)->value('name') ?? 'the school');
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
            $this->resetPage();
            $this->resetPage('packsPage');
            $this->resetPage('paymentsPage');
        }
    }

    public function updatedSchool(): void
    {
        if ($this->school !== '' && !$this->mayOpen((int) $this->school)) {
            $this->school = '';
        }
        $this->resetWhatsAppFilters();
        $this->resetPage();
        $this->resetPage('packsPage');
        $this->resetPage('paymentsPage');
    }

    public function updatedPackChannel(): void
    {
        $this->resetPage('packsPage');
    }

    public function updatedPackState(): void
    {
        $this->resetPage('packsPage');
    }

    /** Open a school on one of the per-school tabs. */
    public function openSchool(int $orgId, string $tab = 'whatsapp'): void
    {
        if (!$this->mayOpen($orgId)) {
            return;
        }
        $this->school      = (string) $orgId;
        $this->detailOrgId = null;
        $this->resetWhatsAppFilters();
        $this->setTab($tab);
    }

    /** Back to every school (WhatsApp / SMS / Email). */
    public function allSchools(): void
    {
        if (!$this->onlyOrgId()) {
            $this->school = '';
            $this->resetWhatsAppFilters();
        }
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
        $this->notification()->success($label . ($on ? ' is on' : ' is off') . ' for ' . $this->schoolName($orgId) . '.');
    }

    public function toggleSms(string $feature = 'welcome'): void
    {
        $orgId = $this->whatsAppOrgId();
        if (!$orgId || !SchoolSms::tracked() || !array_key_exists($feature, SchoolSms::FEATURES)) {
            return;
        }

        $on = !SchoolSms::enabled($orgId, $feature);
        SchoolSms::setEnabled($orgId, $feature, $on, Auth::id());

        $this->notification()->success(SchoolSms::FEATURES[$feature] . ' ' . ($on ? 'is on' : 'is off') . ' for ' . $this->schoolName($orgId) . '.');
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

        $this->notification()->success('Email messaging ' . ($on ? 'is on' : 'is off') . ' for ' . $this->schoolName($orgId) . '.');
    }

    public function emailSummary(): ?array
    {
        $orgId = $this->whatsAppOrgId();

        return $orgId && SchoolEmail::tracked() ? SchoolEmail::summary($orgId) : null;
    }

    // ─── A school's messages (slide-in) ─────────────────────────────────────

    public function openDetail(int $orgId): void
    {
        if ($this->mayOpen($orgId)) {
            $this->detailOrgId = $orgId;
        }
    }

    public function closeDetail(): void
    {
        $this->detailOrgId = null;
    }

    // ─── Give Messages / Take Back ──────────────────────────────────────────

    public function openGive($orgId = null, ?string $channel = null, string $type = 'give'): void
    {
        $this->resetValidation();
        $this->detailOrgId = null;
        $this->gType       = $type === 'take' ? 'take' : 'give';
        $this->gOrgId      = $orgId && $this->mayOpen((int) $orgId)
            ? (string) $orgId
            : ($this->onlyOrgId() ? (string) $this->onlyOrgId() : ($this->school !== '' ? $this->school : ''));
        $this->gChannel    = array_key_exists((string) $channel, MessagePacks::CHANNELS) ? $channel
            : (in_array($this->tab, ['whatsapp', 'sms', 'email'], true) ? $this->tab : 'whatsapp');
        $this->gMessages   = $this->gType === 'take' ? '' : 1000;
        $this->gRate       = (string) MessagePacks::rate($this->gChannel);
        $this->gPayment    = 'paid';
        $this->gMode       = 'UPI';
        $this->gReference  = '';
        $this->gDate       = now()->toDateString();
        $this->gNote       = '';
        $this->showGive    = true;
    }

    public function closeGive(): void
    {
        $this->showGive = false;
    }

    public function updatedGChannel(): void
    {
        if (!array_key_exists($this->gChannel, MessagePacks::CHANNELS)) {
            $this->gChannel = 'whatsapp';
        }
        $this->gRate = (string) MessagePacks::rate($this->gChannel);
    }

    public function pickSize(int $size): void
    {
        $this->gMessages = max(1, $size);
    }

    /** What the pack being given comes to. */
    public function giveTotal(): float
    {
        if ($this->gType === 'take' || $this->gPayment === 'free') {
            return 0.0;
        }

        return round(max(0, (int) $this->gMessages) * max(0, (float) $this->gRate), 2);
    }

    public function saveGive(): void
    {
        $take = $this->gType === 'take';
        $this->validate([
            'gOrgId'     => 'required|integer',
            'gType'      => 'required|in:give,take',
            'gChannel'   => 'required|in:' . implode(',', array_keys(MessagePacks::CHANNELS)),
            'gMessages'  => 'required|integer|min:1|max:10000000',
            'gRate'      => ($take || $this->gPayment === 'free' ? 'nullable' : 'required') . '|numeric|min:0|max:100',
            'gPayment'   => 'required|in:paid,due,free',
            'gMode'      => 'nullable|string|max:40',
            'gReference' => 'nullable|string|max:120',
            'gDate'      => 'required|date',
            'gNote'      => 'nullable|string|max:500',
        ], [], [
            'gOrgId' => 'school', 'gChannel' => 'channel', 'gMessages' => 'messages', 'gRate' => 'rate',
            'gPayment' => 'payment', 'gMode' => 'mode', 'gReference' => 'reference', 'gDate' => 'date', 'gNote' => 'note',
        ]);

        $orgId = (int) $this->gOrgId;
        if (!$this->mayOpen($orgId)) {
            $this->addError('gOrgId', 'Pick a school.');
            return;
        }

        $messages = (int) $this->gMessages;
        $label    = MessagePacks::label($this->gChannel);
        if ($take) {
            $left = max(0, MessagePacks::left($orgId)[$this->gChannel] ?? 0);
            if ($messages > $left) {
                $this->addError('gMessages', $this->schoolName($orgId) . ' has ' . number_format($left) . ' ' . $label . ' messages left to take back.');
                return;
            }
            MessagePacks::take($orgId, $this->gChannel, $messages, trim($this->gNote), Auth::id(), $this->gDate);
        } else {
            if ($this->gPayment !== 'free' && $this->giveTotal() <= 0) {
                $this->addError('gRate', 'A pack that is paid for needs a price — or pick Free.');
                return;
            }
            MessagePacks::give($orgId, $this->gChannel, $messages, (float) $this->gRate, $this->giveTotal(), $this->gPayment,
                $this->gMode, trim($this->gReference), trim($this->gNote), Auth::id(), $this->gDate);
        }

        $this->showGive = false;
        $left = MessagePacks::left($orgId)[$this->gChannel] ?? 0;
        $this->notification()->success(number_format($messages) . ' ' . $label . ' messages '
            . ($take ? 'taken back from ' : 'given to ') . $this->schoolName($orgId) . '. Left now: ' . number_format($left) . '.');
    }

    // ─── Record Payment ─────────────────────────────────────────────────────

    public function openPay($orgId = null, $packId = null): void
    {
        $this->resetValidation();
        $this->detailOrgId = null;
        $pack = $packId ? MessagingPack::approved()->where('type', 'give')->find((int) $packId) : null;
        if ($pack && !$this->mayOpen((int) $pack->organization_id)) {
            $pack = null;
        }
        $orgId = $pack ? (int) $pack->organization_id : (int) $orgId;

        $this->pOrgId     = $orgId && $this->mayOpen($orgId)
            ? (string) $orgId
            : ($this->onlyOrgId() ? (string) $this->onlyOrgId() : ($this->school !== '' ? $this->school : ''));
        $this->pPackId    = $pack ? (string) $pack->id : '';
        $this->pAmount    = '';
        if ($pack) {
            $state = MessagePacks::payState(collect([$pack]))[$pack->id] ?? null;
            $this->pAmount = (string) round(max(0, (float) $pack->amount - (float) ($state['paid'] ?? 0)), 2);
        } elseif ($this->pOrgId !== '') {
            $due = MessagePacks::due((int) $this->pOrgId);
            $this->pAmount = $due > 0 ? (string) $due : '';
        }
        $this->pMode      = 'UPI';
        $this->pReference = '';
        $this->pDate      = now()->toDateString();
        $this->pNote      = '';
        $this->showPay    = true;
    }

    public function closePay(): void
    {
        $this->showPay = false;
    }

    public function updatedPOrgId(): void
    {
        $this->pPackId = '';
        if ($this->pOrgId !== '' && $this->mayOpen((int) $this->pOrgId)) {
            $due = MessagePacks::due((int) $this->pOrgId);
            $this->pAmount = $due > 0 ? (string) $due : '';
        }
    }

    public function savePay(): void
    {
        $this->validate([
            'pOrgId'     => 'required|integer',
            'pAmount'    => 'required|numeric|min:1|max:10000000',
            'pMode'      => 'required|string|max:40',
            'pReference' => 'nullable|string|max:120',
            'pDate'      => 'required|date|before_or_equal:today',
            'pNote'      => 'nullable|string|max:500',
        ], [], [
            'pOrgId' => 'school', 'pAmount' => 'amount', 'pMode' => 'mode', 'pReference' => 'reference', 'pDate' => 'date', 'pNote' => 'note',
        ]);

        $orgId = (int) $this->pOrgId;
        if (!$this->mayOpen($orgId)) {
            $this->addError('pOrgId', 'Pick a school.');
            return;
        }
        $packId = $this->pPackId !== '' && MessagingPack::where('organization_id', $orgId)->whereKey((int) $this->pPackId)->exists()
            ? (int) $this->pPackId : null;

        MessagePacks::recordPayment($orgId, (float) $this->pAmount, $this->pMode, trim($this->pReference), $this->pDate, trim($this->pNote), Auth::id(), $packId);

        $this->showPay = false;
        $due = MessagePacks::due($orgId);
        $this->notification()->success('₹' . number_format((float) $this->pAmount, 2) . ' received from ' . $this->schoolName($orgId)
            . '. ' . ($due > 0 ? 'Still to pay: ₹' . number_format($due, 2) . '.' : 'Nothing left to pay.'));
    }

    // ─── Requests ───────────────────────────────────────────────────────────

    private function findRequest(int $id): ?MessagingPack
    {
        $pack = MessagingPack::pending()->find($id);

        return $pack && $this->mayOpen((int) $pack->organization_id) ? $pack : null;
    }

    public function approveRequest(int $id): void
    {
        if (!$pack = $this->findRequest($id)) {
            return;
        }

        MessagePacks::decide($pack, true, Auth::id());
        $this->notification()->success(number_format($pack->messages) . ' ' . MessagePacks::label($pack->channel) . ' messages added to '
            . ($pack->organization?->name ?? 'the school') . ' — ₹' . number_format((float) $pack->amount, 2) . ' received.');
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
        if (!$this->rejectId || !$pack = $this->findRequest($this->rejectId)) {
            $this->rejectId = null;
            return;
        }

        MessagePacks::decide($pack, false, Auth::id(), trim($this->rejectReason));
        $this->rejectId = null;
        $this->notification()->success('The request was rejected.');
    }

    // ─── Settings ───────────────────────────────────────────────────────────

    private function loadSettings(): void
    {
        $this->setWhatsappRate   = (string) MessagingWallet::whatsappRate();
        $this->setSmsRate        = (string) MessagingWallet::smsRate();
        $this->setEmailRate      = (string) MessagingWallet::emailRate();
        $this->setPrepaid        = MessagingWallet::prepaid();
        $this->setUpiId          = MessagingWallet::upiId();
        $this->setUpiName        = MessagingWallet::upiName();
        $this->setPaymentDetails = MessagingWallet::paymentDetails();
    }

    public function saveSettings(): void
    {
        if ($this->onlyOrgId()) {
            return;   // the rates are everyone's
        }

        $this->validate([
            'setWhatsappRate'   => 'required|numeric|min:0|max:100',
            'setSmsRate'        => 'required|numeric|min:0|max:100',
            'setEmailRate'      => 'required|numeric|min:0|max:100',
            'setUpiId'          => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._\-]{2,}@[A-Za-z0-9.\-]{2,}$/'],
            'setUpiName'        => 'nullable|string|max:60',
            'setPaymentDetails' => 'nullable|string|max:2000',
        ], ['setUpiId.regex' => 'A UPI id is like superlms@okhdfcbank.'], [
            'setWhatsappRate' => 'WhatsApp rate', 'setSmsRate' => 'SMS rate', 'setEmailRate' => 'email rate',
            'setUpiId' => 'UPI id', 'setUpiName' => 'UPI name', 'setPaymentDetails' => 'payment details',
        ]);

        MessagingWallet::setSetting('whatsapp_rate', (string) round((float) $this->setWhatsappRate, 2));
        MessagingWallet::setSetting('sms_rate', (string) round((float) $this->setSmsRate, 2));
        MessagingWallet::setSetting('email_rate', (string) round((float) $this->setEmailRate, 2));
        MessagingWallet::setSetting('prepaid', $this->setPrepaid ? '1' : '0');
        MessagingWallet::setSetting('upi_id', trim($this->setUpiId));
        MessagingWallet::setSetting('upi_name', trim($this->setUpiName));
        MessagingWallet::setSetting('payment_details', trim($this->setPaymentDetails));
        $this->loadSettings();

        $this->notification()->success('Messaging settings saved.');
    }

    // ─── Render ─────────────────────────────────────────────────────────────

    /** Per channel: given, sent, left and this month, added up over $rows. */
    private static function channelTotals(array $rows): array
    {
        $out = [];
        foreach (array_keys(MessagePacks::CHANNELS) as $ch) {
            $out[$ch] = [
                'given' => array_sum(array_map(fn ($r) => $r['given'][$ch], $rows)),
                'sent'  => array_sum(array_map(fn ($r) => $r['sent'][$ch], $rows)),
                'left'  => array_sum(array_map(fn ($r) => max(0, $r['left'][$ch]), $rows)),
                'over'  => array_sum(array_map(fn ($r) => max(0, -$r['left'][$ch]), $rows)),
                'month' => array_sum(array_map(fn ($r) => $r['month'][$ch], $rows)),
            ];
        }

        return $out;
    }

    /** Whether a school is running low: a channel it uses (given or sent on) with fewer than LOW left. */
    public static function isLow(array $row): bool
    {
        foreach (array_keys(MessagePacks::CHANNELS) as $ch) {
            if (($row['given'][$ch] > 0 || $row['sent'][$ch] > 0) && $row['left'][$ch] < MessagePacks::LOW) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        $tracked = MessagePacks::tracked();
        $only    = $this->onlyOrgId();
        $schools = $this->schoolOptions();
        $orgIds  = $only ? [$only] : null;
        $all     = $tracked ? MessagePacks::all($orgIds) : [];
        $rows    = $schools->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'logo' => $s->logo] + ($all[$s->id] ?? MessagePacks::blank()));

        $data = [
            'tracked'     => $tracked,
            'schools'     => $schools,
            'rates'       => MessagePacks::rates(),
            'prepaid'     => MessagingWallet::prepaid(),
            'canSettings' => $only === null,
            'channels'    => self::channelTotals($rows->all()),
            'totals'      => [
                'due'     => round($rows->sum('due'), 2),
                'paid'    => round($rows->sum('paid'), 2),
                'owing'   => $rows->where('due', '>', 0)->count(),
                'pending' => (int) $rows->sum('pending'),
                'low'     => $rows->filter(fn ($r) => self::isLow($r))->count(),
                'month'   => (int) $rows->sum(fn ($r) => array_sum($r['month'])),
            ],
        ];

        if ($tracked && $this->tab === 'overview') {
            $data['monthly']  = MessagePacks::monthly($orgIds);
            $data['failed']   = MessagePacks::failedMonth($orgIds);
            $data['topMonth'] = $rows->map(fn ($r) => ['label' => $r['name'], 'count' => array_sum($r['month'])])
                ->where('count', '>', 0)->sortByDesc('count')->take(6)->values()->all();
            $data['lowRows']  = $rows->filter(fn ($r) => self::isLow($r))->sortBy(fn ($r) => min($r['left']))->take(6)->values();
            $data['recentPayments'] = MessagingPayment::with(['organization:id,name', 'pack:id,channel,messages'])
                ->when($only, fn ($q) => $q->where('organization_id', $only))
                ->latest('paid_on')->latest('id')->limit(6)->get();
            $data['receivedMonth'] = round((float) MessagingPayment::when($only, fn ($q) => $q->where('organization_id', $only))
                ->where('paid_on', '>=', now()->startOfMonth()->toDateString())->sum('amount'), 2);
            $data['pendingList'] = MessagingPack::pending()->with('organization:id,name')
                ->when($only, fn ($q) => $q->where('organization_id', $only))->latest('id')->limit(4)->get();
        }

        if ($this->tab === 'schools') {
            $needle = mb_strtolower(trim($this->search));
            $list   = $rows->filter(fn ($r) => $needle === '' || str_contains(mb_strtolower($r['name']), $needle))
                ->filter(fn ($r) => match ($this->show) {
                    'low'   => self::isLow($r),
                    'due'   => $r['due'] > 0,
                    'none'  => array_sum($r['given']) === 0,
                    default => true,
                });
            $list = match ($this->sort) {
                'left'  => $list->sortBy(fn ($r) => array_sum($r['left'])),
                'used'  => $list->sortByDesc(fn ($r) => array_sum($r['month'])),
                'due'   => $list->sortByDesc('due'),
                default => $list,
            };
            $data['schoolRows'] = $list->values();
        }

        if (in_array($this->tab, ['whatsapp', 'sms', 'email'], true) && $this->school === '') {
            $ch = $this->tab;
            $switch = match ($ch) {
                'whatsapp' => fn ($id) => SchoolWhatsApp::tracked() && collect(['absent_alert', 'fee_received', 'credentials', 'notice'])->contains(fn ($f) => SchoolWhatsApp::enabled($id, $f)),
                'sms'      => fn ($id) => SchoolSms::enabled($id, 'welcome') || SchoolSms::enabled($id, 'notice'),
                default    => fn ($id) => SchoolEmail::enabled($id),
            };
            $failed = MessagePacks::sentBy($ch, $orgIds, now()->startOfMonth(), null, false, true);
            $data['channelRows'] = $rows->map(fn ($r) => $r + ['on' => $switch($r['id']), 'failed' => (int) ($failed[$r['id']] ?? 0)])
                ->sortByDesc(fn ($r) => [$r['month'][$ch], $r['given'][$ch]])->values();
            $data['channelMonthly'] = $tracked ? MessagePacks::monthly($orgIds) : null;
        }

        if ($tracked && in_array($this->tab, ['whatsapp', 'sms', 'email'], true) && $this->school !== '') {
            $data['schoolRow'] = $rows->firstWhere('id', (int) $this->school);
        }

        if ($tracked && $this->tab === 'requests') {
            $base = fn () => MessagingPack::with(['organization:id,name', 'creator:id,name'])
                ->where('source', 'school')
                ->when($only, fn ($q) => $q->where('organization_id', $only));
            $data['pendingRequests'] = $base()->pending()->latest('id')->get();
            $data['decidedRequests'] = $base()->whereIn('status', ['approved', 'rejected'])->latest('decided_at')->limit(30)->get();
        }

        if ($tracked && $this->tab === 'packs') {
            $packs = MessagingPack::with(['organization:id,name', 'creator:id,name'])
                ->where('status', 'approved')
                ->when($only, fn ($q) => $q->where('organization_id', $only))
                ->when($this->school !== '', fn ($q) => $q->where('organization_id', (int) $this->school))
                ->when($this->packChannel !== '', fn ($q) => $q->where('channel', $this->packChannel))
                ->when($this->packState === 'take', fn ($q) => $q->where('type', 'take'))
                ->when($this->packState === 'free', fn ($q) => $q->where('type', 'give')->where('amount', '<=', 0))
                ->when(in_array($this->packState, ['paid', 'part', 'due'], true), fn ($q) => $q->where('type', 'give')->where('amount', '>', 0))
                ->latest('txn_date')->latest('id');

            // How far a pack is paid comes from all its school's payments, so the
            // paid / due filters are applied to the list once that is known.
            if (in_array($this->packState, ['paid', 'part', 'due'], true)) {
                $list  = $packs->limit(1000)->get();
                $state = MessagePacks::payState($list);
                $list  = $list->filter(fn ($p) => ($state[$p->id]['state'] ?? '') === $this->packState)->values();
                $page  = max(1, (int) $this->getPage('packsPage'));
                $data['packs'] = new \Illuminate\Pagination\LengthAwarePaginator(
                    $list->forPage($page, 25)->values(), $list->count(), 25, $page, ['pageName' => 'packsPage'],
                );
                $data['packPay'] = $state;
            } else {
                $data['packs']     = $packs->paginate(25, ['*'], 'packsPage');
                $data['packPay'] = MessagePacks::payState(collect($data['packs']->items()));
            }
        }

        if ($tracked && $this->tab === 'payments') {
            $data['owing'] = $rows->filter(fn ($r) => $r['due'] > 0)->sortByDesc('due')->values();
            $data['payments'] = MessagingPayment::with(['organization:id,name', 'pack:id,channel,messages', 'creator:id,name'])
                ->when($only, fn ($q) => $q->where('organization_id', $only))
                ->when($this->school !== '', fn ($q) => $q->where('organization_id', (int) $this->school))
                ->latest('paid_on')->latest('id')
                ->paginate(25, ['*'], 'paymentsPage');
            $data['monthly'] = MessagePacks::monthly($orgIds);
            $data['receivedMonth'] = round((float) MessagingPayment::when($only, fn ($q) => $q->where('organization_id', $only))
                ->where('paid_on', '>=', now()->startOfMonth()->toDateString())->sum('amount'), 2);
        }

        if ($tracked && $this->tab === 'sent') {
            $data['campaigns'] = MessagingCampaign::with(['organization:id,name', 'creator:id,name'])
                ->when($this->school !== '', fn ($q) => $q->where('organization_id', (int) $this->school))
                ->when($only, fn ($q) => $q->where('organization_id', $only))
                ->latest('id')
                ->paginate(25);
        }

        if ($this->detailOrgId && $tracked) {
            $packs = MessagingPack::with('creator:id,name')->where('organization_id', $this->detailOrgId)
                ->latest('txn_date')->latest('id')->limit(60)->get();
            $data['detail'] = [
                'school'   => $schools->firstWhere('id', $this->detailOrgId),
                'row'      => $rows->firstWhere('id', $this->detailOrgId) ?? MessagePacks::blank(),
                'packs'    => $packs,
                'state'    => MessagePacks::payState($packs),
                'payments' => MessagingPayment::with('pack:id,channel,messages')->where('organization_id', $this->detailOrgId)
                    ->latest('paid_on')->latest('id')->limit(30)->get(),
                'monthly'  => MessagePacks::monthly([$this->detailOrgId]),
            ];
        }

        if ($this->showGive && $this->gOrgId !== '' && $tracked) {
            $data['giveRow'] = $rows->firstWhere('id', (int) $this->gOrgId);
        }
        if ($this->showPay && $this->pOrgId !== '' && $tracked) {
            $data['payRow']   = $rows->firstWhere('id', (int) $this->pOrgId);
            $unpaid           = MessagingPack::approved()->where('type', 'give')->where('amount', '>', 0)
                ->where('organization_id', (int) $this->pOrgId)->orderBy('txn_date')->orderBy('id')->get();
            $state            = MessagePacks::payState($unpaid);
            $data['payPacks'] = $unpaid->filter(fn ($p) => in_array($state[$p->id]['state'] ?? '', ['due', 'part'], true))
                ->map(fn ($p) => ['pack' => $p, 'left' => round((float) $p->amount - (float) ($state[$p->id]['paid'] ?? 0), 2)])->values();
        }

        $data['smsConfigured'] = SmsService::enabled();

        return view('livewire.super-admin.messaging', $data);
    }
}
