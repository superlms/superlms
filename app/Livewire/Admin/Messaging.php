<?php

namespace App\Livewire\Admin;

use App\Jobs\SendMessagingCampaign;
use App\Models\Messaging\MessagingCampaign;
use App\Models\Messaging\MessagingPack;
use App\Models\Messaging\MessagingPayment;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Services\MessagePacks;
use App\Services\MessagingWallet;
use App\Services\SchoolEmail;
use App\Services\SchoolSms;
use App\Services\SchoolWhatsApp;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use App\Support\MessagingAudience;
use App\Support\UpiQr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * More → Messaging: the school's WhatsApp, SMS and email messages, in the
 * Home's analytics look (2026-11-21), a chip a tab:
 *
 *   Overview         messages left on each channel, this month's, what is
 *                    waiting for approval and what is owed
 *   Buy Messages     pick a channel and a pack, pay SuperLMS (a UPI QR for the
 *                    exact amount, or the bank details), say so — the messages
 *                    come once the Super Admin approves it
 *   Send             a message to the school's students — all, a class or a
 *                    section — its teachers, or everyone, by WhatsApp, SMS or
 *                    email, as SuperLMS has turned them on; each person takes
 *                    one message (an SMS one a part)
 *   Sent             the messages sent, and how far each got
 *   Usage            the school's WhatsApp analytics, its SMS and its emails
 *   Packs & Payments every pack and what was paid
 */
class Messaging extends Component
{
    use WireUiActions, WithPagination;
    use \App\Livewire\Concerns\HandlesWhatsAppAnalytics;

    public const TABS = [
        'overview' => 'Overview',
        'buy'      => 'Buy Messages',
        'send'     => 'Send',
        'sent'     => 'Sent',
        'usage'    => 'Usage',
        'history'  => 'Packs & Payments',
    ];

    /** The longest message each way. */
    public const MAX = ['whatsapp' => 900, 'sms' => SchoolSms::NOTICE_MAX, 'email' => 5000];

    #[Url(history: true, except: 'overview')]
    public string $tab = 'overview';

    // ─── Buy Messages ───────────────────────────────────────────────────────
    public string $bChannel   = 'whatsapp';
    public        $bMessages  = 1000;
    public string $bMode      = 'UPI';
    public string $bReference = '';
    public string $bDate      = '';
    public string $bNote      = '';

    // ─── Send ───────────────────────────────────────────────────────────────
    public string $channel    = 'whatsapp';
    public string $audience   = 'students';
    public string $standardId = '';
    public string $sectionId  = '';
    public string $subject    = '';
    public string $body       = '';
    public bool   $confirming = false;

    public function mount(): void
    {
        if (!array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'overview';
        }
        // The first way the school has on.
        $on = array_keys(array_filter($this->channels(), fn ($c) => $c['on']));
        if ($on && !in_array($this->channel, $on, true)) {
            $this->channel = $on[0];
        }
        $this->bDate = now()->toDateString();
    }

    protected function orgId(): int
    {
        return (int) Auth::user()?->organization_id;
    }

    protected function whatsAppOrgId(): ?int
    {
        return $this->orgId() ?: null;
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab        = $tab;
            $this->confirming = false;
            $this->resetPage();
            $this->resetPage('packsPage');
        }
    }

    /** WhatsApp, SMS and email: whether SuperLMS has each on for the school, its rate and the messages left. */
    public function channels(): array
    {
        $orgId = $this->orgId();
        $left  = MessagePacks::tracked() ? MessagePacks::left($orgId) : ['whatsapp' => 0, 'sms' => 0, 'email' => 0];

        return [
            'whatsapp' => ['label' => 'WhatsApp', 'on' => SchoolWhatsApp::enabled($orgId, 'notice'), 'rate' => MessagePacks::rate('whatsapp'), 'left' => $left['whatsapp']],
            'sms'      => ['label' => 'SMS', 'on' => SchoolSms::noticeOn($orgId), 'rate' => MessagePacks::rate('sms'), 'left' => $left['sms']],
            'email'    => ['label' => 'Email', 'on' => SchoolEmail::enabled($orgId), 'rate' => MessagePacks::rate('email'), 'left' => $left['email']],
        ];
    }

    // ─── Buy Messages ───────────────────────────────────────────────────────

    public function buy(?string $channel = null): void
    {
        if ($channel && array_key_exists($channel, MessagePacks::CHANNELS)) {
            $this->bChannel = $channel;
        }
        $this->resetValidation();
        $this->setTab('buy');
    }

    public function updatedBChannel(): void
    {
        if (!array_key_exists($this->bChannel, MessagePacks::CHANNELS)) {
            $this->bChannel = 'whatsapp';
        }
    }

    public function pickSize(int $size): void
    {
        $this->bMessages = max(1, $size);
    }

    /** What the pack being bought comes to. */
    public function buyTotal(): float
    {
        return round(max(0, (int) $this->bMessages) * MessagePacks::rate($this->bChannel), 2);
    }

    public function requestBuy(): void
    {
        if (!MessagePacks::tracked()) {
            return;
        }

        $this->validate([
            'bChannel'   => 'required|in:' . implode(',', array_keys(MessagePacks::CHANNELS)),
            'bMessages'  => 'required|integer|min:1|max:1000000',
            'bMode'      => 'required|string|max:40',
            'bReference' => 'required|string|max:120',
            'bNote'      => 'nullable|string|max:500',
            'bDate'      => 'required|date|before_or_equal:today',
        ], [], [
            'bChannel' => 'channel', 'bMessages' => 'messages', 'bMode' => 'paid by', 'bReference' => 'reference / UTR',
            'bNote' => 'note', 'bDate' => 'date paid',
        ]);
        if ($this->buyTotal() <= 0) {
            $this->addError('bMessages', 'Ask SuperLMS for these messages — they have no price yet.');
            return;
        }

        $pack = MessagePacks::request($this->orgId(), $this->bChannel, (int) $this->bMessages, $this->bMode, trim($this->bReference), trim($this->bNote), Auth::id(), $this->bDate);

        $this->reset(['bReference', 'bNote']);
        $this->setTab('history');
        $this->notification()->success(number_format($pack->messages) . ' ' . MessagePacks::label($pack->channel) . ' messages (₹'
            . number_format((float) $pack->amount, 2) . ') sent to SuperLMS to approve. They come into your account once approved.');
    }

    // ─── Send ───────────────────────────────────────────────────────────────

    public function updatedAudience(): void
    {
        $this->standardId = '';
        $this->sectionId  = '';
        $this->confirming = false;
    }

    public function updatedStandardId(): void
    {
        $this->sectionId  = '';
        $this->confirming = false;
    }

    public function updatedChannel(): void
    {
        $this->confirming = false;
        $this->resetValidation();
    }

    /** How many SMS parts one notice goes as (each takes an SMS message). */
    private function smsParts(): int
    {
        $text = (string) config('services.bhash_sms.templates.notice.text');
        if ($text === '') {
            return 1;
        }
        $slots = max(1, substr_count(strtolower($text), '{#var#}') - 1);
        $vars  = array_merge([Organization::whereKey($this->orgId())->value('name') ?? 'School'], SchoolSms::noticeParts($this->body, $slots));

        return SmsService::parts(SmsService::fill($text, $vars, ''));
    }

    /** Who the message reaches, how many of them it can reach on the way picked, and the messages it takes. */
    public function preview(): array
    {
        $orgId      = $this->orgId();
        $recipients = MessagingAudience::recipients($orgId, $this->audience, (int) $this->standardId ?: null, (int) $this->sectionId ?: null);
        $reachable  = count(array_filter($recipients, fn ($r) => match ($this->channel) {
            'email' => (bool) filter_var((string) $r['email'], FILTER_VALIDATE_EMAIL),
            'sms'   => SmsService::normalizePhone($r['phone']) !== null,
            default => WhatsAppService::normalizePhone($r['phone']) !== null,
        }));
        $each = $this->channel === 'sms' ? $this->smsParts() : 1;
        $rate = MessagePacks::rate($this->channel);

        return [
            'total'     => count($recipients),
            'reachable' => $reachable,
            'each'      => $each,
            'messages'  => $reachable * $each,
            'rate'      => $rate,
            'cost'      => round($reachable * $each * $rate, 2),
            'label'     => MessagingAudience::label($orgId, $this->audience, (int) $this->standardId ?: null, (int) $this->sectionId ?: null),
        ];
    }

    private function validateMessage(): void
    {
        $this->validate([
            'channel'    => 'required|in:whatsapp,sms,email',
            'audience'   => 'required|in:' . implode(',', array_keys(MessagingAudience::AUDIENCES)),
            'standardId' => 'nullable|integer',
            'sectionId'  => 'nullable|integer',
            'subject'    => ($this->channel === 'email' ? 'required' : 'nullable') . '|string|max:150',
            'body'       => 'required|string|min:5|max:' . (self::MAX[$this->channel] ?? 900),
        ], [], ['standardId' => 'class', 'sectionId' => 'section', 'body' => 'message']);

        if ($this->standardId !== '' && !Standard::where('organization_id', $this->orgId())->whereKey((int) $this->standardId)->exists()) {
            $this->addError('standardId', 'Pick one of the school\'s classes.');
        }
        if ($this->sectionId !== '' && !Section::where('standard_id', (int) $this->standardId)->whereKey((int) $this->sectionId)->exists()) {
            $this->addError('sectionId', 'Pick a section of the class.');
        }
        if (!($this->channels()[$this->channel]['on'] ?? false)) {
            $this->addError('channel', MessagePacks::label($this->channel) . ' messaging is not on for your school — ask SuperLMS to turn it on.');
        }
    }

    /** Send's first press: the message is checked and the messages it takes shown. */
    public function review(): void
    {
        $this->validateMessage();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $p = $this->preview();
        if ($p['reachable'] === 0) {
            $this->addError('audience', 'Nobody here has ' . ($this->channel === 'email' ? 'an email address' : 'a mobile number') . ' to send to.');
            return;
        }
        $left = $this->channels()[$this->channel]['left'] ?? 0;
        if (MessagingWallet::prepaid() && $left < $p['messages']) {
            $this->addError('body', 'You have ' . number_format(max(0, $left)) . ' ' . MessagePacks::label($this->channel)
                . ' messages left and this takes ' . number_format($p['messages']) . '. Buy messages first.');
            return;
        }

        $this->confirming = true;
    }

    public function cancelReview(): void
    {
        $this->confirming = false;
    }

    /** The second press: the message is queued and goes out a part at a time. */
    public function send(): void
    {
        $this->validateMessage();
        if ($this->getErrorBag()->isNotEmpty() || !MessagingWallet::tracked()) {
            $this->confirming = false;
            return;
        }

        $p = $this->preview();
        if ($p['reachable'] === 0) {
            $this->confirming = false;
            return;
        }

        $campaign = MessagingCampaign::create([
            'organization_id' => $this->orgId(),
            'channel'         => $this->channel,
            'audience'        => $this->audience,
            'standard_id'     => $this->audience === 'students' && $this->standardId !== '' ? (int) $this->standardId : null,
            'section_id'      => $this->audience === 'students' && $this->sectionId !== '' ? (int) $this->sectionId : null,
            'audience_label'  => $p['label'],
            'subject'         => $this->channel === 'email' ? trim($this->subject) : null,
            'body'            => trim($this->body),
            'recipients'      => $p['total'],
            'rate'            => $p['rate'] * $p['each'],
            'status'          => 'queued',
            'created_by'      => Auth::id(),
        ]);

        SendMessagingCampaign::dispatch($campaign->id);

        $this->reset(['subject', 'body', 'confirming']);
        $this->tab = 'sent';
        $this->resetPage();
        $this->notification()->success('Your message is on its way to ' . number_format($p['reachable']) . ' '
            . ($p['reachable'] === 1 ? 'person' : 'people') . '.');
    }

    // ─── Render ─────────────────────────────────────────────────────────────

    public function render()
    {
        $orgId    = $this->orgId();
        $tracked  = MessagePacks::tracked();
        $channels = $this->channels();
        $data     = [
            'tracked'  => $tracked,
            'channels' => $channels,
            'prepaid'  => MessagingWallet::prepaid(),
            'month'    => $tracked ? MessagePacks::sent($orgId, now()->startOfMonth(), null, false) : ['whatsapp' => 0, 'sms' => 0, 'email' => 0],
            'money'    => ($tracked ? (MessagePacks::moneyBy([$orgId])[$orgId] ?? null) : null) ?? ['charged' => 0.0, 'paid' => 0.0, 'due' => 0.0, 'advance' => 0.0],
            'pending'  => $tracked ? MessagingPack::where('organization_id', $orgId)->pending()->latest('id')->get() : collect(),
            'given'    => $tracked ? (MessagePacks::givenBy([$orgId])[$orgId] ?? ['whatsapp' => 0, 'sms' => 0, 'email' => 0]) : ['whatsapp' => 0, 'sms' => 0, 'email' => 0],
        ];

        if ($tracked && $this->tab === 'overview') {
            $data['monthly'] = MessagePacks::monthly([$orgId]);
            $data['recent']  = MessagingPack::where('organization_id', $orgId)->latest('id')->limit(5)->get();
            $data['recentState'] = MessagePacks::payState($data['recent']);
        }

        if ($this->tab === 'buy') {
            $total = $this->buyTotal();
            $upi   = MessagingWallet::upiId();
            $data['buyTotal'] = $total;
            $data['upiId']    = $upi;
            $data['upiName']  = MessagingWallet::upiName();
            $data['details']  = MessagingWallet::paymentDetails();
            $data['qr']       = $upi !== '' && $total > 0
                ? UpiQr::svg(UpiQr::link($upi, MessagingWallet::upiName(), $total, 'SuperLMS ' . number_format((int) $this->bMessages) . ' ' . MessagePacks::label($this->bChannel)), 180)
                : null;
            $data['upiLink']  = $upi !== '' && $total > 0 ? UpiQr::link($upi, MessagingWallet::upiName(), $total, 'SuperLMS messages') : null;
        }

        if ($this->tab === 'send') {
            $data['standards'] = Standard::where('organization_id', $orgId)->inClassOrder()->get(['id', 'name']);
            $data['sections']  = $this->standardId !== '' ? Section::where('standard_id', (int) $this->standardId)->orderBy('name')->get(['id', 'name']) : collect();
            $data['preview']   = $this->preview();
        }

        if ($this->tab === 'sent' && $tracked) {
            $data['campaigns'] = MessagingCampaign::with('creator:id,name')
                ->where('organization_id', $orgId)
                ->latest('id')
                ->paginate(20);
        }

        if ($this->tab === 'usage') {
            $data['email'] = SchoolEmail::tracked() ? SchoolEmail::summary($orgId, 15) : null;
            $data['sms']   = SchoolSms::tracked() ? SchoolSms::summary($orgId, 15) : null;
        }

        if ($this->tab === 'history' && $tracked) {
            $packs = MessagingPack::where('organization_id', $orgId)->latest('txn_date')->latest('id')->paginate(20, ['*'], 'packsPage');
            $data['packs']    = $packs;
            $data['state']    = MessagePacks::payState(collect($packs->items()));
            $data['payments'] = MessagingPayment::with('pack:id,channel,messages')->where('organization_id', $orgId)->latest('paid_on')->latest('id')->limit(30)->get();
        }

        return view('livewire.admin.messaging', $data);
    }
}
